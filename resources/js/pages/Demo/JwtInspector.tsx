import { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { createLocalJWKSet, type JSONWebKeySet, jwtVerify } from 'jose';
import toast from 'react-hot-toast';
import DemoLayout from '../../layouts/DemoLayout';
import JwtDecodePanel from '../../components/demo/JwtDecodePanel';
import RequestResponsePanel from '../../components/demo/RequestResponsePanel';
import { Button } from '../../components/ui/Button';
import { useDemoFetch } from './useDemoFetch';

interface JwtInspectorProps {
    issuer: string;
    audience: string;
    jwks_endpoint: string;
    [key: string]: unknown;
}

export default function JwtInspector() {
    const { issuer, audience, jwks_endpoint } = usePage<JwtInspectorProps>().props;

    const [token, setToken] = useState('');
    const [isValid, setIsValid] = useState<boolean | null>(null);
    const [error, setError] = useState<string | null>(null);
    const { requestLog, responseLog, isLoading, execute } = useDemoFetch();

    useEffect(() => {
        setToken(localStorage.getItem('demo.last_token') ?? '');
    }, []);

    const verifyToken = async () => {
        if (!token.trim()) {
            toast.error('Paste a token first');
            return;
        }

        setError(null);

        try {
            const headers = { Accept: 'application/json' };
            const body = await execute(
                jwks_endpoint,
                { headers },
                { method: 'GET', url: jwks_endpoint, headers, body: null },
            );

            if (typeof body !== 'object' || body === null || !('keys' in body)) {
                throw new Error('JWKS endpoint did not return a valid key set');
            }

            const keySet = createLocalJWKSet(body as JSONWebKeySet);
            await jwtVerify(token, keySet, { issuer, audience });

            setIsValid(true);
            setError(null);
            toast.success('Signature and claims are valid');
        } catch (caughtError) {
            const message = caughtError instanceof Error ? caughtError.message : 'Token validation failed';
            setIsValid(false);
            setError(message);
            toast.error('Token verification failed');
        }
    };

    return (
        <DemoLayout
            title="JWT Inspector"
            subtitle="Decode and verify JWTs using JOSE and your live JWKS endpoint"
        >
            <div className="space-y-6">
                <section className="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <label className="mb-2 block text-sm font-semibold text-slate-700">Paste JWT</label>
                    <textarea
                        value={token}
                        onChange={(event) => setToken(event.target.value)}
                        placeholder="eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9..."
                        rows={6}
                        className="w-full rounded-md border-slate-300 font-mono text-xs shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                    />
                    <div className="mt-3 flex flex-wrap gap-3">
                        <Button onClick={verifyToken} isLoading={isLoading}>Verify Signature</Button>
                        <Button
                            variant="secondary"
                            onClick={() => setToken(localStorage.getItem('demo.last_token') ?? '')}
                        >
                            Load Last Playground Token
                        </Button>
                    </div>
                    <p className="mt-3 text-xs text-slate-500">Issuer: {issuer} | Audience: {audience}</p>
                </section>

                <JwtDecodePanel token={token} valid={isValid} error={error} />

                <RequestResponsePanel request={requestLog} response={responseLog} title="JWKS Fetch" />
            </div>
        </DemoLayout>
    );
}
