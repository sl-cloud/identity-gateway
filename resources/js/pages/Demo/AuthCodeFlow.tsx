import { usePage } from '@inertiajs/react';
import DemoLayout from '../../layouts/DemoLayout';
import FlowStepIndicator from '../../components/demo/FlowStepIndicator';
import CodeSnippetBlock from '../../components/demo/CodeSnippetBlock';
import { DemoClient, DemoEndpoints } from './types';

interface AuthCodeFlowProps {
    endpoints: DemoEndpoints;
    clients: DemoClient[];
    [key: string]: unknown;
}

export default function AuthCodeFlow() {
    const { endpoints, clients } = usePage<AuthCodeFlowProps>().props;
    const confidentialClient = clients.find((client) => client.type === 'confidential') ?? null;

    const authorizeExample = `${endpoints.authorization}?response_type=code&client_id=${confidentialClient?.id ?? 'CLIENT_ID'}&redirect_uri=${encodeURIComponent(confidentialClient?.redirect ?? 'http://localhost:8000/demo/callback')}&scope=user:read%20resources:read&state=demo-state`;

    const tokenRequest = `curl -X POST ${endpoints.token} \\
  -H "Content-Type: application/x-www-form-urlencoded" \\
  -d "grant_type=authorization_code" \\
  -d "client_id=${confidentialClient?.id ?? 'CLIENT_ID'}" \\
  -d "client_secret=${confidentialClient?.secret ?? 'CLIENT_SECRET'}" \\
  -d "redirect_uri=${confidentialClient?.redirect ?? 'http://localhost:8000/demo/callback'}" \\
  -d "code=AUTHORIZATION_CODE"`;

    return (
        <DemoLayout
            title="Authorization Code Flow"
            subtitle="Browser-based user authentication with back-channel code exchange"
        >
            <div className="space-y-6">
                <FlowStepIndicator
                    steps={[
                        { id: 'authorize', title: 'User Authorization', description: 'Redirect browser to /oauth/authorize' },
                        { id: 'consent', title: 'Consent Approval', description: 'User approves requested scopes' },
                        { id: 'code', title: 'Authorization Code', description: 'Client receives code at redirect URI' },
                        { id: 'token', title: 'Token Exchange', description: 'Client exchanges code for JWT token' },
                    ]}
                />

                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-lg font-bold text-slate-900">Key Characteristics</h2>
                    <ul className="mt-3 list-disc space-y-2 pl-5 text-sm text-slate-700">
                        <li>Best for server-side web applications that can protect a client secret.</li>
                        <li>User authenticates and approves consent in the browser.</li>
                        <li>Authorization code is short-lived and exchanged server-to-server.</li>
                    </ul>
                </section>

                <CodeSnippetBlock title="Step 1: Redirect User to Authorization Endpoint" language="bash" code={authorizeExample} />
                <CodeSnippetBlock title="Step 2: Exchange Code at Token Endpoint" language="bash" code={tokenRequest} />
            </div>
        </DemoLayout>
    );
}
