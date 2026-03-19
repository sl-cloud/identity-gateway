import { usePage } from '@inertiajs/react';
import DemoLayout from '../../layouts/DemoLayout';
import FlowStepIndicator from '../../components/demo/FlowStepIndicator';
import CodeSnippetBlock from '../../components/demo/CodeSnippetBlock';
import { DemoClient, DemoEndpoints } from './types';

interface PkceFlowProps {
    endpoints: DemoEndpoints;
    clients: DemoClient[];
    [key: string]: unknown;
}

export default function PkceFlow() {
    const { endpoints, clients } = usePage<PkceFlowProps>().props;
    const publicClient = clients.find((client) => client.type === 'public') ?? null;

    const challengeScript = `code_verifier=$(openssl rand -base64 64 | tr -d '=+/\\n' | cut -c1-64)\ncode_challenge=$(printf '%s' "$code_verifier" | openssl dgst -binary -sha256 | openssl base64 -A | tr '+/' '-_' | tr -d '=')\necho "Verifier: $code_verifier"\necho "Challenge: $code_challenge"`;

    const authorizeExample = `${endpoints.authorization}?response_type=code&client_id=${publicClient?.id ?? 'PUBLIC_CLIENT_ID'}&redirect_uri=${encodeURIComponent(publicClient?.redirect ?? 'http://localhost:8000/demo/callback')}&scope=user:read&state=pkce-state&code_challenge=CODE_CHALLENGE&code_challenge_method=S256`;

    const tokenRequest = `curl -X POST ${endpoints.token} \\
  -H "Content-Type: application/x-www-form-urlencoded" \\
  -d "grant_type=authorization_code" \\
  -d "client_id=${publicClient?.id ?? 'PUBLIC_CLIENT_ID'}" \\
  -d "redirect_uri=${publicClient?.redirect ?? 'http://localhost:8000/demo/callback'}" \\
  -d "code=AUTHORIZATION_CODE" \\
  -d "code_verifier=CODE_VERIFIER"`;

    return (
        <DemoLayout
            title="PKCE Flow"
            subtitle="Authorization Code flow hardened for public clients that cannot keep a secret"
        >
            <div className="space-y-6">
                <FlowStepIndicator
                    steps={[
                        { id: 'generate', title: 'Generate PKCE Pair', description: 'Create verifier and S256 challenge' },
                        { id: 'authorize', title: 'Authorization Redirect', description: 'Send challenge to /oauth/authorize' },
                        { id: 'exchange', title: 'Code Exchange', description: 'Send verifier to /oauth/token' },
                        { id: 'validate', title: 'Server Validation', description: 'Server hashes verifier and compares challenge' },
                    ]}
                />

                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 className="text-lg font-bold text-slate-900">Why PKCE Matters</h2>
                    <ul className="mt-3 list-disc space-y-2 pl-5 text-sm text-slate-700">
                        <li>Prevents code interception attacks in mobile and SPA environments.</li>
                        <li>Removes dependency on client secrets for public clients.</li>
                        <li>Required for modern OAuth app security baselines.</li>
                    </ul>
                </section>

                <CodeSnippetBlock title="Step 1: Generate code_verifier + code_challenge (S256)" language="bash" code={challengeScript} />
                <CodeSnippetBlock title="Step 2: Redirect with code_challenge" language="bash" code={authorizeExample} />
                <CodeSnippetBlock title="Step 3: Exchange code with code_verifier" language="bash" code={tokenRequest} />
            </div>
        </DemoLayout>
    );
}
