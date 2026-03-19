import { Link, usePage } from '@inertiajs/react';
import DemoLayout from '../../layouts/DemoLayout';
import { DemoEndpoints } from './types';

interface DemoIndexProps {
    endpoints: DemoEndpoints;
    [key: string]: unknown;
}

interface DemoCard {
    title: string;
    description: string;
    href: string;
    category: string;
}

const cards: DemoCard[] = [
    {
        title: 'OAuth Playground',
        description: 'Run Auth Code, PKCE, and Client Credentials with live requests and JWT output.',
        href: '/demo/playground',
        category: 'Interactive',
    },
    {
        title: 'JWT Inspector',
        description: 'Decode token segments and verify signatures against live JWKS using JOSE.',
        href: '/demo/jwt-inspector',
        category: 'Interactive',
    },
    {
        title: 'Auth Code Walkthrough',
        description: 'Step-by-step Authorization Code documentation with exact HTTP examples.',
        href: '/demo/flows/auth-code',
        category: 'Flow Docs',
    },
    {
        title: 'PKCE Walkthrough',
        description: 'Visualize S256 code challenge generation and public client exchange rules.',
        href: '/demo/flows/pkce',
        category: 'Flow Docs',
    },
    {
        title: 'Client Credentials Walkthrough',
        description: 'Understand machine-to-machine token issuance and scope enforcement.',
        href: '/demo/flows/client-credentials',
        category: 'Flow Docs',
    },
    {
        title: 'Introspection Demo',
        description: 'Send RFC 7662 introspection requests with HTTP Basic client authentication.',
        href: '/demo/introspection',
        category: 'Advanced',
    },
    {
        title: 'Revocation Demo',
        description: 'Revoke tokens with RFC 7009 and validate deactivation behavior.',
        href: '/demo/revocation',
        category: 'Advanced',
    },
];

export default function DemoIndex() {
    const { endpoints } = usePage<DemoIndexProps>().props;

    return (
        <DemoLayout
            title="Demo Application"
            subtitle="Interactive identity tooling for OAuth2, JWT validation, introspection, and revocation"
        >
            <div className="space-y-6">
                <section className="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm lg:grid-cols-3">
                    <EndpointItem label="Authorization" value={endpoints.authorization} />
                    <EndpointItem label="Token" value={endpoints.token} />
                    <EndpointItem label="Introspection" value={endpoints.introspection} />
                    <EndpointItem label="Revocation" value={endpoints.revocation} />
                    <EndpointItem label="JWKS" value={endpoints.jwks} />
                    <EndpointItem label="OpenID Config" value={endpoints.openid_configuration} />
                </section>

                <section className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {cards.map((card) => (
                        <Link
                            key={card.href}
                            href={card.href}
                            className="group rounded-xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-cyan-300 hover:shadow"
                        >
                            <p className="text-xs font-semibold uppercase tracking-wide text-cyan-700">{card.category}</p>
                            <h2 className="mt-2 text-lg font-bold text-slate-900 group-hover:text-cyan-800">{card.title}</h2>
                            <p className="mt-2 text-sm leading-6 text-slate-600">{card.description}</p>
                            <p className="mt-4 text-sm font-semibold text-cyan-700">Open demo</p>
                        </Link>
                    ))}
                </section>
            </div>
        </DemoLayout>
    );
}

function EndpointItem({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-lg border border-slate-200 bg-slate-50 p-3">
            <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">{label}</p>
            <p className="mt-1 break-all font-mono text-xs text-slate-700">{value}</p>
        </div>
    );
}
