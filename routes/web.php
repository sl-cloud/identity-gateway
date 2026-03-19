<?php

use App\Http\Controllers\Dashboard\ApiKeyController;
use App\Http\Controllers\Dashboard\AuditLogController;
use App\Http\Controllers\Dashboard\ClientController;
use App\Http\Controllers\Dashboard\TokenController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Demo\FlowDemoController;
use App\Http\Controllers\Demo\JwtInspectorController;
use App\Http\Controllers\Demo\PlaygroundController;
use App\Http\Middleware\DemoEnvironmentOnly;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/auth/login');

Route::prefix('demo')->middleware(DemoEnvironmentOnly::class)->group(function () {
    Route::get('/', [FlowDemoController::class, 'index'])->name('demo.index');
    Route::get('/playground', [PlaygroundController::class, 'index'])->name('demo.playground');
    Route::get('/jwt-inspector', [JwtInspectorController::class, 'index'])->name('demo.jwt-inspector');
    Route::get('/jwks', [JwtInspectorController::class, 'jwks'])->name('demo.jwks');
    Route::get('/flows/auth-code', [FlowDemoController::class, 'authCode'])->name('demo.flows.auth-code');
    Route::get('/flows/pkce', [FlowDemoController::class, 'pkce'])->name('demo.flows.pkce');
    Route::get('/flows/client-credentials', [FlowDemoController::class, 'clientCredentials'])->name('demo.flows.client-credentials');
    Route::get('/introspection', [FlowDemoController::class, 'introspection'])->name('demo.introspection');
    Route::get('/revocation', [FlowDemoController::class, 'revocation'])->name('demo.revocation');
    Route::get('/callback', [FlowDemoController::class, 'callback'])->name('demo.callback');
});

Route::middleware(['auth'])->group(function () {
    // Main dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // OAuth Clients management
    Route::get('/dashboard/clients', [ClientController::class, 'index'])->name('dashboard.clients.index')
        ->middleware('permission:clients:read');
    Route::get('/dashboard/clients/create', [ClientController::class, 'create'])->name('dashboard.clients.create')
        ->middleware('permission:clients:create');
    Route::post('/dashboard/clients', [ClientController::class, 'store'])->name('dashboard.clients.store')
        ->middleware('permission:clients:create');
    Route::get('/dashboard/clients/{client}', [ClientController::class, 'show'])->name('dashboard.clients.show')
        ->middleware('permission:clients:read');
    Route::put('/dashboard/clients/{client}', [ClientController::class, 'update'])->name('dashboard.clients.update')
        ->middleware('permission:clients:update');
    Route::delete('/dashboard/clients/{client}', [ClientController::class, 'destroy'])->name('dashboard.clients.destroy')
        ->middleware('permission:clients:revoke');

    // API Keys management
    Route::get('/dashboard/api-keys', [ApiKeyController::class, 'index'])->name('dashboard.api-keys.index')
        ->middleware('permission:api-keys:read');
    Route::post('/dashboard/api-keys', [ApiKeyController::class, 'store'])->name('dashboard.api-keys.store')
        ->middleware('permission:api-keys:create');
    Route::delete('/dashboard/api-keys/{key}', [ApiKeyController::class, 'destroy'])->name('dashboard.api-keys.destroy')
        ->middleware('permission:api-keys:revoke');

    // Tokens management
    Route::get('/dashboard/tokens', [TokenController::class, 'index'])->name('dashboard.tokens.index')
        ->middleware('permission:tokens:read');
    Route::post('/dashboard/tokens/inspect', [TokenController::class, 'inspect'])->name('dashboard.tokens.inspect')
        ->middleware('permission:tokens:introspect');
    Route::delete('/dashboard/tokens/{token}', [TokenController::class, 'destroy'])->name('dashboard.tokens.destroy')
        ->middleware('permission:tokens:revoke');

    // Audit Logs
    Route::get('/dashboard/audit-logs', [AuditLogController::class, 'index'])->name('dashboard.audit-logs.index')
        ->middleware('permission:audit-logs:read');
    Route::get('/dashboard/audit-logs/{log}', [AuditLogController::class, 'show'])->name('dashboard.audit-logs.show')
        ->middleware('permission:audit-logs:read');
});
