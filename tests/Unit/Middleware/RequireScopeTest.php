<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\RequireScope;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class RequireScopeTest extends TestCase
{
    use RefreshDatabase;

    private RequireScope $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->middleware = new RequireScope;
    }

    public function test_returns_401_when_no_user_is_authenticated(): void
    {
        $request = new Request;

        $response = $this->middleware->handle($request, fn () => response('OK'), 'user:read');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertEquals('unauthorized', $response->getData(true)['error']);
    }

    public function test_passes_request_when_jwt_has_required_scope(): void
    {
        $request = new Request;
        $request->attributes->set('auth_token_payload', (object) ['scopes' => ['user:read', 'resources:read']]);

        $response = $this->middleware->handle($request, fn () => response('OK'), 'user:read');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_returns_403_when_jwt_lacks_required_scope(): void
    {
        $request = new Request;
        $request->attributes->set('auth_token_payload', (object) ['scopes' => ['user:read']]);

        $response = $this->middleware->handle($request, fn () => response('OK'), 'resources:write');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('insufficient_scope', $response->getData(true)['error']);
        $this->assertEquals('resources:write', $response->getData(true)['scope']);
    }

    public function test_passes_request_when_api_key_has_required_scope(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->create([
            'user_id' => $user->id,
            'scopes' => ['user:read', 'resources:read'],
        ]);

        $request = new Request;
        $request->attributes->set('auth_api_key', $apiKey);

        $response = $this->middleware->handle($request, fn () => response('OK'), 'user:read');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_returns_403_when_api_key_lacks_required_scope(): void
    {
        $user = User::factory()->create();
        $apiKey = ApiKey::factory()->create([
            'user_id' => $user->id,
            'scopes' => ['user:read'],
        ]);

        $request = new Request;
        $request->attributes->set('auth_api_key', $apiKey);

        $response = $this->middleware->handle($request, fn () => response('OK'), 'resources:write');

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertEquals('insufficient_scope', $response->getData(true)['error']);
    }

    public function test_checks_multiple_required_scopes(): void
    {
        $request = new Request;
        $request->attributes->set('auth_token_payload', (object) ['scopes' => ['user:read', 'resources:read']]);

        $response = $this->middleware->handle($request, fn () => response('OK'), 'user:read', 'resources:read');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_returns_403_when_missing_one_of_multiple_required_scopes(): void
    {
        $request = new Request;
        $request->attributes->set('auth_token_payload', (object) ['scopes' => ['user:read']]);

        $response = $this->middleware->handle(
            $request,
            fn () => response('OK'),
            'user:read',
            'resources:read'
        );

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_uses_jwt_payload_from_request_attributes_when_available(): void
    {
        $request = new Request;
        $request->attributes->set('auth_token_payload', (object) ['scopes' => ['user:read']]);

        $response = $this->middleware->handle($request, fn () => response('OK'), 'user:read');

        $this->assertEquals('OK', $response->getContent());
    }

    public function test_returns_403_when_jwt_payload_from_attributes_lacks_scope(): void
    {
        $request = new Request;
        $request->attributes->set('auth_token_payload', (object) ['scopes' => ['user:read']]);

        $response = $this->middleware->handle($request, fn () => response('OK'), 'resources:write');

        $this->assertEquals(403, $response->getStatusCode());
    }
}
