<?php

namespace Tests\Feature\Auth;

use App\Models\SigningKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;
use Tests\Traits\CreatesOAuthClient;

class TokenRevocationTest extends TestCase
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

    public function test_token_can_be_revoked(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get a valid token
        $code = $this->getAuthorizationCode($client, $user);
        $tokens = $this->exchangeCodeForToken($client, $code);

        // Revoke the token
        $response = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/revoke', [
                'token' => $tokens['access_token'],
                'token_type_hint' => 'access_token',
            ]);

        $response->assertStatus(200);
    }

    public function test_revoked_token_is_added_to_blacklist(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get a valid token
        $code = $this->getAuthorizationCode($client, $user);
        $tokens = $this->exchangeCodeForToken($client, $code);

        // Decode to get jti
        $parts = explode('.', $tokens['access_token']);
        $payload = json_decode(base64_decode($parts[1]), true);
        $jti = $payload['jti'];

        // Revoke the token
        $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/revoke', [
                'token' => $tokens['access_token'],
            ]);

        // Check if jti is in Redis blacklist
        $this->assertTrue((bool) Redis::exists('revoked:'.$jti));
    }

    public function test_revoked_token_fails_introspection(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get a valid token
        $code = $this->getAuthorizationCode($client, $user);
        $tokens = $this->exchangeCodeForToken($client, $code);

        // Verify token is active before revocation
        $beforeRevoke = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/introspect', [
                'token' => $tokens['access_token'],
            ]);

        $this->assertTrue($beforeRevoke->json('active'));

        // Revoke the token
        $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/revoke', [
                'token' => $tokens['access_token'],
            ]);

        // Verify token is inactive after revocation
        $afterRevoke = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/introspect', [
                'token' => $tokens['access_token'],
            ]);

        $this->assertFalse($afterRevoke->json('active'));
    }

    public function test_refresh_token_can_be_revoked(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get tokens
        $code = $this->getAuthorizationCode($client, $user);
        $tokens = $this->exchangeCodeForToken($client, $code);

        // Revoke the refresh token
        $response = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/revoke', [
                'token' => $tokens['refresh_token'],
                'token_type_hint' => 'refresh_token',
            ]);

        $response->assertStatus(200);

        // Try to use the refresh token (should fail)
        $refreshResponse = $this->postJson('/oauth/token', [
            'grant_type' => 'refresh_token',
            'refresh_token' => $tokens['refresh_token'],
            'client_id' => $client->id,
            'client_secret' => $client->secret,
        ]);

        // Revoked refresh token should be rejected (League returns 401 for revoked tokens)
        $this->assertContains($refreshResponse->getStatusCode(), [400, 401]);
    }

    public function test_revocation_always_returns_200_per_rfc7009(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Try to revoke an invalid token
        $response = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/revoke', [
                'token' => 'invalid-token',
            ]);

        // RFC 7009 requires 200 even if token is invalid
        $response->assertStatus(200);
    }

    public function test_revoking_already_revoked_token_succeeds(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get a valid token
        $code = $this->getAuthorizationCode($client, $user);
        $tokens = $this->exchangeCodeForToken($client, $code);

        // Revoke the token first time
        $firstRevoke = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/revoke', [
                'token' => $tokens['access_token'],
            ]);

        $firstRevoke->assertStatus(200);

        // Revoke the same token again
        $secondRevoke = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/revoke', [
                'token' => $tokens['access_token'],
            ]);

        // Should still return 200
        $secondRevoke->assertStatus(200);
    }

    public function test_revocation_requires_client_authentication(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get a valid token
        $code = $this->getAuthorizationCode($client, $user);
        $tokens = $this->exchangeCodeForToken($client, $code);

        // Try to revoke without authentication
        $response = $this->postJson('/oauth/revoke', [
            'token' => $tokens['access_token'],
        ]);

        // Should fail without proper authentication
        $response->assertStatus(401);
    }
}
