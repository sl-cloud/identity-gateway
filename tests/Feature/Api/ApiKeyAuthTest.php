<?php

namespace Tests\Feature\Api;

use App\Models\ApiKey;
use App\Models\OAuthScope;
use App\Models\Resource;
use App\Models\SigningKey;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyAuthTest extends TestCase
{
    use RefreshDatabase;

    protected ApiKeyService $apiKeyService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an active signing key
        SigningKey::factory()->create([
            'status' => 'active',
            'algorithm' => 'RS256',
        ]);

        // Create OAuth scopes
        OAuthScope::create(['id' => 'user:read', 'description' => 'Read user info', 'is_default' => true]);
        OAuthScope::create(['id' => 'resources:read', 'description' => 'Read resources', 'is_default' => true]);

        $this->user = User::factory()->create();
        $this->apiKeyService = app(ApiKeyService::class);
    }

    public function test_authenticates_with_valid_x_api_key_header(): void
    {
        $result = $this->apiKeyService->generate($this->user, 'Test Key', ['user:read']);
        $apiKey = $result['api_key'];

        $response = $this->withHeader('X-Api-Key', $apiKey)
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'scopes' => ['user:read'],
            ]);
    }

    public function test_authenticates_with_valid_authorization_apikey_header(): void
    {
        $result = $this->apiKeyService->generate($this->user, 'Test Key', ['user:read']);
        $apiKey = $result['api_key'];

        $response = $this->withHeader('Authorization', 'ApiKey '.$apiKey)
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $this->user->id,
            ]);
    }

    public function test_returns_401_for_invalid_api_key(): void
    {
        $response = $this->withHeader('X-Api-Key', 'igw_live_invalidkey')
            ->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'unauthorized',
            ]);
    }

    public function test_returns_401_for_missing_api_key(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'unauthorized',
            ]);
    }

    public function test_enforces_api_key_scopes(): void
    {
        // Create API key with only resources:read scope
        $result = $this->apiKeyService->generate($this->user, 'Test Key', ['resources:read']);
        $apiKey = $result['api_key'];

        // Try to access /me which requires user:read
        $response = $this->withHeader('X-Api-Key', $apiKey)
            ->getJson('/api/v1/me');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'insufficient_scope',
            ]);
    }

    public function test_allows_access_with_correct_api_key_scopes(): void
    {
        $result = $this->apiKeyService->generate($this->user, 'Test Key', ['resources:read']);
        $apiKey = $result['api_key'];

        // Create a resource first
        Resource::factory()->create(['user_id' => $this->user->id]);

        // Access resources endpoint which requires resources:read
        $response = $this->withHeader('X-Api-Key', $apiKey)
            ->getJson('/api/v1/resources');

        $response->assertStatus(200);
    }

    public function test_returns_401_for_revoked_api_key(): void
    {
        $result = $this->apiKeyService->generate($this->user, 'Test Key', ['user:read']);
        $apiKey = $result['api_key'];

        // Revoke the key
        $result['api_key_model']->revoke();

        $response = $this->withHeader('X-Api-Key', $apiKey)
            ->getJson('/api/v1/me');

        $response->assertStatus(401);
    }

    public function test_api_key_is_marked_as_expired_when_past_expiration(): void
    {
        $apiKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'scopes' => ['user:read'],
            'expires_at' => now()->subDay(), // Expired yesterday
        ]);

        // Verify the model state
        $this->assertTrue($apiKey->isExpired());
        $this->assertFalse($apiKey->isActive());
    }
}
