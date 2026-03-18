<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Laravel\Passport\Client;

class OAuthConsentApproval extends Model
{
    protected $table = 'oauth_consent_approvals';

    protected $fillable = [
        'user_id',
        'client_id',
        'scopes',
        'approved_at',
    ];

    protected $casts = [
        'scopes' => 'array',
        'approved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function hasApprovedScopes(array $requestedScopes): bool
    {
        $approvedScopes = $this->scopes ?? [];

        return empty(array_diff($requestedScopes, $approvedScopes));
    }
}
