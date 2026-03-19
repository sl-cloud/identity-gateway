<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Get the authenticated user.
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'error' => 'unauthorized',
                'error_description' => 'No authenticated user found',
                'status' => 401,
            ], 401);
        }

        $scopes = [];
        $tokenPayload = $request->attributes->get('auth_token_payload');
        $apiKey = $request->attributes->get('auth_api_key');
        if ($tokenPayload && isset($tokenPayload->scopes)) {
            $scopes = (array) $tokenPayload->scopes;
        } elseif ($apiKey instanceof ApiKey && $apiKey->scopes) {
            $scopes = (array) $apiKey->scopes;
        }

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'scopes' => $scopes,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ]);
    }

    /**
     * List all users (requires users:read scope).
     * Email is only included for the authenticated user's own record.
     */
    public function index(Request $request): JsonResponse
    {
        $authUserId = $request->user()?->id;

        $users = User::select(['id', 'name', 'email', 'created_at', 'updated_at'])
            ->paginate(20);

        $users->getCollection()->transform(function ($user) use ($authUserId) {
            $data = [
                'id' => $user->id,
                'name' => $user->name,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
            ];

            if ($user->id === $authUserId) {
                $data['email'] = $user->email;
            }

            return $data;
        });

        return response()->json($users);
    }

    /**
     * Get a specific user by ID.
     * Email is only included when viewing your own profile.
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];

        if ($request->user()?->id === $user->id) {
            $data['email'] = $user->email;
        }

        return response()->json($data);
    }
}
