<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\IntrospectTokenRequest;
use App\Services\TokenIntrospectionService;
use Illuminate\Http\JsonResponse;

class IntrospectionController extends Controller
{
    public function __construct(
        private TokenIntrospectionService $introspectionService
    ) {}

    /**
     * Introspect a token (RFC 7662)
     */
    public function introspect(IntrospectTokenRequest $request): JsonResponse
    {
        $token = $request->input('token');
        $client = $request->attributes->get('oauth_client');
        $requestingClientId = $client?->id;
        $result = $this->introspectionService->introspect($token, $requestingClientId);

        return response()->json($result);
    }
}
