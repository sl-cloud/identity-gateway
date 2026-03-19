<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ValidateJwtSignature;
use App\Services\JwtService;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

class ValidateJwtSignatureTest extends TestCase
{
    private JwtService|Mockery\MockInterface $jwtService;

    private ValidateJwtSignature $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtService = Mockery::mock(JwtService::class);
        $this->middleware = new ValidateJwtSignature($this->jwtService);
    }

    public function test_passes_through_when_no_authorization_header_is_present(): void
    {
        $request = new Request;

        $response = $this->middleware->handle($request, fn () => response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_passes_through_when_authorization_header_is_not_a_bearer_token(): void
    {
        $request = new Request;
        $request->headers->set('Authorization', 'Basic dXNlcjpwYXNz');

        $response = $this->middleware->handle($request, fn () => response('OK'));

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_passes_request_through_when_valid_jwt_is_provided(): void
    {
        $payload = (object) [
            'sub' => '123',
            'scopes' => ['user:read'],
            'exp' => time() + 3600,
        ];

        $this->jwtService->shouldReceive('verify')
            ->with('valid-token')
            ->once()
            ->andReturn($payload);

        $request = new Request;
        $request->headers->set('Authorization', 'Bearer valid-token');

        $response = $this->middleware->handle($request, function ($req) {
            return response('OK');
        });

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_stores_jwt_payload_in_request_attributes(): void
    {
        $payload = (object) [
            'sub' => '123',
            'scopes' => ['user:read'],
            'exp' => time() + 3600,
        ];

        $this->jwtService->shouldReceive('verify')
            ->with('valid-token')
            ->once()
            ->andReturn($payload);

        $request = new Request;
        $request->headers->set('Authorization', 'Bearer valid-token');

        $this->middleware->handle($request, function ($req) use ($payload) {
            $this->assertSame($payload, $req->attributes->get('auth_token_payload'));

            return response('OK');
        });
    }

    public function test_returns_401_when_jwt_verification_fails(): void
    {
        $this->jwtService->shouldReceive('verify')
            ->with('invalid-token')
            ->once()
            ->andThrow(new \Exception('Token expired'));

        $request = new Request;
        $request->headers->set('Authorization', 'Bearer invalid-token');

        $response = $this->middleware->handle($request, fn () => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('invalid_token', $response->getData(true)['error']);
        $this->assertStringContainsString('Token expired', $response->getData(true)['error_description']);
        $this->assertEquals(401, $response->getData(true)['status']);
    }

    public function test_handles_invalid_argument_exception_separately(): void
    {
        $this->jwtService->shouldReceive('verify')
            ->with('malformed-token')
            ->once()
            ->andThrow(new \InvalidArgumentException('Malformed JWT'));

        $request = new Request;
        $request->headers->set('Authorization', 'Bearer malformed-token');

        $response = $this->middleware->handle($request, fn () => response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('invalid_token', $response->getData(true)['error']);
        $this->assertStringContainsString('Invalid token format', $response->getData(true)['error_description']);
        $this->assertEquals(401, $response->getData(true)['status']);
    }
}
