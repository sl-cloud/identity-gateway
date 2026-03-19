import { usePage } from '@inertiajs/react';
import DemoLayout from '../../layouts/DemoLayout';
import FlowStepIndicator from '../../components/demo/FlowStepIndicator';
import CodeSnippetBlock from '../../components/demo/CodeSnippetBlock';
import { DemoClient, DemoEndpoints } from './types';

interface ClientCredentialsFlowProps {
    endpoints: DemoEndpoints;
    clients: DemoClient[];
    [key: string]: unknown;
}

export default function ClientCredentialsFlow() {
    const { endpoints, clients } = usePage<ClientCredentialsFlowProps>().props;
    const confidentialClient = clients.find((client) => client.type === 'confidential') ?? null;

    const requestSnippet = `curl -X POST ${endpoints.token} \\
  -H "Content-Type: application/x-www-form-urlencoded" \\
  -d "grant_type=client_credentials" \\
  -d "client_id=${confidentialClient?.id ?? 'CLIENT_ID'}" \\
  -d "client_secret=${confidentialClient?.secret ?? 'CLIENT_SECRET'}" \\
  -d "scope=resources:read resources:write"`;

    const apiSnippet = `curl -X GET ${window.location.origin}/api/v1/resources \\
  -H "Authorization: Bearer ACCESS_TOKEN"`;

    return (
        <DemoLayout
            title="Client Credentials Flow"
            subtitle="Machine-to-machine access where no end-user context is required"
        >
            <div className="space-y-6">
                <FlowStepIndicator
                    steps={[
                        { id: 'authenticate', title: 'Client Authentication', description: 'Client posts ID + secret' },
                        { id: 'token', title: 'Token Issuance', description: 'Server returns scoped JWT token' },
                        { id: 'resource', title: 'Resource Access', description: 'Client calls protected API with Bearer token' },
                    ]}
                />

                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-lg font-bold text-slate-900">Usage Notes</h2>
                    <ul className="mt-3 list-disc space-y-2 pl-5 text-sm text-slate-700">
                        <li>Used for backend services, schedulers, and server-to-server integrations.</li>
                        <li>No browser redirect or user consent step is involved.</li>
                        <li>Scopes should be least-privilege and narrowly defined per service.</li>
                    </ul>
                </section>

                <CodeSnippetBlock title="Step 1: Request client credentials token" language="bash" code={requestSnippet} />
                <CodeSnippetBlock title="Step 2: Use token against Resource API" language="bash" code={apiSnippet} />
            </div>
        </DemoLayout>
    );
}
