import { useState } from 'react';
import { router, usePage, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../layouts/DashboardLayout';
import { Card } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { Badge } from '../../../components/ui/Badge';
import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell, EmptyState } from '../../../components/ui/Table';
import { Input } from '../../../components/ui/Input';

interface AuditLogUser {
    id: number;
    name: string;
    email: string;
}

interface AuditLogAction {
    value: string;
    label: string;
    category: string;
}

interface AuditLog {
    id: number;
    action: AuditLogAction;
    user: AuditLogUser | null;
    entity_type: string | null;
    entity_id: string | null;
    metadata: object;
    ip_address: string | null;
    created_at: string;
}

interface Pagination {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Filters {
    action: string | null;
    entity_type: string | null;
    date_from: string | null;
    date_to: string | null;
}

interface AvailableFilters {
    actions: Record<string, Array<{ value: string; label: string; category: string }>>;
    entity_types: Array<{ value: string; label: string }>;
}

interface AuditLogsIndexProps {
    logs: AuditLog[];
    pagination: Pagination;
    filters: Filters;
    availableFilters: AvailableFilters;
    [key: string]: unknown;
}

export default function AuditLogsIndex() {
    const { logs, pagination, filters, availableFilters } = usePage<AuditLogsIndexProps>().props;

    const { data, setData, get, processing } = useForm({
        action: filters.action || '',
        entity_type: filters.entity_type || '',
        date_from: filters.date_from || '',
        date_to: filters.date_to || '',
    });

    const [showFilters, setShowFilters] = useState(false);

    const handleFilterSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        get('/dashboard/audit-logs', { preserveState: true });
    };

    const clearFilters = () => {
        router.get('/dashboard/audit-logs');
    };

    const getCategoryColor = (category: string): 'default' | 'success' | 'info' | 'warning' | 'error' | 'neutral' => {
        const colors: Record<string, 'default' | 'success' | 'info' | 'warning' | 'error' | 'neutral'> = {
            user: 'info',
            token: 'success',
            client: 'default',
            api_key: 'warning',
            signing_key: 'error',
            consent: 'neutral',
            resource: 'default',
            rbac: 'info',
        };
        return colors[category] || 'neutral';
    };

    return (
        <DashboardLayout>
            <div className="space-y-6">
                {/* Header */}
                <div className="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Audit Logs</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Track all authentication and authorization events.
                        </p>
                    </div>
                    <div className="mt-4 sm:mt-0">
                        <Button
                            variant="secondary"
                            onClick={() => setShowFilters(!showFilters)}
                        >
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                            </svg>
                            Filters
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                {showFilters && (
                    <Card>
                        <form onSubmit={handleFilterSubmit} className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Action</label>
                                    <select
                                        value={data.action}
                                        onChange={(e) => setData('action', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">All Actions</option>
                                        {Object.entries(availableFilters.actions).map(([category, actions]) => (
                                            <optgroup key={category} label={category}>
                                                {actions.map((action) => (
                                                    <option key={action.value} value={action.value}>
                                                        {action.label}
                                                    </option>
                                                ))}
                                            </optgroup>
                                        ))}
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-sm font-medium text-gray-700 mb-1">Entity Type</label>
                                    <select
                                        value={data.entity_type}
                                        onChange={(e) => setData('entity_type', e.target.value)}
                                        className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="">All Types</option>
                                        {availableFilters.entity_types.map((type) => (
                                            <option key={type.value} value={type.value}>
                                                {type.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>

                                <Input
                                    label="From Date"
                                    type="date"
                                    value={data.date_from}
                                    onChange={(e) => setData('date_from', e.target.value)}
                                />

                                <Input
                                    label="To Date"
                                    type="date"
                                    value={data.date_to}
                                    onChange={(e) => setData('date_to', e.target.value)}
                                />
                            </div>

                            <div className="flex items-center justify-end space-x-3">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    onClick={clearFilters}
                                >
                                    Clear Filters
                                </Button>
                                <Button
                                    type="submit"
                                    isLoading={processing}
                                >
                                    Apply Filters
                                </Button>
                            </div>
                        </form>
                    </Card>
                )}

                {/* Logs Table */}
                <Card>
                    {logs.length === 0 ? (
                        <EmptyState
                            title="No audit logs"
                            description="No audit logs match your criteria."
                            icon={
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                </svg>
                            }
                        />
                    ) : (
                        <>
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeader>Time</TableHeader>
                                        <TableHeader>Action</TableHeader>
                                        <TableHeader>User</TableHeader>
                                        <TableHeader>Entity</TableHeader>
                                        <TableHeader>IP Address</TableHeader>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {logs.map((log) => (
                                        <TableRow
                                            key={log.id}
                                            className="cursor-pointer hover:bg-gray-50"
                                            onClick={() => router.visit(`/dashboard/audit-logs/${log.id}`)}
                                        >
                                            <TableCell>
                                                <div className="text-sm text-gray-900">
                                                    {new Date(log.created_at).toLocaleDateString()}
                                                </div>
                                                <div className="text-xs text-gray-500">
                                                    {new Date(log.created_at).toLocaleTimeString()}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={getCategoryColor(log.action.category)} size="sm">
                                                    {log.action.label}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                {log.user ? (
                                                    <div>
                                                        <div className="text-sm font-medium text-gray-900">{log.user.name}</div>
                                                        <div className="text-xs text-gray-500">{log.user.email}</div>
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-500 italic text-sm">System</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {log.entity_type && (
                                                    <div>
                                                        <div className="text-sm text-gray-900">
                                                            {log.entity_type.replace('_', ' ')}
                                                        </div>
                                                        {log.entity_id && (
                                                            <code className="text-xs text-gray-500 font-mono">
                                                                {log.entity_id.slice(0, 8)}...
                                                            </code>
                                                        )}
                                                    </div>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <span className="text-sm text-gray-500 font-mono">
                                                    {log.ip_address || 'N/A'}
                                                </span>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>

                            {/* Pagination */}
                            <div className="flex items-center justify-between border-t border-gray-200 px-4 py-3 sm:px-6">
                                <div className="flex-1 flex justify-between sm:hidden">
                                    <button
                                        onClick={() => router.get('/dashboard/audit-logs', { page: pagination.current_page - 1 }, { preserveState: true })}
                                        disabled={pagination.current_page === 1}
                                        className="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        onClick={() => router.get('/dashboard/audit-logs', { page: pagination.current_page + 1 }, { preserveState: true })}
                                        disabled={pagination.current_page === pagination.last_page}
                                        className="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 disabled:opacity-50"
                                    >
                                        Next
                                    </button>
                                </div>
                                <div className="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p className="text-sm text-gray-700">
                                            Showing <span className="font-medium">{((pagination.current_page - 1) * pagination.per_page) + 1}</span> to{' '}
                                            <span className="font-medium">{Math.min(pagination.current_page * pagination.per_page, pagination.total)}</span> of{' '}
                                            <span className="font-medium">{pagination.total}</span> results
                                        </p>
                                    </div>
                                    <div>
                                        <nav className="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                            <button
                                                onClick={() => router.get('/dashboard/audit-logs', { page: pagination.current_page - 1 }, { preserveState: true })}
                                                disabled={pagination.current_page === 1}
                                                className="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                                            >
                                                <span className="sr-only">Previous</span>
                                                <svg className="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fillRule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clipRule="evenodd" />
                                                </svg>
                                            </button>
                                            <button
                                                onClick={() => router.get('/dashboard/audit-logs', { page: pagination.current_page + 1 }, { preserveState: true })}
                                                disabled={pagination.current_page === pagination.last_page}
                                                className="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50 disabled:opacity-50"
                                            >
                                                <span className="sr-only">Next</span>
                                                <svg className="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fillRule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clipRule="evenodd" />
                                                </svg>
                                            </button>
                                        </nav>
                                    </div>
                                </div>
                            </div>
                        </>
                    )}
                </Card>
            </div>
        </DashboardLayout>
    );
}
