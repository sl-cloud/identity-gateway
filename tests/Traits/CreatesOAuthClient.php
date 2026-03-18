<?php

namespace Tests\Traits;

use App\Models\OAuthScope;
use App\Models\User;
use Database\Seeders\OAuthScopeSeeder;
use Illuminate\Support\Facades\Redis;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

trait CreatesOAuthClient
{
    protected function setUpOAuth(): void
    {
        $this->seed(OAuthScopeSeeder::class);

        // Re-register scopes with Passport (AuthServiceProvider loaded them before DB was seeded)
        $scopes = OAuthScope::pluck('description', 'id')->toArray();
        $defaults = OAuthScope::default()->pluck('id')->toArray();
        Passport::tokensCan($scopes);
        Passport::setDefaultScope($defaults);

        // Use an in-memory store for Redis to avoid requiring a live Redis connection in tests
        $store = [];
        Redis::shouldReceive('exists')
            ->andReturnUsing(function (string $key) use (&$store) {
                return isset($store[$key]) ? 1 : 0;
            });
        Redis::shouldReceive('setex')
            ->andReturnUsing(function (string $key, int $ttl, $value) use (&$store) {
                $store[$key] = $value;

                return true;
            });
    }

    protected function createConfidentialClient(User $user, string $redirect = 'http://localhost/callback'): Client
    {
        $clientRepository = app(ClientRepository::class);

        return $clientRepository->create(
            $user->id,
            'Test Confidential Client',
            $redirect,
            null,
            false,
            false
        );
    }

    protected function createPublicClient(User $user, string $redirect = 'http://localhost/callback'): Client
    {
        $client = Client::create([
            'user_id' => $user->id,
            'name' => 'Test Public Client',
            'secret' => null,
            'redirect' => $redirect,
            'personal_access_client' => false,
            'password_client' => false,
            'revoked' => false,
        ]);

        return $client;
    }

    protected function getAuthorizationCode(Client $client, User $user, array $scopes = ['user:read']): string
    {
        $response = $this->actingAs($user)
            ->get('/oauth/authorize?'.http_build_query([
                'client_id' => $client->id,
                'redirect_uri' => $client->redirect,
                'response_type' => 'code',
                'scope' => implode(' ', $scopes),
                'state' => 'test-state',
            ]));

        // Extract auth_request_key from session
        $authRequestKey = $this->extractAuthRequestKey($response);

        // Approve the consent
        $response = $this->actingAs($user)
            ->post('/auth/consent', [
                'approved' => true,
                'auth_request_key' => $authRequestKey,
            ]);

        // Extract code from redirect URL
        $location = $response->headers->get('Location');
        parse_str(parse_url($location, PHP_URL_QUERY), $query);

        return $query['code'] ?? '';
    }

    /**
     * Extract the auth_request_key from an Inertia consent page response.
     */
    protected function extractAuthRequestKey($response): ?string
    {
        // The key is stored in session with prefix 'oauth_auth_request_'
        $sessionData = session()->all();
        foreach (array_keys($sessionData) as $key) {
            if (str_starts_with($key, 'oauth_auth_request_')) {
                return $key;
            }
        }

        return null;
    }

    protected function exchangeCodeForToken(Client $client, string $code, ?string $codeVerifier = null): array
    {
        $params = [
            'grant_type' => 'authorization_code',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'redirect_uri' => $client->redirect,
            'code' => $code,
        ];

        if ($codeVerifier) {
            $params['code_verifier'] = $codeVerifier;
            unset($params['client_secret']);
        }

        $response = $this->postJson('/oauth/token', $params);

        return $response->json();
    }

    protected function getClientCredentialsToken(Client $client, array $scopes = []): array
    {
        $response = $this->postJson('/oauth/token', [
            'grant_type' => 'client_credentials',
            'client_id' => $client->id,
            'client_secret' => $client->secret,
            'scope' => implode(' ', $scopes),
        ]);

        return $response->json();
    }

    protected function generatePkceChallenge(): array
    {
        $verifier = bin2hex(random_bytes(32));
        $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

        return [
            'code_verifier' => $verifier,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ];
    }
}
