<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OAuthScope;
use Illuminate\Http\JsonResponse;

class ScopeController extends Controller
{
    /**
     * List all available OAuth scopes.
     */
    public function index(): JsonResponse
    {
        $scopes = OAuthScope::all()->map(function (OAuthScope $scope) {
            return [
                'id' => $scope->id,
                'description' => $scope->description,
                'is_default' => $scope->is_default,
            ];
        });

        return response()->json([
            'scopes' => $scopes,
        ]);
    }
}
