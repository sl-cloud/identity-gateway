<?php

namespace Tests\Unit\Guards;

use App\Guards\JwtGuard;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class JwtGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_null_when_no_authorization_header(): void
    {
        $jwtService = Mockery::mock(JwtService::class);
        $request = new Request;
        $guard = new JwtGuard($jwtService, $request);

        $this->assertNull($guard->user());
        $this->assertFalse($guard->check());
        $this->assertTrue($guard->guest());
    }

    public function test_returns_null_when_authorization_is_not_bearer(): void
    {
        $jwtService = Mockery::mock(JwtService::class);
        $request = new Request;
        $request->headers->set('Authorization', 'Basic dXNlcjpwYXNz');
        $guard = new JwtGuard($jwtService, $request);

        $this->assertNull($guard->user());
    }

    public function test_returns_user_when_valid_jwt_provided(): void
    {
        $user = User::factory()->create([
            'name' => 'Test',
            'email' => 'test@example.com',
        ]);

        $payload = (object) [
            'sub' => (string) $user->id,
            'scopes' => ['user:read'],
            'client_id' => 'test-client',
        ];

        $jwtService = Mockery::mock(JwtService::class);
        $jwtService->shouldReceive('verify')
            ->with('valid-token')
            ->once()
            ->andReturn($payload);

        $request = new Request;
        $request->headers->set('Authorization', 'Bearer valid-token');
        $guard = new JwtGuard($jwtService, $request);

        $result = $guard->user();

        $this->assertNotNull($result);
        $this->assertEquals($user->id, $result->id);
    }

    public function test_returns_null_when_jwt_verification_fails(): void
    {
        $jwtService = Mockery::mock(JwtService::class);
        $jwtService->shouldReceive('verify')
            ->with('invalid-token')
            ->andThrow(new \Exception('Invalid token'));

        $request = new Request;
        $request->headers->set('Authorization', 'Bearer invalid-token');
        $guard = new JwtGuard($jwtService, $request);

        $this->assertNull($guard->user());
        $this->assertFalse($guard->check());
    }

    public function test_returns_null_for_client_credentials_tokens(): void
    {
        $payload = (object) [
            'sub' => 'client-id',
            'scopes' => ['resources:read'],
            'client_id' => 'client-id',
        ];

        $jwtService = Mockery::mock(JwtService::class);
        $jwtService->shouldReceive('verify')
            ->with('client-token')
            ->once()
            ->andReturn($payload);

        $request = new Request;
        $request->headers->set('Authorization', 'Bearer client-token');
        $guard = new JwtGuard($jwtService, $request);

        $this->assertNull($guard->user());
    }

    public function test_returns_null_after_logout(): void
    {
        $jwtService = Mockery::mock(JwtService::class);
        $request = new Request;
        $guard = new JwtGuard($jwtService, $request);

        $guard->logout();

        $this->assertNull($guard->user());
        $this->assertFalse($guard->check());
    }

    public function test_validate_returns_false(): void
    {
        $jwtService = Mockery::mock(JwtService::class);
        $request = new Request;
        $guard = new JwtGuard($jwtService, $request);

        // JWT guard doesn't support credential validation
        $this->assertFalse($guard->validate(['email' => 'test@example.com', 'password' => 'password']));
    }
}
