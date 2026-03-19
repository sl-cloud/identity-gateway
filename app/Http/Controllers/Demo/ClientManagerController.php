<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Demo\Concerns\HasDemoData;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;

class ClientManagerController extends Controller
{
    use HasDemoData;

    public function __construct(
        protected ClientRepository $clients
    ) {}

    public function index(): Response
    {
        return Inertia::render('Demo/ClientManager', [
            'clients' => $this->allDemoClients(),
            'endpoints' => $this->endpoints(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'redirect' => ['required', 'url'],
            'confidential' => ['required', 'boolean'],
        ]);

        $name = 'Demo '.$validated['name'];

        $client = $this->clients->create(
            null,
            $name,
            $validated['redirect'],
            null,
            false,
            false,
            $validated['confidential']
        );

        return redirect()
            ->route('demo.clients')
            ->with('newClient', [
                'id' => (string) $client->id,
                'name' => $client->name,
                'secret' => $client->secret,
            ]);
    }

    public function destroy(string $clientId): RedirectResponse
    {
        $client = Client::where('id', $clientId)
            ->where('name', 'like', 'Demo%')
            ->where('revoked', false)
            ->firstOrFail();

        $client->tokens()->update(['revoked' => true]);
        $client->update(['revoked' => true]);

        return redirect()
            ->route('demo.clients')
            ->with('success', "Client \"{$client->name}\" revoked.");
    }

    /**
     * All demo clients with secrets visible (demo env only).
     */
    protected function allDemoClients(): array
    {
        return Client::query()
            ->where('revoked', false)
            ->where('name', 'like', 'Demo%')
            ->orderByDesc('created_at')
            ->get(['id', 'name', 'secret', 'redirect', 'created_at'])
            ->map(fn (Client $client) => [
                'id' => (string) $client->id,
                'name' => $client->name,
                'secret' => $client->secret,
                'redirect' => $client->redirect,
                'type' => $client->secret ? 'confidential' : 'public',
                'created_at' => $client->created_at->toIso8601String(),
            ])
            ->values()
            ->all();
    }
}
