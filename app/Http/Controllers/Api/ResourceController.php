<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Resource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ResourceController extends Controller
{
    /**
     * List all resources for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $resources = Resource::where('user_id', $user->id)
            ->paginate(20);

        return response()->json($resources);
    }

    /**
     * Create a new resource.
     *
     * @throws ValidationException
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);

        $user = $request->user();

        $resource = Resource::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json($resource, 201);
    }

    /**
     * Get a specific resource.
     */
    public function show(Request $request, Resource $resource): JsonResponse
    {
        $user = $request->user();

        if ($resource->user_id !== $user->id) {
            return response()->json([
                'error' => 'access_denied',
                'error_description' => 'You do not have permission to view this resource',
                'status' => 403,
            ], 403);
        }

        return response()->json($resource);
    }

    /**
     * Update a resource.
     *
     * @throws ValidationException
     */
    public function update(Request $request, Resource $resource): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($resource->user_id !== $user->id) {
            return response()->json([
                'error' => 'access_denied',
                'error_description' => 'You do not have permission to update this resource',
                'status' => 403,
            ], 403);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'metadata' => ['nullable', 'array'],
        ]);

        $resource->update($validated);

        return response()->json($resource);
    }

    /**
     * Delete a resource.
     */
    public function destroy(Request $request, Resource $resource): JsonResponse
    {
        $user = $request->user();

        // Check ownership
        if ($resource->user_id !== $user->id) {
            return response()->json([
                'error' => 'access_denied',
                'error_description' => 'You do not have permission to delete this resource',
                'status' => 403,
            ], 403);
        }

        $resource->delete();

        return response()->json(null, 204);
    }
}
