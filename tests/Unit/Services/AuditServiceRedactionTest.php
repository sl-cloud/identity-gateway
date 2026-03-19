<?php

namespace Tests\Unit\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuditServiceRedactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_safe_metadata_passes_through_unchanged(): void
    {
        $metadata = [
            'client_name' => 'My Application',
            'scopes' => ['openid', 'profile', 'email'],
            'is_confidential' => true,
            'redirect_uri' => 'https://example.com/callback',
            'grant_type' => 'authorization_code',
            'response_type' => 'code',
            'count' => 42,
        ];

        $result = AuditService::redactMetadata($metadata);

        $this->assertSame($metadata, $result);
    }

    public function test_direct_sensitive_keys_are_redacted(): void
    {
        $sensitiveKeys = [
            'password' => 'my-secret-password',
            'secret' => 'super-secret-value',
            'token' => 'some-token-value',
            'api_key' => 'ak_live_12345',
            'authorization' => 'Basic dXNlcjpwYXNz',
            'client_secret' => 'cs_abcdef123456',
            'access_token' => 'at_xyz789',
            'refresh_token' => 'rt_abc123',
            'plain_key' => 'pk_test_key',
            'bearer' => 'some-bearer-value',
            'cookie' => 'session=abc123',
            'x-api-key' => 'xak_123',
            'private_key' => '-----BEGIN PRIVATE KEY-----',
            'credential' => 'cred_value',
            'credentials' => 'creds_value',
        ];

        $result = AuditService::redactMetadata($sensitiveKeys);

        foreach ($sensitiveKeys as $key => $value) {
            $this->assertSame('[REDACTED]', $result[$key], "Key '{$key}' should be redacted");
        }
    }

    public function test_case_insensitive_key_matching(): void
    {
        $metadata = [
            'Password' => 'secret123',
            'SECRET' => 'top-secret',
            'Api_Key' => 'ak_test',
            'TOKEN' => 'tok_abc',
            'AUTHORIZATION' => 'Bearer xyz',
        ];

        $result = AuditService::redactMetadata($metadata);

        foreach ($metadata as $key => $value) {
            $this->assertSame('[REDACTED]', $result[$key], "Key '{$key}' should be redacted regardless of case");
        }
    }

    public function test_underscore_and_hyphen_normalization(): void
    {
        $metadata = [
            'client-secret' => 'cs_123',
            'x_api_key' => 'xak_456',
            'access-token' => 'at_789',
            'refresh-token' => 'rt_012',
            'private-key' => 'pk_345',
            'client_secret' => 'cs_678',
            'x-api-key' => 'xak_901',
        ];

        $result = AuditService::redactMetadata($metadata);

        foreach ($metadata as $key => $value) {
            $this->assertSame('[REDACTED]', $result[$key], "Key '{$key}' should be redacted after normalization");
        }
    }

    public function test_nested_arrays_are_recursively_redacted(): void
    {
        $metadata = [
            'request' => [
                'headers' => [
                    'authorization' => 'Bearer abc123',
                    'content-type' => 'application/json',
                ],
                'body' => [
                    'client_secret' => 'cs_nested',
                    'grant_type' => 'client_credentials',
                ],
            ],
            'client_name' => 'Test App',
        ];

        $result = AuditService::redactMetadata($metadata);

        $this->assertSame('[REDACTED]', $result['request']['headers']['authorization']);
        $this->assertSame('application/json', $result['request']['headers']['content-type']);
        $this->assertSame('[REDACTED]', $result['request']['body']['client_secret']);
        $this->assertSame('client_credentials', $result['request']['body']['grant_type']);
        $this->assertSame('Test App', $result['client_name']);
    }

    public function test_bearer_token_values_are_detected_and_redacted(): void
    {
        $metadata = [
            'some_header' => 'Bearer eyJhbGciOiJSUzI1NiJ9',
            'another_field' => 'bearer token_value_here',
            'safe_field' => 'This is not a bearer token',
        ];

        $result = AuditService::redactMetadata($metadata);

        $this->assertSame('[REDACTED]', $result['some_header']);
        $this->assertSame('[REDACTED]', $result['another_field']);
        $this->assertSame('This is not a bearer token', $result['safe_field']);
    }

    public function test_jwt_shaped_values_are_detected_and_redacted(): void
    {
        $jwt = 'eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJzdWIiOiIxMjM0NTY3ODkwIn0.SflKxwRJSMeKKF2QT4fwpMeJf36POk6yJV_adQssw5c';

        $metadata = [
            'some_value' => $jwt,
            'description' => 'A regular string with dots like v1.2.3',
            'ip_address' => '192.168.1.1',
        ];

        $result = AuditService::redactMetadata($metadata);

        $this->assertSame('[REDACTED]', $result['some_value']);
        $this->assertSame('A regular string with dots like v1.2.3', $result['description']);
        $this->assertSame('192.168.1.1', $result['ip_address']);
    }

    public function test_mixed_metadata_preserves_safe_values_and_redacts_sensitive(): void
    {
        $metadata = [
            'client_name' => 'My App',
            'password' => 'hunter2',
            'redirect_uri' => 'https://example.com/callback',
            'client_secret' => 'cs_secret_value',
            'scopes' => ['openid', 'profile'],
            'is_confidential' => true,
            'api_key' => 'ak_live_key',
            'grant_type' => 'authorization_code',
        ];

        $result = AuditService::redactMetadata($metadata);

        $this->assertSame('My App', $result['client_name']);
        $this->assertSame('[REDACTED]', $result['password']);
        $this->assertSame('https://example.com/callback', $result['redirect_uri']);
        $this->assertSame('[REDACTED]', $result['client_secret']);
        $this->assertSame(['openid', 'profile'], $result['scopes']);
        $this->assertTrue($result['is_confidential']);
        $this->assertSame('[REDACTED]', $result['api_key']);
        $this->assertSame('authorization_code', $result['grant_type']);
    }

    public function test_empty_metadata_returns_empty_array(): void
    {
        $result = AuditService::redactMetadata([]);

        $this->assertSame([], $result);
    }

    public function test_redaction_is_applied_during_audit_log_creation(): void
    {
        $user = User::factory()->create();

        $metadata = [
            'client_name' => 'Test OAuth Client',
            'client_secret' => 'cs_live_super_secret_value',
            'redirect_uri' => 'https://example.com/callback',
            'api_key' => 'ak_test_12345',
            'grant_type' => 'client_credentials',
        ];

        $service = app(AuditService::class);
        $service->log(
            action: AuditAction::CLIENT_CREATED,
            user: $user,
            metadata: $metadata,
        );

        $auditLog = AuditLog::latest()->first();

        $this->assertNotNull($auditLog);

        $storedMetadata = $auditLog->metadata;

        $this->assertSame('Test OAuth Client', $storedMetadata['client_name']);
        $this->assertSame('[REDACTED]', $storedMetadata['client_secret']);
        $this->assertSame('https://example.com/callback', $storedMetadata['redirect_uri']);
        $this->assertSame('[REDACTED]', $storedMetadata['api_key']);
        $this->assertSame('client_credentials', $storedMetadata['grant_type']);
    }
}
