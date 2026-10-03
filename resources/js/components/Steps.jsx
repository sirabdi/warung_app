import { Check } from 'lucide-react';
import { cn } from '@/lib/utils';

export const REGISTER_STEPS = ['Email', 'Data toko', 'Paket', 'Bayar'];

// Progress of the sign-up: done steps get a check, the current one is filled.
export default function Steps({ steps = REGISTER_STEPS, current, className }) {
    return (
        <ol className={cn('flex items-center gap-2', className)}>
            {steps.map((label, index) => (
                <li key={label} className="flex flex-1 items-center gap-2">
                    <span
                        className={cn(
                            'flex size-6 shrink-0 items-center justify-center rounded-full border text-xs font-semibold',
                            index < current && 'border-primary bg-primary text-primary-foreground',
                            index === current && 'border-primary text-primary',
                            index > current && 'text-muted-foreground',
                        )}
                    >
                        {index < current ? <Check className="size-3.5" /> : index + 1}
                    </span>
                    <span
                        className={cn(
                            'hidden truncate text-xs sm:inline',
                            index === current ? 'font-semibold' : 'text-muted-foreground',
                        )}
                    >
                        {label}
                    </span>
                    {index < steps.length - 1 && <span className="h-px flex-1 bg-border" />}
                </li>
            ))}
        </ol>
    );
}
