<?php

namespace App\Http\Controllers\Demo\Concerns;

use App\Models\OAuthScope;
use Illuminate\Support\Collection;
use Laravel\Passport\Client;

trait HasDemoData
{
    /**
     * @return array<string, string>
     */
    protected function endpoints(): array
    {
        return [
            'authorization' => url('/oauth/authorize'),
            'token' => url('/oauth/token'),
            'introspection' => url('/oauth/introspect'),
            'revocation' => url('/oauth/revoke'),
            'jwks' => url('/.well-known/jwks.json'),
            'openid_configuration' => url('/.well-known/openid-configuration'),
        ];
    }

    /**
     * Returns demo client data including secrets for the browser-based playground.
     *
     * This is intentional and safe: the DemoEnvironmentOnly middleware restricts
     * all /demo routes to local and testing environments only. These secrets
     * belong to seeded demo clients, not real credentials.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function demoClients(): array
    {
        return Client::query()
            ->where('revoked', false)
            ->where('name', 'like', 'Demo%')
            ->orderBy('name')
            ->get(['id', 'name', 'secret', 'redirect'])
            ->map(fn (Client $client) => [
                'id' => (string) $client->id,
                'name' => $client->name,
                'secret' => $client->secret,
                'redirect' => $client->redirect,
                'type' => $client->secret ? 'confidential' : 'public',
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    protected function demoScopes(): Collection
    {
        return OAuthScope::query()
            ->orderBy('id')
            ->get(['id', 'description', 'is_default'])
            ->map(fn (OAuthScope $scope) => [
                'id' => $scope->id,
                'description' => $scope->description,
                'is_default' => $scope->is_default,
            ])
            ->values();
    }
}
