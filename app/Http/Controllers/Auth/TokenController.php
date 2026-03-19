<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\JwtService;
use Illuminate\Http\Response;
use Laravel\Passport\Http\Controllers\AccessTokenController;
use Laravel\Passport\Token;
use Psr\Http\Message\ServerRequestInterface;

class TokenController extends Controller
{
    public function __construct(
        private AccessTokenController $accessTokenController,
        private JwtService $jwtService,
    ) {}

    /**
     * Issue access tokens for all grant types, replacing the opaque token with a custom JWT.
     */
    public function issueToken(ServerRequestInterface $request): Response
    {
        $response = $this->accessTokenController->issueToken($request);

        $body = json_decode($response->getContent(), true);
        if (! isset($body['access_token'])) {
            return $response;
        }

        // Passport 12 returns a League OAuth2 JWT as the access_token.
        // The token's database ID is stored in the JWT's "jti" claim.
        $jti = $this->extractJtiFromPassportJwt($body['access_token']);
        $passportToken = $jti ? Token::find($jti) : null;

        if (! $passportToken) {
            return $response;
        }

        $user = $passportToken->user_id ? User::find($passportToken->user_id) : null;
        $scopes = $passportToken->scopes ?? [];
        $clientId = (string) $passportToken->client_id;

        $body['access_token'] = $this->jwtService->sign($user, $scopes, $clientId);

        $response->setContent(json_encode($body));

        return $response;
    }

    /**
     * Extract the jti (token ID) from a Passport/League OAuth2 JWT.
     */
    private function extractJtiFromPassportJwt(string $jwt): ?string
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            return null;
        }

        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/'), true));

        return $payload->jti ?? null;
    }
}
