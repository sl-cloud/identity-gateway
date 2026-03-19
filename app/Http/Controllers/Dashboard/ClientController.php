<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class ClientController extends Controller
{
    public function __construct(
        protected ClientRepository $clients,
        protected AuditService $auditService
    ) {}

    /**
     * List all OAuth clients for the authenticated user.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $clients = Client::where('user_id', $user->id)
            ->where('revoked', false)
            ->get()
            ->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'redirect' => $client->redirect,
                'secret' => $client->secret ? '********' : null,
                'is_confidential' => $client->secret !== null,
                'personal_access_client' => $client->personal_access_client,
                'password_client' => $client->password_client,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
            ]);

        return Inertia::render('Dashboard/Clients/Index', [
            'clients' => $clients,
        ]);
    }

    /**
     * Show the form for creating a new OAuth client.
     */
    public function create(Request $request)
    {
        return Inertia::render('Dashboard/Clients/Create');
    }

    /**
     * Store a new OAuth client.
     */
    public function store(Request $request)
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', 'url'],
            'confidential' => ['required', 'boolean'],
        ]);

        // Create the client using Passport's ClientRepository
        $client = $this->clients->create(
            $user->id,
            $validated['name'],
            $validated['redirect'],
            null, // provider
            $validated['confidential'],
            false, // personal_access_client
            false // password_client
        );

        // Log the creation
        $this->auditService->logClientCreated(
            $user,
            $client->id,
            $client->name,
            $validated['confidential'],
            $request
        );

        return redirect()
            ->route('dashboard.clients.index')
            ->with('success', 'OAuth client created successfully.');
    }

    /**
     * Show a specific OAuth client.
     */
    public function show(Request $request, string $clientId)
    {
        $user = $request->user();

        $client = Client::where('id', $clientId)
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->firstOrFail();

        // Get active tokens for this client
        $tokens = $client->tokens()
            ->where('revoked', false)
            ->where('expires_at', '>', now())
            ->with('user')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'scopes' => $token->scopes,
                'expires_at' => $token->expires_at,
                'created_at' => $token->created_at,
                'user' => $token->user ? [
                    'id' => $token->user->id,
                    'name' => $token->user->name,
                    'email' => $token->user->email,
                ] : null,
            ]);

        return Inertia::render('Dashboard/Clients/Show', [
            'client' => [
                'id' => $client->id,
                'name' => $client->name,
                'redirect' => $client->redirect,
                'secret' => $client->secret ? '********' : null,
                'is_confidential' => $client->secret !== null,
                'created_at' => $client->created_at,
                'updated_at' => $client->updated_at,
            ],
            'tokens' => $tokens,
        ]);
    }

    /**
     * Update an OAuth client.
     */
    public function update(Request $request, string $clientId)
    {
        $user = $request->user();

        $client = Client::where('id', $clientId)
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->firstOrFail();

        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'redirect' => ['sometimes', 'required', 'url'],
        ]);

        // Update the client
        if (isset($validated['name'])) {
            $client->name = $validated['name'];
        }
        if (isset($validated['redirect'])) {
            $client->redirect = $validated['redirect'];
        }
        $client->save();

        $this->auditService->log(
            AuditAction::CLIENT_UPDATED,
            $user,
            'oauth_client',
            $client->id,
            [
                'client_name' => $client->name,
                'updated_fields' => array_keys($validated),
            ],
            $request
        );

        return redirect()
            ->route('dashboard.clients.index')
            ->with('success', 'OAuth client updated successfully.');
    }

    /**
     * Revoke an OAuth client.
     */
    public function destroy(Request $request, string $clientId)
    {
        $user = $request->user();

        $client = Client::where('id', $clientId)
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->firstOrFail();

        $clientName = $client->name;

        // Revoke the client using Passport's method
        $client->revoked = true;
        $client->save();

        // Revoke all tokens for this client and log each
        $activeTokens = $client->tokens()->where('revoked', false)->get();
        $client->tokens()->update(['revoked' => true]);

        foreach ($activeTokens as $token) {
            $jti = $token->jwt_id ?? $token->id;
            $this->auditService->logTokenRevoked(
                $user,
                (string) $jti,
                $client->id,
                $request
            );
        }

        // Log the client revocation
        $this->auditService->logClientRevoked(
            $user,
            $client->id,
            $clientName,
            $request
        );

        return redirect()
            ->route('dashboard.clients.index')
            ->with('success', 'OAuth client revoked successfully.');
    }
}
