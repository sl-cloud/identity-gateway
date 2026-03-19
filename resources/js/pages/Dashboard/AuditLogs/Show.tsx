import React from 'react';
import { router, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../layouts/DashboardLayout';
import { Card, CardHeader } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { Badge } from '../../../components/ui/Badge';

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

interface AuditLogDetail {
    id: number;
    action: AuditLogAction;
    user: AuditLogUser | null;
    entity_type: string | null;
    entity_id: string | null;
    metadata: Record<string, unknown>;
    ip_address: string | null;
    user_agent: string | null;
    created_at: string;
}

interface AuditLogShowProps {
    log: AuditLogDetail;
}

export default function AuditLogShow() {
    const { log } = usePage<AuditLogShowProps>().props;

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
                        <h1 className="text-2xl font-bold text-gray-900">Audit Log Detail</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Event #{log.id}
                        </p>
                    </div>
                    <div className="mt-4 sm:mt-0">
                        <Button
                            variant="secondary"
                            onClick={() => router.visit('/dashboard/audit-logs')}
                        >
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Logs
                        </Button>
                    </div>
                </div>

                {/* Log Details */}
                <Card>
                    <CardHeader title="Event Information" />
                    <dl className="divide-y divide-gray-200">
                        <div className="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt className="text-sm font-medium text-gray-500">Action</dt>
                            <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                <Badge variant={getCategoryColor(log.action.category)} size="sm">
                                    {log.action.label}
                                </Badge>
                                <span className="ml-2 text-gray-500">({log.action.category})</span>
                            </dd>
                        </div>

                        <div className="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt className="text-sm font-medium text-gray-500">Timestamp</dt>
                            <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                {new Date(log.created_at).toLocaleString()}
                            </dd>
                        </div>

                        <div className="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt className="text-sm font-medium text-gray-500">User</dt>
                            <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                {log.user ? (
                                    <div>
                                        <span className="font-medium">{log.user.name}</span>
                                        <span className="text-gray-500 ml-2">({log.user.email})</span>
                                    </div>
                                ) : (
                                    <span className="text-gray-500 italic">System</span>
                                )}
                            </dd>
                        </div>

                        {log.entity_type && (
                            <div className="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt className="text-sm font-medium text-gray-500">Entity</dt>
                                <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0">
                                    <span>{log.entity_type.replace('_', ' ')}</span>
                                    {log.entity_id && (
                                        <code className="ml-2 text-xs text-gray-500 font-mono bg-gray-100 px-2 py-0.5 rounded">
                                            {log.entity_id}
                                        </code>
                                    )}
                                </dd>
                            </div>
                        )}

                        <div className="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                            <dt className="text-sm font-medium text-gray-500">IP Address</dt>
                            <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-mono">
                                {log.ip_address || 'N/A'}
                            </dd>
                        </div>

                        {log.user_agent && (
                            <div className="px-4 py-4 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
                                <dt className="text-sm font-medium text-gray-500">User Agent</dt>
                                <dd className="mt-1 text-sm text-gray-900 sm:col-span-2 sm:mt-0 font-mono text-xs break-all">
                                    {log.user_agent}
                                </dd>
                            </div>
                        )}
                    </dl>
                </Card>

                {/* Metadata */}
                {log.metadata && Object.keys(log.metadata).length > 0 && (
                    <Card>
                        <CardHeader title="Metadata" />
                        <div className="px-4 pb-4 sm:px-6">
                            <pre className="bg-gray-50 rounded-md p-4 text-sm text-gray-800 overflow-x-auto">
                                {JSON.stringify(log.metadata, null, 2)}
                            </pre>
                        </div>
                    </Card>
                )}
            </div>
        </DashboardLayout>
    );
}
