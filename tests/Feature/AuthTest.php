<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(array $overrides = []): array
    {
        $apiKey = User::generateApiKey();
        $user = User::create(array_merge([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'api_key' => $apiKey,
            'is_active' => true,
        ], $overrides));

        return [$user, $apiKey];
    }

    public function test_me_returns_user_info(): void
    {
        [, $apiKey] = $this->makeUser();

        $this->withHeader('X-API-KEY', $apiKey)
            ->getJson('/api/v1/auth/me')
            ->assertOk()
            ->assertJsonStructure(['data' => ['id', 'name', 'email', 'is_admin']]);
    }

    public function test_missing_api_key_returns_401(): void
    {
        $this->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'API_KEY_REQUIRED');
    }

    public function test_invalid_api_key_returns_401(): void
    {
        $this->withHeader('X-API-KEY', 'invalid_key')
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401)
            ->assertJsonPath('error.code', 'INVALID_API_KEY');
    }

    public function test_inactive_user_cannot_authenticate(): void
    {
        [, $apiKey] = $this->makeUser(['is_active' => false, 'email' => 'inactive@example.com']);

        $this->withHeader('X-API-KEY', $apiKey)
            ->getJson('/api/v1/auth/me')
            ->assertStatus(401);
    }

    public function test_health_endpoint_is_public(): void
    {
        $this->getJson('/api/v1/health')
            ->assertOk()
            ->assertJsonStructure(['data' => ['status', 'services']]);
    }
}
