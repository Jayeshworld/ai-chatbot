<?php

namespace Tests\Feature;

use App\Models\AiModel;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatTest extends TestCase
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

        AiModel::create([
            'name' => 'Qwen3 8B',
            'ollama_name' => 'qwen3:8b',
            'enabled' => true,
            'is_default' => true,
            'context_length' => 32768,
        ]);
    }

    private function makeUser(string $email): array
    {
        $key = User::generateApiKey();
        $user = User::create(['name' => 'User', 'email' => $email, 'api_key' => $key, 'is_active' => true]);
        return [$user, $key];
    }

    public function test_send_without_conversation_id_creates_new_thread(): void
    {
        $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson('/api/v1/chat/send', ['message' => 'Hello'])
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'conversation_id',
                    'message' => ['id', 'role', 'content', 'created_at'],
                    'usage' => ['prompt_tokens', 'completion_tokens', 'response_time_ms'],
                ],
            ]);

        $this->assertDatabaseCount('conversation_threads', 1);
        $this->assertDatabaseCount('conversation_messages', 2); // user + assistant
    }

    public function test_subsequent_message_uses_existing_conversation(): void
    {
        $response = $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson('/api/v1/chat/send', ['message' => 'First message'])
            ->assertOk();

        $conversationId = $response->json('data.conversation_id');

        $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson('/api/v1/chat/send', [
                'message' => 'Second message',
                'conversation_id' => $conversationId,
            ])
            ->assertOk()
            ->assertJsonPath('data.conversation_id', $conversationId);

        $this->assertDatabaseCount('conversation_messages', 4); // 2 exchanges
    }

    public function test_send_requires_authentication(): void
    {
        $this->postJson('/api/v1/chat/send', ['message' => 'Hello'])
            ->assertStatus(401);
    }

    public function test_send_validates_message_length(): void
    {
        $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson('/api/v1/chat/send', ['message' => str_repeat('a', 32001)])
            ->assertStatus(422);
    }

    public function test_send_rejects_empty_message(): void
    {
        $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson('/api/v1/chat/send', ['message' => ''])
            ->assertStatus(422);
    }

    public function test_user_cannot_access_another_users_conversation(): void
    {
        [, $otherKey] = $this->makeUser('other@example.com');

        $response = $this->withHeader('X-API-KEY', $otherKey)
            ->postJson('/api/v1/chat/send', ['message' => 'Secret'])
            ->assertOk();

        $conversationId = $response->json('data.conversation_id');

        $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson('/api/v1/chat/send', [
                'message' => 'Hello',
                'conversation_id' => $conversationId,
            ])
            ->assertStatus(404);
    }

    public function test_stream_endpoint_returns_event_stream(): void
    {
        $response = $this->withHeaders([
                'X-API-KEY' => $this->apiKey,
                'Accept' => 'text/event-stream',
            ])
            ->postJson('/api/v1/chat/stream', ['message' => 'Hello']);

        $response->assertStatus(200);
        $this->assertStringContainsString('text/event-stream', $response->headers->get('Content-Type'));
    }

    public function test_usage_is_logged_after_send(): void
    {
        $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson('/api/v1/chat/send', ['message' => 'Hello'])
            ->assertOk();

        $this->assertDatabaseCount('chat_usage', 1);
        $this->assertDatabaseHas('chat_usage', ['user_id' => $this->user->id]);
    }

    public function test_conversation_title_auto_generated(): void
    {
        $this->withHeader('X-API-KEY', $this->apiKey)
            ->postJson('/api/v1/chat/send', ['message' => 'Tell me about Laravel'])
            ->assertOk();

        $this->assertDatabaseHas('conversation_threads', [
            'user_id' => $this->user->id,
        ]);
    }
}
