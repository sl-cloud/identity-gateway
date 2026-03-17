<?php

return [

    /*
    |--------------------------------------------------------------------------
    | JWT Configuration
    |--------------------------------------------------------------------------
    |
    | Configure JWT token lifetimes and settings for access and refresh tokens.
    |
    */

    'jwt' => [
        'access_token_ttl' => env('JWT_ACCESS_TOKEN_TTL', 900), // 15 minutes
        'refresh_token_ttl' => env('JWT_REFRESH_TOKEN_TTL', 604800), // 7 days
        'issuer' => env('JWT_ISSUER', env('APP_URL')),
        'audience' => env('JWT_AUDIENCE', env('APP_URL')),
    ],

    /*
    |--------------------------------------------------------------------------
    | Signing Key Rotation Configuration
    |--------------------------------------------------------------------------
    |
    | Configure automatic key rotation intervals and key lifetimes.
    |
    */

    'key_rotation' => [
        'interval_days' => env('KEY_ROTATION_INTERVAL_DAYS', 7), // Rotate keys weekly
        'lifetime_days' => env('KEY_LIFETIME_DAYS', 90), // Keys valid for 90 days
        'algorithm' => 'RS256', // RSA with SHA-256
        'key_size' => 3072, // RSA key size in bits
    ],

    /*
    |--------------------------------------------------------------------------
    | API Key Configuration
    |--------------------------------------------------------------------------
    |
    | Configure API key generation and validation settings.
    |
    */

    'api_keys' => [
        'prefix' => env('API_KEY_PREFIX', 'igw_live_'),
        'default_ttl' => env('API_KEY_DEFAULT_TTL', 365 * 86400), // 1 year
        'hash_algorithm' => 'sha256',
    ],

];
