<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatUsage extends Model
{
    protected $table = 'chat_usage';

    protected $fillable = [
        'user_id',
        'conversation_id',
        'model',
        'prompt_tokens',
        'completion_tokens',
        'response_time_ms',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(ConversationThread::class, 'conversation_id');
    }
}
