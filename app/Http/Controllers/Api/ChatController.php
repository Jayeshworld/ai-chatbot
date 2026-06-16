<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\SendMessageRequest;
use App\Models\ConversationThread;
use App\Services\ConversationService;
use App\Services\OllamaService;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private ConversationService $conversationService,
        private OllamaService $ollamaService
    ) {}

    /**
     * POST /api/v1/chat/send — blocking response.
     */
    public function send(SendMessageRequest $request): JsonResponse
    {
        $user = auth()->user();
        $thread = $this->resolveThread($request, $user);
        $messages = $this->conversationService->buildContext($thread, $request->input('message'));

        $this->conversationService->saveUserMessage($thread, $request->input('message'));

        try {
            $result = $this->ollamaService->chat($messages, $thread->model);
        } catch (\Exception $e) {
            return response()->json([
                'data' => null,
                'error' => [
                    'code' => 'AI_SERVICE_UNAVAILABLE',
                    'message' => $e->getMessage(),
                ],
            ], 503);
        }

        $assistantMessage = $this->conversationService->saveAssistantMessage(
            $thread,
            $result['content'],
            $result['completion_tokens']
        );

        $this->conversationService->logUsage(
            $user->id,
            $thread->id,
            $result['model'],
            $result['prompt_tokens'],
            $result['completion_tokens'],
            $result['response_time_ms']
        );

        return response()->json([
            'data' => [
                'conversation_id' => $thread->id,
                'message' => [
                    'id' => $assistantMessage->id,
                    'role' => 'assistant',
                    'content' => $result['content'],
                    'created_at' => $assistantMessage->created_at->toIso8601String(),
                ],
                'usage' => [
                    'prompt_tokens' => $result['prompt_tokens'],
                    'completion_tokens' => $result['completion_tokens'],
                    'response_time_ms' => $result['response_time_ms'],
                ],
            ],
            'error' => null,
        ]);
    }

    /**
     * POST /api/v1/chat/stream — SSE streaming response.
     */
    public function stream(SendMessageRequest $request): StreamedResponse
    {
        $user = auth()->user();
        $thread = $this->resolveThread($request, $user);
        $messages = $this->conversationService->buildContext($thread, $request->input('message'));

        $this->conversationService->saveUserMessage($thread, $request->input('message'));

        $conversationService = $this->conversationService;
        $ollamaService = $this->ollamaService;

        return response()->stream(function () use (
            $thread, $messages, $user, $conversationService, $ollamaService
        ) {
            echo "data: " . json_encode([
                'type' => 'start',
                'conversation_id' => $thread->id,
            ]) . "\n\n";
            ob_flush();
            flush();

            try {
                $result = $ollamaService->streamChat(
                    $messages,
                    $thread->model,
                    function (string $chunk) {
                        echo "data: " . json_encode([
                            'type' => 'chunk',
                            'content' => $chunk,
                        ]) . "\n\n";
                        ob_flush();
                        flush();
                    }
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::error('Stream error', [
                    'message' => $e->getMessage(),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString(),
                ]);
                echo "data: " . json_encode([
                    'type' => 'error',
                    'message' => $e->getMessage(),
                    'file' => basename($e->getFile()) . ':' . $e->getLine(),
                ]) . "\n\n";
                ob_flush();
                flush();
                return;
            }

            $assistantMessage = $conversationService->saveAssistantMessage(
                $thread,
                $result['content'],
                $result['completion_tokens']
            );

            $conversationService->logUsage(
                $user->id,
                $thread->id,
                $result['model'],
                $result['prompt_tokens'],
                $result['completion_tokens'],
                0
            );

            echo "data: " . json_encode([
                'type' => 'done',
                'message_id' => $assistantMessage->id,
                'usage' => [
                    'prompt_tokens' => $result['prompt_tokens'],
                    'completion_tokens' => $result['completion_tokens'],
                ],
            ]) . "\n\n";
            ob_flush();
            flush();

        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
        ]);
    }

    private function resolveThread(SendMessageRequest $request, $user): ConversationThread
    {
        if ($request->has('conversation_id')) {
            $thread = $this->conversationService->getConversationForUser(
                $request->integer('conversation_id'),
                $user->id
            );

            abort_if(!$thread, 404, 'Conversation not found.');

            return $thread;
        }

        return ConversationThread::create([
            'user_id' => $user->id,
            'model' => $request->input('model', config('ollama.default_model')),
            'system_prompt' => $request->input('system_prompt'),
        ]);
    }
}
