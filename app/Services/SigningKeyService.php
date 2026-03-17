<?php

namespace App\Services;

use App\Models\SigningKey;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use phpseclib3\Crypt\RSA;

class SigningKeyService
{
    /**
     * Generate a new RSA key pair and store it in the database.
     */
    public function generateKeyPair(): SigningKey
    {
        $keySize = config('identity-gateway.key_rotation.key_size', 3072);

        // Validate minimum key size for security
        if ($keySize < 2048) {
            throw new \InvalidArgumentException('RSA key size must be at least 2048 bits for security');
        }

        // Generate RSA key pair using phpseclib
        $privateKey = RSA::createKey($keySize);
        $publicKey = $privateKey->getPublicKey();

        // Convert to PEM format
        $privateKeyPem = $privateKey->toString('PKCS8');
        $publicKeyPem = $publicKey->toString('PKCS8');

        $lifetimeDays = config('identity-gateway.key_rotation.lifetime_days', 90);

        return SigningKey::create([
            'private_key' => $privateKeyPem,
            'public_key' => $publicKeyPem,
            'algorithm' => config('identity-gateway.key_rotation.algorithm', 'RS256'),
            'status' => 'active',
            'activated_at' => now(),
            'expires_at' => now()->addDays($lifetimeDays),
        ]);
    }

    /**
     * Get the current active signing key.
     */
    public function getActiveKey(): ?SigningKey
    {
        return SigningKey::active()
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderBy('activated_at', 'desc')
            ->first();
    }

    /**
     * Get all valid keys for signature validation (active + retired).
     */
    public function getValidationKeys(): Collection
    {
        return SigningKey::validForVerification()
            ->orderBy('activated_at', 'desc')
            ->get();
    }

    /**
     * Rotate keys: create new key and retire the current active key.
     *
     * Uses database transaction with row locking to prevent race conditions.
     * Note: Briefly there may be two 'active' keys during rotation, but
     * getActiveKey() orders by activated_at desc to return the newest one.
     */
    public function rotateKeys(): SigningKey
    {
        return DB::transaction(function () {
            // Lock the active key row to prevent concurrent modifications
            $currentKey = SigningKey::active()
                ->lockForUpdate()
                ->first();

            // Create new active key
            $newKey = $this->generateKeyPair();

            // Retire the current key if it exists
            if ($currentKey) {
                $currentKey->update([
                    'status' => 'retired',
                    'retired_at' => now(),
                ]);
            }

            return $newKey;
        });
    }

    /**
     * Revoke a signing key.
     */
    public function revokeKey(string $keyId): bool
    {
        $key = SigningKey::find($keyId);

        if (! $key) {
            return false;
        }

        $key->update([
            'status' => 'revoked',
        ]);

        return true;
    }

    /**
     * Get a signing key by ID.
     */
    public function getKeyById(string $keyId): ?SigningKey
    {
        return SigningKey::find($keyId);
    }

    /**
     * Ensure at least one active key exists.
     *
     * Uses database transaction with row locking to prevent race conditions
     * when multiple processes try to create the first key simultaneously.
     */
    public function ensureActiveKey(): SigningKey
    {
        // First, try to get existing active key without transaction (fast path)
        $activeKey = $this->getActiveKey();

        if ($activeKey) {
            return $activeKey;
        }

        // No active key exists, use transaction with locking to create one
        return DB::transaction(function () {
            // Re-check inside transaction with lock
            $activeKey = SigningKey::active()
                ->lockForUpdate()
                ->first();

            if ($activeKey) {
                return $activeKey;
            }

            // Create new key - still inside transaction
            return $this->generateKeyPair();
        });
    }
}
