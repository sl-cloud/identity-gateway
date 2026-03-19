import { router, usePage } from '@inertiajs/react';
import DashboardLayout from '../../layouts/DashboardLayout';
import { Card, CardStat } from '../../components/ui/Card';
import { Button } from '../../components/ui/Button';
import { Badge } from '../../components/ui/Badge';

interface Client {
    id: string;
    name: string;
    redirect: string;
    is_confidential: boolean;
    created_at: string;
}

interface Approval {
    id: number;
    client_name: string;
    scopes: string[];
    approved_at: string;
}

interface Scope {
    id: string;
    description: string;
    is_default: boolean;
}

interface Endpoints {
    issuer: string;
    authorization: string;
    token: string;
    introspection: string;
    revocation: string;
    jwks: string;
    openid_config: string;
}

interface DashboardProps {
    auth: {
        user: {
            name: string;
            email: string;
        };
    };
    stats: {
        clients_count: number;
        approvals_count: number;
        active_keys: number;
        retired_keys: number;
    };
    clients: Client[];
    approvals: Approval[];
    scopes: Scope[];
    endpoints: Endpoints;
    [key: string]: unknown;
}

export default function Dashboard() {
    const { auth, stats, clients, approvals, scopes, endpoints } = usePage<DashboardProps>().props;

    const copyToClipboard = (text: string) => {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text);
        } else {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
        }
    };

    return (
        <DashboardLayout>
            <div className="space-y-6">
                {/* Header */}
                <div className="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Dashboard</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Welcome back, {auth.user.name}
                        </p>
                    </div>
                </div>

                {/* Stats Grid */}
                <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <CardStat
                        title="OAuth Clients"
                        value={stats.clients_count}
                        icon={
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                            </svg>
                        }
                    />
                    <CardStat
                        title="Active Consents"
                        value={stats.approvals_count}
                        icon={
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        }
                    />
                    <CardStat
                        title="Active Signing Keys"
                        value={stats.active_keys}
                        icon={
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                        }
                    />
                    <CardStat
                        title="Retired Keys"
                        value={stats.retired_keys}
                        icon={
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        }
                    />
                </div>

                {/* Quick Actions */}
                <Card padding="md">
                    <h3 className="text-lg font-medium text-gray-900 mb-4">Quick Actions</h3>
                    <div className="flex flex-wrap gap-3">
                        <Button onClick={() => router.visit('/dashboard/clients/create')}>
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Create OAuth Client
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() => router.visit('/dashboard/api-keys')}
                        >
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                            </svg>
                            Manage API Keys
                        </Button>
                        <Button
                            variant="secondary"
                            onClick={() => router.visit('/dashboard/audit-logs')}
                        >
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                            View Audit Logs
                        </Button>
                    </div>
                </Card>

                {/* OAuth Endpoints */}
                <Card padding="md">
                    <h3 className="text-lg font-medium text-gray-900 mb-4">OAuth 2.0 / OIDC Endpoints</h3>
                    <div className="space-y-2">
                        <EndpointRow label="Issuer" value={endpoints.issuer} onCopy={() => copyToClipboard(endpoints.issuer)} />
                        <EndpointRow label="Authorization Endpoint" value={endpoints.authorization} onCopy={() => copyToClipboard(endpoints.authorization)} />
                        <EndpointRow label="Token Endpoint" value={endpoints.token} onCopy={() => copyToClipboard(endpoints.token)} />
                        <EndpointRow label="Token Introspection (RFC 7662)" value={endpoints.introspection} onCopy={() => copyToClipboard(endpoints.introspection)} />
                        <EndpointRow label="Token Revocation (RFC 7009)" value={endpoints.revocation} onCopy={() => copyToClipboard(endpoints.revocation)} />
                        <EndpointRow label="JWKS Endpoint" value={endpoints.jwks} onCopy={() => copyToClipboard(endpoints.jwks)} />
                        <EndpointRow label="OpenID Configuration" value={endpoints.openid_config} onCopy={() => copyToClipboard(endpoints.openid_config)} />
                    </div>
                </Card>

                {/* Available Scopes */}
                <Card padding="md">
                    <h3 className="text-lg font-medium text-gray-900 mb-4">Available Scopes</h3>
                    <div className="flex flex-wrap gap-2">
                        {scopes.map((scope) => (
                            <Badge
                                key={scope.id}
                                variant={scope.is_default ? 'success' : 'neutral'}
                            >
                                {scope.id}
                                {scope.is_default && (
                                    <span className="ml-1 text-xs opacity-75">(default)</span>
                                )}
                            </Badge>
                        ))}
                    </div>
                </Card>

                {/* Your OAuth Clients */}
                {clients.length > 0 && (
                    <Card padding="md">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">Your OAuth Clients</h3>
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200">
                                <thead className="bg-gray-50">
                                    <tr>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Redirect URI</th>
                                        <th className="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Client ID</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-200">
                                    {clients.map((client) => (
                                        <tr key={client.id}>
                                            <td className="px-4 py-3 text-sm font-medium text-gray-900">{client.name}</td>
                                            <td className="px-4 py-3 text-sm">
                                                <Badge variant={client.is_confidential ? 'info' : 'success'}>
                                                    {client.is_confidential ? 'Confidential' : 'Public'}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3 text-sm text-gray-500 font-mono">{client.redirect}</td>
                                            <td className="px-4 py-3 text-sm font-mono text-gray-600">{client.id}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </Card>
                )}

                {/* Active Consents */}
                {approvals.length > 0 && (
                    <Card padding="md">
                        <h3 className="text-lg font-medium text-gray-900 mb-4">Your Active Consents</h3>
                        <div className="space-y-3">
                            {approvals.map((approval) => (
                                <div key={approval.id} className="border border-gray-200 rounded-lg p-4">
                                    <div className="flex justify-between items-start">
                                        <div>
                                            <p className="font-medium text-gray-900">{approval.client_name}</p>
                                            <p className="text-sm text-gray-500">
                                                Approved: {new Date(approval.approved_at).toLocaleDateString()}
                                            </p>
                                        </div>
                                        <div className="flex flex-wrap gap-1">
                                            {approval.scopes.map((scope) => (
                                                <Badge key={scope} variant="default" size="sm">
                                                    {scope}
                                                </Badge>
                                            ))}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </Card>
                )}
            </div>
        </DashboardLayout>
    );
}

function EndpointRow({ label, value, onCopy }: { label: string; value: string; onCopy: () => void }) {
    return (
        <div className="flex items-center justify-between p-3 bg-gray-50 rounded-md hover:bg-gray-100 transition-colors">
            <div className="flex-1 min-w-0">
                <p className="text-sm font-medium text-gray-700">{label}</p>
                <p className="text-xs text-gray-500 font-mono truncate">{value}</p>
            </div>
            <button
                onClick={onCopy}
                className="ml-4 p-2 text-gray-400 hover:text-indigo-600 focus:outline-none"
                title="Copy to clipboard"
            >
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                </svg>
            </button>
        </div>
    );
}
