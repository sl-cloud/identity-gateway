<?php

use App\Http\Controllers\Api\ResourceController;
use App\Http\Controllers\Api\ScopeController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| These routes are for the Resource API - protected REST endpoints that
| accept both JWT Bearer tokens and API keys for authentication.
|
*/

// Resource API v1 routes
// Multi-guard: tries JWT first, then API key
Route::prefix('v1')->middleware([
    'force-json',
    'validate-jwt',
    'auth:jwt,api-key',
    'throttle:120,1',
])->group(function () {

    // User endpoints
    Route::get('/me', [UserController::class, 'me'])
        ->middleware('scope:user:read')
        ->name('api.v1.me');

    Route::get('/users', [UserController::class, 'index'])
        ->middleware('scope:users:read')
        ->name('api.v1.users.index');

    Route::get('/users/{user}', [UserController::class, 'show'])
        ->middleware('scope:users:read')
        ->name('api.v1.users.show');

    // Resource endpoints (demo protected resources)
    Route::get('/resources', [ResourceController::class, 'index'])
        ->middleware('scope:resources:read')
        ->name('api.v1.resources.index');

    Route::post('/resources', [ResourceController::class, 'store'])
        ->middleware('scope:resources:write')
        ->name('api.v1.resources.store');

    Route::get('/resources/{resource}', [ResourceController::class, 'show'])
        ->middleware('scope:resources:read')
        ->name('api.v1.resources.show');

    Route::put('/resources/{resource}', [ResourceController::class, 'update'])
        ->middleware('scope:resources:write')
        ->name('api.v1.resources.update');

    Route::delete('/resources/{resource}', [ResourceController::class, 'destroy'])
        ->middleware('scope:resources:write')
        ->name('api.v1.resources.destroy');

    // Scopes endpoint (authenticated, no additional scope required)
    Route::get('/scopes', [ScopeController::class, 'index'])
        ->name('api.v1.scopes.index');
});
