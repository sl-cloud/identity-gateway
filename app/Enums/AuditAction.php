<?php

namespace App\Enums;

enum AuditAction: string
{
    // User actions
    case USER_LOGIN = 'user.login';
    case USER_LOGOUT = 'user.logout';
    case USER_REGISTERED = 'user.registered';
    case USER_PASSWORD_CHANGED = 'user.password_changed';

    // Token actions
    case TOKEN_ISSUED = 'token.issued';
    case TOKEN_REFRESHED = 'token.refreshed';
    case TOKEN_REVOKED = 'token.revoked';
    case TOKEN_INTROSPECTED = 'token.introspected';

    // OAuth client actions
    case CLIENT_CREATED = 'client.created';
    case CLIENT_UPDATED = 'client.updated';
    case CLIENT_REVOKED = 'client.revoked';

    // API key actions
    case API_KEY_CREATED = 'api_key.created';
    case API_KEY_REVOKED = 'api_key.revoked';
    case API_KEY_USED = 'api_key.used';

    // Signing key actions
    case KEY_ROTATED = 'key.rotated';
    case KEY_RETIRED = 'key.retired';
    case KEY_REVOKED = 'key.revoked';

    // Consent actions
    case CONSENT_GRANTED = 'consent.granted';
    case CONSENT_REVOKED = 'consent.revoked';

    // Resource actions
    case RESOURCE_CREATED = 'resource.created';
    case RESOURCE_UPDATED = 'resource.updated';
    case RESOURCE_DELETED = 'resource.deleted';

    // Role/Permission actions
    case ROLE_ASSIGNED = 'role.assigned';
    case ROLE_REMOVED = 'role.removed';
    case PERMISSION_GRANTED = 'permission.granted';
    case PERMISSION_REVOKED = 'permission.revoked';

    /**
     * Get a human-readable label for the action.
     */
    public function label(): string
    {
        return match ($this) {
            self::USER_LOGIN => 'User Login',
            self::USER_LOGOUT => 'User Logout',
            self::USER_REGISTERED => 'User Registered',
            self::USER_PASSWORD_CHANGED => 'Password Changed',
            self::TOKEN_ISSUED => 'Token Issued',
            self::TOKEN_REFRESHED => 'Token Refreshed',
            self::TOKEN_REVOKED => 'Token Revoked',
            self::TOKEN_INTROSPECTED => 'Token Introspected',
            self::CLIENT_CREATED => 'OAuth Client Created',
            self::CLIENT_UPDATED => 'OAuth Client Updated',
            self::CLIENT_REVOKED => 'OAuth Client Revoked',
            self::API_KEY_CREATED => 'API Key Created',
            self::API_KEY_REVOKED => 'API Key Revoked',
            self::API_KEY_USED => 'API Key Used',
            self::KEY_ROTATED => 'Signing Key Rotated',
            self::KEY_RETIRED => 'Signing Key Retired',
            self::KEY_REVOKED => 'Signing Key Revoked',
            self::CONSENT_GRANTED => 'Consent Granted',
            self::CONSENT_REVOKED => 'Consent Revoked',
            self::RESOURCE_CREATED => 'Resource Created',
            self::RESOURCE_UPDATED => 'Resource Updated',
            self::RESOURCE_DELETED => 'Resource Deleted',
            self::ROLE_ASSIGNED => 'Role Assigned',
            self::ROLE_REMOVED => 'Role Removed',
            self::PERMISSION_GRANTED => 'Permission Granted',
            self::PERMISSION_REVOKED => 'Permission Revoked',
        };
    }

    /**
     * Get the category for the action.
     */
    public function category(): string
    {
        return match ($this) {
            self::USER_LOGIN, self::USER_LOGOUT, self::USER_REGISTERED, self::USER_PASSWORD_CHANGED => 'user',
            self::TOKEN_ISSUED, self::TOKEN_REFRESHED, self::TOKEN_REVOKED, self::TOKEN_INTROSPECTED => 'token',
            self::CLIENT_CREATED, self::CLIENT_UPDATED, self::CLIENT_REVOKED => 'client',
            self::API_KEY_CREATED, self::API_KEY_REVOKED, self::API_KEY_USED => 'api_key',
            self::KEY_ROTATED, self::KEY_RETIRED, self::KEY_REVOKED => 'signing_key',
            self::CONSENT_GRANTED, self::CONSENT_REVOKED => 'consent',
            self::RESOURCE_CREATED, self::RESOURCE_UPDATED, self::RESOURCE_DELETED => 'resource',
            self::ROLE_ASSIGNED, self::ROLE_REMOVED, self::PERMISSION_GRANTED, self::PERMISSION_REVOKED => 'rbac',
        };
    }
}
