import { Head, router, usePage } from '@inertiajs/react';
import { Loader2, LogOut, Search, ShieldCheck } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import DataPagination from '@/components/DataPagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Empty, EmptyDescription } from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { daysUntil, formatDate, formatDateTime, formatNumber, formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';

const statusBadge = {
    active: { label: 'Aktif', variant: 'default' },
    expired: { label: 'Habis', variant: 'destructive' },
    pending: { label: 'Belum bayar', variant: 'warning' },
};

const tabs = [
    { value: null, label: 'Semua', count: (s) => s.stores },
    { value: 'active', label: 'Aktif', count: (s) => s.active },
    { value: 'expired', label: 'Habis', count: (s) => s.expired },
    { value: 'pending', label: 'Belum bayar', count: (s) => s.pending },
];

function StatCard({ label, value, sub, className }) {
    return (
        <Card className="gap-1 px-4 py-4">
            <CardDescription>{label}</CardDescription>
            <div className={cn('text-2xl font-bold tabular-nums', className)}>{value}</div>
            {sub && <div className="text-xs text-muted-foreground">{sub}</div>}
        </Card>
    );
}

function StatusCell({ store, expiringDays }) {
    const badge = statusBadge[store.status];
    const daysLeft = store.status === 'active' ? daysUntil(store.ends_at) : null;

    return (
        <>
            <Badge variant={badge.variant}>{badge.label}</Badge>
            {store.ends_at && (
                <div
                    className={cn(
                        'mt-1 text-xs text-muted-foreground',
                        daysLeft !== null && daysLeft <= expiringDays && 'font-medium text-warning',
                    )}
                >
                    {store.status === 'active' ? `s.d. ${formatDate(store.ends_at)} · sisa ${daysLeft} hari` : `sejak ${formatDate(store.ends_at)}`}
                </div>
            )}
        </>
    );
}

export default function Dashboard({ summary, stores, recentPayments, filters, expiringDays }) {
    const { auth } = usePage().props;
    const [search, setSearch] = useState(filters.search);
    const debouncedSearch = useDebouncedValue(search);
    const [loading, setLoading] = useState(false);

    // Filters live in the URL, so a reload or a shared link shows the same list.
    // Empty values and page 1 stay out of it: /admin, not /admin?status=&page=1.
    const visit = (changes) => {
        const params = { status: filters.status, search: filters.search, page: stores.meta.page, ...changes };
        const query = Object.fromEntries(
            Object.entries(params).filter(([key, value]) => value !== null && value !== '' && !(key === 'page' && value === 1)),
        );

        router.get('/admin', query, {
            only: ['stores', 'filters'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onStart: () => setLoading(true),
            onFinish: () => setLoading(false),
        });
    };

    // A new search starts at page 1. Skips the first render: the server already sent that list.
    const firstRender = useRef(true);
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        if (debouncedSearch.trim() !== filters.search) visit({ search: debouncedSearch.trim(), page: 1 });
    }, [debouncedSearch]);

    return (
        <div className="min-h-dvh">
            <Head title="Admin" />
            <header className="sticky top-0 z-20 border-b bg-card/90 backdrop-blur">
                <div className="mx-auto flex h-14 max-w-6xl items-center gap-2 px-3 md:px-4">
                    <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <ShieldCheck className="size-4" />
                    </span>
                    <span className="truncate font-semibold">Admin Warung</span>
                    <Button variant="ghost" size="sm" className="ml-auto text-muted-foreground" onClick={() => router.post('/logout')}>
                        <LogOut />
                        <span className="hidden sm:inline">Keluar ({auth.user?.name})</span>
                        <span className="sm:hidden">Keluar</span>
                    </Button>
                </div>
            </header>

            <main className="mx-auto max-w-6xl space-y-4 p-3 md:p-4">
                <div className="grid grid-cols-2 gap-2 lg:grid-cols-4">
                    <StatCard
                        label="Pelanggan aktif"
                        value={formatNumber(summary.active)}
                        sub={`dari ${formatNumber(summary.stores)} toko terdaftar`}
                        className="text-primary"
                    />
                    <StatCard
                        label="Segera habis"
                        value={formatNumber(summary.expiringSoon)}
                        sub={`dalam ${expiringDays} hari`}
                        className={cn(summary.expiringSoon > 0 && 'text-warning')}
                    />
                    <StatCard label="Pendapatan bulan ini" value={formatRupiah(summary.revenueThisMonth)} />
                    <StatCard label="Total pendapatan" value={formatRupiah(summary.revenueTotal)} sub="semua pembayaran lunas" />
                </div>

                <Card className="gap-0 py-0">
                    <CardHeader className="gap-3 px-4 py-4">
                        <div className="flex items-center gap-2">
                            <CardTitle className="flex-1">Toko pelanggan</CardTitle>
                            {loading && <Loader2 className="size-4 animate-spin text-muted-foreground" />}
                        </div>
                        <div className="flex flex-col gap-2 md:flex-row md:items-center">
                            <div role="tablist" aria-label="Status langganan" className="flex flex-wrap gap-1">
                                {tabs.map((tab) => (
                                    <Button
                                        key={tab.label}
                                        role="tab"
                                        aria-selected={filters.status === tab.value}
                                        size="sm"
                                        variant={filters.status === tab.value ? 'default' : 'outline'}
                                        onClick={() => visit({ status: tab.value, page: 1 })}
                                    >
                                        {tab.label}
                                        <span className="tabular-nums opacity-70">{formatNumber(tab.count(summary))}</span>
                                    </Button>
                                ))}
                            </div>
                            <div className="relative md:ml-auto md:w-72">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    type="search"
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    placeholder="Cari toko, pemilik, atau email"
                                    aria-label="Cari toko"
                                    className="h-9 pl-9"
                                />
                            </div>
                        </div>
                    </CardHeader>

                    {stores.data.length === 0 ? (
                        <Empty className="border-t py-10">
                            <EmptyDescription>
                                {filters.search ? `Tidak ada toko yang cocok dengan “${filters.search}”.` : 'Belum ada toko di sini.'}
                            </EmptyDescription>
                        </Empty>
                    ) : (
                        <div className={cn('border-t transition-opacity', loading && 'opacity-60')}>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="pl-4">Toko</TableHead>
                                        <TableHead>Pemilik</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Total bayar</TableHead>
                                        <TableHead className="pr-4 text-right">Daftar</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {stores.data.map((store) => (
                                        <TableRow key={store.id}>
                                            <TableCell className="pl-4">
                                                <div className="font-medium">{store.name}</div>
                                                <div className="text-xs text-muted-foreground">{store.phone}</div>
                                            </TableCell>
                                            <TableCell>
                                                <div>{store.owner_name ?? '—'}</div>
                                                {store.owner_email && (
                                                    <a href={`mailto:${store.owner_email}`} className="text-xs text-muted-foreground hover:underline">
                                                        {store.owner_email}
                                                    </a>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <StatusCell store={store} expiringDays={expiringDays} />
                                            </TableCell>
                                            <TableCell className="text-right tabular-nums">
                                                {formatRupiah(store.paid_total)}
                                                <div className="text-xs text-muted-foreground">
                                                    {store.paid_count > 0
                                                        ? `${store.paid_count}× · terakhir ${formatDate(store.last_paid_at)}`
                                                        : 'belum pernah'}
                                                </div>
                                            </TableCell>
                                            <TableCell className="pr-4 text-right text-muted-foreground tabular-nums">
                                                {formatDate(store.registered_at)}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                    )}
                    <DataPagination
                        meta={stores.meta}
                        onPageChange={(page) => visit({ page })}
                        disabled={loading}
                        className="border-t px-4 py-3"
                    />
                </Card>

                {recentPayments.length > 0 && (
                    <Card className="gap-0 py-0">
                        <CardHeader className="px-4 py-4">
                            <CardTitle>Pembayaran terbaru</CardTitle>
                        </CardHeader>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-4">Tanggal</TableHead>
                                    <TableHead>Toko</TableHead>
                                    <TableHead>Paket</TableHead>
                                    <TableHead className="pr-4 text-right">Jumlah</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {recentPayments.map((payment) => (
                                    <TableRow key={payment.external_id}>
                                        <TableCell className="pl-4 text-muted-foreground tabular-nums">
                                            {formatDateTime(payment.paid_at)}
                                        </TableCell>
                                        <TableCell className="font-medium">{payment.store_name}</TableCell>
                                        <TableCell>{payment.plan_label}</TableCell>
                                        <TableCell className="pr-4 text-right tabular-nums">{formatRupiah(payment.amount)}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </Card>
                )}
            </main>
        </div>
    );
}
