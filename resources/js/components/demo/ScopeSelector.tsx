import { DemoScope } from '../../pages/Demo/types';

interface ScopeSelectorProps {
    scopes: DemoScope[];
    selected: string[];
    onChange: (next: string[]) => void;
}

export default function ScopeSelector({ scopes, selected, onChange }: ScopeSelectorProps) {
    const toggle = (scopeId: string) => {
        if (selected.includes(scopeId)) {
            onChange(selected.filter((id) => id !== scopeId));

            return;
        }

        onChange([...selected, scopeId]);
    };

    return (
        <div className="space-y-2">
            {scopes.map((scope) => {
                const checked = selected.includes(scope.id);

                return (
                    <label key={scope.id} className="flex cursor-pointer items-start gap-3 rounded-lg border border-slate-200 bg-white p-3 hover:border-cyan-300">
                        <input
                            type="checkbox"
                            checked={checked}
                            onChange={() => toggle(scope.id)}
                            className="mt-1 h-4 w-4 rounded border-slate-300 text-cyan-600 focus:ring-cyan-500"
                        />
                        <span>
                            <span className="block text-sm font-semibold text-slate-900">{scope.id}</span>
                            <span className="block text-xs text-slate-600">{scope.description}</span>
                        </span>
                    </label>
                );
            })}
        </div>
    );
}
