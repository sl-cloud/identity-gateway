<?php

namespace Tests\Feature\Auth;

use App\Models\SigningKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreatesOAuthClient;

class CrossClientSecurityTest extends TestCase
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

    /**
     * Test Bug A: Cross-client introspection vulnerability
     * RFC 7662 Section 2.2: "or the protected resource is not allowed to
     * introspect this particular token, then the authorization server MUST
     * return an introspection response with the 'active' field set to 'false'"
     */
    public function test_client_cannot_introspect_other_clients_tokens(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $client1 = $this->createConfidentialClient($user1);
        $client2 = $this->createConfidentialClient($user2);

        // Client 1 gets a token
        $code = $this->getAuthorizationCode($client1, $user1);
        $tokens = $this->exchangeCodeForToken($client1, $code);

        // Client 2 tries to introspect Client 1's token
        $response = $this->withBasicAuth($client2->id, $client2->secret)
            ->postJson('/oauth/introspect', [
                'token' => $tokens['access_token'],
            ]);

        $response->assertStatus(200);

        // Per RFC 7662, should return active=false when client is not allowed
        // to introspect this particular token
        $this->assertFalse(
            $response->json('active'),
            'Client 2 should not be able to introspect Client 1\'s token'
        );
    }

    /**
     * Test Bug B: Cross-client revocation vulnerability
     * RFC 7009 Section 2.1: "verifies whether the token was issued to the
     * client making the revocation request. If this validation fails, the
     * request is refused"
     */
    public function test_client_cannot_revoke_other_clients_tokens(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $client1 = $this->createConfidentialClient($user1);
        $client2 = $this->createConfidentialClient($user2);

        // Client 1 gets a token
        $code = $this->getAuthorizationCode($client1, $user1);
        $tokens = $this->exchangeCodeForToken($client1, $code);

        // Client 2 tries to revoke Client 1's token
        $response = $this->withBasicAuth($client2->id, $client2->secret)
            ->postJson('/oauth/revoke', [
                'token' => $tokens['access_token'],
            ]);

        // RFC 7009: always returns 200, but the token should NOT actually be revoked
        $response->assertStatus(200);

        // Verify Client 1's token is still active
        $introspectResponse = $this->withBasicAuth($client1->id, $client1->secret)
            ->postJson('/oauth/introspect', [
                'token' => $tokens['access_token'],
            ]);

        $this->assertTrue(
            $introspectResponse->json('active'),
            'Client 1\'s token should still be active after Client 2\'s failed revocation attempt'
        );
    }
}
