<?php

namespace Tests\Feature\Api;

use App\Models\OAuthScope;
use App\Models\SigningKey;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScopeEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected JwtService $jwtService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        SigningKey::factory()->create([
            'status' => 'active',
            'algorithm' => 'RS256',
        ]);

        OAuthScope::create(['id' => 'user:read', 'description' => 'Read user information', 'is_default' => true]);
        OAuthScope::create(['id' => 'resources:read', 'description' => 'Read resources', 'is_default' => false]);

        $this->jwtService = app(JwtService::class);
        $this->user = User::factory()->create();
    }

    public function test_lists_scopes_for_authenticated_request(): void
    {
        $token = $this->jwtService->sign($this->user, ['user:read'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/scopes');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'scopes' => [
                    '*' => ['id', 'description', 'is_default'],
                ],
            ]);
    }

    public function test_returns_401_when_scopes_endpoint_is_unauthenticated(): void
    {
        $response = $this->getJson('/api/v1/scopes');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'unauthorized',
                'status' => 401,
            ]);
    }
}
