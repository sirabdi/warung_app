import { usePage } from "@inertiajs/react";
import {
    AlertCircle,
    Check,
    Minus,
    Monitor,
    Pencil,
    Plus,
    Search,
    ShoppingCart,
    Trash2,
} from "lucide-react";
import { useQueryClient } from "@tanstack/react-query";
import { useRef, useState } from "react";
import DataPagination from "@/components/DataPagination";
import Layout from "@/components/Layout";
import NumberInput from "@/components/NumberInput";
import WeighDialog from "@/components/WeighDialog";
import { Alert, AlertTitle } from "@/components/ui/alert";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import { Card } from "@/components/ui/card";
import { Empty, EmptyDescription } from "@/components/ui/empty";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectSeparator,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetTitle,
} from "@/components/ui/sheet";
import {
    cashierProductsQuery,
    useCashierProducts,
    useCategoryOptions,
    useRecordSale,
} from "@/api/hooks";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import {
    cashSuggestions,
    openCustomerDisplay,
    useCustomerDisplayFeed,
} from "@/lib/customer-display";
import { formatRupiah } from "@/lib/format";
import {
    formatPrice,
    formatQty,
    isLowStock,
    isMeasured,
    itemCount,
    lineTotal,
} from "@/lib/units";
import { cn } from "@/lib/utils";

const ALL = "all";

// What the buyer paid, with one-tap amounts: the exact total and the notes
// they are likely to hand over. Optional: leave it empty to just sell.
function Payment({ total, paid, change, onPaidChange }) {
    const amounts = [total, ...cashSuggestions(total)];

    return (
        <div className="mb-3 space-y-2">
            <div className="flex items-center gap-2">
                <label
                    htmlFor="paid"
                    className="w-24 shrink-0 text-sm text-muted-foreground"
                >
                    Uang dibayar
                </label>
                <NumberInput
                    id="paid"
                    placeholder="Opsional"
                    className="text-right text-lg font-semibold tabular-nums"
                    value={paid}
                    onChange={onPaidChange}
                />
            </div>
            <div className="flex flex-wrap gap-1">
                {amounts.map((amount, index) => (
                    <Button
                        key={amount}
                        type="button"
                        size="sm"
                        variant={paid === amount ? "default" : "outline"}
                        onClick={() => onPaidChange(amount)}
                        className="flex-1 tabular-nums"
                    >
                        {index === 0 ? "Uang pas" : formatRupiah(amount)}
                    </Button>
                ))}
            </div>
            {change !== null && (
                <div className="flex items-baseline justify-between">
                    <span className="text-muted-foreground">
                        {change < 0 ? "Kurang" : "Kembalian"}
                    </span>
                    <span
                        className={cn(
                            "text-2xl font-bold tabular-nums",
                            change < 0 ? "text-destructive" : "text-primary",
                        )}
                    >
                        {formatRupiah(Math.abs(change))}
                    </span>
                </div>
            )}
        </div>
    );
}

// The cart is cleared after "Selesai"; the change stays in view until the next item.
function LastSale({ sale }) {
    return (
        <div className="space-y-2 p-4 text-center">
            <Check className="mx-auto size-8 text-primary" />
            <div className="font-semibold">Transaksi selesai</div>
            <div className="text-sm text-muted-foreground">
                Total {formatRupiah(sale.total)}
                {sale.paid !== null && ` · dibayar ${formatRupiah(sale.paid)}`}
            </div>
            {sale.paid !== null && (
                <div>
                    <div className="text-sm text-muted-foreground">
                        Kembalian
                    </div>
                    <div className="text-3xl font-bold text-primary tabular-nums">
                        {formatRupiah(sale.paid - sale.total)}
                    </div>
                </div>
            )}
        </div>
    );
}

export default function Cashier({
    products: initialPage,
    categories: initialCategories,
}) {
    const { lowStockThreshold } = usePage().props;
    const queryClient = useQueryClient();
    const [query, setQuery] = useState("");
    const [category, setCategory] = useState(ALL);
    // { [productId]: { product, qty } } — qty in pcs / gram / ml. The product is
    // kept with it because the line may be on another page of the list.
    const [cart, setCart] = useState({});
    const [weighing, setWeighing] = useState(null); // product in the weigh dialog
    const [showCart, setShowCart] = useState(false);
    // What the buyer handed over, '' until typed. Only for the change: the sale
    // itself does not store it.
    const [paid, setPaid] = useState("");
    const [highlight, setHighlight] = useState(null); // last product touched
    const [lastSale, setLastSale] = useState(null); // { code, total, paid }
    const searchRef = useRef(null);
    const paidRef = useRef(paid);
    paidRef.current = paid;

    // The page belongs to one filter: a new search or category starts at page 1.
    const debouncedQuery = useDebouncedValue(query.trim());
    const filterKey = `${debouncedQuery}|${category}`;
    const [paging, setPaging] = useState({ filterKey: `|${ALL}`, page: 1 });
    const page = paging.filterKey === filterKey ? paging.page : 1;
    const setPage = (next) => setPaging({ filterKey, page: next });

    const params = {
        page,
        search: debouncedQuery,
        category: category === ALL ? "" : category,
    };
    const isInitial = page === 1 && filterKey === `|${ALL}`;
    const { data, isPlaceholderData } = useCashierProducts(
        params,
        isInitial ? initialPage : undefined,
    );
    const list = data.data;

    const { data: allCategories } = useCategoryOptions(initialCategories);
    const categories = allCategories.filter((item) => item.products_count > 0);

    const sale = useRecordSale((result) => {
        setLastSale({
            code: result.code,
            total: result.total,
            paid: paidRef.current === "" ? null : paidRef.current,
        });
        setCart({});
        setPaid("");
        setHighlight(null);
        setShowCart(false);
        searchRef.current?.focus();
    });

    const saleError = sale.error
        ? (sale.error.fieldErrors?.items ?? sale.error.message)
        : null;

    // Prefer the freshest copy of a product (its stock may have changed).
    const items = Object.values(cart).map((line) => ({
        product: list.find((p) => p.id === line.product.id) ?? line.product,
        qty: line.qty,
    }));
    const total = items.reduce(
        (sum, item) =>
            sum + lineTotal(item.product.sell_price, item.qty, item.product.unit),
        0,
    );
    const count = items.reduce(
        (sum, item) => sum + itemCount(item.qty, item.product.unit),
        0,
    );
    const qtyInCart = (product) => cart[product.id]?.qty ?? 0;
    const change = paid === "" ? null : paid - total;
    const short = change !== null && change < 0;

    // The second monitor follows the cart, the payment, and the finished sale.
    useCustomerDisplayFeed({
        lines: items.map(({ product, qty }) => ({
            id: product.id,
            name: product.name,
            unit: product.unit,
            qty,
            price: product.sell_price,
            total: lineTotal(product.sell_price, qty, product.unit),
        })),
        count,
        total,
        paid: paid === "" ? null : paid,
        highlight,
        done: items.length === 0 ? lastSale : null,
    });

    const clearCart = () => {
        setCart({});
        setPaid("");
        setHighlight(null);
    };

    const setQty = (product, qty) => {
        if (sale.isError) sale.reset();
        setLastSale(null);
        setHighlight(qty > 0 ? product.id : null);
        setCart((current) => {
            const next = { ...current };
            if (qty <= 0) delete next[product.id];
            else next[product.id] = { product, qty: Math.min(qty, product.stock) };
            return next;
        });
    };

    // Pieces: one more per tap. Weighed goods: ask how much first.
    const add = (product) =>
        isMeasured(product.unit)
            ? setWeighing(product)
            : setQty(product, qtyInCart(product) + 1);

    // Enter = add the top result of what is typed right now, then clear the box.
    // The list on screen may still be waiting for the debounce, so ask directly.
    const onSearchKey = async (e) => {
        if (e.key !== "Enter" || !query.trim()) return;
        e.preventDefault();
        const result = await queryClient.fetchQuery(
            cashierProductsQuery({ ...params, page: 1, search: query.trim() }),
        );
        const product = result.data.find((p) => p.stock > qtyInCart(p));
        if (product) add(product);
        setQuery("");
    };

    const checkout = () => {
        if (!items.length || sale.isPending || short) return;
        sale.mutate(
            items.map((item) => ({
                product_id: item.product.id,
                qty: item.qty,
            })),
        );
    };

    const cartPanel = (
        <div className="flex h-full min-h-0 flex-col">
            <div className="flex items-center justify-between border-b px-4 py-3">
                <h2 className="flex items-center gap-2 font-semibold">
                    <ShoppingCart className="size-4" />
                    Keranjang
                    <Badge variant="secondary">{count}</Badge>
                </h2>
                <div className="flex items-center gap-1">
                    {items.length > 0 && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={clearCart}
                            className="text-destructive"
                        >
                            <Trash2 />
                            Kosongkan
                        </Button>
                    )}
                    {/* A second monitor needs a computer: not offered on phones. */}
                    <Button
                        variant="ghost"
                        size="icon"
                        onClick={openCustomerDisplay}
                        className="hidden text-muted-foreground md:inline-flex"
                        title="Buka layar pelanggan (monitor kedua)"
                    >
                        <Monitor />
                        <span className="sr-only">Buka layar pelanggan</span>
                    </Button>
                </div>
            </div>
            <div className="flex-1 overflow-y-auto">
                {items.length === 0 &&
                    (lastSale ? (
                        <LastSale sale={lastSale} />
                    ) : (
                        <Empty>
                            <EmptyDescription>
                                Ketuk produk untuk menambah
                            </EmptyDescription>
                        </Empty>
                    ))}
                {items.map(({ product, qty }) => (
                    <div
                        key={product.id}
                        className="flex items-center gap-2 border-b px-4 py-2 last:border-b-0"
                    >
                        <div className="min-w-0 flex-1">
                            <div className="truncate font-medium">
                                {product.name}
                            </div>
                            <div className="text-sm text-muted-foreground">
                                {isMeasured(product.unit) &&
                                    `${formatQty(qty, product.unit)} · `}
                                {formatRupiah(
                                    lineTotal(
                                        product.sell_price,
                                        qty,
                                        product.unit,
                                    ),
                                )}
                            </div>
                        </div>
                        {isMeasured(product.unit) ? (
                            <>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    onClick={() => setWeighing(product)}
                                >
                                    <Pencil />
                                    <span className="sr-only">
                                        Ubah berat {product.name}
                                    </span>
                                </Button>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    onClick={() => setQty(product, 0)}
                                    className="text-destructive hover:text-destructive"
                                >
                                    <Trash2 />
                                    <span className="sr-only">
                                        Hapus {product.name}
                                    </span>
                                </Button>
                            </>
                        ) : (
                            <>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    onClick={() => setQty(product, qty - 1)}
                                >
                                    <Minus />
                                </Button>
                                <span className="w-8 text-center text-lg font-bold tabular-nums">
                                    {qty}
                                </span>
                                <Button
                                    variant="outline"
                                    size="icon"
                                    onClick={() => setQty(product, qty + 1)}
                                    disabled={qty >= product.stock}
                                >
                                    <Plus />
                                </Button>
                            </>
                        )}
                    </div>
                ))}
            </div>
            {saleError && (
                <Alert
                    variant="destructive"
                    className="rounded-none border-x-0 border-b-0"
                >
                    <AlertCircle />
                    <AlertTitle className="line-clamp-none">
                        {saleError}
                    </AlertTitle>
                </Alert>
            )}
            <div className="border-t p-4">
                <div className="mb-3 flex items-baseline justify-between">
                    <span className="text-muted-foreground">Total</span>
                    <span className="text-3xl font-bold tabular-nums">
                        {formatRupiah(total)}
                    </span>
                </div>
                {items.length > 0 && (
                    <Payment
                        total={total}
                        paid={paid}
                        change={change}
                        onPaidChange={setPaid}
                    />
                )}
                <Button
                    size="xl"
                    onClick={checkout}
                    disabled={!items.length || sale.isPending || short}
                    className="w-full"
                >
                    {sale.isPending ? (
                        "Menyimpan…"
                    ) : (
                        <>
                            <Check />
                            Selesai
                        </>
                    )}
                </Button>
            </div>
        </div>
    );

    return (
        <Layout title="Kasir" footer={false}>
            <div
                className={cn(
                    "md:grid md:grid-cols-[1fr_340px] md:gap-4",
                    items.length && "pb-16 md:pb-0",
                )}
            >
                <div className="@container min-w-0">
                    <div className="sticky top-14 z-10 -mx-3 mb-3 bg-background/90 px-3 pb-2 backdrop-blur md:-mx-4 md:px-4">
                        <div className="grid gap-2 sm:grid-cols-[1fr_15rem]">
                            <div className="relative">
                                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    ref={searchRef}
                                    type="search"
                                    className="pl-9"
                                    placeholder="Cari produk… (Enter = tambah)"
                                    value={query}
                                    onChange={(e) => setQuery(e.target.value)}
                                    onKeyDown={onSearchKey}
                                />
                            </div>
                            <Select
                                value={category}
                                onValueChange={setCategory}
                            >
                                <SelectTrigger aria-label="Filter kategori">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value={ALL}>
                                        Semua kategori (
                                        {categories.reduce(
                                            (sum, item) =>
                                                sum + item.products_count,
                                            0,
                                        )}
                                        )
                                    </SelectItem>
                                    {categories.length > 0 && (
                                        <SelectSeparator />
                                    )}
                                    {categories.map((item) => (
                                        <SelectItem
                                            key={item.id}
                                            value={String(item.id)}
                                        >
                                            {item.name} ({item.products_count})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    {list.length === 0 && (
                        <Card className="py-0">
                            <Empty>
                                <EmptyDescription>
                                    {debouncedQuery || category !== ALL ? (
                                        <>
                                            Produk tidak ditemukan
                                            {category !== ALL &&
                                                " di kategori ini"}
                                            .
                                        </>
                                    ) : (
                                        <>
                                            Belum ada produk. Tambahkan dulu di
                                            menu <b>Produk</b>.
                                        </>
                                    )}
                                </EmptyDescription>
                            </Empty>
                        </Card>
                    )}

                    {/* Columns follow the space left by the sidebar and cart, not the screen. */}
                    <div
                        className={cn(
                            "grid grid-cols-2 gap-2 transition-opacity @lg:grid-cols-3 @3xl:grid-cols-4",
                            isPlaceholderData && "opacity-60",
                        )}
                    >
                        {list.map((product) => {
                            const inCart = qtyInCart(product);
                            const soldOut = product.stock <= inCart;
                            return (
                                <Button
                                    key={product.id}
                                    variant="outline"
                                    onClick={() => add(product)}
                                    disabled={soldOut}
                                    className={cn(
                                        "relative h-auto flex-col items-stretch gap-0 bg-card p-3 text-left text-base whitespace-normal hover:border-primary/50 hover:bg-card hover:text-card-foreground active:scale-[0.97] disabled:opacity-40",
                                        inCart &&
                                            "border-primary ring-1 ring-primary",
                                    )}
                                >
                                    {inCart > 0 && (
                                        <Badge className="absolute -top-2 -right-2 h-7 min-w-7 text-sm font-bold shadow">
                                            {isMeasured(product.unit)
                                                ? formatQty(inCart, product.unit)
                                                : inCart}
                                        </Badge>
                                    )}
                                    <span className="mb-1 truncate text-xs font-normal text-muted-foreground">
                                        {product.category_name ??
                                            "Tanpa kategori"}
                                    </span>
                                    <span
                                        className="truncate leading-tight font-semibold"
                                        title={product.name}
                                    >
                                        {product.name}
                                    </span>
                                    <span className="mt-1 font-bold text-primary">
                                        {formatPrice(
                                            product.sell_price,
                                            product.unit,
                                        )}
                                    </span>
                                    <span
                                        className={cn(
                                            "text-xs font-normal text-muted-foreground",
                                            isLowStock(
                                                product.stock,
                                                product.unit,
                                                lowStockThreshold,
                                            ) &&
                                                "font-medium text-destructive",
                                        )}
                                    >
                                        {product.stock <= 0
                                            ? "Habis"
                                            : `Stok ${formatQty(product.stock, product.unit)}`}
                                    </span>
                                </Button>
                            );
                        })}
                    </div>

                    <DataPagination meta={data.meta} onPageChange={setPage} />
                </div>

                {/* Desktop/tablet: cart panel on the right */}
                <Card className="sticky top-18 hidden h-[calc(100dvh-10rem)] gap-0 overflow-hidden py-0 md:flex lg:h-[calc(100dvh-6rem)]">
                    {cartPanel}
                </Card>
            </div>

            {/* Phone: compact bar at the bottom, tap to open the cart */}
            {items.length > 0 && !showCart && (
                <div className="fixed inset-x-0 bottom-16 z-30 p-3 md:hidden">
                    <div className="flex gap-2">
                        <Button
                            variant="outline"
                            size="lg"
                            onClick={() => setShowCart(true)}
                            className="flex-1 justify-between shadow-lg"
                        >
                            <span className="flex items-center gap-2">
                                <ShoppingCart />
                                {count} item
                            </span>
                            <span className="font-bold">
                                {formatRupiah(total)}
                            </span>
                        </Button>
                        <Button
                            size="lg"
                            onClick={checkout}
                            disabled={sale.isPending || short}
                            className="shadow-lg"
                        >
                            {sale.isPending ? (
                                "…"
                            ) : (
                                <>
                                    <Check />
                                    Selesai
                                </>
                            )}
                        </Button>
                    </div>
                    {saleError && (
                        <Alert variant="destructive" className="mt-2 bg-card">
                            <AlertCircle />
                            <AlertTitle className="line-clamp-none">
                                {saleError}
                            </AlertTitle>
                        </Alert>
                    )}
                </div>
            )}
            <Sheet open={showCart} onOpenChange={setShowCart}>
                <SheetContent
                    side="bottom"
                    showCloseButton={false}
                    className="max-h-[85dvh] gap-0 rounded-t-md md:hidden"
                >
                    <SheetTitle className="sr-only">Keranjang</SheetTitle>
                    <SheetDescription className="sr-only">
                        Daftar barang yang akan dibayar
                    </SheetDescription>
                    {cartPanel}
                </SheetContent>
            </Sheet>
            <WeighDialog
                product={weighing}
                initialQty={weighing ? qtyInCart(weighing) : undefined}
                onConfirm={(qty) => {
                    setQty(weighing, qty);
                    setWeighing(null);
                }}
                onClose={() => setWeighing(null)}
            />
        </Layout>
    );
}
