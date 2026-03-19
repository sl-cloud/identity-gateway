<?php

namespace App\Services;

use App\Models\ApiKey;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ApiKeyService
{
    /**
     * Generate a new API key for a user.
     *
     * @return array{api_key: string, api_key_model: ApiKey}
     */
    public function generate(User $user, string $name, ?array $scopes = null, ?int $ttl = null): array
    {
        $prefix = config('identity-gateway.api_keys.prefix', 'igw_live_');
        $randomPart = bin2hex(random_bytes(16)); // 32 hex characters

        $plainKey = $prefix.$randomPart;
        $keyHash = hash('sha256', $plainKey);

        $expiresAt = null;
        if ($ttl !== null && $ttl > 0) {
            $expiresAt = now()->addSeconds($ttl);
        }

        $apiKey = ApiKey::create([
            'user_id' => $user->id,
            'name' => $name,
            'key_hash' => $keyHash,
            'key_prefix' => $prefix.substr($randomPart, 0, 4),
            'scopes' => $scopes,
            'expires_at' => $expiresAt,
        ]);

        return [
            'api_key' => $plainKey,
            'api_key_model' => $apiKey,
        ];
    }

    /**
     * Validate an API key and return the associated API key model.
     */
    public function validate(string $key): ?ApiKey
    {
        // Check key format
        $prefix = config('identity-gateway.api_keys.prefix', 'igw_live_');
        if (! str_starts_with($key, $prefix)) {
            return null;
        }

        // Hash the key for lookup
        $keyHash = hash('sha256', $key);

        // Find the API key by hash
        $apiKey = ApiKey::where('key_hash', $keyHash)->first();

        if (! $apiKey) {
            return null;
        }

        // Check if the key is active
        if (! $apiKey->isActive()) {
            return null;
        }

        // Update last_used_at (fire and forget - don't block response)
        // Use a dispatch to avoid slowing down the request
        dispatch(function () use ($apiKey) {
            $apiKey->markAsUsed();
        })->afterResponse();

        return $apiKey;
    }

    /**
     * Revoke an API key by its ID.
     */
    public function revoke(string $apiKeyId): bool
    {
        $apiKey = ApiKey::find($apiKeyId);

        if (! $apiKey) {
            return false;
        }

        $apiKey->revoke();

        return true;
    }

    /**
     * Revoke an API key by its plain key value.
     */
    public function revokeByKey(string $key): bool
    {
        $keyHash = hash('sha256', $key);
        $apiKey = ApiKey::where('key_hash', $keyHash)->first();

        if (! $apiKey) {
            return false;
        }

        $apiKey->revoke();

        return true;
    }

    /**
     * Get all active API keys for a user.
     *
     * @return Collection<ApiKey>
     */
    public function getUserApiKeys(User $user)
    {
        return ApiKey::where('user_id', $user->id)
            ->active()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Delete an API key (hard delete - use revoke for soft delete).
     */
    public function delete(string $apiKeyId): bool
    {
        $apiKey = ApiKey::find($apiKeyId);

        if (! $apiKey) {
            return false;
        }

        return $apiKey->delete();
    }
}
