import { formatDistanceToNowStrict } from 'date-fns';
import { RequestLog, ResponseLog } from '../../pages/Demo/types';

interface RequestResponsePanelProps {
    request: RequestLog | null;
    response: ResponseLog | null;
    title?: string;
}

function json(value: unknown): string {
    if (value === null || value === undefined) {
        return 'null';
    }

    if (typeof value === 'string') {
        return value;
    }

    return JSON.stringify(value, null, 2);
}

export default function RequestResponsePanel({ request, response, title = 'HTTP Exchange' }: RequestResponsePanelProps) {
    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h3 className="text-sm font-semibold text-slate-900">{title}</h3>
                {response && (
                    <span className="text-xs text-slate-500">
                        {formatDistanceToNowStrict(new Date(response.timestamp), { addSuffix: true })}
                    </span>
                )}
            </div>

            <div className="grid gap-0 lg:grid-cols-2">
                <div className="border-b border-slate-200 lg:border-b-0 lg:border-r">
                    <div className="bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-200">Request</div>
                    <pre className="max-h-[24rem] overflow-auto bg-slate-950 p-4 text-xs leading-6 text-cyan-100">
{request ? `${request.method} ${request.url}\n\n${json(request.headers ?? {})}\n\n${json(request.body ?? {})}` : 'No request sent yet.'}
                    </pre>
                </div>

                <div>
                    <div className="bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-slate-200">Response</div>
                    <pre className="max-h-[24rem] overflow-auto bg-slate-950 p-4 text-xs leading-6 text-emerald-100">
{response ? `Status: ${response.status} (${response.durationMs}ms)\n\n${json(response.headers ?? {})}\n\n${json(response.body)}` : 'No response received yet.'}
                    </pre>
                </div>
            </div>
        </section>
    );
}
