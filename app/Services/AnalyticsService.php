<?php

namespace App\Services;

use App\Models\ChatUsage;
use App\Models\ConversationMessage;
use App\Models\ConversationThread;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public function getDashboardMetrics(): array
    {
        return [
            'total_users' => User::count(),
            'active_users' => User::where('is_active', true)->count(),
            'total_conversations' => ConversationThread::count(),
            'total_messages' => ConversationMessage::count(),
            'messages_today' => ConversationMessage::whereDate('created_at', today())->count(),
            'avg_response_time_ms' => (int) ChatUsage::avg('response_time_ms'),
            'total_tokens_used' => (int) (ChatUsage::sum('prompt_tokens') + ChatUsage::sum('completion_tokens')),
            'active_users_7d' => ChatUsage::where('created_at', '>=', now()->subDays(7))
                ->distinct('user_id')
                ->count('user_id'),
        ];
    }

    public function getMessagesByDay(int $days = 30): array
    {
        return ConversationMessage::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($days))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    public function getModelUsage(): array
    {
        return ChatUsage::select('model', DB::raw('COUNT(*) as requests'), DB::raw('SUM(prompt_tokens + completion_tokens) as total_tokens'))
            ->groupBy('model')
            ->get()
            ->toArray();
    }

    public function getTopUsers(int $limit = 10): array
    {
        return User::select('users.id', 'users.name', 'users.email')
            ->selectRaw('COUNT(chat_usage.id) as total_requests')
            ->selectRaw('SUM(chat_usage.prompt_tokens + chat_usage.completion_tokens) as total_tokens')
            ->leftJoin('chat_usage', 'users.id', '=', 'chat_usage.user_id')
            ->groupBy('users.id', 'users.name', 'users.email')
            ->orderByDesc('total_requests')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function getUserActivity(int $userId, int $days = 30): array
    {
        return ChatUsage::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays($days))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as requests'),
                DB::raw('SUM(prompt_tokens + completion_tokens) as tokens')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}
