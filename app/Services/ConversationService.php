<?php

namespace App\Services;

use App\Models\ChatUsage;
use App\Models\ConversationMessage;
use App\Models\ConversationThread;

class ConversationService
{
    private int $maxContextMessages;

    public function __construct()
    {
        $this->maxContextMessages = config('ollama.max_context_messages', 20);
    }

    /**
     * Build messages array to send to Ollama:
     * system prompt + recent history + new user message.
     */
    public function buildContext(ConversationThread $thread, string $newUserMessage): array
    {
        $messages = [];

        $systemPrompt = $thread->system_prompt
            ?? 'You are a helpful AI assistant. Be clear, accurate, and concise.';

        $messages[] = ['role' => 'system', 'content' => $systemPrompt];

        $history = ConversationMessage::where('conversation_id', $thread->id)
            ->where('role', '!=', 'system')
            ->orderBy('created_at', 'desc')
            ->limit($this->maxContextMessages)
            ->get()
            ->reverse()
            ->values();

        foreach ($history as $msg) {
            $messages[] = ['role' => $msg->role, 'content' => $msg->content];
        }

        $messages[] = ['role' => 'user', 'content' => $newUserMessage];

        return $messages;
    }

    public function saveUserMessage(ConversationThread $thread, string $content): ConversationMessage
    {
        $message = ConversationMessage::create([
            'conversation_id' => $thread->id,
            'role' => 'user',
            'content' => $content,
            'token_count' => $this->estimateTokens($content),
        ]);

        $thread->increment('message_count');

        return $message;
    }

    public function saveAssistantMessage(
        ConversationThread $thread,
        string $content,
        int $tokenCount = 0
    ): ConversationMessage {
        $message = ConversationMessage::create([
            'conversation_id' => $thread->id,
            'role' => 'assistant',
            'content' => $content,
            'token_count' => $tokenCount ?: $this->estimateTokens($content),
        ]);

        $thread->increment('message_count');

        // Auto-generate title on first exchange
        if (!$thread->title && $thread->message_count <= 2) {
            $this->generateTitle($thread);
        }

        return $message;
    }

    public function logUsage(
        int $userId,
        int $conversationId,
        string $model,
        int $promptTokens,
        int $completionTokens,
        int $responseTimeMs
    ): void {
        ChatUsage::create([
            'user_id' => $userId,
            'conversation_id' => $conversationId,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'response_time_ms' => $responseTimeMs,
        ]);
    }

    public function estimateTokens(string $text): int
    {
        return (int) ceil(strlen($text) / 4);
    }

    public function getConversationForUser(int $conversationId, int $userId): ?ConversationThread
    {
        return ConversationThread::where('id', $conversationId)
            ->where('user_id', $userId)
            ->where('is_active', true)
            ->first();
    }

    private function generateTitle(ConversationThread $thread): void
    {
        $first = ConversationMessage::where('conversation_id', $thread->id)
            ->where('role', 'user')
            ->first();

        if ($first) {
            $title = mb_substr($first->content, 0, 60);
            if (mb_strlen($first->content) > 60) {
                $title .= '...';
            }
            $thread->update(['title' => $title]);
        }
    }
}
