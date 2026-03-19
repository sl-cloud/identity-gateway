<?php

namespace Tests\Unit\Services;

use App\Models\ApiKey;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ApiKeyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ApiKeyService $service;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ApiKeyService::class);
        $this->user = User::factory()->create();
    }

    public function test_generates_api_key_with_correct_format(): void
    {
        $result = $this->service->generate($this->user, 'Test Key');

        $this->assertStringStartsWith('igw_live_', $result['api_key']);
        $this->assertEquals(41, strlen($result['api_key'])); // 'igw_live_' (9) + 32 hex chars
        $this->assertInstanceOf(ApiKey::class, $result['api_key_model']);
        $this->assertEquals('Test Key', $result['api_key_model']->name);

        // key_prefix stores config prefix + first 4 chars of random part for identification
        $prefix = $result['api_key_model']->key_prefix;
        $this->assertStringStartsWith('igw_live_', $prefix);
        $this->assertEquals(13, strlen($prefix)); // 'igw_live_' (9) + 4 hex chars
    }

    public function test_generates_api_key_with_custom_scopes(): void
    {
        $scopes = ['user:read', 'resources:read'];
        $result = $this->service->generate($this->user, 'Scoped Key', $scopes);

        $this->assertEquals($scopes, $result['api_key_model']->scopes);
    }

    public function test_generates_api_key_with_expiration(): void
    {
        $ttl = 3600; // 1 hour
        $before = now();
        $result = $this->service->generate($this->user, 'Expiring Key', null, $ttl);
        $after = now();

        $this->assertNotNull($result['api_key_model']->expires_at);
        // Check that expiration is roughly 1 hour from now (within reasonable bounds)
        $expiresAt = $result['api_key_model']->expires_at;
        $this->assertTrue($expiresAt->greaterThanOrEqualTo($before->addSeconds($ttl - 10)));
        $this->assertTrue($expiresAt->lessThanOrEqualTo($after->addSeconds($ttl + 10)));
    }

    public function test_stores_sha256_hash_of_api_key(): void
    {
        $result = $this->service->generate($this->user, 'Test Key');
        $plainKey = $result['api_key'];

        $expectedHash = hash('sha256', $plainKey);
        $this->assertEquals($expectedHash, $result['api_key_model']->key_hash);
    }

    public function test_validates_correct_api_key(): void
    {
        $result = $this->service->generate($this->user, 'Test Key', ['user:read']);
        $plainKey = $result['api_key'];

        $validated = $this->service->validate($plainKey);

        $this->assertInstanceOf(ApiKey::class, $validated);
        $this->assertEquals($result['api_key_model']->id, $validated->id);
    }

    public function test_returns_null_for_invalid_api_key(): void
    {
        $validated = $this->service->validate('igw_live_invalidkey');

        $this->assertNull($validated);
    }

    public function test_returns_null_for_api_key_with_wrong_prefix(): void
    {
        $validated = $this->service->validate('wrong_prefix_123456');

        $this->assertNull($validated);
    }

    public function test_returns_null_for_revoked_api_key(): void
    {
        $result = $this->service->generate($this->user, 'Test Key');
        $plainKey = $result['api_key'];

        $result['api_key_model']->revoke();

        $validated = $this->service->validate($plainKey);

        $this->assertNull($validated);
    }

    public function test_api_key_marked_expired_when_past_expiration(): void
    {
        $apiKey = ApiKey::factory()->create([
            'user_id' => $this->user->id,
            'expires_at' => now()->subDay(),
        ]);

        $this->assertTrue($apiKey->isExpired());
        $this->assertFalse($apiKey->isActive());
    }

    public function test_revokes_api_key_by_id(): void
    {
        $result = $this->service->generate($this->user, 'Test Key');
        $apiKeyId = $result['api_key_model']->id;

        $success = $this->service->revoke($apiKeyId);

        $this->assertTrue($success);
        $this->assertTrue(ApiKey::find($apiKeyId)->isRevoked());
    }

    public function test_revokes_api_key_by_key_value(): void
    {
        $result = $this->service->generate($this->user, 'Test Key');
        $plainKey = $result['api_key'];

        $success = $this->service->revokeByKey($plainKey);

        $this->assertTrue($success);
        $this->assertTrue($result['api_key_model']->fresh()->isRevoked());
    }

    public function test_returns_false_when_revoking_non_existent_key(): void
    {
        $success = $this->service->revoke('non-existent-uuid');

        $this->assertFalse($success);
    }

    public function test_gets_all_api_keys_for_user(): void
    {
        $this->service->generate($this->user, 'Key 1');
        $this->service->generate($this->user, 'Key 2');

        $keys = $this->service->getUserApiKeys($this->user);

        $this->assertCount(2, $keys);
    }

    public function test_excludes_revoked_keys_when_getting_user_api_keys(): void
    {
        $this->service->generate($this->user, 'Active Key');
        $this->service->generate($this->user, 'Revoked Key');

        // Revoke the second key
        ApiKey::where('name', 'Revoked Key')->first()->revoke();

        $keys = $this->service->getUserApiKeys($this->user);

        $this->assertCount(1, $keys);
        $this->assertEquals('Active Key', $keys->first()->name);
    }

    public function test_deletes_api_key(): void
    {
        $result = $this->service->generate($this->user, 'Test Key');
        $apiKeyId = $result['api_key_model']->id;

        $success = $this->service->delete($apiKeyId);

        $this->assertTrue($success);
        $this->assertNull(ApiKey::find($apiKeyId));
    }
}
