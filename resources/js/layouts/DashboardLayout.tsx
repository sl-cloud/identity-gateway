import React, { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

interface User {
    id: number;
    name: string;
    email: string;
    roles?: string[];
}

interface PageProps {
    auth: {
        user: User;
    };
    [key: string]: unknown;
}

interface NavItem {
    name: string;
    href: string;
    icon: React.ReactNode;
    active?: boolean;
}

export default function DashboardLayout({ children }: { children: React.ReactNode }) {
    const { auth } = usePage<PageProps>().props as Partial<PageProps>;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

    const handleLogout = () => {
        router.post('/auth/logout');
    };

    const currentPath = window.location.pathname;

    const navigation: NavItem[] = [
        {
            name: 'Overview',
            href: '/dashboard',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
            ),
            active: currentPath === '/dashboard',
        },
        {
            name: 'OAuth Clients',
            href: '/dashboard/clients',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                </svg>
            ),
            active: currentPath.startsWith('/dashboard/clients'),
        },
        {
            name: 'API Keys',
            href: '/dashboard/api-keys',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                </svg>
            ),
            active: currentPath.startsWith('/dashboard/api-keys'),
        },
        {
            name: 'Tokens',
            href: '/dashboard/tokens',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            ),
            active: currentPath.startsWith('/dashboard/tokens'),
        },
        {
            name: 'Audit Logs',
            href: '/dashboard/audit-logs',
            icon: (
                <svg className="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                </svg>
            ),
            active: currentPath.startsWith('/dashboard/audit-logs'),
        },
    ];

    return (
        <div className="min-h-screen bg-gray-50">
            {/* Mobile menu overlay */}
            {mobileMenuOpen && (
                <div
                    className="fixed inset-0 z-40 bg-gray-600 bg-opacity-75 lg:hidden"
                    onClick={() => setMobileMenuOpen(false)}
                />
            )}

            {/* Mobile sidebar */}
            <div className={`fixed inset-y-0 left-0 z-50 w-64 bg-gray-900 transform transition-transform duration-200 ease-in-out lg:hidden ${mobileMenuOpen ? 'translate-x-0' : '-translate-x-full'}`}>
                <div className="flex items-center justify-between h-16 px-4 bg-gray-800">
                    <span className="text-white font-semibold">Identity Gateway</span>
                    <button
                        onClick={() => setMobileMenuOpen(false)}
                        className="text-gray-400 hover:text-white"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
                <nav className="px-2 py-4 space-y-1">
                    {navigation.map((item) => (
                        <a
                            key={item.name}
                            href={item.href}
                            className={`flex items-center px-3 py-2 text-sm font-medium rounded-md ${item.active ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'}`}
                        >
                            <span className="mr-3">{item.icon}</span>
                            {item.name}
                        </a>
                    ))}
                </nav>
            </div>

            {/* Desktop sidebar */}
            <div className="hidden lg:fixed lg:inset-y-0 lg:flex lg:flex-col lg:w-64 lg:bg-gray-900">
                <div className="flex items-center h-16 px-4 bg-gray-800">
                    <span className="text-white font-semibold text-lg">Identity Gateway</span>
                </div>
                <nav className="flex-1 px-2 py-4 space-y-1 overflow-y-auto">
                    {navigation.map((item) => (
                        <a
                            key={item.name}
                            href={item.href}
                            className={`flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors ${item.active ? 'bg-gray-800 text-white' : 'text-gray-300 hover:bg-gray-700 hover:text-white'}`}
                        >
                            <span className="mr-3">{item.icon}</span>
                            {item.name}
                        </a>
                    ))}
                </nav>
                {auth?.user && (
                    <div className="p-4 border-t border-gray-800">
                        <div className="flex items-center">
                            <div className="flex-shrink-0">
                                <div className="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white font-semibold">
                                    {auth.user.name.charAt(0).toUpperCase()}
                                </div>
                            </div>
                            <div className="ml-3">
                                <p className="text-sm font-medium text-white">{auth.user.name}</p>
                                <p className="text-xs text-gray-400 truncate max-w-[140px]">{auth.user.email}</p>
                            </div>
                        </div>
                        <button
                            onClick={handleLogout}
                            className="mt-3 w-full flex items-center justify-center px-3 py-2 text-sm font-medium text-gray-300 bg-gray-800 rounded-md hover:bg-gray-700 hover:text-white transition-colors"
                        >
                            <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Logout
                        </button>
                    </div>
                )}
            </div>

            {/* Main content */}
            <div className="lg:pl-64">
                {/* Top navigation bar */}
                <div className="sticky top-0 z-30 bg-white border-b border-gray-200">
                    <div className="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">
                        <button
                            onClick={() => setMobileMenuOpen(true)}
                            className="lg:hidden p-2 text-gray-400 hover:text-gray-500"
                        >
                            <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        </button>

                        <div className="flex items-center space-x-4">
                            <a
                                href="/.well-known/openid-configuration"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="hidden sm:inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100 transition-colors"
                            >
                                <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                Discovery
                            </a>
                            <a
                                href="/.well-known/jwks.json"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="hidden sm:inline-flex items-center px-3 py-1.5 text-sm font-medium text-indigo-600 bg-indigo-50 rounded-md hover:bg-indigo-100 transition-colors"
                            >
                                <svg className="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                </svg>
                                JWKS
                            </a>
                        </div>
                    </div>
                </div>

                {/* Page content */}
                <main className="py-6 px-4 sm:px-6 lg:px-8">
                    {children}
                </main>
            </div>
        </div>
    );
}
