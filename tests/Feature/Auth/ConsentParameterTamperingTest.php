<?php

namespace Tests\Feature\Auth;

use App\Models\SigningKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesOAuthClient;

class ConsentParameterTamperingTest extends TestCase
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

    public function test_tampered_client_id_in_consent_approval_is_rejected(): void
    {
        $user = User::factory()->create();
        $legitimateClient = $this->createConfidentialClient($user, 'http://localhost/callback');
        $maliciousClient = $this->createConfidentialClient($user, 'http://evil.com/callback');

        // User sees consent screen for legitimate client
        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $legitimateClient->id,
                'redirect_uri' => $legitimateClient->redirect,
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'test-state',
            ]));

        $authRequestKey = $this->extractAuthRequestKey(null);

        // Attacker submits approval with different client_id — but the session-stored
        // auth request preserves the original client, so the tampered value is ignored.
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => true,
                'auth_request_key' => $authRequestKey,
                'client_id' => $maliciousClient->id, // TAMPERED — should be ignored
            ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        // The redirect must go to the legitimate client's callback, not the malicious one
        $this->assertStringStartsWith('http://localhost/callback', $location);
        $this->assertStringNotContainsString('evil.com', $location);

        // The issued code should be for the legitimate client
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('code', $query, 'Authorization code should be issued');

        // Exchange the code using the legitimate client credentials — should succeed
        $tokens = $this->exchangeCodeForToken($legitimateClient, $query['code']);
        $this->assertArrayHasKey('access_token', $tokens, 'Legitimate client should be able to exchange the code');
    }

    public function test_tampered_redirect_uri_in_consent_approval(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user, 'http://localhost/callback');

        // User sees consent screen
        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => $client->redirect,
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'test-state',
            ]));

        $authRequestKey = $this->extractAuthRequestKey(null);

        // Attacker submits approval with different redirect_uri
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => true,
                'auth_request_key' => $authRequestKey,
                'redirect_uri' => 'http://evil.com/steal-code', // TAMPERED
            ]);

        // The session-stored auth request preserves the original redirect_uri,
        // so the tampered value in the POST body is ignored entirely.
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringStartsWith('http://localhost/callback', $location);
        $this->assertStringNotContainsString('evil.com', $location);
    }

    public function test_tampered_state_in_consent_approval(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // User sees consent screen with state=original-state
        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => $client->redirect,
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'original-state',
            ]));

        $authRequestKey = $this->extractAuthRequestKey(null);

        // Attacker submits approval with different state
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => true,
                'auth_request_key' => $authRequestKey,
                'state' => 'tampered-state', // TAMPERED
            ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        // The tampered state should be ignored — the session-stored auth request preserves the original
        $this->assertStringContainsString('state=original-state', $location);
        $this->assertStringNotContainsString('state=tampered-state', $location);
    }

    public function test_tampered_scope_in_consent_approval(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // User sees consent screen requesting user:read
        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => $client->redirect,
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'test-state',
            ]));

        $authRequestKey = $this->extractAuthRequestKey(null);

        // Attacker submits approval with elevated scopes
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => true,
                'auth_request_key' => $authRequestKey,
                'scope' => 'user:read users:read resources:write', // TAMPERED — escalated privileges
            ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');

        // Extract and exchange the code
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertArrayHasKey('code', $query, 'Authorization code should still be issued');

        $tokens = $this->exchangeCodeForToken($client, $query['code']);
        $this->assertArrayHasKey('access_token', $tokens, 'Token exchange should succeed');

        // Decode the JWT to verify the granted scopes match the original request, not the tampered ones
        $parts = explode('.', $tokens['access_token']);
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/'), true), true);

        $this->assertIsArray($payload['scopes']);
        $this->assertContains('user:read', $payload['scopes'], 'Original requested scope should be granted');
        $this->assertNotContains('users:read', $payload['scopes'], 'Tampered scope should not be granted');
        $this->assertNotContains('resources:write', $payload['scopes'], 'Tampered scope should not be granted');
    }

    public function test_consent_approval_without_auth_request_key_is_rejected(): void
    {
        $user = User::factory()->create();

        // Submit consent approval without ever visiting the authorize endpoint
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => true,
            ]);

        $response->assertRedirect(route('login'));
    }

    public function test_consent_approval_with_invalid_auth_request_key_is_rejected(): void
    {
        $user = User::factory()->create();

        // Submit consent approval with a fabricated key
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => true,
                'auth_request_key' => 'oauth_auth_request_fabricated_key_12345',
            ]);

        $response->assertRedirect(route('login'));
    }
}
