import { AlertCircle, ChevronRight, PackagePlus, Search } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import DataPagination from '@/components/DataPagination';
import Layout from '@/components/Layout';
import QuantityInput from '@/components/QuantityInput';
import { Alert, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Empty, EmptyDescription } from '@/components/ui/empty';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useProducts, useRecordStockIn, useStockInHistory } from '@/api/hooks';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { formatQty, isMeasured, unitOf } from '@/lib/units';
import { replaceUrl } from '@/lib/url';
import { cn } from '@/lib/utils';

// Pieces come in dozens and cartons; weighed goods in sacks and jerry cans.
const quickAdd = { pcs: [1, 5, 10, 12, 24], measured: [1, 5, 10, 25, 50] };
const PICKER_PER_PAGE = 10;

// Step one: search products on the server, one page at a time.
function ProductPicker({ onSelect }) {
    const [search, setSearch] = useState('');
    const debouncedSearch = useDebouncedValue(search.trim());
    // A new search starts again at page 1.
    const [paging, setPaging] = useState({ search: '', page: 1 });
    const page = paging.search === debouncedSearch ? paging.page : 1;

    const { data, isPending, isPlaceholderData } = useProducts({
        page,
        per_page: PICKER_PER_PAGE,
        search: debouncedSearch,
    });
    const products = data?.data ?? [];

    return (
        <div className="grid gap-3">
            <div className="relative">
                <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input
                    type="search"
                    className="pl-9"
                    placeholder="Cari produk yang masuk…"
                    value={search}
                    onChange={(e) => setSearch(e.target.value)}
                    autoFocus
                />
            </div>
            <Card
                className={cn(
                    'gap-0 divide-y overflow-hidden py-0 transition-opacity',
                    isPlaceholderData && 'opacity-60',
                )}
            >
                {isPending && (
                    <Empty>
                        <EmptyDescription>Memuat produk…</EmptyDescription>
                    </Empty>
                )}
                {!isPending && products.length === 0 && (
                    <Empty>
                        <EmptyDescription>
                            {debouncedSearch ? (
                                'Produk tidak ditemukan.'
                            ) : (
                                <>
                                    Belum ada produk. Tambahkan dulu di menu <b>Produk</b>.
                                </>
                            )}
                        </EmptyDescription>
                    </Empty>
                )}
                {products.map((product) => (
                    <Button
                        key={product.id}
                        variant="ghost"
                        onClick={() => onSelect(product)}
                        className="h-auto w-full shrink-0 justify-start gap-3 rounded-none p-3 text-left text-base active:scale-100"
                    >
                        <span className="flex-1 truncate font-semibold">{product.name}</span>
                        <span className="text-sm font-normal text-muted-foreground">
                            stok {formatQty(product.stock, product.unit)}
                        </span>
                        <ChevronRight className="text-muted-foreground" />
                    </Button>
                ))}
            </Card>
            <DataPagination
                className="mt-0"
                meta={data?.meta}
                onPageChange={(next) => setPaging({ search: debouncedSearch, page: next })}
            />
        </div>
    );
}

// Step two: how many came in. Mounted only while the dialog is open, so it
// always starts at step one.
function StockInForm({ onDone }) {
    const [selected, setSelected] = useState(null);
    const [qty, setQty] = useState('');
    const qtyRef = useRef(null);

    const stockIn = useRecordStockIn(onDone);
    const errors = stockIn.error?.fieldErrors ?? {};

    const select = (product) => {
        if (stockIn.isError) stockIn.reset();
        setSelected(product);
        setQty('');
        setTimeout(() => qtyRef.current?.focus(), 50);
    };

    const submit = (e) => {
        e.preventDefault();
        if (!selected || !qty) return;
        stockIn.mutate({ product_id: selected.id, qty });
    };

    if (!selected) return <ProductPicker onSelect={select} />;

    return (
        <form onSubmit={submit} className="grid gap-4">
            <Card className="flex-row items-center gap-3 px-3 py-3">
                <div className="min-w-0 flex-1">
                    <div className="truncate font-semibold">{selected.name}</div>
                    <div className="text-sm text-muted-foreground">Stok sekarang {formatQty(selected.stock, selected.unit)}</div>
                </div>
                <Button type="button" variant="outline" size="sm" onClick={() => setSelected(null)}>
                    Ganti
                </Button>
            </Card>

            {errors.product_id && (
                <Alert variant="destructive">
                    <AlertCircle />
                    <AlertTitle>{errors.product_id}</AlertTitle>
                </Alert>
            )}

            <Field>
                <FieldLabel htmlFor="qty">
                    Jumlah masuk{isMeasured(selected.unit) && ` (${unitOf(selected.unit).label})`}
                </FieldLabel>
                <QuantityInput
                    id="qty"
                    ref={qtyRef}
                    unit={selected.unit}
                    placeholder="0"
                    value={qty}
                    onChange={setQty}
                    aria-invalid={!!errors.qty}
                    className="h-14 text-center text-2xl font-bold md:text-2xl"
                />
                <FieldError>{errors.qty}</FieldError>
            </Field>

            <Field>
                <FieldLabel>Tambah cepat</FieldLabel>
                <div className="grid grid-cols-5 gap-2">
                    {(isMeasured(selected.unit) ? quickAdd.measured : quickAdd.pcs).map((n) => (
                        <Button
                            key={n}
                            type="button"
                            variant="outline"
                            size="lg"
                            onClick={() => setQty((current) => (current || 0) + n * unitOf(selected.unit).scale)}
                            className="px-0 text-base font-bold tabular-nums"
                        >
                            +{n}
                        </Button>
                    ))}
                </div>
                <FieldDescription>
                    {qty
                        ? `Stok jadi ${formatQty(selected.stock + qty, selected.unit)} setelah disimpan.`
                        : 'Ketik jumlahnya, atau ketuk tombol untuk menambah.'}
                </FieldDescription>
            </Field>

            <DialogFooter>
                <DialogClose asChild>
                    <Button type="button" variant="outline">
                        Batal
                    </Button>
                </DialogClose>
                <Button disabled={stockIn.isPending || !qty}>{stockIn.isPending ? 'Menyimpan…' : 'Simpan'}</Button>
            </DialogFooter>
        </form>
    );
}

export default function StockIn({ history: initialPage }) {
    const [open, setOpen] = useState(false);
    const [page, setPage] = useState(initialPage.meta.page);

    const { data, isPlaceholderData } = useStockInHistory(
        { page },
        page === initialPage.meta.page ? initialPage : undefined,
    );
    const history = data.data;

    useEffect(() => {
        replaceUrl('/stock-in', { page: page > 1 ? page : '' });
    }, [page]);

    return (
        <Layout title="Stok Masuk">
            <div className="mb-3 flex items-center gap-2">
                <h1 className="flex-1 text-xl font-bold">Stok Masuk</h1>
                <Button onClick={() => setOpen(true)}>
                    <PackagePlus />
                    Catat stok masuk
                </Button>
            </div>

            <Card className={cn('gap-0 py-0 transition-opacity', isPlaceholderData && 'opacity-60')}>
                {history.length === 0 ? (
                    <Empty>
                        <EmptyDescription>Belum ada catatan barang masuk.</EmptyDescription>
                    </Empty>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Produk</TableHead>
                                <TableHead className="text-right">Jumlah</TableHead>
                                <TableHead className="text-right">Waktu</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {history.map((entry) => (
                                <TableRow key={entry.id}>
                                    <TableCell className="max-w-48 truncate sm:max-w-none">{entry.name}</TableCell>
                                    <TableCell className="text-right font-bold text-primary tabular-nums">
                                        +{formatQty(entry.qty, entry.unit)}
                                    </TableCell>
                                    <TableCell className="text-right text-muted-foreground tabular-nums">
                                        {entry.time}
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </Card>

            <DataPagination meta={data.meta} onPageChange={setPage} />

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Catat stok masuk</DialogTitle>
                        <DialogDescription>Pilih produk, lalu isi jumlah barang yang datang.</DialogDescription>
                    </DialogHeader>
                    <StockInForm onDone={() => setOpen(false)} />
                </DialogContent>
            </Dialog>
        </Layout>
    );
}
