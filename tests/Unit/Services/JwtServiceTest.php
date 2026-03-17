<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Services\JwtService;
use App\Services\SigningKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

class JwtServiceTest extends TestCase
{
    use RefreshDatabase;

    protected JwtService $jwtService;

    protected SigningKeyService $keyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyService = app(SigningKeyService::class);
        $this->jwtService = app(JwtService::class);

        // Ensure we have a signing key
        $this->keyService->ensureActiveKey();
    }

    /**
     * Mock Redis to return that token is not revoked.
     * Call this in tests that need token verification to work.
     */
    protected function mockTokenNotRevoked(?string $jti = null): void
    {
        $key = $jti ? "revoked:{$jti}" : \Mockery::pattern('/revoked:.*/');
        Redis::shouldReceive('exists')
            ->with($key)
            ->andReturn(false);
    }

    public function test_signs_jwt_token(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $token = $this->jwtService->sign(
            $user,
            ['user:read', 'resources:read'],
            'test-client-id'
        );

        $this->assertNotEmpty($token);
        $this->assertCount(3, explode('.', $token));
    }

    public function test_verifies_valid_token(): void
    {
        $user = User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        $token = $this->jwtService->sign($user, ['user:read'], 'test-client-id');

        // Mock token as not revoked for verification
        $this->mockTokenNotRevoked();

        $payload = $this->jwtService->verify($token);

        $this->assertEquals((string) $user->id, $payload->sub);
        $this->assertEquals($user->email, $payload->email);
        $this->assertEquals($user->name, $payload->name);
        $this->assertContains('user:read', $payload->scopes);
        $this->assertEquals('test-client-id', $payload->client_id);
    }

    public function test_verify_throws_exception_for_expired_token(): void
    {
        $this->expectException(\Exception::class);

        // Create an expired token by manipulating the config
        config(['identity-gateway.jwt.access_token_ttl' => -1]);

        $user = User::factory()->create();
        $token = $this->jwtService->sign($user, ['user:read'], 'test-client-id');

        // Reset config
        config(['identity-gateway.jwt.access_token_ttl' => 900]);

        // Mock token as not revoked (expiration happens before revocation check)
        $this->mockTokenNotRevoked();

        $this->jwtService->verify($token);
    }

    public function test_verify_throws_exception_for_revoked_token(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->sign($user, ['user:read'], 'test-client-id');

        $jti = $this->jwtService->extractJti($token);

        // Mock Redis for revoke (setex) and verification (exists)
        Redis::shouldReceive('setex')
            ->with("revoked:{$jti}", 900, true)
            ->once();
        Redis::shouldReceive('exists')
            ->with("revoked:{$jti}")
            ->andReturn(true);

        $this->jwtService->revoke($jti, 900);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Token has been revoked');

        $this->jwtService->verify($token);
    }

    public function test_extracts_jti_from_token(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->sign($user, ['user:read'], 'test-client-id');

        $jti = $this->jwtService->extractJti($token);

        $this->assertNotEmpty($jti);
        $this->assertIsString($jti);
    }

    public function test_checks_if_token_is_revoked(): void
    {
        $jti = 'test-jti-12345';

        // Mock exists to return false then true on consecutive calls
        Redis::shouldReceive('exists')
            ->with("revoked:{$jti}")
            ->twice()
            ->andReturn(false, true);

        // First check - token not revoked
        $this->assertFalse($this->jwtService->isRevoked($jti));

        // Add to revoked tokens
        Redis::shouldReceive('setex')
            ->with("revoked:{$jti}", 3600, true)
            ->once();
        $this->jwtService->revoke($jti, 3600);

        // Second check - token is revoked
        $this->assertTrue($this->jwtService->isRevoked($jti));
    }

    public function test_token_includes_issuer_and_audience(): void
    {
        $user = User::factory()->create();
        $token = $this->jwtService->sign($user, ['user:read'], 'test-client-id');

        // Mock token as not revoked for verification
        $this->mockTokenNotRevoked();

        $payload = $this->jwtService->verify($token);

        $this->assertEquals(config('identity-gateway.jwt.issuer'), $payload->iss);
        $this->assertEquals(config('identity-gateway.jwt.audience'), $payload->aud);
    }

    public function test_token_includes_timestamps(): void
    {
        $user = User::factory()->create();
        $before = time();

        $token = $this->jwtService->sign($user, ['user:read'], 'test-client-id');

        $after = time();

        // Mock token as not revoked for verification
        $this->mockTokenNotRevoked();

        $payload = $this->jwtService->verify($token);

        $this->assertGreaterThanOrEqual($before, $payload->iat);
        $this->assertLessThanOrEqual($after, $payload->iat);
        $this->assertGreaterThan($payload->iat, $payload->exp);
    }

    public function test_verify_throws_exception_for_invalid_jwt_format(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid JWT format');

        $this->jwtService->verify('invalid-token');
    }

    public function test_verify_throws_exception_for_missing_kid(): void
    {
        // Create a JWT with 3 parts but missing kid in header
        $header = json_encode(['alg' => 'RS256']); // No kid!
        $payload = json_encode(['sub' => '123', 'exp' => time() + 3600]);
        $signature = 'dummysignature';

        $headerB64 = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
        $payloadB64 = rtrim(strtr(base64_encode($payload), '+/', '-_'), '=');
        $token = "{$headerB64}.{$payloadB64}.{$signature}";

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Missing or invalid kid in JWT header');

        $this->jwtService->verify($token);
    }
}
