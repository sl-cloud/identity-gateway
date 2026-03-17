<?php

namespace App\Services;

use App\Models\User;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;

class JwtService
{
    public function __construct(
        protected SigningKeyService $signingKeyService
    ) {}

    /**
     * Sign a JWT token for the given user or client (client credentials).
     */
    public function sign(?User $user, array $scopes, string $clientId): string
    {
        $signingKey = $this->signingKeyService->ensureActiveKey();

        $now = time();
        $ttl = config('identity-gateway.jwt.access_token_ttl', 900);
        $issuer = config('identity-gateway.jwt.issuer');
        $audience = config('identity-gateway.jwt.audience');

        $payload = [
            'iss' => $issuer,
            'aud' => $audience,
            'sub' => $user ? (string) $user->id : $clientId,
            'exp' => $now + $ttl,
            'iat' => $now,
            'jti' => Str::uuid()->toString(),
            'scopes' => $scopes,
            'client_id' => $clientId,
        ];

        // Add user-specific claims only if user exists
        if ($user) {
            $payload['name'] = $user->name;
            $payload['email'] = $user->email;

            // Add roles if user has any
            if (method_exists($user, 'getRoleNames')) {
                $payload['roles'] = $user->getRoleNames()->toArray();
            }
        }

        // Sign the JWT with the private key
        return JWT::encode(
            $payload,
            $signingKey->private_key,
            $signingKey->algorithm,
            $signingKey->id
        );
    }

    /**
     * Decode a JWT token and extract claims without signature verification.
     * Used for introspection and debugging purposes.
     */
    public function decode(string $token): ?object
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $header = json_decode(base64_decode(strtr($parts[0], '-_', '+/'), true));
            if (! $header) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/'), true));
            if (! $payload) {
                return null;
            }

            return (object) [
                'header' => $header,
                'payload' => $payload,
            ];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Verify and validate a JWT token.
     *
     * @throws \InvalidArgumentException|\Exception
     */
    public function verify(string $token): object
    {
        // Validate JWT format (must have 3 parts separated by dots)
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new \InvalidArgumentException('Invalid JWT format: token must have 3 parts');
        }

        // Extract header to get kid
        $headerEncoded = $parts[0];
        $header = json_decode(base64_decode(strtr($headerEncoded, '-_', '+/'), true));

        if (! $header || ! isset($header->kid)) {
            throw new \Exception('Missing or invalid kid in JWT header');
        }

        // Get the signing key
        $signingKey = $this->signingKeyService->getKeyById($header->kid);

        if (! $signingKey || ! $signingKey->isValid()) {
            throw new \Exception('Invalid signing key');
        }

        // Verify the signature and decode
        try {
            $payload = JWT::decode(
                $token,
                new Key($signingKey->public_key, $signingKey->algorithm)
            );
        } catch (\Exception $e) {
            throw new \Exception('Invalid token signature: '.$e->getMessage());
        }

        // Validate issuer and audience
        $expectedIssuer = config('identity-gateway.jwt.issuer');
        $expectedAudience = config('identity-gateway.jwt.audience');

        if ($payload->iss !== $expectedIssuer) {
            throw new \Exception('Invalid issuer');
        }

        if ($payload->aud !== $expectedAudience) {
            throw new \Exception('Invalid audience');
        }

        // Check if token is revoked
        if ($this->isRevoked($payload->jti)) {
            throw new \Exception('Token has been revoked');
        }

        return $payload;
    }

    /**
     * Check if a token is revoked.
     */
    public function isRevoked(string $jti): bool
    {
        return (bool) Redis::exists('revoked:'.$jti);
    }

    /**
     * Revoke a token by its JTI.
     */
    public function revoke(string $jti, int $ttl): void
    {
        // Use individual key with TTL instead of shared set
        // This ensures each JTI expires independently
        Redis::setex('revoked:'.$jti, $ttl, true);
    }

    /**
     * Extract JTI from token without full verification.
     */
    public function extractJti(string $token): ?string
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/'), true));

            return $payload->jti ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
