<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConversationThread extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'system_prompt',
        'model',
        'is_active',
        'message_count',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ConversationMessage::class, 'conversation_id')
                    ->orderBy('created_at', 'asc');
    }

    public function recentMessages(int $limit = 20)
    {
        return $this->hasMany(ConversationMessage::class, 'conversation_id')
                    ->latest()
                    ->limit($limit)
                    ->get()
                    ->reverse()
                    ->values();
    }

    public function usage(): HasMany
    {
        return $this->hasMany(ChatUsage::class, 'conversation_id');
    }
}
