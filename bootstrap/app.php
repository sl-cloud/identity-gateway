<?php

use App\Http\Middleware\AuthenticateOAuthClient;
use App\Http\Middleware\ForceJsonResponse;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\RequireScope;
use App\Http\Middleware\ValidateJwtSignature;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Http\Middleware\CheckClientCredentials;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));

            Route::group([], base_path('routes/oauth.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
        ]);

        $middleware->alias([
            'client' => CheckClientCredentials::class,
            'oauth.client' => AuthenticateOAuthClient::class,
            'scope' => RequireScope::class,
            'force-json' => ForceJsonResponse::class,
            'validate-jwt' => ValidateJwtSignature::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Customize API error responses
        $exceptions->shouldRenderJsonWhen(function (Request $request) {
            return $request->is('api/*') || $request->is('oauth/*') || $request->expectsJson();
        });

        // Handle authentication errors
        $exceptions->render(function (AuthenticationException $e, Request $request) {
            if ($request->is('api/*') || $request->is('oauth/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'unauthorized',
                    'error_description' => 'Authentication required to access this resource',
                    'status' => 401,
                ], 401);
            }
        });

        // Handle access denied errors
        $exceptions->render(function (AuthorizationException $e, Request $request) {
            if ($request->is('api/*') || $request->is('oauth/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'access_denied',
                    'error_description' => 'You do not have permission to access this resource',
                    'status' => 403,
                ], 403);
            }
        });

        // Handle validation errors
        $exceptions->render(function (ValidationException $e, Request $request) {
            if ($request->is('api/*') || $request->is('oauth/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'invalid_request',
                    'error_description' => 'The request contains invalid data',
                    'errors' => $e->errors(),
                    'status' => 422,
                ], 422);
            }
        });

        // Handle not found errors
        $exceptions->render(function (NotFoundHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->is('oauth/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'not_found',
                    'error_description' => 'The requested resource was not found',
                    'status' => 404,
                ], 404);
            }
        });

        // Handle method not allowed errors
        $exceptions->render(function (MethodNotAllowedHttpException $e, Request $request) {
            if ($request->is('api/*') || $request->is('oauth/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'method_not_allowed',
                    'error_description' => 'The HTTP method is not allowed for this endpoint',
                    'status' => 405,
                ], 405);
            }
        });

        // Handle throttle/rate limit errors
        $exceptions->render(function (ThrottleRequestsException $e, Request $request) {
            if ($request->is('api/*') || $request->is('oauth/*') || $request->expectsJson()) {
                return response()->json([
                    'error' => 'rate_limit_exceeded',
                    'error_description' => 'Too many requests. Please try again later.',
                    'retry_after' => $e->getHeaders()['Retry-After'] ?? null,
                    'status' => 429,
                ], 429);
            }
        });
    })->create();
