<?php

namespace App\Http\Middleware;

use App\Services\JwtService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateJwtSignature
{
    public function __construct(
        protected JwtService $jwtService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (! $token) {
            return $next($request);
        }

        try {
            $payload = $this->jwtService->verify($token);

            // Store the validated payload in the request for later use
            $request->attributes->set('auth_token_payload', $payload);

            return $next($request);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => 'Invalid token format: '.$e->getMessage(),
                'status' => 401,
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'invalid_token',
                'error_description' => $e->getMessage(),
                'status' => 401,
            ], 401);
        }
    }

    /**
     * Extract the Bearer token from the request.
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        return $request->bearerToken();
    }
}
