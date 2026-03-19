<?php

namespace Tests\Feature\Api;

use App\Models\OAuthScope;
use App\Models\SigningKey;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected JwtService $jwtService;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an active signing key for JWT generation
        SigningKey::factory()->create([
            'status' => 'active',
            'algorithm' => 'RS256',
        ]);

        // Create OAuth scopes
        OAuthScope::create(['id' => 'user:read', 'description' => 'Read user information', 'is_default' => true]);
        OAuthScope::create(['id' => 'users:read', 'description' => 'List all users', 'is_default' => false]);

        $this->jwtService = app(JwtService::class);
    }

    public function test_returns_authenticated_user_with_user_read_scope(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->sign($user, ['user:read'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me');

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'scopes' => ['user:read'],
            ]);
    }

    public function test_returns_401_when_no_token_provided(): void
    {
        $response = $this->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'unauthorized',
            ]);
    }

    public function test_returns_403_when_token_lacks_user_read_scope(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->sign($user, ['resources:read'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'insufficient_scope',
            ]);
    }

    public function test_returns_401_for_invalid_tokens(): void
    {
        $response = $this->withHeader('Authorization', 'Bearer invalid.token.here')
            ->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'unauthorized',
            ]);
    }

    public function test_returns_401_for_revoked_tokens(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->sign($user, ['user:read'], 'test-client');
        $jti = $this->jwtService->extractJti($token);
        $this->assertNotNull($jti);
        $this->jwtService->revoke($jti, 900);

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/me');

        $response->assertStatus(401)
            ->assertJson([
                'error' => 'unauthorized',
            ]);
    }

    public function test_lists_all_users_with_users_read_scope(): void
    {
        $user = User::factory()->create();
        User::factory()->count(3)->create();

        $token = $this->jwtService->sign($user, ['users:read'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/users');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'created_at', 'updated_at'],
                ],
            ]);

        // Email should only be present for the authenticated user's own record
        $data = $response->json('data');
        foreach ($data as $record) {
            if ($record['id'] === $user->id) {
                $this->assertArrayHasKey('email', $record);
                $this->assertEquals($user->email, $record['email']);
            } else {
                $this->assertArrayNotHasKey('email', $record);
            }
        }
    }

    public function test_returns_403_when_listing_users_without_users_read_scope(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->sign($user, ['user:read'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/users');

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'insufficient_scope',
            ]);
    }

    public function test_shows_specific_user_with_users_read_scope(): void
    {
        $authUser = User::factory()->create();
        $targetUser = User::factory()->create();

        $token = $this->jwtService->sign($authUser, ['users:read'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/users/'.$targetUser->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $targetUser->id,
                'name' => $targetUser->name,
            ])
            ->assertJsonMissing(['email' => $targetUser->email]);
    }

    public function test_shows_own_profile_with_email(): void
    {
        $user = User::factory()->create();

        $token = $this->jwtService->sign($user, ['users:read'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/users/'.$user->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ]);
    }

    public function test_returns_404_for_non_existent_user(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->sign($user, ['users:read'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/users/99999');

        $response->assertStatus(404)
            ->assertJson([
                'error' => 'not_found',
            ]);
    }
}
