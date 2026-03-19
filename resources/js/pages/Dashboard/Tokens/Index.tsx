import React, { useState } from 'react';
import { router, usePage, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../layouts/DashboardLayout';
import { Card, CardHeader } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { Badge } from '../../../components/ui/Badge';
import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell, EmptyState } from '../../../components/ui/Table';
import { Modal, ConfirmModal } from '../../../components/ui/Modal';
import { Input } from '../../../components/ui/Input';

interface Token {
    id: string;
    scopes: string[];
    expires_at: string;
    created_at: string;
    client: {
        id: string;
        name: string;
    } | null;
}

interface TokenInspectResult {
    valid: boolean;
    expired: boolean;
    revoked: boolean;
    error: string | null;
    header: object;
    payload: object;
}

interface TokensIndexProps {
    tokens: Token[];
}

export default function TokensIndex() {
    const { tokens } = usePage<TokensIndexProps>().props;

    const [inspectModalOpen, setInspectModalOpen] = useState(false);
    const [inspectResult, setInspectResult] = useState<TokenInspectResult | null>(null);
    const [isInspecting, setIsInspecting] = useState(false);

    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [tokenToDelete, setTokenToDelete] = useState<Token | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        token: '',
    });

    const handleInspect = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsInspecting(true);

        try {
            const response = await fetch('/dashboard/tokens/inspect', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({ token: data.token }),
            });

            const result = await response.json();
            setInspectResult(result as TokenInspectResult);
        } catch {
            setInspectResult(null);
        } finally {
            setIsInspecting(false);
        }
    };

    const handleDeleteClick = (token: Token) => {
        setTokenToDelete(token);
        setDeleteModalOpen(true);
    };

    const handleConfirmDelete = () => {
        if (!tokenToDelete) return;

        setIsDeleting(true);
        router.delete(`/dashboard/tokens/${tokenToDelete.id}`, {
            onFinish: () => {
                setIsDeleting(false);
                setDeleteModalOpen(false);
                setTokenToDelete(null);
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
                <div className="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Tokens</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            View and manage your active OAuth access tokens.
                        </p>
                    </div>
                    <div className="mt-4 sm:mt-0">
                        <Button onClick={() => setInspectModalOpen(true)} variant="secondary">
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                            Inspect Token
                        </Button>
                    </div>
                </div>

                {/* Tokens Table */}
                <Card>
                    {tokens.length === 0 ? (
                        <EmptyState
                            title="No active tokens"
                            description="You don't have any active OAuth tokens."
                            icon={
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            }
                        />
                    ) : (
                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableHeader>Client</TableHeader>
                                    <TableHeader>Scopes</TableHeader>
                                    <TableHeader>Expires</TableHeader>
                                    <TableHeader>Created</TableHeader>
                                    <TableHeader className="text-right">Actions</TableHeader>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {tokens.map((token) => (
                                    <TableRow key={token.id}>
                                        <TableCell>
                                            {token.client ? (
                                                <div>
                                                    <div className="font-medium text-gray-900">{token.client.name}</div>
                                                    <div className="text-xs text-gray-500 font-mono">{token.client.id}</div>
                                                </div>
                                            ) : (
                                                <span className="text-gray-500 italic">Direct Access</span>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1 max-w-xs">
                                                {token.scopes.map((scope) => (
                                                    <Badge key={scope} variant="default" size="sm">
                                                        {scope}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className={`text-sm ${new Date(token.expires_at) < new Date() ? 'text-red-600' : 'text-gray-900'}`}>
                                                {new Date(token.expires_at).toLocaleDateString()}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {new Date(token.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <button
                                                onClick={() => handleDeleteClick(token)}
                                                className="text-red-600 hover:text-red-900"
                                                title="Revoke token"
                                            >
                                                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </Card>

                {/* Token Inspection Modal */}
                <Modal
                    isOpen={inspectModalOpen}
                    onClose={() => {
                        setInspectModalOpen(false);
                        setInspectResult(null);
                        reset();
                    }}
                    title="Inspect Token"
                    description="Decode and verify a JWT token without making an API request."
                    size="lg"
                    footer={
                        <Button
                            onClick={() => {
                                setInspectModalOpen(false);
                                setInspectResult(null);
                                reset();
                            }}
                        >
                            Close
                        </Button>
                    }
                >
                    <form onSubmit={handleInspect} className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">
                                JWT Token
                            </label>
                            <textarea
                                value={data.token}
                                onChange={(e) => setData('token', e.target.value)}
                                rows={4}
                                className="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm font-mono"
                                placeholder="eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
                            />
                            {errors.token && (
                                <p className="mt-1 text-sm text-red-600">{errors.token}</p>
                            )}
                        </div>

                        <Button
                            type="submit"
                            isLoading={isInspecting}
                            disabled={!data.token}
                        >
                            Inspect Token
                        </Button>

                        {inspectResult && (
                            <div className="mt-4 space-y-4 border-t border-gray-200 pt-4">
                                <div className="flex items-center space-x-2">
                                    <span className="text-sm font-medium text-gray-700">Status:</span>
                                    {inspectResult.valid ? (
                                        <Badge variant="success">Valid</Badge>
                                    ) : inspectResult.expired ? (
                                        <Badge variant="warning">Expired</Badge>
                                    ) : inspectResult.revoked ? (
                                        <Badge variant="error">Revoked</Badge>
                                    ) : (
                                        <Badge variant="error">Invalid</Badge>
                                    )}
                                </div>

                                {inspectResult.error && (
                                    <div className="bg-red-50 border border-red-200 rounded-md p-3">
                                        <p className="text-sm text-red-700">{inspectResult.error}</p>
                                    </div>
                                )}

                                <div>
                                    <label className="block text-xs font-medium text-gray-500 uppercase mb-1">Header</label>
                                    <pre className="bg-gray-900 text-green-400 p-3 rounded-md overflow-x-auto text-xs">
                                        {JSON.stringify(inspectResult.header, null, 2)}
                                    </pre>
                                </div>

                                <div>
                                    <label className="block text-xs font-medium text-gray-500 uppercase mb-1">Payload</label>
                                    <pre className="bg-gray-900 text-blue-400 p-3 rounded-md overflow-x-auto text-xs">
                                        {JSON.stringify(inspectResult.payload, null, 2)}
                                    </pre>
                                </div>
                            </div>
                        )}
                    </form>
                </Modal>

                {/* Delete Confirmation Modal */}
                <ConfirmModal
                    isOpen={deleteModalOpen}
                    onClose={() => {
                        setDeleteModalOpen(false);
                        setTokenToDelete(null);
                    }}
                    onConfirm={handleConfirmDelete}
                    title="Revoke Token"
                    description="Are you sure you want to revoke this access token? The application using this token will need to re-authenticate."
                    confirmText="Revoke"
                    isLoading={isDeleting}
                    variant="warning"
                />
            </div>
        </DashboardLayout>
    );
}
