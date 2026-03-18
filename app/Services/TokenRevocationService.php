<?php

namespace App\Services;

use Defuse\Crypto\Crypto;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\RefreshToken;
use Laravel\Passport\Token;
use Throwable;

class TokenRevocationService
{
    public function __construct(
        private JwtService $jwtService
    ) {}

    /**
     * Revoke a token according to RFC 7009
     *
     * @param  string  $token  The token to revoke
     * @param  string  $tokenTypeHint  Token type hint (access_token or refresh_token)
     * @param  string|null  $requestingClientId  The authenticated client performing the revocation
     */
    public function revoke(string $token, string $tokenTypeHint = 'access_token', ?string $requestingClientId = null): bool
    {
        try {
            if ($tokenTypeHint === 'refresh_token') {
                return $this->revokeRefreshToken($token, $requestingClientId);
            }

            return $this->revokeAccessToken($token, $requestingClientId);
        } catch (Throwable $e) {
            // RFC 7009: The authorization server responds with HTTP status code 200
            // whether or not the token was actually revoked
            return true;
        }
    }

    private function revokeAccessToken(string $token, ?string $requestingClientId = null): bool
    {
        try {
            // Verify the JWT signature and decode claims
            $payload = $this->jwtService->verify($token);

            // Enforce cross-client authorization: a client can only revoke its own tokens
            $tokenClientId = $payload->client_id ?? null;
            if ($requestingClientId && $tokenClientId && $requestingClientId !== $tokenClientId) {
                // RFC 7009: return success even if we don't actually revoke
                return true;
            }

            $jti = $payload->jti ?? null;
            $exp = $payload->exp ?? 0;

            if (! $jti) {
                return false;
            }

            // Add to Redis revocation blacklist with TTL matching token expiry
            $ttl = max(0, $exp - time());
            if ($ttl > 0) {
                Redis::setex('revoked:'.$jti, $ttl, true);
            }

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    private function revokeRefreshToken(string $token, ?string $requestingClientId = null): bool
    {
        try {
            // Decrypt the refresh token to extract the payload
            // League OAuth2 encrypts refresh tokens with Defuse using APP_KEY
            $encryptionKey = app('encrypter')->getKey();
            $decrypted = Crypto::decryptWithPassword($token, $encryptionKey);
            $payload = json_decode($decrypted);

            if (! $payload || ! isset($payload->refresh_token_id)) {
                return true;
            }

            $refreshToken = RefreshToken::find($payload->refresh_token_id);

            if (! $refreshToken) {
                // Token not found, but RFC 7009 requires success response
                return true;
            }

            // Enforce cross-client authorization
            if ($refreshToken->access_token_id) {
                $accessToken = Token::find($refreshToken->access_token_id);
                if ($accessToken && $requestingClientId && $accessToken->client_id !== $requestingClientId) {
                    // RFC 7009: return success even if we don't actually revoke
                    return true;
                }

                // Also revoke the associated access token
                if ($accessToken) {
                    $accessToken->revoke();
                }
            }

            // Delete the refresh token
            $refreshToken->delete();

            return true;
        } catch (Throwable $e) {
            return false;
        }
    }
}
