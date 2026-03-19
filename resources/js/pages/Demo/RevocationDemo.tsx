import { useEffect, useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';
import toast from 'react-hot-toast';
import DemoLayout from '../../layouts/DemoLayout';
import RequestResponsePanel from '../../components/demo/RequestResponsePanel';
import { Button } from '../../components/ui/Button';
import { DemoClient, DemoEndpoints } from './types';
import { basicAuth } from './utils';
import { useDemoFetch } from './useDemoFetch';

interface RevocationDemoProps {
    endpoints: DemoEndpoints;
    clients: DemoClient[];
    /** Display-only: shown as informational text, not used for interactive scope selection. */
    scopes: string[];
    [key: string]: unknown;
}

export default function RevocationDemo() {
    const { endpoints, clients, scopes } = usePage<RevocationDemoProps>().props;

    const confidentialClients = useMemo(() => clients.filter((client) => client.secret), [clients]);
    const [selectedClientId, setSelectedClientId] = useState<string>(confidentialClients[0]?.id ?? '');
    const [token, setToken] = useState('');
    const { requestLog, responseLog, isLoading, execute } = useDemoFetch();

    useEffect(() => {
        setToken(localStorage.getItem('demo.last_token') ?? '');
    }, []);

    const selectedClient = confidentialClients.find((client) => client.id === selectedClientId) ?? null;

    const revoke = async () => {
        if (!selectedClient) {
            toast.error('No confidential client available');
            return;
        }

        if (!token.trim()) {
            toast.error('Provide a token to revoke');
            return;
        }

        try {
            const payload = { token, token_type_hint: 'access_token' };
            const headers = {
                'Content-Type': 'application/x-www-form-urlencoded',
                Authorization: `Basic ${basicAuth(selectedClient)}`,
                Accept: 'application/json',
            };

            await execute(
                endpoints.revocation,
                { method: 'POST', headers, body: new URLSearchParams(payload) },
                { method: 'POST', url: endpoints.revocation, headers, body: payload },
            );
            toast.success('Revocation request sent');
        } catch {
            toast.error('Revocation request failed');
        }
    };

    return (
        <DemoLayout
            title="Token Revocation"
            subtitle="RFC 7009 revocation endpoint demo using confidential client authentication"
        >
            <div className="space-y-6">
                <section className="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-2">
                    <div>
                        <label className="mb-1 block text-sm font-semibold text-slate-700">Confidential Client</label>
                        <select
                            value={selectedClientId}
                            onChange={(event) => setSelectedClientId(event.target.value)}
                            className="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        >
                            {confidentialClients.map((client) => (
                                <option key={client.id} value={client.id}>
                                    {client.name}
                                </option>
                            ))}
                        </select>
                    </div>

                    <div>
                        <label className="mb-1 block text-sm font-semibold text-slate-700">Token</label>
                        <textarea
                            value={token}
                            onChange={(event) => setToken(event.target.value)}
                            rows={4}
                            className="w-full rounded-md border-slate-300 font-mono text-xs shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                        />
                    </div>

                    <div className="lg:col-span-2 flex flex-wrap gap-3">
                        <Button isLoading={isLoading} onClick={revoke}>Send Revocation Request</Button>
                        <Button variant="secondary" onClick={() => setToken(localStorage.getItem('demo.last_token') ?? '')}>
                            Load Last Playground Token
                        </Button>
                    </div>

                    <div className="lg:col-span-2 rounded-lg border border-slate-200 bg-slate-50 p-3 text-xs text-slate-600">
                        Available scopes in this environment: {scopes.join(', ')}
                    </div>
                </section>

                <RequestResponsePanel request={requestLog} response={responseLog} title="/oauth/revoke Exchange" />
            </div>
        </DemoLayout>
    );
}
