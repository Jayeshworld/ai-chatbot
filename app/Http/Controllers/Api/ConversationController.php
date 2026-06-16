<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConversationThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = auth()->user();

        $conversations = ConversationThread::where('user_id', $user->id)
            ->where('is_active', true)
            ->orderBy('updated_at', 'desc')
            ->paginate(20);

        return response()->json([
            'data' => $conversations->items(),
            'meta' => [
                'page' => $conversations->currentPage(),
                'per_page' => $conversations->perPage(),
                'total' => $conversations->total(),
                'last_page' => $conversations->lastPage(),
            ],
            'error' => null,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'system_prompt' => 'sometimes|string|max:4000',
            'model' => 'sometimes|string|exists:ai_models,ollama_name',
        ]);

        $thread = ConversationThread::create([
            'user_id' => $user->id,
            'title' => $validated['title'] ?? null,
            'system_prompt' => $validated['system_prompt'] ?? null,
            'model' => $validated['model'] ?? config('ollama.default_model'),
        ]);

        return response()->json(['data' => $thread, 'error' => null], 201);
    }

    public function show(int $id): JsonResponse
    {
        $user = auth()->user();

        $thread = ConversationThread::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        return response()->json([
            'data' => $thread->load('messages'),
            'error' => null,
        ]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();

        $thread = ConversationThread::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'system_prompt' => 'sometimes|string|max:4000',
        ]);

        $thread->update($validated);

        return response()->json(['data' => $thread, 'error' => null]);
    }

    public function destroy(int $id): JsonResponse
    {
        $user = auth()->user();

        ConversationThread::where('id', $id)
            ->where('user_id', $user->id)
            ->update(['is_active' => false]);

        return response()->json([
            'data' => ['message' => 'Conversation deleted.'],
            'error' => null,
        ]);
    }
}
