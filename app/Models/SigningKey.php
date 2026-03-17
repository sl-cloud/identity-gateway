<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class SigningKey extends Model
{
    use HasUuids;

    protected $fillable = [
        'private_key',
        'public_key',
        'algorithm',
        'status',
        'activated_at',
        'retired_at',
        'expires_at',
    ];

    protected $casts = [
        'private_key' => 'encrypted',
        'activated_at' => 'datetime',
        'retired_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isRetired(): bool
    {
        return $this->status === 'retired';
    }

    public function isRevoked(): bool
    {
        return $this->status === 'revoked';
    }

    public function isValid(): bool
    {
        return $this->isActive() || $this->isRetired();
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeValidForVerification($query)
    {
        return $query->whereIn('status', ['active', 'retired'])
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
