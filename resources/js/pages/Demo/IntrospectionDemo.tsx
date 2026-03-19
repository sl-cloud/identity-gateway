import { useEffect, useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';
import toast from 'react-hot-toast';
import DemoLayout from '../../layouts/DemoLayout';
import RequestResponsePanel from '../../components/demo/RequestResponsePanel';
import { Button } from '../../components/ui/Button';
import { DemoClient, DemoEndpoints } from './types';
import { basicAuth } from './utils';
import { useDemoFetch } from './useDemoFetch';

interface IntrospectionDemoProps {
    endpoints: DemoEndpoints;
    clients: DemoClient[];
    [key: string]: unknown;
}

export default function IntrospectionDemo() {
    const { endpoints, clients } = usePage<IntrospectionDemoProps>().props;

    const confidentialClients = useMemo(() => clients.filter((client) => client.secret), [clients]);
    const [selectedClientId, setSelectedClientId] = useState<string>(confidentialClients[0]?.id ?? '');
    const [token, setToken] = useState('');
    const { requestLog, responseLog, isLoading, execute } = useDemoFetch();

    useEffect(() => {
        setToken(localStorage.getItem('demo.last_token') ?? '');
    }, []);

    const selectedClient = confidentialClients.find((client) => client.id === selectedClientId) ?? null;

    const introspect = async () => {
        if (!selectedClient) {
            toast.error('No confidential client available');
            return;
        }

        if (!token.trim()) {
            toast.error('Provide a token to introspect');
            return;
        }

        try {
            const payload = { token };
            const headers = {
                'Content-Type': 'application/x-www-form-urlencoded',
                Authorization: `Basic ${basicAuth(selectedClient)}`,
                Accept: 'application/json',
            };

            await execute(
                endpoints.introspection,
                { method: 'POST', headers, body: new URLSearchParams(payload) },
                { method: 'POST', url: endpoints.introspection, headers, body: payload },
            );
            toast.success('Introspection request complete');
        } catch {
            toast.error('Introspection request failed');
        }
    };

    return (
        <DemoLayout
            title="Token Introspection"
            subtitle="RFC 7662 endpoint demo with HTTP Basic authenticated client"
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
                        <Button isLoading={isLoading} onClick={introspect}>Send Introspection Request</Button>
                        <Button variant="secondary" onClick={() => setToken(localStorage.getItem('demo.last_token') ?? '')}>
                            Load Last Playground Token
                        </Button>
                    </div>
                </section>

                <RequestResponsePanel request={requestLog} response={responseLog} title="/oauth/introspect Exchange" />
            </div>
        </DemoLayout>
    );
}
