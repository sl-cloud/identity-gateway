import React, { ReactNode } from 'react';

interface AuthLayoutProps {
    children: ReactNode;
}

export default function AuthLayout({ children }: AuthLayoutProps) {
    return (
        <div className="min-h-screen flex items-center justify-center bg-gradient-to-br from-indigo-50 via-white to-purple-50">
            <div className="w-full max-w-md px-6">
                <div className="mb-8 text-center">
                    <h1 className="text-3xl font-bold text-gray-900 mb-2">
                        Identity Gateway
                    </h1>
                    <p className="text-gray-600">
                        Secure OAuth2 Authentication
                    </p>
                </div>
                <div className="bg-white shadow-xl rounded-lg p-8">
                    {children}
                </div>
            </div>
        </div>
    );
}
