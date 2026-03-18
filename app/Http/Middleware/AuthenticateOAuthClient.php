<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateOAuthClient
{
    public function __construct(
        private ClientRepository $clients
    ) {}

    /**
     * Authenticate the OAuth client via HTTP Basic (RFC 7662/7009).
     */
    public function handle(Request $request, Closure $next): Response
    {
        $clientId = null;
        $clientSecret = null;

        // Try HTTP Basic Authorization header first
        $authorization = $request->header('Authorization');
        if ($authorization && str_starts_with($authorization, 'Basic ')) {
            $decoded = base64_decode(substr($authorization, 6), true);
            if ($decoded && str_contains($decoded, ':')) {
                [$clientId, $clientSecret] = explode(':', $decoded, 2);
            }
        }

        // Fall back to POST body parameters (per RFC 6749 §2.3.1)
        if (! $clientId) {
            $clientId = $request->input('client_id');
            $clientSecret = $request->input('client_secret');
        }

        if (! $clientId || ! $clientSecret) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        $client = $this->clients->findActive($clientId);

        if (! $client || $client->secret === null || ! $this->verifySecret($client->secret, $clientSecret)) {
            return response()->json(['error' => 'invalid_client'], 401);
        }

        // Store the authenticated client on the request for downstream use
        $request->attributes->set('oauth_client', $client);

        return $next($request);
    }

    private function verifySecret(string $storedSecret, string $clientSecret): bool
    {
        if (Passport::$hashesClientSecrets) {
            return password_verify($clientSecret, $storedSecret);
        }

        return hash_equals($storedSecret, $clientSecret);
    }
}
