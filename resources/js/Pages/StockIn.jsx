import { useMemo, useRef, useState } from 'react';
import Layout from '../Components/Layout';
import NumberInput from '../Components/NumberInput';
import { useProducts, useRecordStockIn, useStockInHistory } from '../api/hooks';
import { matches } from '../lib';

const quickAdd = [1, 5, 10, 12, 24];

export default function StockIn({ products: initialProducts, history: initialHistory }) {
    const [query, setQuery] = useState('');
    const [selectedId, setSelectedId] = useState(null);
    const [qty, setQty] = useState('');
    const qtyRef = useRef(null);

    const { data: products } = useProducts(initialProducts);
    const { data: history } = useStockInHistory(initialHistory);

    const stockIn = useRecordStockIn(() => {
        setSelectedId(null);
        setQty('');
        setQuery('');
    });

    const errors = stockIn.error?.fieldErrors ?? {};
    // Read the selected product from the query cache so its stock stays current.
    const selected = products.find((p) => p.id === selectedId) ?? null;

    const list = useMemo(
        () => (query ? products.filter((p) => matches(p.name, query)) : products),
        [products, query],
    );

    const select = (product) => {
        if (stockIn.isError) stockIn.reset();
        setSelectedId(product.id);
        setQty('');
        setTimeout(() => qtyRef.current?.focus(), 50);
    };

    const submit = (e) => {
        e?.preventDefault();
        if (!selected || !qty) return;
        stockIn.mutate({ product_id: selected.id, qty });
    };

    return (
        <Layout title="Stok Masuk">
            {selected ? (
                <form onSubmit={submit} className="card mb-3 p-4">
                    <div className="mb-3 flex items-center justify-between">
                        <div>
                            <div className="text-lg font-bold">{selected.name}</div>
                            <div className="text-sm text-stone-500">Stok sekarang {selected.stock}</div>
                        </div>
                        <button type="button" onClick={() => setSelectedId(null)} className="btn-ghost px-3 py-1.5 text-sm">
                            Ganti
                        </button>
                    </div>
                    <div className="mb-2 flex gap-2">
                        {quickAdd.map((n) => (
                            <button
                                key={n}
                                type="button"
                                onClick={() => setQty((current) => (current || 0) + n)}
                                className="btn-ghost flex-1 px-0"
                            >
                                +{n}
                            </button>
                        ))}
                    </div>
                    <div className="flex gap-2">
                        <NumberInput
                            ref={qtyRef}
                            placeholder="Jumlah masuk"
                            value={qty}
                            onChange={setQty}
                            className="input flex-1 text-center text-2xl font-bold"
                        />
                        <button className="btn-primary px-8" disabled={stockIn.isPending || !qty}>
                            {stockIn.isPending ? '…' : 'Simpan'}
                        </button>
                    </div>
                    {errors.qty && <p className="mt-1 text-sm text-red-600">{errors.qty}</p>}
                </form>
            ) : (
                <>
                    <input
                        type="search"
                        className="input mb-2"
                        placeholder="Cari produk yang masuk…"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                    />
                    {errors.product_id && <p className="mb-2 text-sm text-red-600">{errors.product_id}</p>}
                    <div className="card mb-3 divide-y divide-stone-100">
                        {list.length === 0 && (
                            <p className="p-6 text-center text-stone-400">
                                Belum ada produk. Tambahkan dulu di menu <b>Produk</b>.
                            </p>
                        )}
                        {list.map((product) => (
                            <button
                                key={product.id}
                                onClick={() => select(product)}
                                className="flex w-full items-center gap-3 p-3 text-left"
                            >
                                <span className="flex-1 truncate font-semibold">{product.name}</span>
                                <span className="text-sm text-stone-500">stok {product.stock}</span>
                                <span className="text-xl text-emerald-700">＋</span>
                            </button>
                        ))}
                    </div>
                </>
            )}

            <h2 className="mb-2 px-1 font-bold">Barang masuk terakhir</h2>
            <div className="card divide-y divide-stone-100">
                {history.length === 0 && <p className="p-6 text-center text-stone-400">Belum ada catatan.</p>}
                {history.map((entry) => (
                    <div key={entry.id} className="flex items-center gap-3 p-3">
                        <span className="flex-1 truncate">{entry.name}</span>
                        <span className="font-bold text-emerald-700">+{entry.qty}</span>
                        <span className="w-20 text-right text-sm text-stone-400">{entry.time}</span>
                    </div>
                ))}
            </div>
        </Layout>
    );
}
