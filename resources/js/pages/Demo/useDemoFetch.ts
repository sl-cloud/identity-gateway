import { useState } from 'react';
import { RequestLog, ResponseLog } from './types';
import { parseResponse } from './utils';

interface UseDemoFetchReturn {
    requestLog: RequestLog | null;
    responseLog: ResponseLog | null;
    isLoading: boolean;
    execute: (url: string, init: RequestInit, requestMeta?: Partial<RequestLog>) => Promise<unknown>;
}

export function useDemoFetch(): UseDemoFetchReturn {
    const [requestLog, setRequestLog] = useState<RequestLog | null>(null);
    const [responseLog, setResponseLog] = useState<ResponseLog | null>(null);
    const [isLoading, setIsLoading] = useState(false);

    const execute = async (
        url: string,
        init: RequestInit,
        requestMeta?: Partial<RequestLog>,
    ): Promise<unknown> => {
        setIsLoading(true);

        const request: RequestLog = {
            method: (init.method ?? 'GET').toUpperCase(),
            url,
            headers: requestMeta?.headers,
            body: requestMeta?.body,
        };

        const startedAt = performance.now();
        const response = await fetch(url, init);
        const body = await parseResponse(response);
        const durationMs = Math.round(performance.now() - startedAt);

        const responseValue: ResponseLog = {
            status: response.status,
            body,
            headers: {
                'content-type': response.headers.get('content-type') ?? 'unknown',
            },
            durationMs,
            timestamp: new Date().toISOString(),
        };

        setRequestLog(request);
        setResponseLog(responseValue);
        setIsLoading(false);

        return body;
    };

    return { requestLog, responseLog, isLoading, execute };
}
