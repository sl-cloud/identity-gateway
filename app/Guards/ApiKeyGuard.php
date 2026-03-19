<?php

namespace App\Guards;

use App\Models\ApiKey;
use App\Models\User;
use App\Services\ApiKeyService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;

class ApiKeyGuard implements Guard
{
    protected ?User $user = null;

    protected ?ApiKey $apiKey = null;

    protected bool $loggedOut = false;

    public function __construct(
        protected ApiKeyService $apiKeyService,
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

        $apiKey = $this->getApiKeyFromRequest();

        if (! $apiKey) {
            return null;
        }

        // Store the API key for later scope checks
        $this->apiKey = $apiKey;
        $this->request->attributes->set('auth_api_key', $apiKey);

        // Load the user
        $this->user = $apiKey->user;

        return $this->user;
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
        // API key guard doesn't support traditional credential validation
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
     * Note: For API keys, this just clears the current request's user.
     * The API key itself remains valid for future requests.
     */
    public function logout(): void
    {
        $this->user = null;
        $this->apiKey = null;
        $this->loggedOut = true;
        $this->request->attributes->remove('auth_api_key');
    }

    /**
     * Get the current API key model.
     */
    public function getApiKey(): ?ApiKey
    {
        return $this->apiKey;
    }

    /**
     * Get the API key from the request and validate it.
     */
    protected function getApiKeyFromRequest(): ?ApiKey
    {
        $key = $this->getKeyFromRequest();

        if (! $key) {
            return null;
        }

        return $this->apiKeyService->validate($key);
    }

    /**
     * Extract the API key from the request header.
     */
    protected function getKeyFromRequest(): ?string
    {
        // Check X-Api-Key header first
        $key = $this->request->header('X-Api-Key');

        if ($key) {
            return $key;
        }

        // Also check Authorization: ApiKey <key> format (alternative)
        $header = $this->request->header('Authorization');
        if ($header && str_starts_with($header, 'ApiKey ')) {
            return substr($header, 7);
        }

        return null;
    }
}
