<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OAuthScope extends Model
{
    protected $table = 'oauth_scopes';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }
}
