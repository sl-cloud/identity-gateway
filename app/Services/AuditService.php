<?php

namespace App\Services;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuditService
{
    /**
     * Keys that must never be persisted in audit metadata.
     * Matched case-insensitively against metadata keys (including nested).
     */
    private const REDACTED_KEYS = [
        'authorization',
        'password',
        'secret',
        'client_secret',
        'token',
        'access_token',
        'refresh_token',
        'api_key',
        'plain_key',
        'bearer',
        'cookie',
        'x-api-key',
        'private_key',
        'credential',
        'credentials',
    ];

    private const REDACTED_PLACEHOLDER = '[REDACTED]';

    /**
     * Log an audit event.
     *
     * @param  AuditAction  $action  The action being performed
     * @param  User|null  $user  The user performing the action (null for system/client actions)
     * @param  string|null  $entityType  The type of entity being affected (e.g., 'oauth_client')
     * @param  string|null  $entityId  The ID of the affected entity
     * @param  array<string, mixed>  $metadata  Additional context about the action
     * @param  Request|null  $request  The HTTP request (for IP/user agent capture)
     */
    public function log(
        AuditAction $action,
        ?User $user = null,
        ?string $entityType = null,
        ?string $entityId = null,
        array $metadata = [],
        ?Request $request = null
    ): AuditLog {
        try {
            $auditLog = AuditLog::create([
                'user_id' => $user?->id,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metadata' => self::redactMetadata($metadata),
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'created_at' => now(),
            ]);

            // Also write to Laravel log for redundancy
            Log::info('Audit log created', [
                'action' => $action->value,
                'user_id' => $user?->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
            ]);

            return $auditLog;
        } catch (\Exception $e) {
            // Don't let audit logging failures break the application
            Log::error('Failed to create audit log', [
                'action' => $action->value,
                'error' => $e->getMessage(),
            ]);

            // Return a dummy model for type safety — still redact to prevent leaks via caller inspection
            return new AuditLog([
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'metadata' => self::redactMetadata($metadata),
            ]);
        }
    }

    /**
     * Log a user login event.
     */
    public function logUserLogin(User $user, ?Request $request = null): AuditLog
    {
        return $this->log(
            AuditAction::USER_LOGIN,
            $user,
            'user',
            (string) $user->id,
            ['email' => $user->email],
            $request
        );
    }

    /**
     * Log a user logout event.
     */
    public function logUserLogout(User $user, ?Request $request = null): AuditLog
    {
        return $this->log(
            AuditAction::USER_LOGOUT,
            $user,
            'user',
            (string) $user->id,
            ['email' => $user->email],
            $request
        );
    }

    /**
     * Log a token issuance event.
     */
    public function logTokenIssued(
        ?User $user,
        string $clientId,
        array $scopes,
        string $jti,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::TOKEN_ISSUED,
            $user,
            'oauth_client',
            $clientId,
            [
                'client_id' => $clientId,
                'scopes' => $scopes,
                'jti' => $jti,
                'has_user' => $user !== null,
            ],
            $request
        );
    }

    /**
     * Log a token revocation event.
     */
    public function logTokenRevoked(
        ?User $user,
        string $jti,
        ?string $clientId = null,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::TOKEN_REVOKED,
            $user,
            'token',
            $jti,
            [
                'jti' => $jti,
                'client_id' => $clientId,
            ],
            $request
        );
    }

    /**
     * Log an OAuth client creation event.
     */
    public function logClientCreated(
        User $user,
        string $clientId,
        string $clientName,
        bool $isConfidential,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::CLIENT_CREATED,
            $user,
            'oauth_client',
            $clientId,
            [
                'client_name' => $clientName,
                'is_confidential' => $isConfidential,
            ],
            $request
        );
    }

    /**
     * Log an OAuth client revocation event.
     */
    public function logClientRevoked(
        User $user,
        string $clientId,
        string $clientName,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::CLIENT_REVOKED,
            $user,
            'oauth_client',
            $clientId,
            [
                'client_name' => $clientName,
                'revoked_by' => $user->id,
            ],
            $request
        );
    }

    /**
     * Log an API key creation event.
     */
    public function logApiKeyCreated(
        User $user,
        string $apiKeyId,
        string $apiKeyName,
        array $scopes,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::API_KEY_CREATED,
            $user,
            'api_key',
            $apiKeyId,
            [
                'key_name' => $apiKeyName,
                'scopes' => $scopes,
            ],
            $request
        );
    }

    /**
     * Log an API key revocation event.
     */
    public function logApiKeyRevoked(
        User $user,
        string $apiKeyId,
        string $apiKeyName,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::API_KEY_REVOKED,
            $user,
            'api_key',
            $apiKeyId,
            [
                'key_name' => $apiKeyName,
                'revoked_by' => $user->id,
            ],
            $request
        );
    }

    /**
     * Log a signing key rotation event.
     */
    public function logKeyRotated(
        ?User $user,
        string $newKeyId,
        ?string $previousKeyId = null,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::KEY_ROTATED,
            $user,
            'signing_key',
            $newKeyId,
            [
                'new_key_id' => $newKeyId,
                'previous_key_id' => $previousKeyId,
                'rotated_by' => $user?->id,
            ],
            $request
        );
    }

    /**
     * Log a consent grant event.
     */
    public function logConsentGranted(
        User $user,
        string $clientId,
        array $scopes,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::CONSENT_GRANTED,
            $user,
            'oauth_client',
            $clientId,
            [
                'client_id' => $clientId,
                'scopes' => $scopes,
            ],
            $request
        );
    }

    /**
     * Log a consent revocation event.
     */
    public function logConsentRevoked(
        User $user,
        string $clientId,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::CONSENT_REVOKED,
            $user,
            'oauth_client',
            $clientId,
            [
                'client_id' => $clientId,
            ],
            $request
        );
    }

    /**
     * Log a role assignment event.
     */
    public function logRoleAssigned(
        User $performedBy,
        User $targetUser,
        string $role,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::ROLE_ASSIGNED,
            $performedBy,
            'user',
            (string) $targetUser->id,
            [
                'target_user_id' => $targetUser->id,
                'target_user_email' => $targetUser->email,
                'role' => $role,
            ],
            $request
        );
    }

    /**
     * Log a resource creation event.
     */
    public function logResourceCreated(
        User $user,
        string $resourceId,
        string $resourceName,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::RESOURCE_CREATED,
            $user,
            'resource',
            $resourceId,
            [
                'resource_name' => $resourceName,
            ],
            $request
        );
    }

    /**
     * Log a resource update event.
     */
    public function logResourceUpdated(
        User $user,
        string $resourceId,
        string $resourceName,
        array $changedFields,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::RESOURCE_UPDATED,
            $user,
            'resource',
            $resourceId,
            [
                'resource_name' => $resourceName,
                'changed_fields' => array_keys($changedFields),
            ],
            $request
        );
    }

    /**
     * Log a resource deletion event.
     */
    public function logResourceDeleted(
        User $user,
        string $resourceId,
        string $resourceName,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::RESOURCE_DELETED,
            $user,
            'resource',
            $resourceId,
            [
                'resource_name' => $resourceName,
            ],
            $request
        );
    }

    /**
     * Log a token inspection event.
     */
    public function logTokenInspected(
        User $user,
        bool $isValid,
        ?string $jti = null,
        ?Request $request = null
    ): AuditLog {
        return $this->log(
            AuditAction::TOKEN_INTROSPECTED,
            $user,
            'token',
            $jti,
            [
                'is_valid' => $isValid,
            ],
            $request
        );
    }

    /**
     * Recursively redact sensitive keys from metadata.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    public static function redactMetadata(array $metadata): array
    {
        $redacted = [];

        foreach ($metadata as $key => $value) {
            if (self::isSensitiveKey($key)) {
                $redacted[$key] = self::REDACTED_PLACEHOLDER;
            } elseif (is_array($value)) {
                $redacted[$key] = self::redactMetadata($value);
            } elseif (is_string($value) && self::looksLikeSecret($value)) {
                $redacted[$key] = self::REDACTED_PLACEHOLDER;
            } else {
                $redacted[$key] = $value;
            }
        }

        return $redacted;
    }

    private static function isSensitiveKey(string $key): bool
    {
        $normalized = strtolower(str_replace(['-', '_'], '', $key));

        foreach (self::REDACTED_KEYS as $sensitiveKey) {
            $normalizedSensitive = strtolower(str_replace(['-', '_'], '', $sensitiveKey));
            if ($normalized === $normalizedSensitive) {
                return true;
            }
        }

        return false;
    }

    private static function looksLikeSecret(string $value): bool
    {
        // Detect Bearer tokens
        if (preg_match('/^Bearer\s+/i', $value)) {
            return true;
        }

        // Detect JWTs (three base64url segments separated by dots)
        if (preg_match('/^[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+$/', $value) && substr_count($value, '.') === 2) {
            return true;
        }

        return false;
    }
}
