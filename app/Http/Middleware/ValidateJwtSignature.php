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
        } catch (\Exception $e) {
            // Don't reject here — store the error and let the downstream auth
            // guard decide.  This preserves the API-key fallback: if a request
            // carries both an invalid Bearer token and a valid X-Api-Key, the
            // api-key guard still gets a chance to authenticate.
            $request->attributes->set('jwt_validation_error', $e->getMessage());
        }

        return $next($request);
    }

    /**
     * Extract the Bearer token from the request.
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        return $request->bearerToken();
    }
}
