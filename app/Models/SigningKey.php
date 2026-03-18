<?php

namespace App\Models;

use App\Enums\KeyStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SigningKey extends Model
{
    use HasFactory, HasUuids;

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
        return $this->status === KeyStatus::ACTIVE->value;
    }

    public function isRetired(): bool
    {
        return $this->status === KeyStatus::RETIRED->value;
    }

    public function isRevoked(): bool
    {
        return $this->status === KeyStatus::REVOKED->value;
    }

    public function isValid(): bool
    {
        return $this->isActive() || $this->isRetired();
    }

    public function scopeActive($query)
    {
        return $query->where('status', KeyStatus::ACTIVE->value);
    }

    public function scopeValidForVerification($query)
    {
        return $query->whereIn('status', [KeyStatus::ACTIVE->value, KeyStatus::RETIRED->value])
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }
}
