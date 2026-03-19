<?php

namespace App\Http\Controllers\Demo;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Demo\Concerns\HasDemoData;
use Inertia\Inertia;
use Inertia\Response;

class PlaygroundController extends Controller
{
    use HasDemoData;

    public function index(): Response
    {
        return Inertia::render('Demo/Playground', [
            'clients' => $this->demoClients(),
            'scopes' => $this->demoScopes(),
            'endpoints' => $this->endpoints(),
            'demo_credentials' => [
                'email' => 'demo@identitygateway.test',
                'password' => 'password',
            ],
        ]);
    }
}
