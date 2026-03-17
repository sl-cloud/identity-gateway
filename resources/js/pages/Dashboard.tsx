import React from 'react';
import { router } from '@inertiajs/react';

interface DashboardProps {
    auth: {
        user: {
            name: string;
            email: string;
        };
    };
}

export default function Dashboard({ auth }: DashboardProps) {
    const handleLogout = () => {
        router.post('/auth/logout');
    };

    return (
        <div className="min-h-screen bg-gray-100">
            <nav className="bg-white shadow-sm">
                <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="flex justify-between h-16">
                        <div className="flex items-center">
                            <h1 className="text-xl font-bold text-gray-900">
                                Identity Gateway
                            </h1>
                        </div>
                        <div className="flex items-center space-x-4">
                            <span className="text-gray-700">{auth.user.name}</span>
                            <button
                                onClick={handleLogout}
                                className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500"
                            >
                                Logout
                            </button>
                        </div>
                    </div>
                </div>
            </nav>

            <main className="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
                <div className="px-4 py-6 sm:px-0">
                    <div className="bg-white shadow rounded-lg p-6">
                        <h2 className="text-2xl font-bold text-gray-900 mb-4">
                            Welcome to Identity Gateway
                        </h2>
                        <p className="text-gray-600 mb-4">
                            You are logged in as <strong>{auth.user.email}</strong>
                        </p>
                        <div className="bg-indigo-50 border border-indigo-200 rounded-md p-4">
                            <p className="text-sm text-indigo-700">
                                Phase 1 Complete: Laravel 12, Docker, Inertia+React, and basic authentication are now set up!
                            </p>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    );
}
