import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../layouts/DashboardLayout';
import { Card, CardHeader } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { Badge } from '../../../components/ui/Badge';
import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell, EmptyState } from '../../../components/ui/Table';
import { ConfirmModal } from '../../../components/ui/Modal';

interface Token {
    id: string;
    scopes: string[];
    expires_at: string;
    created_at: string;
    user: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface Client {
    id: string;
    name: string;
    redirect: string;
    secret: string | null;
    is_confidential: boolean;
    created_at: string;
    updated_at: string;
}

interface ClientShowProps {
    client: Client;
    tokens: Token[];
}

export default function ClientsShow() {
    const { client, tokens } = usePage<ClientShowProps>().props;
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDelete = () => {
        setIsDeleting(true);
        router.delete(`/dashboard/clients/${client.id}`, {
            onFinish: () => {
                setIsDeleting(false);
                setDeleteModalOpen(false);
            },
        });
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
    };

    return (
        <DashboardLayout>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <button
                            onClick={() => router.visit('/dashboard/clients')}
                            className="text-sm text-indigo-600 hover:text-indigo-800 flex items-center"
                        >
                            <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Back to Clients
                        </button>
                        <h1 className="mt-4 text-2xl font-bold text-gray-900">{client.name}</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            OAuth client details and active tokens
                        </p>
                    </div>
                    <Button
                        variant="danger"
                        onClick={() => setDeleteModalOpen(true)}
                    >
                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Revoke Client
                    </Button>
                </div>

                {/* Client Details */}
                <Card>
                    <CardHeader
                        title="Client Details"
                        description="OAuth 2.0 client configuration"
                    />
                    <div className="mt-4 space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label className="block text-xs font-medium text-gray-500 uppercase">Client ID</label>
                                <div className="mt-1 flex items-center">
                                    <code className="text-sm bg-gray-100 px-3 py-2 rounded flex-1 font-mono">
                                        {client.id}
                                    </code>
                                    <button
                                        onClick={() => copyToClipboard(client.id)}
                                        className="ml-2 p-2 text-gray-400 hover:text-indigo-600"
                                        title="Copy to clipboard"
                                    >
                                        <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-500 uppercase">Client Type</label>
                                <div className="mt-1">
                                    <Badge variant={client.is_confidential ? 'info' : 'success'} size="md">
                                        {client.is_confidential ? 'Confidential' : 'Public'}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        {client.secret && (
                            <div>
                                <label className="block text-xs font-medium text-gray-500 uppercase">Client Secret</label>
                                <div className="mt-1">
                                    <code className="text-sm bg-gray-100 px-3 py-2 rounded inline-block font-mono text-red-600">
                                        {client.secret}
                                    </code>
                                    <p className="text-xs text-gray-500 mt-1">
                                        This is a hashed representation. The original secret was shown once during creation.
                                    </p>
                                </div>
                            </div>
                        )}

                        <div>
                            <label className="block text-xs font-medium text-gray-500 uppercase">Redirect URI</label>
                            <div className="mt-1">
                                <code className="text-sm bg-gray-100 px-3 py-2 rounded inline-block font-mono">
                                    {client.redirect}
                                </code>
                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 pt-4 border-t border-gray-200">
                            <div>
                                <label className="block text-xs font-medium text-gray-500 uppercase">Created</label>
                                <p className="mt-1 text-sm text-gray-900">
                                    {new Date(client.created_at).toLocaleString()}
                                </p>
                            </div>
                            <div>
                                <label className="block text-xs font-medium text-gray-500 uppercase">Last Updated</label>
                                <p className="mt-1 text-sm text-gray-900">
                                    {new Date(client.updated_at).toLocaleString()}
                                </p>
                            </div>
                        </div>
                    </div>
                </Card>

                {/* Active Tokens */}
                <Card>
                    <CardHeader
                        title={`Active Tokens (${tokens.length})`}
                        description="Currently active access tokens for this client"
                    />
                    {tokens.length === 0 ? (
                        <div className="mt-4">
                            <EmptyState
                                title="No active tokens"
                                description="There are no active access tokens for this client."
                                icon={
                                    <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                }
                            />
                        </div>
                    ) : (
                        <div className="mt-4 overflow-x-auto">
                            <Table>
                                <TableHead>
                                    <TableRow>
                                        <TableHeader>User</TableHeader>
                                        <TableHeader>Scopes</TableHeader>
                                        <TableHeader>Expires</TableHeader>
                                        <TableHeader>Created</TableHeader>
                                    </TableRow>
                                </TableHead>
                                <TableBody>
                                    {tokens.map((token) => (
                                        <TableRow key={token.id}>
                                            <TableCell>
                                                {token.user ? (
                                                    <div>
                                                        <div className="font-medium text-gray-900">{token.user.name}</div>
                                                        <div className="text-xs text-gray-500">{token.user.email}</div>
                                                    </div>
                                                ) : (
                                                    <span className="text-gray-500 italic">Client Credentials</span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-1">
                                                    {token.scopes.map((scope) => (
                                                        <Badge key={scope} variant="default" size="sm">
                                                            {scope}
                                                        </Badge>
                                                    ))}
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {new Date(token.expires_at).toLocaleDateString()}
                                            </TableCell>
                                            <TableCell>
                                                {new Date(token.created_at).toLocaleDateString()}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                </Card>

                {/* Delete Confirmation Modal */}
                <ConfirmModal
                    isOpen={deleteModalOpen}
                    onClose={() => setDeleteModalOpen(false)}
                    onConfirm={handleDelete}
                    title="Revoke OAuth Client"
                    description={`Are you sure you want to revoke "${client.name}"? This will also revoke all ${tokens.length} active token${tokens.length !== 1 ? 's' : ''}. This action cannot be undone.`}
                    confirmText="Revoke"
                    isLoading={isDeleting}
                    variant="danger"
                />
            </div>
        </DashboardLayout>
    );
}
