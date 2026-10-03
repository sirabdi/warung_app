import { Link } from '@inertiajs/react';
import { ArrowRight, Loader2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import DataPagination from '@/components/DataPagination';
import Layout from '@/components/Layout';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Empty, EmptyDescription } from '@/components/ui/empty';
import { Input } from '@/components/ui/input';
import { Progress } from '@/components/ui/progress';
import { useReport } from '@/api/hooks';
import { formatNumber, formatRupiah } from '@/lib/format';
import { formatQty, isMeasured } from '@/lib/units';
import { replaceUrl } from '@/lib/url';
import { cn } from '@/lib/utils';

function StatCard({ label, value, sub, className }) {
    return (
        <Card className="gap-1 px-4 py-4">
            <CardDescription>{label}</CardDescription>
            <div className={cn('text-2xl font-bold tabular-nums', className)}>{value}</div>
            {sub && <div className="text-xs text-muted-foreground">{sub}</div>}
        </Card>
    );
}

export default function Report({ date: initialDate, isToday, summary, bestSellers, lowStock, lowStockThreshold, history }) {
    const initial = { date: initialDate, isToday, summary, bestSellers, lowStock, lowStockThreshold, history };
    const initialParams = { date: initialDate, low_page: lowStock.meta.page, history_page: history.meta.page };
    const [params, setParams] = useState(initialParams);
    const set = (changes) => setParams((current) => ({ ...current, ...changes }));

    // Switching day or page is a fetch, not a page load: the old numbers stay on
    // screen until the new ones arrive, and each combination is cached.
    const isInitial = Object.keys(initialParams).every((key) => params[key] === initialParams[key]);
    const { data: report, isFetching } = useReport(params, isInitial ? initial : undefined);

    useEffect(() => {
        replaceUrl('/report', {
            date: params.date,
            low_page: params.low_page > 1 ? params.low_page : '',
            history_page: params.history_page > 1 ? params.history_page : '',
        });
    }, [params]);

    // Another day has other sales, so its list starts at page 1 again.
    const changeDate = (value) => set({ date: value, history_page: 1 });

    // Ranked by revenue: 3 pcs and 1,5 kg cannot be compared by quantity.
    const maxRevenue = Math.max(1, ...report.bestSellers.map((item) => item.revenue));

    return (
        <Layout title="Laporan">
            <div className="mb-3 flex items-center gap-2">
                <h1 className="flex-1 text-xl font-bold">{report.isToday ? 'Hari ini' : 'Laporan'}</h1>
                {isFetching && <Loader2 className="size-4 animate-spin text-muted-foreground" />}
                <Input type="date" className="w-auto" value={params.date} onChange={(e) => changeDate(e.target.value)} />
            </div>

            <div className={cn('transition-opacity', isFetching && 'opacity-60')}>
                <div className="mb-4 grid grid-cols-2 gap-2 lg:grid-cols-4">
                    <StatCard label="Penjualan" value={formatRupiah(report.summary.revenue)} className="text-primary" />
                    <StatCard label="Laba kotor" value={formatRupiah(report.summary.profit)} sub="jual − harga beli" />
                    <StatCard
                        label="Transaksi"
                        value={formatNumber(report.summary.sales)}
                        sub={`${formatNumber(report.summary.items)} item terjual`}
                    />
                    <StatCard
                        label="Stok menipis"
                        value={formatNumber(report.lowStock.meta.total)}
                        sub={`sisa ≤ ${report.lowStockThreshold}`}
                        className={cn(report.lowStock.meta.total > 0 && 'text-destructive')}
                    />
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                    <Card className="gap-3 py-4">
                        <CardHeader className="px-4">
                            <CardTitle>Produk terlaris</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3 px-4">
                            {report.bestSellers.length === 0 && (
                                <Empty className="p-2">
                                    <EmptyDescription>Belum ada penjualan.</EmptyDescription>
                                </Empty>
                            )}
                            {report.bestSellers.map((item) => (
                                <div key={item.name} className="space-y-1">
                                    <div className="flex justify-between gap-2 text-sm">
                                        <span className="truncate font-medium">{item.name}</span>
                                        <span className="shrink-0 text-muted-foreground">
                                            {isMeasured(item.unit) ? formatQty(item.qty, item.unit) : `${item.qty}×`} ·{' '}
                                            {formatRupiah(item.revenue)}
                                        </span>
                                    </div>
                                    <Progress value={(item.revenue / maxRevenue) * 100} />
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card className="gap-3 py-4">
                        <CardHeader className="px-4">
                            <CardTitle>Stok menipis</CardTitle>
                            <CardAction>
                                <Button asChild variant="link" size="sm" className="h-auto p-0">
                                    <Link href="/stock-in">
                                        Catat masuk
                                        <ArrowRight />
                                    </Link>
                                </Button>
                            </CardAction>
                        </CardHeader>
                        <CardContent className="divide-y px-4">
                            {report.lowStock.data.length === 0 && (
                                <Empty className="p-2">
                                    <EmptyDescription>Aman, tidak ada yang menipis.</EmptyDescription>
                                </Empty>
                            )}
                            {report.lowStock.data.map((product) => (
                                <div key={product.id} className="flex items-center justify-between gap-2 py-2">
                                    <span className="truncate">{product.name}</span>
                                    <Badge variant={product.stock <= 0 ? 'destructive' : 'warning'}>
                                        {product.stock <= 0 ? 'habis' : `sisa ${formatQty(product.stock, product.unit)}`}
                                    </Badge>
                                </div>
                            ))}
                            <DataPagination
                                className="mt-0 pt-3"
                                meta={report.lowStock.meta}
                                onPageChange={(page) => set({ low_page: page })}
                            />
                        </CardContent>
                    </Card>

                    <Card className="gap-3 py-4 md:col-span-2">
                        <CardHeader className="px-4">
                            <CardTitle>Transaksi {report.isToday ? 'hari ini' : 'pada tanggal ini'}</CardTitle>
                        </CardHeader>
                        <CardContent className="divide-y px-4">
                            {report.history.data.length === 0 && (
                                <Empty className="p-2">
                                    <EmptyDescription>Belum ada transaksi.</EmptyDescription>
                                </Empty>
                            )}
                            {report.history.data.map((sale) => (
                                <div key={sale.code} className="flex items-center gap-3 py-2">
                                    <span className="w-12 text-sm text-muted-foreground tabular-nums">{sale.time}</span>
                                    <span className="flex-1 text-sm text-muted-foreground">{sale.items} item</span>
                                    <span className="font-semibold tabular-nums">{formatRupiah(sale.total)}</span>
                                </div>
                            ))}
                            <DataPagination
                                className="mt-0 pt-3"
                                meta={report.history.meta}
                                onPageChange={(page) => set({ history_page: page })}
                            />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </Layout>
    );
}
