<?php

namespace Tests\Unit\Guards;

use App\Guards\ApiKeyGuard;
use App\Models\ApiKey;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class ApiKeyGuardTest extends TestCase
{
    use RefreshDatabase;

    private ApiKeyService|Mockery\MockInterface $apiKeyService;

    private Request $request;

    private ApiKeyGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();

        $this->apiKeyService = Mockery::mock(ApiKeyService::class);
        $this->request = new Request;
        $this->guard = new ApiKeyGuard($this->apiKeyService, $this->request);
    }

    public function test_returns_null_when_no_api_key_header_is_present(): void
    {
        $this->assertNull($this->guard->user());
        $this->assertFalse($this->guard->check());
        $this->assertTrue($this->guard->guest());
    }

    public function test_returns_user_when_valid_api_key_is_provided_in_x_api_key_header(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

        $this->apiKeyService->shouldReceive('validate')
            ->with('igw_live_validkey123')
            ->once()
            ->andReturn($apiKey);

        $this->request->headers->set('X-Api-Key', 'igw_live_validkey123');

        $result = $this->guard->user();

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
        $this->assertTrue($this->guard->check());
        $this->assertEquals($user->id, $this->guard->id());
    }

    public function test_returns_user_when_valid_api_key_is_provided_in_authorization_header(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

        $this->apiKeyService->shouldReceive('validate')
            ->with('igw_live_validkey123')
            ->once()
            ->andReturn($apiKey);

        $this->request->headers->set('Authorization', 'ApiKey igw_live_validkey123');

        $result = $this->guard->user();

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_returns_null_when_api_key_validation_fails(): void
    {
        $this->apiKeyService->shouldReceive('validate')
            ->with('igw_live_invalidkey')
            ->andReturn(null);

        $this->request->headers->set('X-Api-Key', 'igw_live_invalidkey');

        $this->assertNull($this->guard->user());
        $this->assertFalse($this->guard->check());
    }

    public function test_stores_api_key_on_request_attributes_for_later_access(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->create(['user_id' => $user->id, 'scopes' => ['user:read']]);

        $this->apiKeyService->shouldReceive('validate')
            ->with('igw_live_validkey123')
            ->once()
            ->andReturn($apiKey);

        $this->request->headers->set('X-Api-Key', 'igw_live_validkey123');

        $this->guard->user();

        $this->assertEquals($apiKey->id, $this->request->attributes->get('auth_api_key')->id);
    }

    public function test_returns_null_after_logout(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

        $this->apiKeyService->shouldReceive('validate')
            ->with('igw_live_validkey123')
            ->once()
            ->andReturn($apiKey);

        $this->request->headers->set('X-Api-Key', 'igw_live_validkey123');

        // First authenticate
        $this->guard->user();
        $this->assertTrue($this->guard->check());

        // Then logout
        $this->guard->logout();

        $this->assertNull($this->guard->user());
        $this->assertFalse($this->guard->check());
    }

    public function test_can_set_user_manually(): void
    {
        $user = User::factory()->create();

        $this->guard->setUser($user);

        $this->assertSame($user, $this->guard->user());
        $this->assertTrue($this->guard->check());
        $this->assertTrue($this->guard->hasUser());
    }

    public function test_validate_returns_false(): void
    {
        $this->assertFalse($this->guard->validate(['email' => 'test@example.com', 'password' => 'password']));
    }

    public function test_can_retrieve_the_api_key_model(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->create(['user_id' => $user->id]);

        $this->apiKeyService->shouldReceive('validate')
            ->with('igw_live_validkey123')
            ->once()
            ->andReturn($apiKey);

        $this->request->headers->set('X-Api-Key', 'igw_live_validkey123');

        $this->guard->user();

        $this->assertEquals($apiKey->id, $this->guard->getApiKey()->id);
    }
}
