import React, { FormEvent, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AuthLayout from '@/layouts/AuthLayout';

interface LoginProps {
    errors?: {
        email?: string;
        password?: string;
    };
}

export default function Login({ errors = {} }: LoginProps) {
    const [formData, setFormData] = useState({
        email: '',
        password: '',
        remember: false,
    });
    const [processing, setProcessing] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        
        router.post('/auth/login', formData, {
            onFinish: () => setProcessing(false),
        });
    };

    return (
        <AuthLayout>
            <h2 className="text-2xl font-bold text-gray-900 mb-6">Sign In</h2>
            
            <form onSubmit={handleSubmit} className="space-y-4">
                <div>
                    <label htmlFor="email" className="block text-sm font-medium text-gray-700 mb-1">
                        Email Address
                    </label>
                    <input
                        id="email"
                        type="email"
                        value={formData.email}
                        onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                    />
                    {errors.email && (
                        <p className="mt-1 text-sm text-red-600">{errors.email}</p>
                    )}
                </div>

                <div>
                    <label htmlFor="password" className="block text-sm font-medium text-gray-700 mb-1">
                        Password
                    </label>
                    <input
                        id="password"
                        type="password"
                        value={formData.password}
                        onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        required
                    />
                    {errors.password && (
                        <p className="mt-1 text-sm text-red-600">{errors.password}</p>
                    )}
                </div>

                <div className="flex items-center">
                    <input
                        id="remember"
                        type="checkbox"
                        checked={formData.remember}
                        onChange={(e) => setFormData({ ...formData, remember: e.target.checked })}
                        className="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded"
                    />
                    <label htmlFor="remember" className="ml-2 block text-sm text-gray-700">
                        Remember me
                    </label>
                </div>

                <button
                    type="submit"
                    disabled={processing}
                    className="w-full bg-indigo-600 text-white py-2 px-4 rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    {processing ? 'Signing in...' : 'Sign In'}
                </button>
            </form>

            <p className="mt-6 text-center text-sm text-gray-600">
                Don't have an account?{' '}
                <Link href="/auth/register" className="text-indigo-600 hover:text-indigo-500 font-medium">
                    Sign up
                </Link>
            </p>
        </AuthLayout>
    );
}
