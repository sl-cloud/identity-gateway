<?php

namespace Tests\Feature\Auth;

use App\Models\SigningKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesOAuthClient;

class ClientCredentialsFlowTest extends TestCase
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

    public function test_client_credentials_flow_issues_token(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'scope' => 'resources:read',
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
        ]);

        $this->assertEquals('Bearer', $response->json('token_type'));
    }

    public function test_client_credentials_flow_fails_with_invalid_secret(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => 'wrong-secret',
            'scope' => 'resources:read',
        ]);

        $response->assertStatus(401);
    }

    public function test_client_credentials_flow_fails_with_invalid_client_id(): void
    {
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => 'invalid-client-id',
            'client_secret' => 'some-secret',
            'scope' => 'resources:read',
        ]);

        $response->assertStatus(401);
    }

    public function test_client_credentials_token_has_no_user_context(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
        ]);

        $response->assertStatus(200);

        $token = $response->json('access_token');
        $this->assertNotEmpty($token);

        // Decode JWT to verify no user_id claim
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]), true);

        // Client credentials tokens should not have a user context
        $this->assertArrayNotHasKey('user_id', $payload);
    }

    public function test_client_credentials_with_multiple_scopes(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'scope' => 'resources:read resources:write',
        ]);

        $response->assertStatus(200);

        $token = $response->json('access_token');
        $parts = explode('.', $token);
        $payload = json_decode(base64_decode($parts[1]), true);

        $this->assertArrayHasKey('scopes', $payload);
        $this->assertContains('resources:read', $payload['scopes']);
        $this->assertContains('resources:write', $payload['scopes']);
    }
}
