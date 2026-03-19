import React from 'react';
import { Link, usePage } from '@inertiajs/react';
import { Toaster } from 'react-hot-toast';

interface DemoLayoutProps {
    children: React.ReactNode;
    title?: string;
    subtitle?: string;
}

interface NavItem {
    label: string;
    href: string;
}

const navItems: NavItem[] = [
    { label: 'Overview', href: '/demo' },
    { label: 'Playground', href: '/demo/playground' },
    { label: 'JWT Inspector', href: '/demo/jwt-inspector' },
    { label: 'Auth Code', href: '/demo/flows/auth-code' },
    { label: 'PKCE', href: '/demo/flows/pkce' },
    { label: 'Client Credentials', href: '/demo/flows/client-credentials' },
    { label: 'Introspection', href: '/demo/introspection' },
    { label: 'Revocation', href: '/demo/revocation' },
];

export default function DemoLayout({ children, title, subtitle }: DemoLayoutProps) {
    const currentPath = usePage().url.split('?')[0];

    return (
        <div className="min-h-screen bg-slate-50">
            <Toaster position="top-right" />

            <header className="border-b border-slate-200 bg-white/95 backdrop-blur">
                <div className="mx-auto w-full max-w-7xl px-4 py-4 sm:px-6 lg:px-8">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-600">Identity Gateway</p>
                            <h1 className="mt-1 text-2xl font-bold text-slate-900">{title ?? 'Interactive OAuth Demo'}</h1>
                            {subtitle && <p className="mt-1 text-sm text-slate-600">{subtitle}</p>}
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <a
                                href="/.well-known/openid-configuration"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="rounded-md border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-semibold text-cyan-700 transition hover:bg-cyan-100"
                            >
                                OpenID Config
                            </a>
                            <a
                                href="/.well-known/jwks.json"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="rounded-md border border-slate-200 bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-200"
                            >
                                JWKS
                            </a>
                        </div>
                    </div>
                </div>
            </header>

            <nav className="border-b border-slate-200 bg-white">
                <div className="mx-auto flex w-full max-w-7xl flex-wrap gap-2 px-4 py-3 sm:px-6 lg:px-8">
                    {navItems.map((item) => {
                        const isActive = currentPath === item.href;
                        return (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`rounded-md px-3 py-1.5 text-sm font-medium transition ${
                                    isActive
                                        ? 'bg-slate-900 text-white'
                                        : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
                                }`}
                            >
                                {item.label}
                            </Link>
                        );
                    })}
                </div>
            </nav>

            <main className="mx-auto w-full max-w-7xl px-4 py-8 sm:px-6 lg:px-8">{children}</main>
        </div>
    );
}
