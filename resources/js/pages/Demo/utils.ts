import { DemoClient } from './types';

export async function parseResponse(response: Response): Promise<unknown> {
    const contentType = response.headers.get('content-type') ?? '';

    if (contentType.includes('application/json')) {
        return await response.json();
    }

    return await response.text();
}

export function basicAuth(client: DemoClient): string {
    return btoa(`${client.id}:${client.secret ?? ''}`);
}
