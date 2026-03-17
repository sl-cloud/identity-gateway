<?php

namespace Tests\Unit\Services;

use App\Models\SigningKey;
use App\Services\SigningKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SigningKeyServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SigningKeyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SigningKeyService::class);
    }

    public function test_generates_key_pair(): void
    {
        $key = $this->service->generateKeyPair();

        $this->assertInstanceOf(SigningKey::class, $key);
        $this->assertNotEmpty($key->id);
        $this->assertNotEmpty($key->private_key);
        $this->assertNotEmpty($key->public_key);
        $this->assertEquals('RS256', $key->algorithm);
        $this->assertEquals('active', $key->status);
        $this->assertNotNull($key->activated_at);
        $this->assertNotNull($key->expires_at);
    }

    public function test_gets_active_key(): void
    {
        $key1 = $this->service->generateKeyPair();

        $activeKey = $this->service->getActiveKey();

        $this->assertNotNull($activeKey);
        $this->assertEquals($key1->id, $activeKey->id);
    }

    public function test_rotates_keys(): void
    {
        $oldKey = $this->service->generateKeyPair();

        $newKey = $this->service->rotateKeys();

        $this->assertNotEquals($oldKey->id, $newKey->id);
        $this->assertEquals('active', $newKey->status);

        $oldKey->refresh();
        $this->assertEquals('retired', $oldKey->status);
        $this->assertNotNull($oldKey->retired_at);
    }

    public function test_gets_validation_keys(): void
    {
        $key1 = $this->service->generateKeyPair();
        $key2 = $this->service->rotateKeys();

        $keys = $this->service->getValidationKeys();

        $this->assertCount(2, $keys);
        $this->assertTrue($keys->contains('id', $key1->id));
        $this->assertTrue($keys->contains('id', $key2->id));
    }

    public function test_revokes_key(): void
    {
        $key = $this->service->generateKeyPair();

        $result = $this->service->revokeKey($key->id);

        $this->assertTrue($result);

        $key->refresh();
        $this->assertEquals('revoked', $key->status);
    }

    public function test_revoke_returns_false_for_nonexistent_key(): void
    {
        $result = $this->service->revokeKey('nonexistent-uuid');

        $this->assertFalse($result);
    }

    public function test_ensures_active_key_creates_if_none_exists(): void
    {
        $this->assertDatabaseCount('signing_keys', 0);

        $key = $this->service->ensureActiveKey();

        $this->assertNotNull($key);
        $this->assertEquals('active', $key->status);
        $this->assertDatabaseCount('signing_keys', 1);
    }

    public function test_ensures_active_key_returns_existing_if_available(): void
    {
        $existingKey = $this->service->generateKeyPair();

        $key = $this->service->ensureActiveKey();

        $this->assertEquals($existingKey->id, $key->id);
        $this->assertDatabaseCount('signing_keys', 1);
    }
}
