import React from 'react';
import { router } from '@inertiajs/react';

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
}

export default function Dashboard({ auth, stats, clients, approvals, scopes, endpoints }: DashboardProps) {
    const handleLogout = () => {
        router.post('/auth/logout');
    };

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
        <div className="min-h-screen bg-gray-50">
            {/* Navigation */}
            <nav className="bg-white shadow-sm border-b border-gray-200">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex items-center space-x-8">
                            <h1 className="text-xl font-bold text-gray-900">
                                Identity Gateway
                            </h1>
                            <div className="hidden md:flex space-x-4 text-sm">
                                <span className="text-gray-500">Dashboard</span>
                                <a href="/.well-known/openid-configuration" target="_blank" rel="noopener noreferrer" className="text-indigo-600 hover:text-indigo-800">
                                    Discovery
                                </a>
                                <a href="/.well-known/jwks.json" target="_blank" rel="noopener noreferrer" className="text-indigo-600 hover:text-indigo-800">
                                    JWKS
                                </a>
                            </div>
                        </div>
                        <div className="flex items-center space-x-4">
                            <span className="text-gray-700">{auth.user.name}</span>
                            <button
                                onClick={handleLogout}
                                className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 text-sm"
                            >
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <div className="px-4 py-6 sm:px-0 space-y-6">
                    {/* Welcome Banner */}
                    <div className="bg-gradient-to-r from-indigo-600 to-blue-600 rounded-lg p-6 text-white shadow-lg">
                        <h2 className="text-2xl font-bold mb-2">
                            Welcome to Identity Gateway
                        </h2>
                        <p className="text-indigo-100">
                            You are logged in as <strong>{auth.user.email}</strong>
                        </p>
                        <div className="mt-4 flex flex-wrap gap-2">
                            <span className="px-3 py-1 bg-white/20 rounded-full text-sm">
                                OAuth 2.0 Server
                            </span>
                            <span className="px-3 py-1 bg-white/20 rounded-full text-sm">
                                OpenID Connect
                            </span>
                            <span className="px-3 py-1 bg-white/20 rounded-full text-sm">
                                JWT with Rotating Keys
                            </span>
                        </div>
                    </div>

                    {/* Stats Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div className="bg-white rounded-lg shadow p-4 border-l-4 border-indigo-500">
                            <p className="text-sm text-gray-500">OAuth Clients</p>
                            <p className="text-2xl font-bold text-gray-900">{stats.clients_count}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
                            <p className="text-sm text-gray-500">Active Consents</p>
                            <p className="text-2xl font-bold text-gray-900">{stats.approvals_count}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
                            <p className="text-sm text-gray-500">Active Signing Keys</p>
                            <p className="text-2xl font-bold text-gray-900">{stats.active_keys}</p>
                        </div>
                        <div className="bg-white rounded-lg shadow p-4 border-l-4 border-amber-500">
                            <p className="text-sm text-gray-500">Retired Keys</p>
                            <p className="text-2xl font-bold text-gray-900">{stats.retired_keys}</p>
                        </div>
                    </div>

                    {/* OAuth Endpoints Section */}
                    <div className="bg-white rounded-lg shadow p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg className="w-5 h-5 mr-2 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                            </svg>
                            OAuth 2.0 / OIDC Endpoints
                        </h3>
                        <div className="space-y-2">
                            <EndpointRow label="Issuer" value={endpoints.issuer} onCopy={() => copyToClipboard(endpoints.issuer)} />
                            <EndpointRow label="Authorization Endpoint" value={endpoints.authorization} onCopy={() => copyToClipboard(endpoints.authorization)} />
                            <EndpointRow label="Token Endpoint" value={endpoints.token} onCopy={() => copyToClipboard(endpoints.token)} />
                            <EndpointRow label="Token Introspection (RFC 7662)" value={endpoints.introspection} onCopy={() => copyToClipboard(endpoints.introspection)} />
                            <EndpointRow label="Token Revocation (RFC 7009)" value={endpoints.revocation} onCopy={() => copyToClipboard(endpoints.revocation)} />
                            <EndpointRow label="JWKS Endpoint" value={endpoints.jwks} onCopy={() => copyToClipboard(endpoints.jwks)} />
                            <EndpointRow label="OpenID Configuration" value={endpoints.openid_config} onCopy={() => copyToClipboard(endpoints.openid_config)} />
                        </div>
                    </div>

                    {/* Supported Flows */}
                    <div className="bg-white rounded-lg shadow p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg className="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Supported OAuth 2.0 Flows
                        </h3>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <FlowCard
                                title="Authorization Code"
                                description="Standard OAuth 2.0 flow for web applications"
                                features={['Authorization Code', 'Refresh Token', 'Consent Management']}
                                color="blue"
                            />
                            <FlowCard
                                title="PKCE"
                                description="Proof Key for Code Exchange for mobile/SPA apps"
                                features={['S256 Challenge Method', 'Public Clients', 'No Client Secret']}
                                color="purple"
                            />
                            <FlowCard
                                title="Client Credentials"
                                description="Server-to-server authentication"
                                features={['Confidential Clients', 'No Refresh Token', 'Client Authentication']}
                                color="green"
                            />
                        </div>
                    </div>

                    {/* Available Scopes */}
                    <div className="bg-white rounded-lg shadow p-6">
                        <h3 className="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <svg className="w-5 h-5 mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                            Available Scopes
                        </h3>
                        <div className="flex flex-wrap gap-2">
                            {scopes.map((scope) => (
                                <span
                                    key={scope.id}
                                    className={`px-3 py-1 rounded-full text-sm ${
                                        scope.is_default
                                            ? 'bg-green-100 text-green-800 border border-green-300'
                                            : 'bg-gray-100 text-gray-700 border border-gray-300'
                                    }`}
                                    title={scope.description}
                                >
                                    {scope.id}
                                    {scope.is_default && (
                                        <span className="ml-1 text-xs text-green-600">(default)</span>
                                    )}
                                </span>
                            ))}
                        </div>
                    </div>

                    {/* Your OAuth Clients */}
                    {clients.length > 0 && (
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Your OAuth Clients</h3>
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead>
                                        <tr>
                                            <th className="px-4 py-2 text-left text-sm font-medium text-gray-500">Name</th>
                                            <th className="px-4 py-2 text-left text-sm font-medium text-gray-500">Type</th>
                                            <th className="px-4 py-2 text-left text-sm font-medium text-gray-500">Redirect URI</th>
                                            <th className="px-4 py-2 text-left text-sm font-medium text-gray-500">Client ID</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200">
                                        {clients.map((client) => (
                                            <tr key={client.id}>
                                                <td className="px-4 py-2 text-sm text-gray-900">{client.name}</td>
                                                <td className="px-4 py-2 text-sm">
                                                    <span className={`px-2 py-1 rounded text-xs ${client.is_confidential ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'}`}>
                                                        {client.is_confidential ? 'Confidential' : 'Public'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-500">{client.redirect}</td>
                                                <td className="px-4 py-2 text-sm font-mono text-xs text-gray-600">{client.id}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    )}

                    {/* Active Consents */}
                    {approvals.length > 0 && (
                        <div className="bg-white rounded-lg shadow p-6">
                            <h3 className="text-lg font-semibold text-gray-900 mb-4">Your Active Consents</h3>
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
                                                    <span key={scope} className="px-2 py-1 bg-indigo-50 text-indigo-700 rounded text-xs">
                                                        {scope}
                                                    </span>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Footer */}
                    <div className="text-center text-sm text-gray-500 pt-4">
                        <p>Identity Gateway — OAuth 2.0 & OpenID Connect Provider</p>
                        <p className="mt-1">
                            <a href="/.well-known/openid-configuration" className="text-indigo-600 hover:underline" target="_blank" rel="noopener noreferrer">
                                OpenID Discovery Document
                            </a>
                            {' • '}
                            <a href="/.well-known/jwks.json" className="text-indigo-600 hover:underline" target="_blank" rel="noopener noreferrer">
                                JWKS Endpoint
                            </a>
                        </p>
                    </div>
                </div>
            </main>
        </div>
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

function FlowCard({ title, description, features, color }: { title: string; description: string; features: string[]; color: string }) {
    const colorClasses: Record<string, { border: string; badge: string }> = {
        blue: { border: 'border-blue-500', badge: 'bg-blue-100 text-blue-800' },
        purple: { border: 'border-purple-500', badge: 'bg-purple-100 text-purple-800' },
        green: { border: 'border-green-500', badge: 'bg-green-100 text-green-800' },
    };

    const colors = colorClasses[color] || colorClasses.blue;

    return (
        <div className={`border-l-4 ${colors.border} bg-gray-50 rounded-r-lg p-4`}>
            <h4 className="font-semibold text-gray-900">{title}</h4>
            <p className="text-sm text-gray-600 mt-1">{description}</p>
            <div className="mt-3 flex flex-wrap gap-1">
                {features.map((feature) => (
                    <span key={feature} className={`px-2 py-1 rounded text-xs ${colors.badge}`}>
                        {feature}
                    </span>
                ))}
            </div>
        </div>
    );
}
