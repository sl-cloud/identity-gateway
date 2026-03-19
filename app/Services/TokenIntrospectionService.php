<?php

namespace App\Services;

use Throwable;

class TokenIntrospectionService
{
    public function __construct(
        private JwtService $jwtService
    ) {}

    /**
     * Introspect a token according to RFC 7662
     *
     * @param  string  $token  The token to introspect
     * @param  string|null  $requestingClientId  The authenticated client performing the introspection
     */
    public function introspect(string $token, ?string $requestingClientId = null): array
    {
        try {
            // Decode and verify the token (returns stdClass)
            $payload = $this->jwtService->verify($token);

            // Check if token is in revocation blacklist
            $jti = $payload->jti ?? null;
            if ($jti && $this->jwtService->isRevoked($jti)) {
                return [
                    'active' => false,
                ];
            }

            // Check expiration
            $exp = $payload->exp ?? 0;
            if ($exp < time()) {
                return [
                    'active' => false,
                ];
            }

            // Enforce cross-client authorization: a client can only introspect its own tokens
            $tokenClientId = $payload->client_id ?? null;
            if ($requestingClientId && $tokenClientId && $requestingClientId !== $tokenClientId) {
                return [
                    'active' => false,
                ];
            }

            // Token is valid and active
            return [
                'active' => true,
                'scope' => implode(' ', (array) ($payload->scopes ?? [])),
                'client_id' => $tokenClientId,
                'username' => $payload->email ?? null,
                'token_type' => 'Bearer',
                'exp' => $exp,
                'iat' => $payload->iat ?? null,
                'sub' => $payload->sub ?? null,
                'jti' => $jti,
                'iss' => $payload->iss ?? null,
            ];
        } catch (Throwable $e) {
            // Any error means the token is not active
            return [
                'active' => false,
            ];
        }
    }
}
