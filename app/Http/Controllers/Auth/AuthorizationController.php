<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\OAuthConsentApproval;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Passport\Bridge\User as BridgeUser;
use Laravel\Passport\ClientRepository;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\RequestTypes\AuthorizationRequest;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ServerRequestInterface;

class AuthorizationController extends Controller
{
    public function __construct(
        private AuthorizationServer $server,
        private ClientRepository $clients,
    ) {}

    /**
     * Authorize a client to access the user's account.
     */
    public function authorize(ServerRequestInterface $psrRequest, Request $request): Response|RedirectResponse
    {
        try {
            // Validate the authorization request
            $authRequest = $this->server->validateAuthorizationRequest($psrRequest);

            $client = $this->clients->find($authRequest->getClient()->getIdentifier());
            $scopes = collect($authRequest->getScopes())->map->getIdentifier()->all();
            $user = $request->user();

            // Check if user has already approved this client with these scopes
            $existingApproval = OAuthConsentApproval::where('user_id', $user->id)
                ->where('client_id', $client->id)
                ->first();

            if ($existingApproval && $existingApproval->hasApprovedScopes($scopes)) {
                // User has already approved, skip consent screen
                $authRequest->setUser(new BridgeUser($user->getAuthIdentifier()));
                $authRequest->setAuthorizationApproved(true);

                $psr17Factory = new Psr17Factory;
                $psrResponse = $psr17Factory->createResponse();

                return redirect()->away(
                    $this->server->completeAuthorizationRequest($authRequest, $psrResponse)
                        ->getHeader('Location')[0]
                );
            }

            // Store the validated auth request in the session with a unique key
            // so concurrent authorization tabs don't overwrite each other
            $authRequestKey = 'oauth_auth_request_'.Str::random(40);
            $request->session()->put($authRequestKey, serialize($authRequest));

            // Show consent screen
            return Inertia::render('Auth/Consent', [
                'client' => [
                    'id' => $client->id,
                    'name' => $client->name,
                    'redirect' => $client->redirect,
                ],
                'scopes' => $scopes,
                'state' => $authRequest->getState(),
                'auth_request_key' => $authRequestKey,
            ]);
        } catch (OAuthServerException $e) {
            return redirect()->route('login')->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Approve or deny the authorization request.
     */
    public function approve(Request $request): RedirectResponse
    {
        $approved = filter_var($request->input('approved'), FILTER_VALIDATE_BOOLEAN);
        $authRequestKey = $request->input('auth_request_key');

        try {
            // Retrieve the validated auth request from the session using the per-request key
            if (! $authRequestKey || ! $request->session()->has($authRequestKey)) {
                return redirect()->route('login')->withErrors([
                    'error' => 'Authorization request has expired. Please try again.',
                ]);
            }

            $serialized = $request->session()->pull($authRequestKey);
            if (! $serialized) {
                return redirect()->route('login')->withErrors([
                    'error' => 'Authorization request has expired. Please try again.',
                ]);
            }

            /** @var AuthorizationRequest $authRequest */
            $authRequest = unserialize($serialized, ['allowed_classes' => true]);

            $authRequest->setUser(new BridgeUser($request->user()->getAuthIdentifier()));
            $authRequest->setAuthorizationApproved($approved);

            if ($approved) {
                // Store consent approval
                $scopes = collect($authRequest->getScopes())->map->getIdentifier()->all();
                $client = $this->clients->find($authRequest->getClient()->getIdentifier());

                OAuthConsentApproval::updateOrCreate(
                    [
                        'user_id' => $request->user()->id,
                        'client_id' => $client->id,
                    ],
                    [
                        'scopes' => $scopes,
                        'approved_at' => now(),
                    ]
                );
            }

            // Complete the authorization request
            $psr17Factory = new Psr17Factory;
            $psrResponse = $psr17Factory->createResponse();
            $response = $this->server->completeAuthorizationRequest($authRequest, $psrResponse);

            return redirect()->away($response->getHeader('Location')[0]);
        } catch (OAuthServerException $e) {
            // If the exception has a redirect URI (e.g. access_denied), redirect back to the client with error params
            if ($e->hasRedirect()) {
                $psr17Factory = new Psr17Factory;
                $psrResponse = $e->generateHttpResponse($psr17Factory->createResponse());

                return redirect()->away($psrResponse->getHeader('Location')[0]);
            }

            return redirect()->route('login')->withErrors([
                'error' => $e->getMessage(),
            ]);
        }
    }
}
