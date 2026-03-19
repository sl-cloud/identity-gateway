interface JwtDecodePanelProps {
    token: string;
    valid?: boolean | null;
    error?: string | null;
}

interface DecodedJwt {
    header: Record<string, unknown> | null;
    payload: Record<string, unknown> | null;
    signature: string;
}

function base64UrlToUtf8(value: string): string {
    const normalized = value.replace(/-/g, '+').replace(/_/g, '/');
    const padded = normalized + '='.repeat((4 - (normalized.length % 4)) % 4);

    try {
        return decodeURIComponent(
            atob(padded)
                .split('')
                .map((char) => `%${char.charCodeAt(0).toString(16).padStart(2, '0')}`)
                .join('')
        );
    } catch {
        return atob(padded);
    }
}

function decodeToken(token: string): DecodedJwt | null {
    const parts = token.split('.');
    if (parts.length !== 3) {
        return null;
    }

    try {
        return {
            header: JSON.parse(base64UrlToUtf8(parts[0])) as Record<string, unknown>,
            payload: JSON.parse(base64UrlToUtf8(parts[1])) as Record<string, unknown>,
            signature: parts[2],
        };
    } catch {
        return null;
    }
}

function pretty(value: unknown): string {
    return JSON.stringify(value, null, 2);
}

export default function JwtDecodePanel({ token, valid = null, error = null }: JwtDecodePanelProps) {
    const decoded = token ? decodeToken(token) : null;

    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h3 className="text-sm font-semibold text-slate-900">JWT Inspector</h3>
                {valid !== null && (
                    <span className={`rounded-full px-2.5 py-1 text-xs font-semibold ${valid ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'}`}>
                        {valid ? 'Signature valid' : 'Invalid token'}
                    </span>
                )}
            </div>

            {!token && <p className="p-4 text-sm text-slate-500">Paste or generate a token to view its structure.</p>}

            {token && !decoded && (
                <p className="p-4 text-sm text-rose-700">Token is not in valid JWT format (header.payload.signature).</p>
            )}

            {token && decoded && (
                <div className="grid gap-0 md:grid-cols-2">
                    <div className="border-b border-slate-200 md:border-b-0 md:border-r">
                        <div className="bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-200">Header</div>
                        <pre className="max-h-64 overflow-auto bg-slate-950 p-4 text-xs leading-6 text-cyan-100">{pretty(decoded.header)}</pre>
                    </div>
                    <div>
                        <div className="bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-200">Payload</div>
                        <pre className="max-h-64 overflow-auto bg-slate-950 p-4 text-xs leading-6 text-emerald-100">{pretty(decoded.payload)}</pre>
                    </div>
                </div>
            )}

            {decoded && (
                <div className="border-t border-slate-200 bg-slate-50 p-4">
                    <p className="text-xs font-semibold uppercase tracking-wide text-slate-500">Signature Segment</p>
                    <p className="mt-2 break-all font-mono text-xs text-slate-700">{decoded.signature}</p>
                </div>
            )}

            {error && (
                <div className="border-t border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">{error}</div>
            )}
        </section>
    );
}
