<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class OpenIdConfigController extends Controller
{
    /**
     * Return OpenID Connect Discovery document.
     */
    public function __invoke(): JsonResponse
    {
        $baseUrl = config('app.url');

        return response()->json([
            'issuer' => config('identity-gateway.jwt.issuer'),
            'authorization_endpoint' => $baseUrl.'/oauth/authorize',
            'token_endpoint' => $baseUrl.'/oauth/token',
            'userinfo_endpoint' => $baseUrl.'/api/v1/me',
            'jwks_uri' => $baseUrl.'/.well-known/jwks.json',
            'introspection_endpoint' => $baseUrl.'/oauth/introspect',
            'revocation_endpoint' => $baseUrl.'/oauth/revoke',
            'scopes_supported' => [
                'openid',
                'user:read',
                'users:read',
                'resources:read',
                'resources:write',
            ],
            'response_types_supported' => [
                'code',
                'token',
            ],
            'grant_types_supported' => [
                'authorization_code',
                'client_credentials',
                'refresh_token',
            ],
            'subject_types_supported' => [
                'public',
            ],
            'id_token_signing_alg_values_supported' => [
                'RS256',
            ],
            'token_endpoint_auth_methods_supported' => [
                'client_secret_basic',
                'client_secret_post',
            ],
            'code_challenge_methods_supported' => [
                'S256',
                'plain',
            ],
        ]);
    }
}
