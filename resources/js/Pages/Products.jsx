import { useMemo, useState } from 'react';
import Layout from '../Components/Layout';
import NumberInput from '../Components/NumberInput';
import { useProducts, useSaveProduct } from '../api/hooks';
import { formatRupiah, matches } from '../lib';

const emptyForm = { name: '', sell_price: '', cost_price: '', stock: '' };

function ProductForm({ product, onDone }) {
    const [form, setForm] = useState(
        product
            ? { name: product.name, sell_price: product.sell_price, cost_price: product.cost_price, stock: '' }
            : emptyForm,
    );

    const save = useSaveProduct(() => {
        setForm(emptyForm);
        onDone?.();
    });

    const errors = save.error?.fieldErrors ?? {};
    const set = (field, value) => {
        if (save.isError) save.reset();
        setForm((current) => ({ ...current, [field]: value }));
    };

    const submit = (e) => {
        e.preventDefault();
        save.mutate({
            id: product?.id,
            name: form.name,
            sell_price: form.sell_price === '' ? 0 : form.sell_price,
            cost_price: form.cost_price === '' ? null : form.cost_price,
            ...(product ? {} : { stock: form.stock === '' ? null : form.stock }),
        });
    };

    return (
        <form onSubmit={submit} className="space-y-2">
            <div className="grid gap-2 sm:grid-cols-[2fr_1fr_1fr_auto]">
                <div>
                    <input
                        className="input"
                        placeholder="Nama produk"
                        value={form.name}
                        onChange={(e) => set('name', e.target.value)}
                        autoFocus={!product}
                    />
                    {errors.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
                </div>
                <div>
                    <NumberInput
                        placeholder="Harga jual"
                        value={form.sell_price}
                        onChange={(value) => set('sell_price', value)}
                    />
                    {errors.sell_price && <p className="mt-1 text-sm text-red-600">{errors.sell_price}</p>}
                </div>
                <NumberInput
                    placeholder="Harga beli (ops.)"
                    value={form.cost_price}
                    onChange={(value) => set('cost_price', value)}
                />
                {!product && (
                    <NumberInput placeholder="Stok awal" value={form.stock} onChange={(value) => set('stock', value)} />
                )}
                <div className="flex gap-2">
                    <button className="btn-primary flex-1" disabled={save.isPending}>
                        {save.isPending ? 'Menyimpan…' : 'Simpan'}
                    </button>
                    {product && (
                        <button type="button" onClick={onDone} className="btn-ghost">
                            Batal
                        </button>
                    )}
                </div>
            </div>
        </form>
    );
}

export default function Products({ products: initialProducts }) {
    const [query, setQuery] = useState('');
    const [editId, setEditId] = useState(null);

    const { data: products } = useProducts(initialProducts);

    const list = useMemo(
        () => (query ? products.filter((p) => matches(p.name, query)) : products),
        [products, query],
    );

    return (
        <Layout title="Produk">
            <div className="card mb-3 p-3">
                <h2 className="mb-2 font-bold">Tambah produk</h2>
                <ProductForm />
            </div>

            <input
                type="search"
                className="input mb-2"
                placeholder="Cari produk…"
                value={query}
                onChange={(e) => setQuery(e.target.value)}
            />

            <div className="card divide-y divide-stone-100">
                {list.length === 0 && <p className="p-6 text-center text-stone-400">Belum ada produk.</p>}
                {list.map((product) =>
                    editId === product.id ? (
                        <div key={product.id} className="p-3">
                            <ProductForm product={product} onDone={() => setEditId(null)} />
                        </div>
                    ) : (
                        <div key={product.id} className="flex items-center gap-3 p-3">
                            <div className="min-w-0 flex-1">
                                <div className="truncate font-semibold">{product.name}</div>
                                <div className="text-sm text-stone-500">
                                    Jual {formatRupiah(product.sell_price)}
                                    {product.cost_price > 0 && ` · Beli ${formatRupiah(product.cost_price)}`}
                                </div>
                            </div>
                            <div className="text-right">
                                <div className={`font-bold ${product.stock <= 0 ? 'text-red-600' : ''}`}>{product.stock}</div>
                                <div className="text-xs text-stone-400">stok</div>
                            </div>
                            <button onClick={() => setEditId(product.id)} className="btn-ghost px-3 py-1.5 text-sm">
                                Edit
                            </button>
                        </div>
                    ),
                )}
            </div>
        </Layout>
    );
}
