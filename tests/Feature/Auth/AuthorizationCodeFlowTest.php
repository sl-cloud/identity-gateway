<?php

namespace Tests\Feature\Auth;

use App\Models\OAuthConsentApproval;
use App\Models\SigningKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesOAuthClient;

class AuthorizationCodeFlowTest extends TestCase
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

    public function test_authorization_endpoint_rejects_unauthenticated_request(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        $response = $this->getJson('/oauth/authorize?'.http_build_query([
            'client_id' => $client->id,
            'redirect_uri' => $client->redirect,
            'response_type' => 'code',
            'scope' => 'user:read',
            'state' => 'test-state',
        ]));

        $response->assertStatus(401);
    }

    public function test_authorization_endpoint_shows_consent_screen(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        $response = $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => $client->redirect,
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'test-state',
            ]));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Auth/Consent', false)
            ->has('client')
            ->has('scopes')
        );
    }

    public function test_consent_approval_creates_approval_record(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // First, get to consent screen
        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => $client->redirect,
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'test-state',
            ]));

        // Extract auth_request_key from session
        $authRequestKey = $this->extractAuthRequestKey(null);

        // Approve consent
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => true,
                'auth_request_key' => $authRequestKey,
            ]);

        $response->assertRedirect();

        // Check approval was stored
        $this->assertDatabaseHas('oauth_consent_approvals', [
            'user_id' => $user->id,
            'client_id' => $client->id,
        ]);
    }

    public function test_subsequent_authorization_skips_consent_if_already_approved(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Create pre-existing approval
        OAuthConsentApproval::create([
            'user_id' => $user->id,
            'client_id' => $client->id,
            'scopes' => ['user:read'],
            'approved_at' => now(),
        ]);

        // Request authorization
        $response = $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => $client->redirect,
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'test-state',
            ]));

        // Should redirect directly with code (skipping consent)
        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('code=', $location);
        $this->assertStringContainsString('state=test-state', $location);
    }

    public function test_full_authorization_code_flow(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Step 1: Get authorization code
        $code = $this->getAuthorizationCode($client, $user);
        $this->assertNotEmpty($code);

        // Step 2: Exchange code for token
        $tokenResponse = $this->exchangeCodeForToken($client, $code);

        $this->assertArrayHasKey('access_token', $tokenResponse);
        $this->assertArrayHasKey('token_type', $tokenResponse);
        $this->assertArrayHasKey('expires_in', $tokenResponse);
        $this->assertEquals('Bearer', $tokenResponse['token_type']);
    }

    public function test_consent_denial_redirects_with_error(): void
    {
        $user = User::factory()->create();
        $client = $this->createConfidentialClient($user);

        // Get to consent screen
        $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => $client->redirect,
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'test-state',
            ]));

        // Extract auth_request_key from session
        $authRequestKey = $this->extractAuthRequestKey(null);

        // Deny consent
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => false,
                'auth_request_key' => $authRequestKey,
            ]);

        $response->assertRedirect();
        $location = $response->headers->get('Location');
        $this->assertStringContainsString('error=', $location);
    }

    public function test_invalid_client_id_returns_error(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => 'invalid-client-id',
                'redirect_uri' => 'http://localhost/callback',
                'response_type' => 'code',
                'scope' => 'user:read',
                'state' => 'test-state',
            ]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors();
    }
}
