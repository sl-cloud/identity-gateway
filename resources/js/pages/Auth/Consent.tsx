import React, { FormEvent, useState } from 'react';
import { router } from '@inertiajs/react';
import AuthLayout from '@/layouts/AuthLayout';

interface ConsentProps {
    client: {
        id: number;
        name: string;
        redirect: string;
    };
    scopes: string[];
    state?: string;
    auth_request_key: string;
}

const scopeDescriptions: Record<string, { name: string; description: string }> = {
    'openid': {
        name: 'OpenID Connect',
        description: 'Authenticate your identity',
    },
    'user:read': {
        name: 'Read your profile',
        description: 'Access your basic profile information (name, email)',
    },
    'users:read': {
        name: 'Read all users',
        description: 'Access information about all users (admin permission)',
    },
    'resources:read': {
        name: 'Read resources',
        description: 'View resources in the system',
    },
    'resources:write': {
        name: 'Manage resources',
        description: 'Create, update, and delete resources',
    },
};

export default function Consent({ client, scopes, auth_request_key }: ConsentProps) {
    const [processing, setProcessing] = useState(false);

    const handleApprove = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);

        router.post('/auth/consent', {
            approved: true,
            auth_request_key,
        }, {
            onFinish: () => setProcessing(false),
        });
    };

    const handleDeny = () => {
        setProcessing(true);

        router.post('/auth/consent', {
            approved: false,
            auth_request_key,
        }, {
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AuthLayout>
            <div className="text-center mb-6">
                <div className="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-4">
                    <svg className="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <h2 className="text-2xl font-bold text-gray-900 mb-2">Authorization Request</h2>
                <p className="text-gray-600">
                    <span className="font-semibold text-gray-900">{client.name}</span> is requesting access to your account
                </p>
            </div>

            <div className="bg-gray-50 rounded-lg p-4 mb-6">
                <h3 className="text-sm font-semibold text-gray-700 mb-3">This application will be able to:</h3>
                <ul className="space-y-3">
                    {scopes.map((scope) => {
                        const info = scopeDescriptions[scope] || { name: scope, description: 'Access to ' + scope };
                        return (
                            <li key={scope} className="flex items-start">
                                <svg className="w-5 h-5 text-green-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clipRule="evenodd" />
                                </svg>
                                <div>
                                    <p className="text-sm font-medium text-gray-900">{info.name}</p>
                                    <p className="text-sm text-gray-600">{info.description}</p>
                                </div>
                            </li>
                        );
                    })}
                </ul>
            </div>

            <div className="bg-blue-50 border border-blue-200 rounded-md p-3 mb-6">
                <div className="flex">
                    <svg className="w-5 h-5 text-blue-500 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fillRule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clipRule="evenodd" />
                    </svg>
                    <p className="text-sm text-blue-800">
                        You can revoke this authorization at any time from your account settings.
                    </p>
                </div>
            </div>

            <form onSubmit={handleApprove} className="space-y-3">
                <button
                    type="submit"
                    disabled={processing}
                    className="w-full bg-indigo-600 text-white py-2.5 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                >
                    {processing ? 'Processing...' : 'Authorize'}
                </button>

                <button
                    type="button"
                    onClick={handleDeny}
                    disabled={processing}
                    className="w-full bg-white text-gray-700 py-2.5 px-4 rounded-md border border-gray-300 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed font-medium"
                >
                    Cancel
                </button>
            </form>

            <p className="mt-6 text-center text-xs text-gray-500">
                By authorizing, you agree to share the requested information with {client.name}.
            </p>
        </AuthLayout>
    );
}
