<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiKey extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'user_id',
        'name',
        'key_hash',
        'key_prefix',
        'scopes',
        'last_used_at',
        'expires_at',
        'revoked_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
    ];

    /**
     * Get the user that owns this API key.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if the API key is active (not revoked and not expired).
     */
    public function isActive(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        if ($this->expires_at !== null && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Check if the API key is revoked.
     */
    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    /**
     * Check if the API key is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Check if the API key has a specific scope.
     */
    public function hasScope(string $scope): bool
    {
        if (empty($this->scopes)) {
            return false;
        }

        return in_array($scope, $this->scopes, true);
    }

    /**
     * Check if the API key has any of the given scopes.
     *
     * @param  array<string>  $scopes
     */
    public function hasAnyScope(array $scopes): bool
    {
        if (empty($this->scopes)) {
            return false;
        }

        foreach ($scopes as $scope) {
            if ($this->hasScope($scope)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if the API key has all of the given scopes.
     *
     * @param  array<string>  $scopes
     */
    public function hasAllScopes(array $scopes): bool
    {
        if (empty($this->scopes)) {
            return empty($scopes);
        }

        foreach ($scopes as $scope) {
            if (! $this->hasScope($scope)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Mark the API key as used (update last_used_at).
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Revoke the API key.
     */
    public function revoke(): void
    {
        $this->update(['revoked_at' => now()]);
    }

    /**
     * Scope a query to only include active API keys.
     */
    public function scopeActive($query)
    {
        return $query
            ->whereNull('revoked_at')
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope a query to only include revoked API keys.
     */
    public function scopeRevoked($query)
    {
        return $query->whereNotNull('revoked_at');
    }

    /**
     * Scope a query to only include expired API keys.
     */
    public function scopeExpired($query)
    {
        return $query
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }
}
