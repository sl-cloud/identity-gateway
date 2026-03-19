import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import DemoLayout from '../../layouts/DemoLayout';
import { Badge } from '../../components/ui/Badge';
import { Button } from '../../components/ui/Button';
import { ConfirmModal } from '../../components/ui/Modal';
import { DemoEndpoints } from './types';
import toast from 'react-hot-toast';

interface DemoClient {
    id: string;
    name: string;
    secret: string | null;
    redirect: string;
    type: 'confidential' | 'public';
    created_at: string;
}

interface NewClient {
    id: string;
    name: string;
    secret: string | null;
}

interface FlashMessages {
    newClient?: NewClient;
    success?: string;
}

interface ClientManagerProps {
    clients: DemoClient[];
    endpoints: DemoEndpoints;
    flash?: FlashMessages;
    [key: string]: unknown;
}

export default function ClientManager() {
    const { clients, flash } = usePage<ClientManagerProps>().props;

    const [name, setName] = useState('');
    const [redirect, setRedirect] = useState('http://localhost/callback');
    const [confidential, setConfidential] = useState(true);
    const [isCreating, setIsCreating] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const [revokeModalOpen, setRevokeModalOpen] = useState(false);
    const [clientToRevoke, setClientToRevoke] = useState<DemoClient | null>(null);
    const [isRevoking, setIsRevoking] = useState(false);

    const [copiedField, setCopiedField] = useState<string | null>(null);

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        setIsCreating(true);
        setErrors({});

        router.post('/demo/clients', { name, redirect, confidential }, {
            onSuccess: () => {
                setName('');
                setRedirect('http://localhost/callback');
                setConfidential(true);
            },
            onError: (errs) => setErrors(errs),
            onFinish: () => setIsCreating(false),
        });
    };

    const handleRevoke = () => {
        if (!clientToRevoke) return;
        setIsRevoking(true);

        router.delete(`/demo/clients/${clientToRevoke.id}`, {
            onSuccess: () => toast.success('Client revoked'),
            onFinish: () => {
                setIsRevoking(false);
                setRevokeModalOpen(false);
                setClientToRevoke(null);
            },
        });
    };

    const copyToClipboard = (text: string, field: string) => {
        navigator.clipboard.writeText(text);
        setCopiedField(field);
        setTimeout(() => setCopiedField(null), 2000);
    };

    return (
        <DemoLayout
            title="Client Manager"
            subtitle="Create and manage OAuth 2.0 demo clients"
        >
            <div className="space-y-6">
                {/* New client credentials flash */}
                {flash?.newClient && (
                    <div className="rounded-xl border border-green-200 bg-green-50 p-5 shadow-sm">
                        <h3 className="text-sm font-semibold text-green-800">Client Created Successfully</h3>
                        <p className="mt-1 text-xs text-green-700">
                            Save the client secret now — it won't be shown again in plaintext.
                        </p>
                        <div className="mt-3 space-y-2">
                            <CopyField
                                label="Client ID"
                                value={flash.newClient.id}
                                copied={copiedField === 'new-id'}
                                onCopy={() => copyToClipboard(flash.newClient!.id, 'new-id')}
                            />
                            {flash.newClient.secret && (
                                <CopyField
                                    label="Client Secret"
                                    value={flash.newClient.secret}
                                    copied={copiedField === 'new-secret'}
                                    onCopy={() => copyToClipboard(flash.newClient!.secret!, 'new-secret')}
                                />
                            )}
                        </div>
                    </div>
                )}

                {flash?.success && (
                    <div className="rounded-xl border border-green-200 bg-green-50 p-4 text-sm text-green-800 shadow-sm">
                        {flash.success}
                    </div>
                )}

                {/* Create form */}
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-lg font-bold text-slate-900">Create New Client</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Clients are prefixed with "Demo" and visible across all demo pages.
                    </p>

                    <form onSubmit={handleCreate} className="mt-4 grid gap-4 sm:grid-cols-2">
                        <div>
                            <label className="block text-sm font-medium text-slate-700">
                                Client Name <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="text"
                                value={name}
                                onChange={(e) => setName(e.target.value)}
                                placeholder="e.g. Mobile App"
                                required
                                className={`mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm ${errors.name ? 'border-red-300' : ''}`}
                            />
                            {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                            <p className="mt-1 text-xs text-slate-400">Will be saved as "Demo {name || '...'}"</p>
                        </div>

                        <div>
                            <label className="block text-sm font-medium text-slate-700">
                                Redirect URI <span className="text-red-500">*</span>
                            </label>
                            <input
                                type="url"
                                value={redirect}
                                onChange={(e) => setRedirect(e.target.value)}
                                required
                                className={`mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 sm:text-sm ${errors.redirect ? 'border-red-300' : ''}`}
                            />
                            {errors.redirect && <p className="mt-1 text-xs text-red-600">{errors.redirect}</p>}
                        </div>

                        <div className="sm:col-span-2">
                            <label className="block text-sm font-medium text-slate-700">Client Type</label>
                            <div className="mt-2 flex gap-4">
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="confidential"
                                        checked={confidential}
                                        onChange={() => setConfidential(true)}
                                        className="text-cyan-600 focus:ring-cyan-500"
                                    />
                                    <span className="text-sm text-slate-700">Confidential</span>
                                    <span className="text-xs text-slate-400">(has secret — server apps)</span>
                                </label>
                                <label className="flex items-center gap-2 cursor-pointer">
                                    <input
                                        type="radio"
                                        name="confidential"
                                        checked={!confidential}
                                        onChange={() => setConfidential(false)}
                                        className="text-cyan-600 focus:ring-cyan-500"
                                    />
                                    <span className="text-sm text-slate-700">Public</span>
                                    <span className="text-xs text-slate-400">(no secret — SPAs, mobile)</span>
                                </label>
                            </div>
                        </div>

                        <div className="sm:col-span-2">
                            <Button type="submit" isLoading={isCreating}>
                                Create Client
                            </Button>
                        </div>
                    </form>
                </section>

                {/* Clients list */}
                <section className="rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-200 px-5 py-4">
                        <h2 className="text-lg font-bold text-slate-900">Demo Clients</h2>
                        <p className="mt-0.5 text-sm text-slate-500">
                            {clients.length} client{clients.length !== 1 ? 's' : ''} available
                        </p>
                    </div>

                    {clients.length === 0 ? (
                        <div className="px-5 py-12 text-center">
                            <p className="text-sm text-slate-500">No demo clients yet. Create one above.</p>
                        </div>
                    ) : (
                        <div className="divide-y divide-slate-100">
                            {clients.map((client) => (
                                <div key={client.id} className="px-5 py-4">
                                    <div className="flex items-start justify-between gap-4">
                                        <div className="min-w-0 flex-1">
                                            <div className="flex items-center gap-2">
                                                <h3 className="text-sm font-semibold text-slate-900 truncate">
                                                    {client.name}
                                                </h3>
                                                <Badge
                                                    variant={client.type === 'confidential' ? 'info' : 'success'}
                                                    size="sm"
                                                >
                                                    {client.type}
                                                </Badge>
                                            </div>
                                            <div className="mt-2 grid gap-1.5">
                                                <CopyField
                                                    label="Client ID"
                                                    value={client.id}
                                                    copied={copiedField === `id-${client.id}`}
                                                    onCopy={() => copyToClipboard(client.id, `id-${client.id}`)}
                                                />
                                                {client.secret && (
                                                    <CopyField
                                                        label="Secret"
                                                        value={client.secret}
                                                        copied={copiedField === `secret-${client.id}`}
                                                        onCopy={() => copyToClipboard(client.secret!, `secret-${client.id}`)}
                                                    />
                                                )}
                                                <div className="flex items-center gap-2 text-xs">
                                                    <span className="font-medium text-slate-500 w-16">Redirect</span>
                                                    <code className="text-slate-600 truncate">{client.redirect}</code>
                                                </div>
                                            </div>
                                        </div>
                                        <button
                                            onClick={() => {
                                                setClientToRevoke(client);
                                                setRevokeModalOpen(true);
                                            }}
                                            className="shrink-0 rounded-md px-2.5 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 transition"
                                        >
                                            Revoke
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </section>
            </div>

            <ConfirmModal
                isOpen={revokeModalOpen}
                onClose={() => {
                    setRevokeModalOpen(false);
                    setClientToRevoke(null);
                }}
                onConfirm={handleRevoke}
                title="Revoke Client"
                description={`Revoke "${clientToRevoke?.name}"? All its active tokens will also be revoked.`}
                confirmText="Revoke"
                isLoading={isRevoking}
                variant="danger"
            />
        </DemoLayout>
    );
}

function CopyField({
    label,
    value,
    copied,
    onCopy,
}: {
    label: string;
    value: string;
    copied: boolean;
    onCopy: () => void;
}) {
    return (
        <div className="flex items-center gap-2 text-xs">
            <span className="font-medium text-slate-500 w-16">{label}</span>
            <code className="flex-1 truncate rounded bg-slate-100 px-2 py-1 font-mono text-slate-700">
                {value}
            </code>
            <button
                onClick={onCopy}
                className="shrink-0 rounded px-2 py-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition"
                title="Copy"
            >
                {copied ? (
                    <svg className="h-3.5 w-3.5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                ) : (
                    <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z" />
                    </svg>
                )}
            </button>
        </div>
    );
}
