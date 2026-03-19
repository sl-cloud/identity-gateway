<?php

namespace App\Providers;

use App\Models\OAuthScope;
use App\Passport\CustomBearerTokenResponse;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Configure Passport token lifetimes
        Passport::tokensExpireIn(now()->addMinutes(15));
        Passport::refreshTokensExpireIn(now()->addDays(7));
        Passport::personalAccessTokensExpireIn(now()->addDays(7));

        // Use custom JWT bearer token response
        Passport::$authorizationServerResponseType = new CustomBearerTokenResponse;

        // Enable grant types
        Passport::enablePasswordGrant();

        // Register OAuth scopes from database
        try {
            $scopes = OAuthScope::pluck('description', 'id')->toArray();
            $defaults = OAuthScope::default()->pluck('id')->toArray();
        } catch (\Throwable) {
            // Fallback before migrations have run
            $scopes = [];
            $defaults = [];
        }

        Passport::tokensCan($scopes);
        Passport::setDefaultScope($defaults);
    }
}
