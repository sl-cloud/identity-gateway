<?php

use App\Http\Controllers\Auth\IntrospectionController;
use App\Http\Controllers\Auth\JwksController;
use App\Http\Controllers\Auth\OpenIdConfigController;
use App\Http\Controllers\Auth\RevocationController;
use App\Http\Controllers\Auth\TokenController;
use Illuminate\Support\Facades\Route;

// OAuth2 API endpoints (no CSRF, no session)
Route::prefix('oauth')->group(function () {
    // Token endpoint
    Route::post('/token', [TokenController::class, 'issueToken'])
        ->middleware(['throttle:30,1'])
        ->name('oauth.token');

    // Token introspection (RFC 7662) - requires client authentication via HTTP Basic
    Route::post('/introspect', [IntrospectionController::class, 'introspect'])
        ->middleware(['throttle:60,1', 'oauth.client'])
        ->name('oauth.introspect');

    // Token revocation (RFC 7009) - requires client authentication via HTTP Basic
    Route::post('/revoke', [RevocationController::class, 'revoke'])
        ->middleware(['throttle:60,1', 'oauth.client'])
        ->name('oauth.revoke');
});

// OpenID Connect / OAuth2 Discovery
Route::prefix('.well-known')->group(function () {
    Route::get('/jwks.json', JwksController::class)->name('jwks');
    Route::get('/openid-configuration', OpenIdConfigController::class)->name('openid-config');
});
