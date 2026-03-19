<?php

namespace App\Providers;

use App\Guards\ApiKeyGuard;
use App\Guards\JwtGuard;
use App\Services\ApiKeyService;
use App\Services\JwtService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register custom JWT guard
        Auth::extend('jwt', function ($app, $name, array $config) {
            return new JwtGuard(
                $app->make(JwtService::class),
                $app['request']
            );
        });

        // Register custom API key guard
        Auth::extend('api-key', function ($app, $name, array $config) {
            return new ApiKeyGuard(
                $app->make(ApiKeyService::class),
                $app['request']
            );
        });
    }
}
