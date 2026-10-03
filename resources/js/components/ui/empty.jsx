import { cn } from '@/lib/utils';

function Empty({ className, ...props }) {
    return (
        <div
            data-slot="empty"
            className={cn(
                'flex min-w-0 flex-1 flex-col items-center justify-center gap-4 rounded-md p-6 text-center text-balance',
                className,
            )}
            {...props}
        />
    );
}

function EmptyHeader({ className, ...props }) {
    return <div data-slot="empty-header" className={cn('flex max-w-sm flex-col items-center gap-2 text-center', className)} {...props} />;
}

function EmptyTitle({ className, ...props }) {
    return <div data-slot="empty-title" className={cn('text-lg font-medium tracking-tight', className)} {...props} />;
}

function EmptyDescription({ className, ...props }) {
    return (
        <div
            data-slot="empty-description"
            className={cn('text-sm/relaxed text-muted-foreground [&>a]:underline [&>a]:underline-offset-4', className)}
            {...props}
        />
    );
}

export { Empty, EmptyHeader, EmptyTitle, EmptyDescription };
