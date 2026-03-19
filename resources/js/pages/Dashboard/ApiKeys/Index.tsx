import React, { useState } from 'react';
import { router, usePage, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../layouts/DashboardLayout';
import { Card, CardHeader } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { Badge } from '../../../components/ui/Badge';
import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell, EmptyState } from '../../../components/ui/Table';
import { Modal, ConfirmModal } from '../../../components/ui/Modal';
import { Input } from '../../../components/ui/Input';

interface ApiKey {
    id: string;
    name: string;
    prefix: string;
    scopes: string[];
    is_active: boolean;
    is_revoked: boolean;
    is_expired: boolean;
    last_used_at: string | null;
    expires_at: string | null;
    created_at: string;
}

interface ApiKeysIndexProps {
    apiKeys: ApiKey[];
    flash?: {
        success?: string;
        newApiKey?: string;
    };
}

export default function ApiKeysIndex() {
    const { apiKeys, flash } = usePage<ApiKeysIndexProps>().props;

    const [createModalOpen, setCreateModalOpen] = useState(false);
    const [newKeyModalOpen, setNewKeyModalOpen] = useState(false);
    const [newApiKey, setNewApiKey] = useState<string | null>(null);
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [keyToDelete, setKeyToDelete] = useState<ApiKey | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        scopes: [] as string[],
        expires_in_days: '30',
    });

    const availableScopes = [
        { value: 'user:read', label: 'user:read - Read your profile information' },
        { value: 'users:read', label: 'users:read - Read user directory (admin only)' },
        { value: 'resources:read', label: 'resources:read - Read protected resources' },
        { value: 'resources:write', label: 'resources:write - Create and modify resources' },
    ];

    // Show new API key modal if flash message exists
    React.useEffect(() => {
        if (flash?.newApiKey) {
            setNewApiKey(flash.newApiKey);
            setNewKeyModalOpen(true);
        }
    }, [flash?.newApiKey]);

    const handleCreateSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/dashboard/api-keys', {
            onSuccess: () => {
                setCreateModalOpen(false);
                reset();
            },
        });
    };

    const handleDeleteClick = (key: ApiKey) => {
        setKeyToDelete(key);
        setDeleteModalOpen(true);
    };

    const handleConfirmDelete = () => {
        if (!keyToDelete) return;

        setIsDeleting(true);
        router.delete(`/dashboard/api-keys/${keyToDelete.id}`, {
            onFinish: () => {
                setIsDeleting(false);
                setDeleteModalOpen(false);
                setKeyToDelete(null);
            },
        });
    };

    const toggleScope = (scope: string) => {
        const newScopes = data.scopes.includes(scope)
            ? data.scopes.filter((s) => s !== scope)
            : [...data.scopes, scope];
        setData('scopes', newScopes);
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
    };

    const getStatusBadge = (key: ApiKey) => {
        if (key.is_revoked) {
            return <Badge variant="error">Revoked</Badge>;
        }
        if (key.is_expired) {
            return <Badge variant="warning">Expired</Badge>;
        }
        if (key.is_active) {
            return <Badge variant="success">Active</Badge>;
        }
        return <Badge variant="neutral">Inactive</Badge>;
    };

    return (
        <DashboardLayout>
            <div className="space-y-6">
                {/* Header */}
                <div className="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">API Keys</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Manage API keys for programmatic access to the Identity Gateway API.
                        </p>
                    </div>
                    <div className="mt-4 sm:mt-0">
                        <Button onClick={() => setCreateModalOpen(true)}>
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Generate API Key
                        </Button>
                    </div>
                </div>

                {/* API Keys Table */}
                <Card>
                    {apiKeys.length === 0 ? (
                        <EmptyState
                            title="No API keys"
                            description="Get started by generating a new API key."
                            icon={
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                                </svg>
                            }
                            action={
                                <Button onClick={() => setCreateModalOpen(true)}>
                                    Generate API Key
                                </Button>
                            }
                        />
                    ) : (
                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableHeader>Name</TableHeader>
                                    <TableHeader>Key</TableHeader>
                                    <TableHeader>Status</TableHeader>
                                    <TableHeader>Scopes</TableHeader>
                                    <TableHeader>Last Used</TableHeader>
                                    <TableHeader>Expires</TableHeader>
                                    <TableHeader className="text-right">Actions</TableHeader>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {apiKeys.map((key) => (
                                    <TableRow key={key.id}>
                                        <TableCell>
                                            <div className="font-medium text-gray-900">{key.name}</div>
                                        </TableCell>
                                        <TableCell>
                                            <code className="text-xs bg-gray-100 px-2 py-1 rounded font-mono">
                                                {key.prefix}...
                                            </code>
                                        </TableCell>
                                        <TableCell>{getStatusBadge(key)}</TableCell>
                                        <TableCell>
                                            <div className="flex flex-wrap gap-1 max-w-xs">
                                                {key.scopes.map((scope) => (
                                                    <Badge key={scope} variant="default" size="sm">
                                                        {scope}
                                                    </Badge>
                                                ))}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {key.last_used_at
                                                ? new Date(key.last_used_at).toLocaleDateString()
                                                : 'Never'}
                                        </TableCell>
                                        <TableCell>
                                            {key.expires_at
                                                ? new Date(key.expires_at).toLocaleDateString()
                                                : 'Never'}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {!key.is_revoked && (
                                                <button
                                                    onClick={() => handleDeleteClick(key)}
                                                    className="text-red-600 hover:text-red-900"
                                                    title="Revoke key"
                                                >
                                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </Card>

                {/* Create API Key Modal */}
                <Modal
                    isOpen={createModalOpen}
                    onClose={() => {
                        setCreateModalOpen(false);
                        reset();
                    }}
                    title="Generate API Key"
                    description="Create a new API key for programmatic access. The key will only be shown once."
                    size="md"
                    footer={
                        <>
                            <Button
                                variant="secondary"
                                onClick={() => {
                                    setCreateModalOpen(false);
                                    reset();
                                }}
                                disabled={processing}
                            >
                                Cancel
                            </Button>
                            <Button
                                onClick={handleCreateSubmit}
                                isLoading={processing}
                                disabled={data.scopes.length === 0}
                            >
                                Generate Key
                            </Button>
                        </>
                    }
                >
                    <form onSubmit={handleCreateSubmit} className="space-y-4">
                        <Input
                            label="Key Name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            error={errors.name}
                            placeholder="Production API Key"
                            required
                        />

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Scopes <span className="text-red-500">*</span>
                            </label>
                            <div className="space-y-2">
                                {availableScopes.map((scope) => (
                                    <label key={scope.value} className="flex items-center">
                                        <input
                                            type="checkbox"
                                            checked={data.scopes.includes(scope.value)}
                                            onChange={() => toggleScope(scope.value)}
                                            className="h-4 w-4 text-indigo-600 border-gray-300 rounded"
                                        />
                                        <span className="ml-2 text-sm text-gray-700">{scope.label}</span>
                                    </label>
                                ))}
                            </div>
                            {errors.scopes && (
                                <p className="mt-1 text-sm text-red-600">{errors.scopes}</p>
                            )}
                        </div>

                        <Input
                            label="Expires In (Days)"
                            type="number"
                            min="1"
                            max="365"
                            value={data.expires_in_days}
                            onChange={(e) => setData('expires_in_days', e.target.value)}
                            error={errors.expires_in_days}
                            helperText="Leave empty for no expiration (max 365 days)"
                        />
                    </form>
                </Modal>

                {/* New API Key Display Modal */}
                <Modal
                    isOpen={newKeyModalOpen}
                    onClose={() => {
                        setNewKeyModalOpen(false);
                        setNewApiKey(null);
                    }}
                    title="API Key Generated"
                    description="Copy this key now. You won't be able to see it again!"
                    size="lg"
                    footer={
                        <Button
                            onClick={() => {
                                setNewKeyModalOpen(false);
                                setNewApiKey(null);
                            }}
                        >
                            I've Saved The Key
                        </Button>
                    }
                >
                    <div className="space-y-4">
                        <div className="bg-yellow-50 border border-yellow-200 rounded-md p-4">
                            <div className="flex">
                                <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                    <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                </svg>
                                <div className="ml-3">
                                    <p className="text-sm text-yellow-700">
                                        This is the only time you will see this key. Store it securely.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Your API Key</label>
                            <div className="flex items-center space-x-2">
                                <code className="flex-1 bg-gray-900 text-green-400 px-4 py-3 rounded-md font-mono text-sm break-all">
                                    {newApiKey}
                                </code>
                                <button
                                    onClick={() => newApiKey && copyToClipboard(newApiKey)}
                                    className="p-3 bg-gray-100 rounded-md hover:bg-gray-200"
                                    title="Copy to clipboard"
                                >
                                    <svg className="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div className="bg-gray-50 rounded-md p-3">
                            <p className="text-xs text-gray-600">
                                Use this key in the <code>X-Api-Key</code> header when making API requests:
                            </p>
                            <code className="block mt-2 text-xs bg-gray-800 text-gray-200 px-2 py-2 rounded font-mono">
                                curl -H "X-Api-Key: {newApiKey?.slice(0, 20)}..." /api/v1/me
                            </code>
                        </div>
                    </div>
                </Modal>

                {/* Delete Confirmation Modal */}
                <ConfirmModal
                    isOpen={deleteModalOpen}
                    onClose={() => {
                        setDeleteModalOpen(false);
                        setKeyToDelete(null);
                    }}
                    onConfirm={handleConfirmDelete}
                    title="Revoke API Key"
                    description={`Are you sure you want to revoke "${keyToDelete?.name}"? This will immediately invalidate the key. This action cannot be undone.`}
                    confirmText="Revoke"
                    isLoading={isDeleting}
                    variant="danger"
                />
            </div>
        </DashboardLayout>
    );
}
