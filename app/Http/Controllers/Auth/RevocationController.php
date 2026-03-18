<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RevokeTokenRequest;
use App\Services\TokenRevocationService;
use Illuminate\Http\JsonResponse;

class RevocationController extends Controller
{
    public function __construct(
        private TokenRevocationService $revocationService
    ) {}

    /**
     * Revoke a token (RFC 7009)
     */
    public function revoke(RevokeTokenRequest $request): JsonResponse
    {
        $token = $request->input('token');
        $tokenTypeHint = $request->input('token_type_hint', 'access_token');
        $client = $request->attributes->get('oauth_client');
        $requestingClientId = $client?->id;

        $this->revocationService->revoke($token, $tokenTypeHint, $requestingClientId);

        // RFC 7009: Always return 200, regardless of whether token was revoked
        return response()->json(null, 200);
    }
}
