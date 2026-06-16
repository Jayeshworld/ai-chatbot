<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'api_key',
        'is_active',
        'is_admin',
        'permissions',
        'last_used_at',
    ];

    protected $hidden = ['api_key'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_admin' => 'boolean',
            'permissions' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(ConversationThread::class);
    }

    public function usage(): HasMany
    {
        return $this->hasMany(ChatUsage::class);
    }

    public static function generateApiKey(): string
    {
        return 'ak_' . bin2hex(random_bytes(32));
    }

    // No password-based auth — satisfy Authenticatable contract minimally
    public function getAuthPassword(): string
    {
        return '';
    }
}
