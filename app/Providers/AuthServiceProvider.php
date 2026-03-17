<?php

namespace App\Providers;

use App\Passport\CustomBearerTokenResponse;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;

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

        // Register OAuth scopes
        Passport::tokensCan([
            'openid' => 'OpenID Connect authentication',
            'user:read' => 'Read authenticated user information',
            'users:read' => 'Read all users (admin only)',
            'resources:read' => 'Read resources',
            'resources:write' => 'Create, update, and delete resources',
        ]);

        // Set default scopes
        Passport::setDefaultScope([
            'user:read',
        ]);

        // Register custom bearer token response
        $this->app->make(AuthorizationServer::class)
            ->setResponseType(new CustomBearerTokenResponse);
    }
}
