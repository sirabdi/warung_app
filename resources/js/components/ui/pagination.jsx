import { ChevronLeftIcon, ChevronRightIcon, MoreHorizontalIcon } from 'lucide-react';
import { buttonVariants } from '@/components/ui/button';
import { cn } from '@/lib/utils';

function Pagination({ className, ...props }) {
    return (
        <nav
            role="navigation"
            aria-label="pagination"
            data-slot="pagination"
            className={cn('mx-auto flex w-full justify-center', className)}
            {...props}
        />
    );
}

function PaginationContent({ className, ...props }) {
    return <ul data-slot="pagination-content" className={cn('flex flex-row items-center gap-1', className)} {...props} />;
}

function PaginationItem(props) {
    return <li data-slot="pagination-item" {...props} />;
}

// A <button> instead of shadcn's <a>: pages here are client state, not URLs.
function PaginationLink({ className, isActive, size = 'icon', ...props }) {
    return (
        <button
            type="button"
            aria-current={isActive ? 'page' : undefined}
            data-slot="pagination-link"
            data-active={isActive}
            className={cn(buttonVariants({ variant: isActive ? 'outline' : 'ghost', size }), className)}
            {...props}
        />
    );
}

function PaginationPrevious({ className, ...props }) {
    return (
        <PaginationLink aria-label="Halaman sebelumnya" size="default" className={cn('gap-1 px-2.5 sm:pl-2.5', className)} {...props}>
            <ChevronLeftIcon />
            <span className="hidden sm:block">Sebelumnya</span>
        </PaginationLink>
    );
}

function PaginationNext({ className, ...props }) {
    return (
        <PaginationLink aria-label="Halaman berikutnya" size="default" className={cn('gap-1 px-2.5 sm:pr-2.5', className)} {...props}>
            <span className="hidden sm:block">Berikutnya</span>
            <ChevronRightIcon />
        </PaginationLink>
    );
}

function PaginationEllipsis({ className, ...props }) {
    return (
        <span aria-hidden data-slot="pagination-ellipsis" className={cn('flex size-9 items-center justify-center', className)} {...props}>
            <MoreHorizontalIcon className="size-4" />
            <span className="sr-only">Halaman lain</span>
        </span>
    );
}

export {
    Pagination,
    PaginationContent,
    PaginationLink,
    PaginationItem,
    PaginationPrevious,
    PaginationNext,
    PaginationEllipsis,
};
