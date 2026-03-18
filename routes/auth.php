<?php

use App\Http\Controllers\Auth\AuthorizationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::post('/logout', LogoutController::class)->name('logout');

    // OAuth consent flow
    Route::middleware(['auth'])->group(function () {
        Route::post('/consent', [AuthorizationController::class, 'approve'])->name('consent.approve');
    });
});

// OAuth2 Authorization endpoint (requires authentication + session)
Route::prefix('oauth')->group(function () {
    Route::middleware(['auth'])->group(function () {
        Route::get('/authorize', [AuthorizationController::class, 'authorize'])->name('oauth.authorize');
    });
});
