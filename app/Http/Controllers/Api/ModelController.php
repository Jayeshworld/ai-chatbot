<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\ConversationThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ModelController extends Controller
{
    public function index(): JsonResponse
    {
        $models = AiModel::where('enabled', true)->get();

        return response()->json(['data' => $models, 'error' => null]);
    }

    public function change(Request $request): JsonResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'conversation_id' => 'required|integer|exists:conversation_threads,id',
            'model' => 'required|string|exists:ai_models,ollama_name',
        ]);

        $thread = ConversationThread::where('id', $validated['conversation_id'])
            ->where('user_id', $user->id)
            ->firstOrFail();

        $thread->update(['model' => $validated['model']]);

        return response()->json([
            'data' => [
                'conversation_id' => $thread->id,
                'model' => $thread->model,
            ],
            'error' => null,
        ]);
    }
}
