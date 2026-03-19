<?php

namespace App\Models;

use App\Enums\AuditAction;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'action' => AuditAction::class,
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user that performed this action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to filter by action.
     */
    public function scopeForAction($query, AuditAction|string $action)
    {
        $actionValue = $action instanceof AuditAction ? $action->value : $action;

        return $query->where('action', $actionValue);
    }

    /**
     * Scope a query to filter by entity type.
     */
    public function scopeForEntity($query, string $entityType)
    {
        return $query->where('entity_type', $entityType);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to filter by user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get a human-readable label for the action.
     */
    public function actionLabel(): string
    {
        return $this->action?->label() ?? $this->action;
    }

    /**
     * Get the category for the action.
     */
    public function category(): string
    {
        return $this->action?->category() ?? 'unknown';
    }
}
