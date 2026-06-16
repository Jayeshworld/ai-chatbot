<?php

namespace Tests\Feature;

use App\Models\ConversationThread;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private string $apiKey;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKey = User::generateApiKey();
        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'api_key' => $this->apiKey,
            'is_active' => true,
        ]);
    }

    private function makeThread(array $overrides = []): ConversationThread
    {
        return ConversationThread::create(array_merge([
            'user_id' => $this->user->id,
            'model' => 'qwen3:8b',
        ], $overrides));
    }

    public function test_list_returns_only_users_conversations(): void
    {
        $otherKey = User::generateApiKey();
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'api_key' => $otherKey, 'is_active' => true]);

        $this->makeThread();
        $this->makeThread();
        ConversationThread::create(['user_id' => $other->id, 'model' => 'qwen3:8b']);

        $this->withHeader('X-API-KEY', $this->apiKey)
            ->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_create_conversation(): void
    {
        $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson('/api/v1/conversations', [
                'title' => 'My Conversation',
                'system_prompt' => 'You are helpful.',
            ])
            ->assertStatus(201)
            ->assertJsonPath('data.title', 'My Conversation');
    }

    public function test_show_conversation_with_messages(): void
    {
        $thread = $this->makeThread(['title' => 'Test Thread']);

        $this->withHeader('X-API-KEY', $this->apiKey)
            ->getJson("/api/v1/conversations/{$thread->id}")
            ->assertOk()
            ->assertJsonPath('data.title', 'Test Thread')
            ->assertJsonStructure(['data' => ['id', 'title', 'messages']]);
    }

    public function test_cannot_view_another_users_conversation(): void
    {
        $otherKey = User::generateApiKey();
        $other = User::create(['name' => 'Other', 'email' => 'other@example.com', 'api_key' => $otherKey, 'is_active' => true]);
        $thread = ConversationThread::create(['user_id' => $other->id, 'model' => 'qwen3:8b']);

        $this->withHeader('X-API-KEY', $this->apiKey)
            ->getJson("/api/v1/conversations/{$thread->id}")
            ->assertStatus(404);
    }

    public function test_update_conversation_title(): void
    {
        $thread = $this->makeThread();

        $this->withHeader('X-API-KEY', $this->apiKey)
            ->putJson("/api/v1/conversations/{$thread->id}", ['title' => 'New Title'])
            ->assertOk()
            ->assertJsonPath('data.title', 'New Title');
    }

    public function test_delete_soft_deletes_conversation(): void
    {
        $thread = $this->makeThread();

        $this->withHeader('X-API-KEY', $this->apiKey)
            ->deleteJson("/api/v1/conversations/{$thread->id}")
            ->assertOk();

        $this->assertDatabaseHas('conversation_threads', [
            'id' => $thread->id,
            'is_active' => 0,
        ]);
    }

    public function test_deleted_conversation_not_in_list(): void
    {
        $thread = $this->makeThread();
        $thread->update(['is_active' => false]);

        $this->withHeader('X-API-KEY', $this->apiKey)
            ->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_list_is_paginated(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->makeThread(['title' => "Thread $i"]);
        }

        $this->withHeader('X-API-KEY', $this->apiKey)
            ->getJson('/api/v1/conversations')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['page', 'per_page', 'total', 'last_page']]);
    }
}
