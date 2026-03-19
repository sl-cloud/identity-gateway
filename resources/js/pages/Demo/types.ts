export interface DemoClient {
    id: string;
    name: string;
    secret: string | null;
    redirect: string;
    type: 'confidential' | 'public';
}

export interface DemoScope {
    id: string;
    description: string;
    is_default: boolean;
}

export interface DemoEndpoints {
    authorization: string;
    token: string;
    introspection: string;
    revocation: string;
    jwks: string;
    openid_configuration: string;
}

export interface RequestLog {
    method: string;
    url: string;
    headers?: Record<string, string>;
    body?: unknown;
}

export interface ResponseLog {
    status: number;
    body: unknown;
    headers?: Record<string, string>;
    durationMs: number;
    timestamp: string;
}
