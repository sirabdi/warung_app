import { usePage } from '@inertiajs/react';
import { useMemo, useRef, useState } from 'react';
import Layout from '../Components/Layout';
import { useCashierProducts, useRecordSale } from '../api/hooks';
import { formatRupiah, matches } from '../lib';

export default function Cashier({ products: initialProducts }) {
    const { lowStockThreshold } = usePage().props;
    const [query, setQuery] = useState('');
    const [cart, setCart] = useState({}); // { [productId]: qty }
    const [showCart, setShowCart] = useState(false);
    const searchRef = useRef(null);

    // Server-rendered list first, then kept fresh by TanStack Query.
    const { data: products } = useCashierProducts(initialProducts);

    const sale = useRecordSale(() => {
        setCart({});
        setShowCart(false);
        searchRef.current?.focus();
    });

    const saleError = sale.error ? (sale.error.fieldErrors?.items ?? sale.error.message) : null;

    const byId = useMemo(() => Object.fromEntries(products.map((p) => [p.id, p])), [products]);
    const list = useMemo(
        () => (query ? products.filter((p) => matches(p.name, query)) : products),
        [products, query],
    );

    const items = Object.entries(cart)
        .map(([id, qty]) => ({ product: byId[id], qty }))
        .filter((item) => item.product);
    const total = items.reduce((sum, item) => sum + item.product.sell_price * item.qty, 0);
    const count = items.reduce((sum, item) => sum + item.qty, 0);

    const setQty = (id, qty) => {
        if (sale.isError) sale.reset();
        setCart((current) => {
            const next = { ...current };
            const max = byId[id]?.stock ?? 0;
            if (qty <= 0) delete next[id];
            else next[id] = Math.min(qty, max);
            return next;
        });
    };

    const add = (product) => setQty(product.id, (cart[product.id] || 0) + 1);

    const onSearchKey = (e) => {
        // Enter = add the top search result, then clear the search box.
        if (e.key === 'Enter' && list.length) {
            const product = list.find((p) => p.stock > (cart[p.id] || 0));
            if (product) add(product);
            setQuery('');
        }
    };

    const checkout = () => {
        if (!items.length || sale.isPending) return;
        sale.mutate(items.map((item) => ({ product_id: item.product.id, qty: item.qty })));
    };

    const cartPanel = (
        <div className="flex h-full min-h-0 flex-col">
            <div className="flex items-center justify-between border-b border-stone-200 px-4 py-3">
                <h2 className="font-bold">Keranjang ({count})</h2>
                {items.length > 0 && (
                    <button onClick={() => setCart({})} className="text-sm text-red-600">
                        Kosongkan
                    </button>
                )}
            </div>
            <div className="flex-1 overflow-y-auto">
                {items.length === 0 && <p className="p-6 text-center text-stone-400">Ketuk produk untuk menambah</p>}
                {items.map(({ product, qty }) => (
                    <div key={product.id} className="flex items-center gap-2 border-b border-stone-100 px-4 py-2">
                        <div className="min-w-0 flex-1">
                            <div className="truncate font-medium">{product.name}</div>
                            <div className="text-sm text-stone-500">{formatRupiah(product.sell_price * qty)}</div>
                        </div>
                        <button onClick={() => setQty(product.id, qty - 1)} className="btn-ghost size-10 p-0 text-xl">
                            −
                        </button>
                        <span className="w-8 text-center text-lg font-bold">{qty}</span>
                        <button
                            onClick={() => setQty(product.id, qty + 1)}
                            disabled={qty >= product.stock}
                            className="btn-ghost size-10 p-0 text-xl"
                        >
                            +
                        </button>
                    </div>
                ))}
            </div>
            {saleError && <p className="bg-red-50 px-4 py-2 text-sm font-medium text-red-700">{saleError}</p>}
            <div className="border-t border-stone-200 p-4">
                <div className="mb-3 flex items-baseline justify-between">
                    <span className="text-stone-500">Total</span>
                    <span className="text-3xl font-bold">{formatRupiah(total)}</span>
                </div>
                <button
                    onClick={checkout}
                    disabled={!items.length || sale.isPending}
                    className="btn-primary w-full py-4 text-xl"
                >
                    {sale.isPending ? 'Menyimpan…' : 'Selesai ✓'}
                </button>
            </div>
        </div>
    );

    return (
        <Layout title="Kasir">
            <div className={`md:grid md:grid-cols-[1fr_340px] md:gap-4 ${items.length ? 'pb-16 md:pb-0' : ''}`}>
                <div>
                    <input
                        ref={searchRef}
                        type="search"
                        className="input sticky top-14 z-10 mb-3 shadow-sm"
                        placeholder="Cari produk… (Enter = tambah)"
                        value={query}
                        onChange={(e) => setQuery(e.target.value)}
                        onKeyDown={onSearchKey}
                    />

                    {products.length === 0 && (
                        <p className="card p-6 text-center text-stone-500">
                            Belum ada produk. Tambahkan dulu di menu <b>Produk</b>.
                        </p>
                    )}

                    <div className="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                        {list.map((product) => {
                            const inCart = cart[product.id] || 0;
                            const soldOut = product.stock <= inCart;
                            return (
                                <button
                                    key={product.id}
                                    onClick={() => add(product)}
                                    disabled={soldOut}
                                    className={`card relative p-3 text-left transition active:scale-[0.97] disabled:opacity-40 ${inCart ? 'ring-2 ring-emerald-600' : ''}`}
                                >
                                    {inCart > 0 && (
                                        <span className="absolute -top-2 -right-2 flex size-7 items-center justify-center rounded-full bg-emerald-700 text-sm font-bold text-white">
                                            {inCart}
                                        </span>
                                    )}
                                    <div className="line-clamp-2 min-h-12 font-semibold leading-tight">{product.name}</div>
                                    <div className="mt-1 font-bold text-emerald-700">{formatRupiah(product.sell_price)}</div>
                                    <div className={`text-xs ${product.stock <= lowStockThreshold ? 'text-red-600' : 'text-stone-400'}`}>
                                        {product.stock <= 0 ? 'Habis' : `Stok ${product.stock}`}
                                    </div>
                                </button>
                            );
                        })}
                    </div>
                </div>

                {/* Desktop/tablet: cart panel on the right */}
                <aside className="card sticky top-18 hidden h-[calc(100dvh-6rem)] overflow-hidden md:block">{cartPanel}</aside>
            </div>

            {/* Phone: compact bar at the bottom, tap to open the cart */}
            {items.length > 0 && !showCart && (
                <div className="fixed inset-x-0 bottom-16 z-30 p-3 md:hidden">
                    <div className="flex gap-2">
                        <button onClick={() => setShowCart(true)} className="btn-ghost flex-1 justify-between shadow-lg">
                            <span>{count} item</span>
                            <span className="font-bold">{formatRupiah(total)}</span>
                        </button>
                        <button onClick={checkout} disabled={sale.isPending} className="btn-primary shadow-lg">
                            {sale.isPending ? '…' : 'Selesai ✓'}
                        </button>
                    </div>
                    {saleError && <p className="mt-2 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700">{saleError}</p>}
                </div>
            )}
            {showCart && (
                <div className="fixed inset-0 z-40 flex flex-col bg-black/40 md:hidden" onClick={() => setShowCart(false)}>
                    <div className="mt-auto flex max-h-[85dvh] flex-col rounded-t-2xl bg-white" onClick={(e) => e.stopPropagation()}>
                        {cartPanel}
                    </div>
                </div>
            )}
        </Layout>
    );
}
