<?php

namespace App\Passport;

use App\Models\User;
use App\Services\JwtService;
use GuzzleHttp\Psr7\Utils;
use Laravel\Passport\Bridge\AccessToken;
use League\OAuth2\Server\ResponseTypes\BearerTokenResponse;
use Psr\Http\Message\ResponseInterface;

class CustomBearerTokenResponse extends BearerTokenResponse
{
    /**
     * Generate the HTTP response with custom JWT.
     */
    public function generateHttpResponse(ResponseInterface $response): ResponseInterface
    {
        // Get the original response
        $response = parent::generateHttpResponse($response);

        // Decode the response body
        $body = json_decode($response->getBody()->__toString(), true);

        if (! isset($body['access_token'])) {
            return $response;
        }

        // Get user and scopes from the access token
        $accessToken = $this->accessToken;

        if ($accessToken instanceof AccessToken) {
            $userId = $accessToken->getUserIdentifier();
            $user = $userId ? User::find($userId) : null;

            // Generate JWT for both user-based grants and client_credentials
            $scopes = $accessToken->getScopes();
            $scopeIdentifiers = array_map(fn ($scope) => $scope->getIdentifier(), $scopes);
            $clientId = $accessToken->getClient()->getIdentifier();

            // Generate custom JWT (works with null user for client_credentials)
            $jwtService = app(JwtService::class);
            $customJwt = $jwtService->sign($user, $scopeIdentifiers, $clientId);

            // Replace the access token with our custom JWT
            $body['access_token'] = $customJwt;

            // Replace the response body entirely to avoid stale bytes
            $newBody = json_encode($body);
            $response = $response
                ->withBody(Utils::streamFor($newBody))
                ->withHeader('Content-Length', strlen($newBody));
        }

        return $response;
    }
}
