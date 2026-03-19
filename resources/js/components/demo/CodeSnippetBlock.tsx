import { useEffect, useRef } from 'react';
import Prism from 'prismjs';
import toast from 'react-hot-toast';
import { Button } from '../ui/Button';
import 'prismjs/themes/prism-tomorrow.css';
import 'prismjs/components/prism-bash';
import 'prismjs/components/prism-json';
import 'prismjs/components/prism-javascript';

interface CodeSnippetBlockProps {
    title: string;
    code: string;
    language?: 'bash' | 'json' | 'javascript' | 'markup';
}

export default function CodeSnippetBlock({ title, code, language = 'bash' }: CodeSnippetBlockProps) {
    const preRef = useRef<HTMLPreElement | null>(null);

    useEffect(() => {
        if (preRef.current) {
            Prism.highlightElement(preRef.current.querySelector('code') as Element);
        }
    }, [code, language]);

    const onCopy = async () => {
        try {
            await navigator.clipboard.writeText(code);
            toast.success('Snippet copied');
        } catch {
            toast.error('Clipboard unavailable');
        }
    };

    return (
        <section className="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
            <div className="flex items-center justify-between border-b border-slate-200 bg-slate-50 px-4 py-3">
                <h3 className="text-sm font-semibold text-slate-900">{title}</h3>
                <Button size="sm" variant="secondary" onClick={onCopy}>
                    Copy
                </Button>
            </div>
            <pre ref={preRef} className={`language-${language} m-0 max-h-[24rem] overflow-auto !rounded-none !bg-slate-950 !p-4 text-xs`}>
                <code className={`language-${language}`}>{code}</code>
            </pre>
        </section>
    );
}
