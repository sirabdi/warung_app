import { useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

/** A password field with an eye button that shows or hides what was typed. */
export default function PasswordInput({ className, ...props }) {
    const [visible, setVisible] = useState(false);
    const Icon = visible ? EyeOff : Eye;

    return (
        <div className="relative">
            <Input type={visible ? 'text' : 'password'} className={cn('pr-11', className)} {...props} />
            <button
                type="button"
                onClick={() => setVisible((v) => !v)}
                className="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-md text-muted-foreground outline-none hover:text-foreground focus-visible:ring-[3px] focus-visible:ring-ring/50"
                aria-label={visible ? 'Sembunyikan password' : 'Tampilkan password'}
                aria-pressed={visible}
            >
                <Icon className="size-5" />
            </button>
        </div>
    );
}
