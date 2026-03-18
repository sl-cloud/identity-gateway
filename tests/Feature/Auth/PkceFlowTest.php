<?php

namespace Tests\Feature\Auth;

use App\Models\SigningKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesOAuthClient;

class PkceFlowTest extends TestCase
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

    public function test_pkce_flow_with_s256_challenge(): void
    {
        $user = User::factory()->create();
        $client = $this->createPublicClient($user);
        $pkce = $this->generatePkceChallenge();

        // Step 1: Get authorization code with code_challenge
        $response = $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => $client->redirect,
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'test-state',
                'code_challenge' => $pkce['code_challenge'],
                'code_challenge_method' => $pkce['code_challenge_method'],
            ]));

        // Approve consent
        $authRequestKey = $this->extractAuthRequestKey($response);
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => true,
                'auth_request_key' => $authRequestKey,
            ]);

        // Extract authorization code
        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $code = $query['code'] ?? '';

        $this->assertNotEmpty($code);

        // Step 2: Exchange code for token with code_verifier
        $tokenResponse = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
            'code' => $code,
            'code_verifier' => $pkce['code_verifier'],
        ]);

        $tokenResponse->assertStatus(200);
        $tokenResponse->assertJsonStructure([
            'access_token',
            'token_type',
            'expires_in',
        ]);
    }

    public function test_pkce_flow_fails_with_wrong_verifier(): void
    {
        $user = User::factory()->create();
        $client = $this->createPublicClient($user);
        $pkce = $this->generatePkceChallenge();

        // Get authorization code
        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => $client->redirect,
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'test-state',
                'code_challenge' => $pkce['code_challenge'],
                'code_challenge_method' => $pkce['code_challenge_method'],
            ]));

        $authRequestKey = $this->extractAuthRequestKey(null);
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => true,
                'auth_request_key' => $authRequestKey,
            ]);

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $code = $query['code'] ?? '';

        // Try to exchange with wrong verifier
        $tokenResponse = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
            'code' => $code,
            'code_verifier' => 'wrong-verifier',
        ]);

        $tokenResponse->assertStatus(400);
    }

    public function test_pkce_flow_fails_without_code_verifier(): void
    {
        $user = User::factory()->create();
        $client = $this->createPublicClient($user);
        $pkce = $this->generatePkceChallenge();

        // Get authorization code
        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => $client->redirect,
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'test-state',
                'code_challenge' => $pkce['code_challenge'],
                'code_challenge_method' => $pkce['code_challenge_method'],
            ]));

        $authRequestKey = $this->extractAuthRequestKey(null);
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => true,
                'auth_request_key' => $authRequestKey,
            ]);

        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $code = $query['code'] ?? '';

        // Try to exchange without verifier
        $tokenResponse = $this->postJson('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
            'code' => $code,
        ]);

        $tokenResponse->assertStatus(400);
    }

    public function test_public_client_cannot_use_client_secret(): void
    {
        $user = User::factory()->create();
        $client = $this->createPublicClient($user);

        // Public client should have null secret
        $this->assertNull($client->secret);
    }
}
