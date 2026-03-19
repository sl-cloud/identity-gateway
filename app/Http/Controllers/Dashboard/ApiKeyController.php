<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ApiKey;
use App\Services\ApiKeyService;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApiKeyController extends Controller
{
    public function __construct(
        protected ApiKeyService $apiKeyService,
        protected AuditService $auditService
    ) {}

    /**
     * List all API keys for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $apiKeys = ApiKey::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn ($key) => [
                'id' => $key->id,
                'name' => $key->name,
                'prefix' => $key->key_prefix,
                'scopes' => $key->scopes,
                'is_active' => $key->isActive(),
                'is_revoked' => $key->isRevoked(),
                'is_expired' => $key->isExpired(),
                'last_used_at' => $key->last_used_at,
                'expires_at' => $key->expires_at,
                'created_at' => $key->created_at,
            ]);

        return Inertia::render('Dashboard/ApiKeys/Index', [
            'apiKeys' => $apiKeys,
        ]);
    }

    /**
     * Generate a new API key.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'scopes' => ['required', 'array'],
            'scopes.*' => ['string'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:365'],
        ]);

        $expiresAt = isset($validated['expires_in_days'])
            ? now()->addDays($validated['expires_in_days'])
            : now()->addYear();

        // Generate the API key
        $apiKey = $this->apiKeyService->generate(
            $user->id,
            $validated['name'],
            $validated['scopes'],
            $expiresAt
        );

        // Log the creation
        $this->auditService->logApiKeyCreated(
            $user,
            $apiKey->id,
            $apiKey->name,
            $validated['scopes'],
            $request
        );

        return redirect()
            ->route('dashboard.api-keys.index')
            ->with([
                'success' => 'API key created successfully. Copy it now - you won\'t see it again!',
                'newApiKey' => $apiKey->plain_key,
            ]);
    }

    /**
     * Revoke an API key.
     */
    public function destroy(Request $request, string $keyId)
    {
        $user = $request->user();

        $apiKey = ApiKey::where('id', $keyId)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $keyName = $apiKey->name;

        // Revoke the key
        $this->apiKeyService->revoke($keyId);

        // Log the revocation
        $this->auditService->logApiKeyRevoked(
            $user,
            $apiKey->id,
            $keyName,
            $request
        );

        return redirect()
            ->route('dashboard.api-keys.index')
            ->with('success', 'API key revoked successfully.');
    }
}
