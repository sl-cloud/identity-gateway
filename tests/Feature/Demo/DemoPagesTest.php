<?php

namespace Tests\Feature\Demo;

use Database\Seeders\DemoUserSeeder;
use Database\Seeders\OAuthScopeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia;
use Tests\TestCase;

class DemoPagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(OAuthScopeSeeder::class);
        $this->seed(DemoUserSeeder::class);
    }

    public function test_demo_landing_page_renders(): void
    {
        $this->get('/demo')
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Demo/Index', false)
                ->has('endpoints')
            );
    }

    public function test_demo_playground_page_renders_with_clients_and_scopes(): void
    {
        $this->get('/demo/playground')
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Demo/Playground', false)
                ->has('clients')
                ->has('scopes')
                ->has('endpoints')
            );
    }

    public function test_demo_jwt_inspector_page_renders(): void
    {
        $this->get('/demo/jwt-inspector')
            ->assertStatus(200)
            ->assertInertia(fn (AssertableInertia $page) => $page
                ->component('Demo/JwtInspector', false)
                ->has('issuer')
                ->has('audience')
                ->has('jwks_endpoint')
            );
    }

    public function test_demo_flow_walkthrough_pages_render(): void
    {
        $routes = [
            '/demo/flows/auth-code' => 'Demo/AuthCodeFlow',
            '/demo/flows/pkce' => 'Demo/PkceFlow',
            '/demo/flows/client-credentials' => 'Demo/ClientCredentialsFlow',
            '/demo/introspection' => 'Demo/IntrospectionDemo',
            '/demo/revocation' => 'Demo/RevocationDemo',
        ];

        foreach ($routes as $uri => $component) {
            $this->get($uri)
                ->assertStatus(200)
                ->assertInertia(fn (AssertableInertia $page) => $page->component($component, false));
        }
    }

    public function test_demo_jwks_endpoint_returns_key_set_shape(): void
    {
        $this->getJson('/demo/jwks')
            ->assertStatus(200)
            ->assertJsonStructure(['keys']);
    }

    public function test_demo_callback_redirects_back_to_playground_with_query_values(): void
    {
        $this->get('/demo/callback?code=abc123&state=xyz')
            ->assertRedirect('/demo/playground?code=abc123&state=xyz');
    }
}
