<?php

namespace App\Guards;

use App\Models\User;
use App\Services\JwtService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class JwtGuard implements Guard
{
    protected ?User $user = null;

    protected ?object $tokenPayload = null;

    protected bool $loggedOut = false;

    public function __construct(
        protected JwtService $jwtService,
        protected Request $request
    ) {}

    /**
     * Determine if the current user is authenticated.
     */
    public function check(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Determine if the current user is a guest.
     */
    public function guest(): bool
    {
        return ! $this->check();
    }

    /**
     * Get the currently authenticated user.
     */
    public function user(): ?User
    {
        if ($this->loggedOut) {
            return null;
        }

        if ($this->user !== null) {
            return $this->user;
        }

        $payload = $this->getTokenPayload();
        if (! $payload) {
            return null;
        }

        try {
            // Hydrate user from sub claim (user ID)
            $userId = $payload->sub ?? null;
            if (! $userId) {
                return null;
            }

            // For client credentials, sub is the client_id, not a user
            // In that case, we don't return a user but still consider the request authenticated
            // We'll create a synthetic user-like object for client credentials
            if (isset($payload->client_id) && $payload->client_id === $userId) {
                // This is a client credentials token - no user associated
                // Return null but mark as authenticated via other means
                return null;
            }

            $this->user = User::find($userId);

            return $this->user;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the ID of the currently authenticated user.
     */
    public function id(): ?int
    {
        $user = $this->user();

        return $user ? $user->getAuthIdentifier() : null;
    }

    /**
     * Validate a user's credentials.
     *
     * @param  array<string, mixed>  $credentials
     */
    public function validate(array $credentials = []): bool
    {
        // JWT guard doesn't support traditional credential validation
        // Authentication is done via token in the request
        return false;
    }

    /**
     * Determine if the guard has a user instance.
     */
    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    /**
     * Set the current user.
     *
     * @return $this
     */
    public function setUser(Authenticatable $user): static
    {
        $this->user = $user;
        $this->loggedOut = false;

        return $this;
    }

    /**
     * Log the user out.
     *
     * Note: For stateless JWT, this doesn't actually invalidate the token.
     * Token revocation must be done via the revocation endpoint.
     */
    public function logout(): void
    {
        $this->user = null;
        $this->tokenPayload = null;
        $this->loggedOut = true;
        $this->request->attributes->remove('auth_token_payload');
    }

    /**
     * Get the token payload from the current request.
     */
    public function getTokenPayload(): ?object
    {
        if ($this->tokenPayload !== null) {
            return $this->tokenPayload;
        }

        $payload = $this->request->attributes->get('auth_token_payload');
        if (is_object($payload)) {
            $this->tokenPayload = $payload;

            return $this->tokenPayload;
        }

        $token = $this->getTokenFromRequest();

        if (! $token) {
            return null;
        }

        try {
            $this->tokenPayload = $this->jwtService->verify($token);
            $this->request->attributes->set('auth_token_payload', $this->tokenPayload);

            return $this->tokenPayload;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Get the raw token from the request.
     */
    protected function getTokenFromRequest(): ?string
    {
        $header = $this->request->header('Authorization');

        if (! $header || ! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        return substr($header, 7);
    }
}
