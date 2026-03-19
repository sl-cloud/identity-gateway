import { router, useForm } from '@inertiajs/react';
import DashboardLayout from '../../../layouts/DashboardLayout';
import { Card } from '../../../components/ui/Card';
import { Button } from '../../../components/ui/Button';
import { Input } from '../../../components/ui/Input';
import { Badge } from '../../../components/ui/Badge';

interface FormData {
    name: string;
    redirect: string;
    confidential: string;
}

export default function ClientsCreate() {
    const { data, setData, post, processing, errors } = useForm<FormData>({
        name: '',
        redirect: '',
        confidential: 'true',
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/dashboard/clients', {
            onSuccess: () => {
                router.visit('/dashboard/clients');
            },
        });
    };

    return (
        <DashboardLayout>
            <div className="space-y-6">
                {/* Header */}
                <div>
                    <button
                        onClick={() => router.visit('/dashboard/clients')}
                        className="text-sm text-indigo-600 hover:text-indigo-800 flex items-center"
                    >
                        <svg className="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Back to Clients
                    </button>
                    <h1 className="mt-4 text-2xl font-bold text-gray-900">Create OAuth Client</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Register a new OAuth 2.0 client for your application.
                    </p>
                </div>

                {/* Form */}
                <Card>
                    <form onSubmit={handleSubmit} className="space-y-6">
                        <div className="space-y-4">
                            <Input
                                label="Client Name"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                error={errors.name}
                                placeholder="My Application"
                                required
                            />

                            <Input
                                label="Redirect URI"
                                type="url"
                                value={data.redirect}
                                onChange={(e) => setData('redirect', e.target.value)}
                                error={errors.redirect}
                                placeholder="https://myapp.com/callback"
                                helperText="The URI where users will be redirected after authorization"
                                required
                            />

                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                    Client Type
                                </label>
                                <div className="space-y-3">
                                    <label className={`flex items-center p-4 border rounded-lg cursor-pointer transition-colors ${data.confidential === 'true' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:bg-gray-50'}`}>
                                        <input
                                            type="radio"
                                            name="confidential"
                                            value="true"
                                            checked={data.confidential === 'true'}
                                            onChange={(e) => setData('confidential', e.target.value)}
                                            className="h-4 w-4 text-indigo-600 border-gray-300"
                                        />
                                        <div className="ml-3">
                                            <div className="flex items-center">
                                                <span className="text-sm font-medium text-gray-900">Confidential Client</span>
                                                <Badge variant="info" size="sm" className="ml-2">Recommended</Badge>
                                            </div>
                                            <p className="text-sm text-gray-500 mt-1">
                                                Can keep credentials confidential. Suitable for web applications running on a server.
                                                Supports Authorization Code flow and Client Credentials.
                                            </p>
                                        </div>
                                    </label>

                                    <label className={`flex items-center p-4 border rounded-lg cursor-pointer transition-colors ${data.confidential === 'false' ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200 hover:bg-gray-50'}`}>
                                        <input
                                            type="radio"
                                            name="confidential"
                                            value="false"
                                            checked={data.confidential === 'false'}
                                            onChange={(e) => setData('confidential', e.target.value)}
                                            className="h-4 w-4 text-indigo-600 border-gray-300"
                                        />
                                        <div className="ml-3">
                                            <div className="flex items-center">
                                                <span className="text-sm font-medium text-gray-900">Public Client</span>
                                                <Badge variant="success" size="sm" className="ml-2">PKCE</Badge>
                                            </div>
                                            <p className="text-sm text-gray-500 mt-1">
                                                Cannot keep credentials confidential. Suitable for mobile apps and SPAs.
                                                Supports PKCE flow. No client secret will be generated.
                                            </p>
                                        </div>
                                    </label>
                                </div>
                                {errors.confidential && (
                                    <p className="mt-1 text-sm text-red-600">{errors.confidential}</p>
                                )}
                            </div>
                        </div>

                        <div className="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => router.visit('/dashboard/clients')}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                isLoading={processing}
                            >
                                Create Client
                            </Button>
                        </div>
                    </form>
                </Card>
            </div>
        </DashboardLayout>
    );
}
