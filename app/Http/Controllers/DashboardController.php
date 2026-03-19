<?php

namespace App\Http\Controllers;

use App\Models\OAuthConsentApproval;
use App\Models\OAuthScope;
use App\Models\SigningKey;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Laravel\Passport\Client;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        // Get user's OAuth clients
        $clients = Client::where('user_id', $user->id)
            ->where('revoked', false)
            ->get()
            ->map(fn ($client) => [
                'id' => $client->id,
                'name' => $client->name,
                'redirect' => $client->redirect,
                'is_confidential' => $client->secret !== null,
                'created_at' => $client->created_at,
            ]);

        // Get user's consent approvals
        $approvals = OAuthConsentApproval::where('user_id', $user->id)
            ->with('client')
            ->get()
            ->map(fn ($approval) => [
                'id' => $approval->id,
                'client_name' => $approval->client?->name ?? 'Unknown',
                'scopes' => $approval->scopes,
                'approved_at' => $approval->approved_at,
            ]);

        // Get active signing keys info
        $activeKeys = SigningKey::where('status', 'active')->count();
        $retiredKeys = SigningKey::where('status', 'retired')->count();

        // Get available scopes
        $scopes = OAuthScope::all()->map(fn ($scope) => [
            'id' => $scope->id,
            'description' => $scope->description,
            'is_default' => $scope->is_default,
        ]);

        return Inertia::render('Dashboard/Index', [
            'auth' => [
                'user' => $user,
            ],
            'stats' => [
                'clients_count' => $clients->count(),
                'approvals_count' => $approvals->count(),
                'active_keys' => $activeKeys,
                'retired_keys' => $retiredKeys,
            ],
            'clients' => $clients,
            'approvals' => $approvals,
            'scopes' => $scopes,
            'endpoints' => [
                'issuer' => config('app.url'),
                'authorization' => route('oauth.authorize'),
                'token' => route('oauth.token'),
                'introspection' => route('oauth.introspect'),
                'revocation' => route('oauth.revoke'),
                'jwks' => route('jwks'),
                'openid_config' => route('openid-config'),
            ],
        ]);
    }
}
