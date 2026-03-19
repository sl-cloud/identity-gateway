
interface FlowStep {
    id: string;
    title: string;
    description?: string;
}

interface FlowStepIndicatorProps {
    steps: FlowStep[];
    /** Active step number (1-based). Omit or pass 0 for no active step. */
    currentStep?: number;
}

export default function FlowStepIndicator({ steps, currentStep = 0 }: FlowStepIndicatorProps) {
    return (
        <ol className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
            {steps.map((step, index) => {
                const stepNumber = index + 1;
                const isActive = stepNumber === currentStep;
                const isComplete = stepNumber < currentStep;

                return (
                    <li
                        key={step.id}
                        className={`rounded-lg border p-4 transition ${
                            isActive
                                ? 'border-cyan-500 bg-cyan-50'
                                : isComplete
                                    ? 'border-emerald-200 bg-emerald-50'
                                    : 'border-slate-200 bg-white'
                        }`}
                    >
                        <div className="flex items-center gap-3">
                            <span
                                className={`inline-flex h-7 w-7 items-center justify-center rounded-full text-xs font-bold ${
                                    isActive
                                        ? 'bg-cyan-600 text-white'
                                        : isComplete
                                            ? 'bg-emerald-600 text-white'
                                            : 'bg-slate-200 text-slate-700'
                                }`}
                            >
                                {stepNumber}
                            </span>
                            <p className="text-sm font-semibold text-slate-900">{step.title}</p>
                        </div>
                        {step.description && <p className="mt-2 text-xs text-slate-600">{step.description}</p>}
                    </li>
                );
            })}
        </ol>
    );
}
