<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireScope
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     * @param  array<string>  $scopes
     */
    public function handle(Request $request, Closure $next, string ...$scopes): Response
    {
        $tokenPayload = $request->attributes->get('auth_token_payload')
            ?? $request->attributes->get('jwt_payload');
        if ($tokenPayload && isset($tokenPayload->scopes)) {
            $tokenScopes = (array) $tokenPayload->scopes;
            if (! $this->hasRequiredScopes($scopes, $tokenScopes)) {
                return response()->json([
                    'error' => 'insufficient_scope',
                    'error_description' => 'The token does not have the required scope(s): '.implode(', ', $scopes),
                    'scope' => implode(' ', $scopes),
                    'status' => 403,
                ], 403);
            }

            return $next($request);
        }

        $apiKey = $request->attributes->get('auth_api_key');
        if ($apiKey instanceof ApiKey) {
            $apiKeyScopes = (array) ($apiKey->scopes ?? []);

            if (! $this->hasRequiredScopes($scopes, $apiKeyScopes)) {
                return response()->json([
                    'error' => 'insufficient_scope',
                    'error_description' => 'The API key does not have the required scope(s): '.implode(', ', $scopes),
                    'scope' => implode(' ', $scopes),
                    'status' => 403,
                ], 403);
            }

            return $next($request);
        }

        // No valid authentication found
        return response()->json([
            'error' => 'unauthorized',
            'error_description' => 'Authentication required to access this resource',
            'status' => 401,
        ], 401);
    }

    /**
     * Check if the provided scopes contain all required scopes.
     *
     * @param  array<string>  $requiredScopes
     * @param  array<string>|mixed  $providedScopes
     */
    protected function hasRequiredScopes(array $requiredScopes, mixed $providedScopes): bool
    {
        if (! is_array($providedScopes)) {
            return false;
        }

        foreach ($requiredScopes as $required) {
            if (! in_array($required, $providedScopes, true)) {
                return false;
            }
        }

        return true;
    }
}
