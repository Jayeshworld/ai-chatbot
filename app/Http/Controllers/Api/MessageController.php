<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConversationMessage;
use App\Models\ConversationThread;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, int $id): JsonResponse
    {
        $user = auth()->user();

        $thread = ConversationThread::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $messages = ConversationMessage::where('conversation_id', $thread->id)
            ->orderBy('created_at', 'asc')
            ->paginate(50);

        return response()->json([
            'data' => $messages->items(),
            'meta' => [
                'page' => $messages->currentPage(),
                'per_page' => $messages->perPage(),
                'total' => $messages->total(),
                'last_page' => $messages->lastPage(),
            ],
            'error' => null,
        ]);
    }
}
