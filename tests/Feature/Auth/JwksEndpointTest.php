<?php

namespace Tests\Feature\Auth;

use App\Services\SigningKeyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwksEndpointTest extends TestCase
{
    use RefreshDatabase;

    protected SigningKeyService $keyService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->keyService = app(SigningKeyService::class);
    }

    public function test_jwks_endpoint_returns_valid_json(): void
    {
        $this->keyService->ensureActiveKey();

        $response = $this->get('/.well-known/jwks.json');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/json');
    }

    public function test_jwks_contains_keys_array(): void
    {
        $this->keyService->ensureActiveKey();

        $response = $this->get('/.well-known/jwks.json');

        $data = $response->json();
        $this->assertArrayHasKey('keys', $data);
        $this->assertIsArray($data['keys']);
    }

    public function test_jwks_key_has_required_fields(): void
    {
        $key = $this->keyService->ensureActiveKey();

        $response = $this->get('/.well-known/jwks.json');

        $data = $response->json();
        $this->assertNotEmpty($data['keys']);

        $jwk = $data['keys'][0];
        $this->assertArrayHasKey('kty', $jwk);
        $this->assertArrayHasKey('use', $jwk);
        $this->assertArrayHasKey('alg', $jwk);
        $this->assertArrayHasKey('kid', $jwk);
        $this->assertArrayHasKey('n', $jwk);
        $this->assertArrayHasKey('e', $jwk);
    }

    public function test_jwks_key_matches_signing_key(): void
    {
        $key = $this->keyService->ensureActiveKey();

        $response = $this->get('/.well-known/jwks.json');

        $data = $response->json();
        $jwk = $data['keys'][0];

        $this->assertEquals('RSA', $jwk['kty']);
        $this->assertEquals('sig', $jwk['use']);
        $this->assertEquals($key->algorithm, $jwk['alg']);
        $this->assertEquals($key->id, $jwk['kid']);
    }

    public function test_jwks_includes_multiple_keys_after_rotation(): void
    {
        $key1 = $this->keyService->ensureActiveKey();
        $key2 = $this->keyService->rotateKeys();

        $response = $this->get('/.well-known/jwks.json');

        $data = $response->json();
        $this->assertCount(2, $data['keys']);

        $kids = array_column($data['keys'], 'kid');
        $this->assertContains($key1->id, $kids);
        $this->assertContains($key2->id, $kids);
    }

    public function test_jwks_does_not_include_revoked_keys(): void
    {
        $activeKey = $this->keyService->ensureActiveKey();
        $revokedKey = $this->keyService->rotateKeys();
        $this->keyService->revokeKey($revokedKey->id);

        $response = $this->get('/.well-known/jwks.json');

        $data = $response->json();
        $kids = array_column($data['keys'], 'kid');

        $this->assertContains($activeKey->id, $kids);
        $this->assertNotContains($revokedKey->id, $kids);
    }

    public function test_openid_configuration_endpoint(): void
    {
        $response = $this->get('/.well-known/openid-configuration');

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertArrayHasKey('issuer', $data);
        $this->assertArrayHasKey('authorization_endpoint', $data);
        $this->assertArrayHasKey('token_endpoint', $data);
        $this->assertArrayHasKey('jwks_uri', $data);
        $this->assertArrayHasKey('scopes_supported', $data);
        $this->assertArrayHasKey('grant_types_supported', $data);
    }

    public function test_openid_configuration_has_correct_endpoints(): void
    {
        $response = $this->get('/.well-known/openid-configuration');

        $data = $response->json();
        $baseUrl = config('app.url');

        $this->assertEquals($baseUrl.'/oauth/authorize', $data['authorization_endpoint']);
        $this->assertEquals($baseUrl.'/oauth/token', $data['token_endpoint']);
        $this->assertEquals($baseUrl.'/.well-known/jwks.json', $data['jwks_uri']);
        $this->assertEquals($baseUrl.'/oauth/introspect', $data['introspection_endpoint']);
        $this->assertEquals($baseUrl.'/oauth/revoke', $data['revocation_endpoint']);
    }
}
