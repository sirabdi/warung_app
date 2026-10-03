import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';

function Field({ className, ...props }) {
    return <div role="group" data-slot="field" className={cn('group/field flex w-full flex-col gap-2', className)} {...props} />;
}

function FieldLabel({ className, ...props }) {
    return <Label data-slot="field-label" className={cn('w-fit leading-snug', className)} {...props} />;
}

function FieldDescription({ className, ...props }) {
    return (
        <p data-slot="field-description" className={cn('text-sm leading-normal text-muted-foreground', className)} {...props} />
    );
}

// Renders nothing when there is no message, so it can sit under every input.
function FieldError({ className, children, ...props }) {
    if (!children) return null;

    return (
        <div role="alert" data-slot="field-error" className={cn('text-sm font-normal text-destructive', className)} {...props}>
            {children}
        </div>
    );
}

export { Field, FieldLabel, FieldDescription, FieldError };
