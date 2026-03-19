import { useEffect, useMemo, useState } from 'react';
import { usePage } from '@inertiajs/react';
import toast from 'react-hot-toast';
import DemoLayout from '../../layouts/DemoLayout';
import FlowStepIndicator from '../../components/demo/FlowStepIndicator';
import RequestResponsePanel from '../../components/demo/RequestResponsePanel';
import JwtDecodePanel from '../../components/demo/JwtDecodePanel';
import ScopeSelector from '../../components/demo/ScopeSelector';
import { Button } from '../../components/ui/Button';
import { DemoClient, DemoEndpoints, DemoScope, RequestLog, ResponseLog } from './types';
import { parseResponse } from './utils';

type PlaygroundFlow = 'authorization_code' | 'pkce' | 'client_credentials';

interface PlaygroundProps {
    clients: DemoClient[];
    scopes: DemoScope[];
    endpoints: DemoEndpoints;
    demo_credentials: {
        email: string;
        password: string;
    };
    [key: string]: unknown;
}

function randomString(): string {
    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);

    return Array.from(bytes, (value) => value.toString(16).padStart(2, '0')).join('');
}

function toBase64Url(bytes: Uint8Array): string {
    let binary = '';
    bytes.forEach((byte) => {
        binary += String.fromCharCode(byte);
    });

    return btoa(binary).replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
}

async function pkceChallenge(verifier: string): Promise<string> {
    const hash = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(verifier));

    return toBase64Url(new Uint8Array(hash));
}

export default function Playground() {
    const { clients, scopes, endpoints, demo_credentials } = usePage<PlaygroundProps>().props;

    const defaultScopes = useMemo(() => scopes.filter((scope) => scope.is_default).map((scope) => scope.id), [scopes]);
    const [flow, setFlow] = useState<PlaygroundFlow>('authorization_code');
    const [selectedClientId, setSelectedClientId] = useState<string>(clients[0]?.id ?? '');
    const [selectedScopes, setSelectedScopes] = useState<string[]>(defaultScopes);
    const [requestLog, setRequestLog] = useState<RequestLog | null>(null);
    const [responseLog, setResponseLog] = useState<ResponseLog | null>(null);
    const [token, setToken] = useState<string>('');
    const [authorizationCode, setAuthorizationCode] = useState<string | null>(null);
    const [authorizationError, setAuthorizationError] = useState<string | null>(null);
    const [authorizationStarted, setAuthorizationStarted] = useState<boolean>(false);
    const [isLoading, setIsLoading] = useState<boolean>(false);

    useEffect(() => {
        const storedClient = localStorage.getItem('demo.playground.client');
        const storedFlow = localStorage.getItem('demo.playground.flow');
        const storedToken = localStorage.getItem('demo.playground.token');

        if (storedClient && clients.some((client) => client.id === storedClient)) {
            setSelectedClientId(storedClient);
        }

        if (storedFlow === 'authorization_code' || storedFlow === 'pkce' || storedFlow === 'client_credentials') {
            setFlow(storedFlow);
        }

        if (storedToken) {
            setToken(storedToken);
        }
    }, [clients]);

    useEffect(() => {
        localStorage.setItem('demo.playground.client', selectedClientId);
    }, [selectedClientId]);

    useEffect(() => {
        localStorage.setItem('demo.playground.flow', flow);
    }, [flow]);

    useEffect(() => {
        if (token) {
            localStorage.setItem('demo.playground.token', token);
            localStorage.setItem('demo.last_token', token);
        }
    }, [token]);

    useEffect(() => {
        const query = new URLSearchParams(window.location.search);
        const code = query.get('code');
        const state = query.get('state');
        const error = query.get('error_description') ?? query.get('error');
        const expectedState = localStorage.getItem('demo.playground.state');

        if (error) {
            setAuthorizationError(error);
            setAuthorizationStarted(true);
            return;
        }

        if (code) {
            if (state && expectedState && state !== expectedState) {
                setAuthorizationError('Returned state did not match request state.');
                return;
            }

            setAuthorizationCode(code);
            setAuthorizationStarted(true);
            toast.success('Authorization code received');
        }
    }, []);

    const selectedClient = useMemo(() => clients.find((client) => client.id === selectedClientId) ?? null, [clients, selectedClientId]);

    const currentStep = useMemo(() => {
        if (flow === 'client_credentials') {
            if (token) {
                return 3;
            }

            if (requestLog) {
                return 2;
            }

            return 1;
        }

        if (token) {
            return 4;
        }

        if (authorizationCode) {
            return 3;
        }

        if (authorizationStarted) {
            return 2;
        }

        return 1;
    }, [authorizationCode, authorizationStarted, flow, requestLog, token]);

    const steps = useMemo(() => {
        if (flow === 'client_credentials') {
            return [
                { id: 'configure', title: 'Configure Request', description: 'Choose client and scopes' },
                { id: 'request-token', title: 'Request Token', description: 'POST /oauth/token' },
                { id: 'inspect-token', title: 'Inspect JWT', description: 'Decode claims and signature segment' },
            ];
        }

        return [
            { id: 'configure', title: 'Configure Request', description: 'Choose client and scopes' },
            { id: 'authorize', title: 'Authorize User', description: 'Redirect to /oauth/authorize' },
            { id: 'exchange', title: 'Exchange Code', description: 'POST code to /oauth/token' },
            { id: 'inspect', title: 'Inspect JWT', description: 'Decode issued access token' },
        ];
    }, [flow]);

    const setExchange = (request: RequestLog, response: ResponseLog) => {
        setRequestLog(request);
        setResponseLog(response);
    };

    const requestToken = async (payload: Record<string, string>) => {
        const request: RequestLog = {
            method: 'POST',
            url: endpoints.token,
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Accept: 'application/json',
            },
            body: payload,
        };

        const startedAt = performance.now();
        const response = await fetch(endpoints.token, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                Accept: 'application/json',
            },
            body: new URLSearchParams(payload),
        });

        const body = await parseResponse(response);
        const durationMs = Math.round(performance.now() - startedAt);
        const responseLogValue: ResponseLog = {
            status: response.status,
            body,
            headers: {
                'content-type': response.headers.get('content-type') ?? 'unknown',
            },
            durationMs,
            timestamp: new Date().toISOString(),
        };

        setExchange(request, responseLogValue);

        if (!response.ok || typeof body !== 'object' || body === null || !('access_token' in body)) {
            toast.error('Token request failed');
            return;
        }

        const accessToken = String((body as Record<string, unknown>).access_token ?? '');
        if (accessToken) {
            setToken(accessToken);
            toast.success('Token issued successfully');
        }
    };

    const startAuthorization = async () => {
        if (!selectedClient) {
            toast.error('Select a demo client first');
            return;
        }

        setIsLoading(true);
        setAuthorizationError(null);

        try {
            const state = randomString();
            localStorage.setItem('demo.playground.state', state);
            localStorage.setItem('demo.playground.client', selectedClient.id);
            localStorage.setItem('demo.playground.flow', flow);

            const params: Record<string, string> = {
                response_type: 'code',
                client_id: selectedClient.id,
                redirect_uri: selectedClient.redirect,
                scope: selectedScopes.join(' '),
                state,
            };

            if (flow === 'pkce') {
                const verifier = randomString() + randomString();
                const challenge = await pkceChallenge(verifier);
                params.code_challenge = challenge;
                params.code_challenge_method = 'S256';
                localStorage.setItem('demo.playground.pkce_verifier', verifier);
            } else {
                localStorage.removeItem('demo.playground.pkce_verifier');
            }

            const authorizeUrl = `${endpoints.authorization}?${new URLSearchParams(params).toString()}`;
            setRequestLog({ method: 'GET', url: authorizeUrl, headers: {}, body: null });
            setAuthorizationStarted(true);

            window.location.assign(authorizeUrl);
        } finally {
            setIsLoading(false);
        }
    };

    const exchangeAuthorizationCode = async () => {
        if (!selectedClient || !authorizationCode) {
            toast.error('No authorization code available to exchange');
            return;
        }

        setIsLoading(true);

        try {
            const payload: Record<string, string> = {
                grant_type: 'authorization_code',
                client_id: selectedClient.id,
                redirect_uri: selectedClient.redirect,
                code: authorizationCode,
            };

            if (flow === 'pkce') {
                const verifier = localStorage.getItem('demo.playground.pkce_verifier') ?? '';
                if (!verifier) {
                    toast.error('Missing PKCE code verifier in browser storage');
                    return;
                }
                payload.code_verifier = verifier;
            } else if (selectedClient.secret) {
                payload.client_secret = selectedClient.secret;
            }

            await requestToken(payload);

            window.history.replaceState({}, document.title, '/demo/playground');
            localStorage.removeItem('demo.playground.state');
            localStorage.removeItem('demo.playground.pkce_verifier');
            setAuthorizationCode(null);
        } finally {
            setIsLoading(false);
        }
    };

    const runClientCredentials = async () => {
        if (!selectedClient?.secret) {
            toast.error('Client Credentials requires a confidential client with secret');
            return;
        }

        setIsLoading(true);

        try {
            await requestToken({
                grant_type: 'client_credentials',
                client_id: selectedClient.id,
                client_secret: selectedClient.secret,
                scope: selectedScopes.join(' '),
            });
        } finally {
            setIsLoading(false);
        }
    };

    return (
        <DemoLayout
            title="OAuth Playground"
            subtitle="Run real token requests and inspect responses with the seeded demo clients"
        >
            <div className="space-y-6">
                <div className="rounded-xl border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-900">
                    <p className="font-semibold">Demo account for Authorization Code + PKCE login</p>
                    <p className="mt-1 font-mono text-xs">{demo_credentials.email} / {demo_credentials.password}</p>
                </div>

                <FlowStepIndicator steps={steps} currentStep={currentStep} />

                <section className="grid gap-6 lg:grid-cols-2">
                    <div className="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                        <h2 className="text-lg font-bold text-slate-900">Flow Configuration</h2>

                        <div>
                            <label className="mb-1 block text-sm font-semibold text-slate-700">Grant Type</label>
                            <select
                                value={flow}
                                onChange={(event) => {
                                    const nextFlow = event.target.value as PlaygroundFlow;
                                    setFlow(nextFlow);
                                    setToken('');
                                    setAuthorizationCode(null);
                                    setAuthorizationError(null);
                                    setRequestLog(null);
                                    setResponseLog(null);
                                }}
                                className="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                                <option value="authorization_code">Authorization Code</option>
                                <option value="pkce">Authorization Code + PKCE</option>
                                <option value="client_credentials">Client Credentials</option>
                            </select>
                        </div>

                        <div>
                            <label className="mb-1 block text-sm font-semibold text-slate-700">Demo Client</label>
                            <select
                                value={selectedClientId}
                                onChange={(event) => setSelectedClientId(event.target.value)}
                                className="w-full rounded-md border-slate-300 text-sm shadow-sm focus:border-cyan-500 focus:ring-cyan-500"
                            >
                                {clients.map((client) => (
                                    <option key={client.id} value={client.id}>
                                        {client.name} ({client.type})
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div>
                            <p className="mb-1 block text-sm font-semibold text-slate-700">Scopes</p>
                            <ScopeSelector scopes={scopes} selected={selectedScopes} onChange={setSelectedScopes} />
                        </div>

                        <div className="flex flex-wrap gap-3">
                            {flow === 'client_credentials' && (
                                <Button isLoading={isLoading} onClick={runClientCredentials}>
                                    Execute Token Request
                                </Button>
                            )}

                            {(flow === 'authorization_code' || flow === 'pkce') && (
                                <Button isLoading={isLoading} onClick={startAuthorization}>
                                    Start Authorization Redirect
                                </Button>
                            )}

                            {(flow === 'authorization_code' || flow === 'pkce') && authorizationCode && (
                                <Button variant="secondary" isLoading={isLoading} onClick={exchangeAuthorizationCode}>
                                    Exchange Authorization Code
                                </Button>
                            )}
                        </div>

                        {authorizationCode && (
                            <p className="rounded-md border border-emerald-200 bg-emerald-50 p-3 font-mono text-xs text-emerald-700">
                                Received code: {authorizationCode}
                            </p>
                        )}

                        {authorizationError && (
                            <p className="rounded-md border border-rose-200 bg-rose-50 p-3 text-sm text-rose-700">
                                Authorization failed: {authorizationError}
                            </p>
                        )}
                    </div>

                    <JwtDecodePanel token={token} />
                </section>

                <RequestResponsePanel request={requestLog} response={responseLog} title="Live OAuth Request / Response" />
            </div>
        </DemoLayout>
    );
}
