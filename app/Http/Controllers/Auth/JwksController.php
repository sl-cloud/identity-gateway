<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\SigningKeyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class JwksController extends Controller
{
    public function __construct(
        protected SigningKeyService $signingKeyService
    ) {}

    /**
     * Return the JSON Web Key Set (JWKS) for token verification.
     */
    public function __invoke(): JsonResponse
    {
        $keys = $this->signingKeyService->getValidationKeys();

        $jwks = [
            'keys' => $keys->map(function ($key) {
                try {
                    return $this->convertToJwk($key);
                } catch (\RuntimeException $e) {
                    Log::error('Failed to convert signing key to JWK', [
                        'key_id' => $key->id,
                        'error' => $e->getMessage(),
                    ]);

                    return null;
                }
            })
                ->filter()
                ->values()
                ->toArray(),
        ];

        return response()->json($jwks);
    }

    /**
     * Convert a SigningKey to JWK format.
     *
     * @throws \RuntimeException If the public key cannot be parsed
     */
    protected function convertToJwk($key): array
    {
        // Parse the public key
        $publicKey = openssl_pkey_get_public($key->public_key);

        if ($publicKey === false) {
            throw new \RuntimeException("Invalid public key format for key {$key->id}");
        }

        $details = openssl_pkey_get_details($publicKey);

        if ($details === false) {
            throw new \RuntimeException("Could not extract key details for key {$key->id}");
        }

        // Verify we have RSA key details
        if (! isset($details['rsa']) || ! isset($details['rsa']['n']) || ! isset($details['rsa']['e'])) {
            throw new \RuntimeException("Invalid RSA key structure for key {$key->id}");
        }

        // Extract RSA components
        $n = base64_encode($details['rsa']['n']);
        $e = base64_encode($details['rsa']['e']);

        // Convert to base64url
        $n = rtrim(strtr($n, '+/', '-_'), '=');
        $e = rtrim(strtr($e, '+/', '-_'), '=');

        return [
            'kty' => 'RSA',
            'use' => 'sig',
            'alg' => $key->algorithm,
            'kid' => $key->id,
            'n' => $n,
            'e' => $e,
        ];
    }
}
