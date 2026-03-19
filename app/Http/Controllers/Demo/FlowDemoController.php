<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Demo\Concerns\HasDemoData;
use App\Models\OAuthScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FlowDemoController extends Controller
{
    use HasDemoData;

    public function index(): Response
    {
        return Inertia::render('Demo/Index', [
            'endpoints' => $this->endpoints(),
        ]);
    }

    public function authCode(): Response
    {
        return Inertia::render('Demo/AuthCodeFlow', [
            'endpoints' => $this->endpoints(),
            'clients' => $this->demoClients(),
        ]);
    }

    public function pkce(): Response
    {
        return Inertia::render('Demo/PkceFlow', [
            'endpoints' => $this->endpoints(),
            'clients' => $this->demoClients(),
        ]);
    }

    public function clientCredentials(): Response
    {
        return Inertia::render('Demo/ClientCredentialsFlow', [
            'endpoints' => $this->endpoints(),
            'clients' => $this->demoClients(),
        ]);
    }

    public function introspection(): Response
    {
        return Inertia::render('Demo/IntrospectionDemo', [
            'endpoints' => $this->endpoints(),
            'clients' => $this->demoClients(),
        ]);
    }

    public function revocation(): Response
    {
        return Inertia::render('Demo/RevocationDemo', [
            'endpoints' => $this->endpoints(),
            'clients' => $this->demoClients(),
            'scopes' => OAuthScope::query()
                ->orderBy('id')
                ->pluck('id')
                ->values()
                ->all(),
        ]);
    }

    public function callback(Request $request): RedirectResponse
    {
        $query = http_build_query(array_filter([
            'code' => $request->query('code'),
            'state' => $request->query('state'),
            'error' => $request->query('error'),
            'error_description' => $request->query('error_description'),
        ], fn ($value) => filled($value)));

        return redirect('/demo/playground'.($query ? '?'.$query : ''));
    }
}
