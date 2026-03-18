<?php

namespace Tests\Feature\Auth;

use App\Models\SigningKey;
use App\Models\User;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\CreatesOAuthClient;

class TokenIntrospectionTest extends TestCase
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

    public function test_introspection_returns_active_for_valid_token(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get a valid token
        $code = $this->getAuthorizationCode($client, $user, ['user:read', 'resources:read']);
        $tokens = $this->exchangeCodeForToken($client, $code);

        // Introspect the token
        $response = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/introspect', [
                'token' => $tokens['access_token'],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'active' => true,
            'token_type' => 'Bearer',
        ]);

        $response->assertJsonStructure([
            'active',
            'scope',
            'client_id',
            'username',
            'exp',
            'iat',
            'sub',
            'jti',
        ]);
    }

    public function test_introspection_returns_inactive_for_expired_token(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get the active signing key to create a properly signed but expired token
        $signingKey = SigningKey::where('status', 'active')->first();

        $expiredPayload = [
            'iss' => config('identity-gateway.jwt.issuer'),
            'aud' => config('identity-gateway.jwt.audience'),
            'sub' => (string) $user->id,
            'exp' => time() - 3600, // Expired 1 hour ago
            'iat' => time() - 7200,
            'jti' => Str::uuid()->toString(),
            'scopes' => ['user:read'],
            'client_id' => $client->id,
        ];

        $expiredToken = JWT::encode(
            $expiredPayload,
            $signingKey->private_key,
            $signingKey->algorithm,
            $signingKey->id
        );

        // Introspect the expired token
        $response = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/introspect', [
                'token' => $expiredToken,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'active' => false,
        ]);
    }

    public function test_introspection_returns_inactive_for_revoked_token(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get a valid token
        $code = $this->getAuthorizationCode($client, $user);
        $tokens = $this->exchangeCodeForToken($client, $code);

        // Revoke the token
        $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/revoke', [
                'token' => $tokens['access_token'],
            ]);

        // Introspect the revoked token
        $response = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/introspect', [
                'token' => $tokens['access_token'],
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'active' => false,
        ]);
    }

    public function test_introspection_returns_inactive_for_invalid_token(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        $response = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/introspect', [
                'token' => 'invalid.jwt.token',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'active' => false,
        ]);
    }

    public function test_introspection_includes_scope_information(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get token with specific scopes
        $code = $this->getAuthorizationCode($client, $user, ['user:read', 'resources:read']);
        $tokens = $this->exchangeCodeForToken($client, $code);

        $response = $this->withBasicAuth($client->id, $client->secret)
            ->postJson('/oauth/introspect', [
                'token' => $tokens['access_token'],
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertTrue($data['active']);
        $this->assertStringContainsString('user:read', $data['scope']);
        $this->assertStringContainsString('resources:read', $data['scope']);
    }
}
