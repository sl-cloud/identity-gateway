<?php

namespace Tests\Unit\Middleware;

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class ForceJsonResponseTest extends TestCase
{
    public function test_sets_accept_header_to_application_json(): void
    {
        $request = new Request;

        $middleware = new ForceJsonResponse;

        $next = function ($req) {
            $this->assertEquals('application/json', $req->header('Accept'));

            return new Response('OK');
        };

        $middleware->handle($request, $next);
    }

    public function test_passes_through_valid_json_responses(): void
    {
        $request = new Request;

        $middleware = new ForceJsonResponse;

        $next = function ($req) {
            $response = new Response(json_encode(['data' => 'test']));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        };

        $response = $middleware->handle($request, $next);

        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
    }

    public function test_converts_html_error_to_json_for_api_requests(): void
    {
        $request = Request::create('/api/v1/test');

        $middleware = new ForceJsonResponse;

        $next = function ($req) {
            return new Response('<html>Error</html>', 500);
        };

        $response = $middleware->handle($request, $next);

        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));
        $data = json_decode($response->getContent(), true);
        $this->assertEquals('server_error', $data['error']);
        $this->assertEquals(500, $data['status']);
    }

    public function test_returns_appropriate_error_codes(): void
    {
        $testCases = [
            [400, 'invalid_request'],
            [401, 'unauthorized'],
            [403, 'access_denied'],
            [404, 'not_found'],
            [405, 'method_not_allowed'],
            [429, 'rate_limit_exceeded'],
            [500, 'server_error'],
            [503, 'temporarily_unavailable'],
            [418, 'error'], // Unknown status code
        ];

        foreach ($testCases as [$status, $expectedError]) {
            $request = Request::create('/api/v1/test');

            $middleware = new ForceJsonResponse;

            $next = function ($req) use ($status) {
                return new Response('Error', $status);
            };

            $response = $middleware->handle($request, $next);
            $data = json_decode($response->getContent(), true);

            $this->assertEquals($expectedError, $data['error'], "Failed for status $status");
            $this->assertEquals($status, $data['status']);
        }
    }
}
