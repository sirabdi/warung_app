import { Link } from '@inertiajs/react';
import { useState } from 'react';
import Layout from '../Components/Layout';
import { useReport } from '../api/hooks';
import { formatNumber, formatRupiah } from '../lib';

function Card({ label, value, sub, color = 'text-stone-900' }) {
    return (
        <div className="card p-4">
            <div className="text-sm text-stone-500">{label}</div>
            <div className={`text-2xl font-bold ${color}`}>{value}</div>
            {sub && <div className="text-xs text-stone-400">{sub}</div>}
        </div>
    );
}

export default function Report({ date: initialDate, isToday, summary, bestSellers, lowStock, lowStockThreshold, history }) {
    const initial = { date: initialDate, isToday, summary, bestSellers, lowStock, lowStockThreshold, history };
    const [date, setDate] = useState(initialDate);

    // Switching day is a fetch, not a page load: the old numbers stay on screen
    // until the new ones arrive, and each day is cached.
    const { data: report, isFetching } = useReport(date, date === initialDate ? initial : undefined);

    const changeDate = (value) => {
        setDate(value);
        window.history.replaceState({}, '', value ? `/report?date=${value}` : '/report');
    };

    const maxQty = Math.max(1, ...report.bestSellers.map((item) => item.qty));

    return (
        <Layout title="Laporan">
            <div className="mb-3 flex items-center gap-2">
                <h1 className="flex-1 text-xl font-bold">{report.isToday ? 'Hari ini' : 'Laporan'}</h1>
                {isFetching && <span className="text-sm text-stone-400">memuat…</span>}
                <input type="date" className="input w-auto" value={date} onChange={(e) => changeDate(e.target.value)} />
            </div>

            <div className={`transition-opacity ${isFetching ? 'opacity-60' : ''}`}>
                <div className="mb-4 grid grid-cols-2 gap-2 lg:grid-cols-4">
                    <Card label="Penjualan" value={formatRupiah(report.summary.revenue)} color="text-emerald-700" />
                    <Card label="Laba kotor" value={formatRupiah(report.summary.profit)} sub="jual − harga beli" />
                    <Card
                        label="Transaksi"
                        value={formatNumber(report.summary.sales)}
                        sub={`${formatNumber(report.summary.items)} item terjual`}
                    />
                    <Card
                        label="Stok menipis"
                        value={formatNumber(report.lowStock.length)}
                        sub={`sisa ≤ ${report.lowStockThreshold}`}
                        color={report.lowStock.length ? 'text-red-600' : 'text-stone-900'}
                    />
                </div>

                <div className="grid gap-3 md:grid-cols-2">
                    <section className="card p-4">
                        <h2 className="mb-3 font-bold">Produk terlaris</h2>
                        {report.bestSellers.length === 0 && <p className="text-stone-400">Belum ada penjualan.</p>}
                        <div className="space-y-2">
                            {report.bestSellers.map((item) => (
                                <div key={item.name}>
                                    <div className="flex justify-between text-sm">
                                        <span className="truncate font-medium">{item.name}</span>
                                        <span className="shrink-0 text-stone-500">
                                            {item.qty}× · {formatRupiah(item.revenue)}
                                        </span>
                                    </div>
                                    <div className="mt-1 h-2 rounded-full bg-stone-100">
                                        <div
                                            className="h-2 rounded-full bg-emerald-600"
                                            style={{ width: `${(item.qty / maxQty) * 100}%` }}
                                        />
                                    </div>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="card p-4">
                        <div className="mb-3 flex items-center justify-between">
                            <h2 className="font-bold">Stok menipis</h2>
                            <Link href="/stock-in" className="text-sm font-medium text-emerald-700">
                                Catat masuk →
                            </Link>
                        </div>
                        {report.lowStock.length === 0 && <p className="text-stone-400">Aman, tidak ada yang menipis.</p>}
                        <div className="divide-y divide-stone-100">
                            {report.lowStock.map((product) => (
                                <div key={product.id} className="flex items-center justify-between py-2">
                                    <span className="truncate">{product.name}</span>
                                    <span className={`font-bold ${product.stock <= 0 ? 'text-red-600' : 'text-amber-600'}`}>
                                        {product.stock <= 0 ? 'habis' : `sisa ${product.stock}`}
                                    </span>
                                </div>
                            ))}
                        </div>
                    </section>

                    <section className="card p-4 md:col-span-2">
                        <h2 className="mb-2 font-bold">Transaksi {report.isToday ? 'hari ini' : 'pada tanggal ini'}</h2>
                        {report.history.length === 0 && <p className="text-stone-400">Belum ada transaksi.</p>}
                        <div className="divide-y divide-stone-100">
                            {report.history.map((sale) => (
                                <div key={sale.code} className="flex items-center gap-3 py-2">
                                    <span className="w-12 text-sm text-stone-500">{sale.time}</span>
                                    <span className="flex-1 text-sm text-stone-400">{sale.items} item</span>
                                    <span className="font-semibold">{formatRupiah(sale.total)}</span>
                                </div>
                            ))}
                        </div>
                    </section>
                </div>
            </div>
        </Layout>
    );
}
