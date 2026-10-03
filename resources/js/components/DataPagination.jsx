import {
    Pagination,
    PaginationContent,
    PaginationEllipsis,
    PaginationItem,
    PaginationLink,
    PaginationNext,
    PaginationPrevious,
} from '@/components/ui/pagination';
import { formatNumber } from '@/lib/format';
import { cn } from '@/lib/utils';

// 1 … 4 5 6 … 12 — first, last, and the neighbours of the current page.
function pageNumbers(page, pageCount) {
    if (pageCount <= 7) return Array.from({ length: pageCount }, (_, i) => i + 1);

    const middle = [page - 1, page, page + 1].filter((n) => n > 1 && n < pageCount);
    return [1, middle[0] > 2 ? '…' : null, ...middle, middle.at(-1) < pageCount - 1 ? '…' : null, pageCount].filter(
        (n) => n !== null,
    );
}

// `meta` comes straight from the server: { page, per_page, total, last_page }.
export default function DataPagination({ meta, onPageChange, disabled = false, className }) {
    if (!meta || meta.total === 0) return null;

    const { page, per_page: perPage, total, last_page: pageCount } = meta;
    const from = (page - 1) * perPage + 1;
    const to = Math.min(page * perPage, total);

    return (
        <div className={cn('mt-3 flex flex-col items-center gap-2 sm:flex-row sm:justify-between', className)}>
            <p className="text-sm text-muted-foreground">
                {formatNumber(from)}–{formatNumber(to)} dari {formatNumber(total)}
            </p>
            {pageCount > 1 && (
                <Pagination className="mx-0 w-auto">
                    <PaginationContent>
                        <PaginationItem>
                            <PaginationPrevious disabled={disabled || page <= 1} onClick={() => onPageChange(page - 1)} />
                        </PaginationItem>
                        {pageNumbers(page, pageCount).map((n, i) => (
                            <PaginationItem key={`${n}-${i}`}>
                                {n === '…' ? (
                                    <PaginationEllipsis />
                                ) : (
                                    <PaginationLink isActive={n === page} disabled={disabled} onClick={() => onPageChange(n)}>
                                        {n}
                                    </PaginationLink>
                                )}
                            </PaginationItem>
                        ))}
                        <PaginationItem>
                            <PaginationNext
                                disabled={disabled || page >= pageCount}
                                onClick={() => onPageChange(page + 1)}
                            />
                        </PaginationItem>
                    </PaginationContent>
                </Pagination>
            )}
        </div>
    );
}
