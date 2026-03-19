import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import DashboardLayout from '../../../layouts/DashboardLayout';
import { Card } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { Badge } from '../../../components/ui/Badge';
import { Table, TableHead, TableBody, TableRow, TableHeader, TableCell, EmptyState } from '../../../components/ui/Table';
import { ConfirmModal } from '../../../components/ui/Modal';

interface Client {
    id: string;
    name: string;
    redirect: string;
    secret: string | null;
    is_confidential: boolean;
    personal_access_client: boolean;
    password_client: boolean;
    created_at: string;
    updated_at: string;
}

interface ClientsIndexProps {
    clients: Client[];
    [key: string]: unknown;
}

export default function ClientsIndex() {
    const { clients } = usePage<ClientsIndexProps>().props;
    const [deleteModalOpen, setDeleteModalOpen] = useState(false);
    const [clientToDelete, setClientToDelete] = useState<Client | null>(null);
    const [isDeleting, setIsDeleting] = useState(false);

    const handleDeleteClick = (client: Client) => {
        setClientToDelete(client);
        setDeleteModalOpen(true);
    };

    const handleConfirmDelete = () => {
        if (!clientToDelete) return;

        setIsDeleting(true);
        router.delete(`/dashboard/clients/${clientToDelete.id}`, {
            onFinish: () => {
                setIsDeleting(false);
                setDeleteModalOpen(false);
                setClientToDelete(null);
            },
        });
    };

    return (
        <DashboardLayout>
            <div className="space-y-6">
                {/* Header */}
                <div className="sm:flex sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">OAuth Clients</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Manage your OAuth 2.0 clients for authentication flows.
                        </p>
                    </div>
                    <div className="mt-4 sm:mt-0">
                        <Button onClick={() => router.visit('/dashboard/clients/create')}>
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                            </svg>
                            Create Client
                        </Button>
                    </div>
                </div>

                {/* Clients Table */}
                <Card>
                    {clients.length === 0 ? (
                        <EmptyState
                            title="No OAuth clients"
                            description="Get started by creating a new OAuth client."
                            icon={
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={1.5} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                            }
                            action={
                                <Button onClick={() => router.visit('/dashboard/clients/create')}>
                                    Create Client
                                </Button>
                            }
                        />
                    ) : (
                        <Table>
                            <TableHead>
                                <TableRow>
                                    <TableHeader>Name</TableHeader>
                                    <TableHeader>Type</TableHeader>
                                    <TableHeader>Redirect URI</TableHeader>
                                    <TableHeader>Client ID</TableHeader>
                                    <TableHeader>Created</TableHeader>
                                    <TableHeader className="text-right">Actions</TableHeader>
                                </TableRow>
                            </TableHead>
                            <TableBody>
                                {clients.map((client) => (
                                    <TableRow key={client.id}>
                                        <TableCell>
                                            <div className="font-medium text-gray-900">{client.name}</div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={client.is_confidential ? 'info' : 'success'}>
                                                {client.is_confidential ? 'Confidential' : 'Public'}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <code className="text-xs bg-gray-100 px-2 py-1 rounded">
                                                {client.redirect}
                                            </code>
                                        </TableCell>
                                        <TableCell>
                                            <code className="text-xs text-gray-600">{client.id}</code>
                                        </TableCell>
                                        <TableCell>
                                            {new Date(client.created_at).toLocaleDateString()}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex justify-end space-x-2">
                                                <button
                                                    onClick={() => router.visit(`/dashboard/clients/${client.id}`)}
                                                    className="text-indigo-600 hover:text-indigo-900"
                                                    title="View details"
                                                >
                                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                </button>
                                                <button
                                                    onClick={() => handleDeleteClick(client)}
                                                    className="text-red-600 hover:text-red-900"
                                                    title="Revoke client"
                                                >
                                                    <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </Card>

                {/* Delete Confirmation Modal */}
                <ConfirmModal
                    isOpen={deleteModalOpen}
                    onClose={() => {
                        setDeleteModalOpen(false);
                        setClientToDelete(null);
                    }}
                    onConfirm={handleConfirmDelete}
                    title="Revoke OAuth Client"
                    description={`Are you sure you want to revoke "${clientToDelete?.name}"? This will also revoke all active tokens for this client. This action cannot be undone.`}
                    confirmText="Revoke"
                    isLoading={isDeleting}
                    variant="danger"
                />
            </div>
        </DashboardLayout>
    );
}
