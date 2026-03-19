<?php

namespace Tests\Feature\Api;

use App\Models\OAuthScope;
use App\Models\Resource;
use App\Models\SigningKey;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResourceEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected JwtService $jwtService;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create an active signing key for JWT generation
        SigningKey::factory()->create([
            'status' => 'active',
            'algorithm' => 'RS256',
        ]);

        // Create OAuth scopes
        OAuthScope::create(['id' => 'resources:read', 'description' => 'Read resources', 'is_default' => true]);
        OAuthScope::create(['id' => 'resources:write', 'description' => 'Create and modify resources', 'is_default' => false]);

        $this->jwtService = app(JwtService::class);
        $this->user = User::factory()->create();
    }

    public function test_lists_resources_for_authenticated_user(): void
    {
        $token = $this->jwtService->sign($this->user, ['resources:read'], 'test-client');

        Resource::factory()->count(3)->create(['user_id' => $this->user->id]);
        Resource::factory()->create(['user_id' => User::factory()->create()->id]); // Other user's resource

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/resources');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_creates_resource_with_resources_write_scope(): void
    {
        $token = $this->jwtService->sign($this->user, ['resources:read', 'resources:write'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/resources', [
                'name' => 'Test Resource',
                'description' => 'A test resource',
                'metadata' => ['key' => 'value'],
            ]);

        $response->assertStatus(201)
            ->assertJson([
                'name' => 'Test Resource',
                'description' => 'A test resource',
                'metadata' => ['key' => 'value'],
                'user_id' => $this->user->id,
            ]);

        $this->assertDatabaseHas('resources', [
            'name' => 'Test Resource',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_returns_403_when_creating_resource_without_resources_write_scope(): void
    {
        $token = $this->jwtService->sign($this->user, ['resources:read'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/resources', [
                'name' => 'Test Resource',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'insufficient_scope',
            ]);
    }

    public function test_shows_specific_resource(): void
    {
        $resource = Resource::factory()->create(['user_id' => $this->user->id]);
        $token = $this->jwtService->sign($this->user, ['resources:read'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/resources/'.$resource->id);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $resource->id,
                'name' => $resource->name,
                'user_id' => $this->user->id,
            ]);
    }

    public function test_returns_403_when_showing_other_users_resource(): void
    {
        $otherUser = User::factory()->create();
        $resource = Resource::factory()->create(['user_id' => $otherUser->id]);
        $token = $this->jwtService->sign($this->user, ['resources:read'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/v1/resources/'.$resource->id);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'access_denied',
                'status' => 403,
            ]);
    }

    public function test_updates_resource_with_resources_write_scope(): void
    {
        $resource = Resource::factory()->create(['user_id' => $this->user->id]);
        $token = $this->jwtService->sign($this->user, ['resources:read', 'resources:write'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/resources/'.$resource->id, [
                'name' => 'Updated Name',
                'description' => 'Updated description',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'id' => $resource->id,
                'name' => 'Updated Name',
                'description' => 'Updated description',
            ]);

        $this->assertDatabaseHas('resources', [
            'id' => $resource->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_returns_403_when_updating_other_users_resource(): void
    {
        $otherUser = User::factory()->create();
        $resource = Resource::factory()->create(['user_id' => $otherUser->id]);
        $token = $this->jwtService->sign($this->user, ['resources:read', 'resources:write'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->putJson('/api/v1/resources/'.$resource->id, [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(403)
            ->assertJson([
                'error' => 'access_denied',
            ]);
    }

    public function test_deletes_resource_with_resources_write_scope(): void
    {
        $resource = Resource::factory()->create(['user_id' => $this->user->id]);
        $token = $this->jwtService->sign($this->user, ['resources:read', 'resources:write'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->deleteJson('/api/v1/resources/'.$resource->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('resources', [
            'id' => $resource->id,
        ]);
    }

    public function test_validates_resource_creation_data(): void
    {
        $token = $this->jwtService->sign($this->user, ['resources:write'], 'test-client');

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/v1/resources', [
                'name' => '', // Empty name should fail
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
}
