<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Auth\JwksController;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class JwtInspectorController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Demo/JwtInspector', [
            'issuer' => config('identity-gateway.jwt.issuer'),
            'audience' => config('identity-gateway.jwt.audience'),
            'jwks_endpoint' => url('/demo/jwks'),
        ]);
    }

    public function jwks(JwksController $jwksController): JsonResponse
    {
        return $jwksController();
    }
}
