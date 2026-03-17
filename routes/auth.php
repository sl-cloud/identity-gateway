<?php

use App\Http\Controllers\Auth\JwksController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\OpenIdConfigController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
    Route::get('/register', [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'store']);
    Route::post('/logout', LogoutController::class)->name('logout');
});

// OpenID Connect / OAuth2 Discovery
Route::prefix('.well-known')->group(function () {
    Route::get('/jwks.json', JwksController::class)->name('jwks');
    Route::get('/openid-configuration', OpenIdConfigController::class)->name('openid-config');
});
