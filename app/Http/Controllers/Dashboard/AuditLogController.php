<?php

namespace App\Http\Controllers\Dashboard;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    /**
     * List audit logs with filtering.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Build query with filters
        $query = AuditLog::query()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc');

        // Filter by user's own actions if not admin
        if (! $user->hasRole('admin')) {
            $query->where('user_id', $user->id);
        }

        // Apply filters
        if ($request->has('action') && $request->input('action')) {
            $query->forAction($request->input('action'));
        }

        if ($request->has('entity_type') && $request->input('entity_type')) {
            $query->forEntity($request->input('entity_type'));
        }

        if ($request->has('date_from') && $request->input('date_from')) {
            $query->where('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to') && $request->input('date_to')) {
            $query->where('created_at', '<=', $request->input('date_to').' 23:59:59');
        }

        // Paginate results
        $perPage = $request->input('per_page', 25);
        $auditLogs = $query->paginate($perPage)->withQueryString();

        // Transform for frontend
        $logs = $auditLogs->map(fn ($log) => [
            'id' => $log->id,
            'action' => [
                'value' => $log->action->value,
                'label' => $log->action->label(),
                'category' => $log->action->category(),
            ],
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
                'email' => $log->user->email,
            ] : null,
            'entity_type' => $log->entity_type,
            'entity_id' => $log->entity_id,
            'metadata' => $log->metadata,
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at,
        ]);

        // Get available actions for filter dropdown
        $actions = collect(AuditAction::cases())->map(fn ($action) => [
            'value' => $action->value,
            'label' => $action->label(),
            'category' => $action->category(),
        ])->groupBy('category');

        // Get available entity types
        $entityTypes = AuditLog::distinct()
            ->whereNotNull('entity_type')
            ->pluck('entity_type')
            ->map(fn ($type) => [
                'value' => $type,
                'label' => ucwords(str_replace('_', ' ', $type)),
            ]);

        return Inertia::render('Dashboard/AuditLogs/Index', [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $auditLogs->currentPage(),
                'last_page' => $auditLogs->lastPage(),
                'per_page' => $auditLogs->perPage(),
                'total' => $auditLogs->total(),
            ],
            'filters' => [
                'action' => $request->input('action'),
                'entity_type' => $request->input('entity_type'),
                'date_from' => $request->input('date_from'),
                'date_to' => $request->input('date_to'),
            ],
            'availableFilters' => [
                'actions' => $actions,
                'entity_types' => $entityTypes,
            ],
        ]);
    }

    /**
     * Show a specific audit log entry.
     */
    public function show(Request $request, int $logId)
    {
        $user = $request->user();

        $log = AuditLog::with('user:id,name,email')->findOrFail($logId);

        // Check if user can view this log
        if (! $user->hasRole('admin') && $log->user_id !== $user->id) {
            abort(403, 'You do not have permission to view this audit log.');
        }

        return Inertia::render('Dashboard/AuditLogs/Show', [
            'log' => [
                'id' => $log->id,
                'action' => [
                    'value' => $log->action->value,
                    'label' => $log->action->label(),
                    'category' => $log->action->category(),
                ],
                'user' => $log->user ? [
                    'id' => $log->user->id,
                    'name' => $log->user->name,
                    'email' => $log->user->email,
                ] : null,
                'entity_type' => $log->entity_type,
                'entity_id' => $log->entity_id,
                'metadata' => $log->metadata,
                'ip_address' => $log->ip_address,
                'user_agent' => $log->user_agent,
                'created_at' => $log->created_at,
            ],
        ]);
    }
}
