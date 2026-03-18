<?php

namespace Tests\Feature\Auth;

use App\Models\SigningKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesOAuthClient;

class RefreshTokenTest extends TestCase
{
    use CreatesOAuthClient, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('passport:keys', ['--force' => true]);
        $this->setUpOAuth();

        SigningKey::factory()->create([
            'status' => 'active',
        ]);
    }

    public function test_refresh_token_can_be_used_to_get_new_access_token(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get initial tokens
        $code = $this->getAuthorizationCode($client, $user);
        $initialTokens = $this->exchangeCodeForToken($client, $code);

        $this->assertArrayHasKey('refresh_token', $initialTokens);

        // Use refresh token to get new access token
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $initialTokens['refresh_token'],
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'scope' => 'user:read',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'refresh_token',
            'token_type',
            'expires_in',
        ]);

        // New access token should be different from the old one
        $this->assertNotEquals($initialTokens['access_token'], $response->json('access_token'));
    }

    public function test_refresh_token_rotation_invalidates_old_refresh_token(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get initial tokens
        $code = $this->getAuthorizationCode($client, $user);
        $initialTokens = $this->exchangeCodeForToken($client, $code);

        // Use refresh token to get new tokens
        $newTokens = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $initialTokens['refresh_token'],
            'client_id' => $client->id,
            'client_secret' => $client->secret,
        ])->json();

        // Try to use the old refresh token again (should fail)
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $initialTokens['refresh_token'],
            'client_id' => $client->id,
            'client_secret' => $client->secret,
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_token_fails_with_invalid_token(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => 'invalid-refresh-token',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_token_fails_with_wrong_client(): void
    {
        $user = User::factory()->create();
        $client1 = $this->createConfidentialClient($user, 'http://localhost/callback1');
        $client2 = $this->createConfidentialClient($user, 'http://localhost/callback2');

        // Get tokens with client1
        $code = $this->getAuthorizationCode($client1, $user);
        $tokens = $this->exchangeCodeForToken($client1, $code);

        // Try to refresh with client2 (should fail)
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => $client2->id,
            'client_secret' => $client2->secret,
        ]);

        $response->assertStatus(401);
    }

    public function test_refresh_token_preserves_scopes(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get initial tokens with multiple scopes
        $code = $this->getAuthorizationCode($client, $user, ['user:read', 'resources:read']);
        $initialTokens = $this->exchangeCodeForToken($client, $code);

        // Refresh the token
        $newTokens = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $initialTokens['refresh_token'],
            'client_id' => $client->id,
            'client_secret' => $client->secret,
        ])->json();

        // Decode new access token
        $parts = explode('.', $newTokens['access_token']);
        $payload = json_decode(base64_decode($parts[1]), true);

        // Should have the same scopes
        $this->assertArrayHasKey('scopes', $payload);
        $this->assertContains('user:read', $payload['scopes']);
        $this->assertContains('resources:read', $payload['scopes']);
    }
}
