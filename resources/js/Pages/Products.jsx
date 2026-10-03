import { Pencil, Plus, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import DataPagination from '@/components/DataPagination';
import Layout from '@/components/Layout';
import NumberInput from '@/components/NumberInput';
import QuantityInput from '@/components/QuantityInput';
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
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectSeparator, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useCategoryOptions, useProducts, useSaveProduct } from '@/api/hooks';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { UNITS, formatPrice, formatQty, isMeasured, unitOf } from '@/lib/units';
import { replaceUrl } from '@/lib/url';
import { cn } from '@/lib/utils';

const emptyForm = { name: '', category_id: '', unit: 'pcs', sell_price: '', cost_price: '', stock: '' };
const ALL = 'all';

// Add when `product` is empty, edit otherwise. Mounted only while the dialog is
// open, so every opening starts from a clean form.
function ProductForm({ product, categories, onDone }) {
    const [form, setForm] = useState(
        product
            ? {
                  name: product.name,
                  category_id: product.category_id ? String(product.category_id) : '',
                  unit: product.unit,
                  sell_price: product.sell_price,
                  cost_price: product.cost_price,
                  stock: '',
              }
            : emptyForm,
    );

    const save = useSaveProduct(onDone);

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
            category_id: form.category_id === '' ? null : Number(form.category_id),
            sell_price: form.sell_price === '' ? 0 : form.sell_price,
            cost_price: form.cost_price === '' ? null : form.cost_price,
            // The unit is chosen once; editing leaves it out so it is kept.
            ...(product ? {} : { unit: form.unit, stock: form.stock === '' ? null : form.stock }),
        });
    };

    return (
        <form onSubmit={submit} className="grid gap-4">
            <Field>
                <FieldLabel htmlFor="name">Nama produk</FieldLabel>
                <Input
                    id="name"
                    value={form.name}
                    onChange={(e) => set('name', e.target.value)}
                    aria-invalid={!!errors.name}
                    autoFocus
                />
                <FieldError>{errors.name}</FieldError>
            </Field>
            <Field>
                <FieldLabel htmlFor="category_id">Kategori</FieldLabel>
                <Select value={form.category_id} onValueChange={(value) => set('category_id', value)}>
                    <SelectTrigger id="category_id" aria-invalid={!!errors.category_id}>
                        <SelectValue placeholder="Pilih kategori" />
                    </SelectTrigger>
                    <SelectContent>
                        {categories.map((category) => (
                            <SelectItem key={category.id} value={String(category.id)}>
                                {category.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {categories.length === 0 && (
                    <FieldDescription>Belum ada kategori. Buat dulu di menu Kategori.</FieldDescription>
                )}
                <FieldError>{errors.category_id}</FieldError>
            </Field>
            <Field>
                <FieldLabel htmlFor="unit">Satuan jual</FieldLabel>
                <Select
                    value={form.unit}
                    onValueChange={(value) => {
                        set('unit', value);
                        set('stock', '');
                    }}
                    disabled={!!product}
                >
                    <SelectTrigger id="unit" aria-invalid={!!errors.unit}>
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        {Object.entries(UNITS).map(([value, unit]) => (
                            <SelectItem key={value} value={value}>
                                {unit.option}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                <FieldDescription>
                    {product
                        ? 'Satuan tidak bisa diganti. Untuk satuan lain, buat produk baru.'
                        : isMeasured(form.unit)
                          ? `Harga ditulis per ${unitOf(form.unit).label}; di kasir bisa isi berat atau nominal uang.`
                          : 'Untuk barang yang dijual per buah atau per bungkus.'}
                </FieldDescription>
                <FieldError>{errors.unit}</FieldError>
            </Field>
            <div className="grid grid-cols-2 gap-4">
                <Field>
                    <FieldLabel htmlFor="sell_price">
                        Harga jual{isMeasured(form.unit) && ` per ${unitOf(form.unit).label}`}
                    </FieldLabel>
                    <NumberInput
                        id="sell_price"
                        value={form.sell_price}
                        onChange={(value) => set('sell_price', value)}
                        aria-invalid={!!errors.sell_price}
                    />
                    <FieldError>{errors.sell_price}</FieldError>
                </Field>
                <Field>
                    <FieldLabel htmlFor="cost_price">
                        Harga beli{isMeasured(form.unit) && ` per ${unitOf(form.unit).label}`}
                    </FieldLabel>
                    <NumberInput
                        id="cost_price"
                        placeholder="Opsional"
                        value={form.cost_price}
                        onChange={(value) => set('cost_price', value)}
                        aria-invalid={!!errors.cost_price}
                    />
                    <FieldError>{errors.cost_price}</FieldError>
                </Field>
            </div>
            {!product && (
                <Field>
                    <FieldLabel htmlFor="stock">
                        Stok awal{isMeasured(form.unit) && ` (${unitOf(form.unit).label})`}
                    </FieldLabel>
                    <QuantityInput
                        id="stock"
                        unit={form.unit}
                        placeholder={isMeasured(form.unit) ? 'misal 25 atau 12,5' : '0'}
                        value={form.stock}
                        onChange={(value) => set('stock', value)}
                        aria-invalid={!!errors.stock}
                    />
                    <FieldDescription>Stok berikutnya dicatat lewat menu Stok Masuk.</FieldDescription>
                    <FieldError>{errors.stock}</FieldError>
                </Field>
            )}
            <DialogFooter>
                <DialogClose asChild>
                    <Button type="button" variant="outline">
                        Batal
                    </Button>
                </DialogClose>
                <Button disabled={save.isPending}>{save.isPending ? 'Menyimpan…' : 'Simpan'}</Button>
            </DialogFooter>
        </form>
    );
}

export default function Products({ products: initialPage, categories: initialCategories, filters }) {
    const initialCategory = filters.category ? String(filters.category) : ALL;
    const [search, setSearch] = useState(filters.search);
    const [category, setCategory] = useState(initialCategory);
    const debouncedSearch = useDebouncedValue(search.trim());

    // The page belongs to one filter: a new search or category starts at page 1.
    const filterKey = `${debouncedSearch}|${category}`;
    const [paging, setPaging] = useState({ filterKey: `${filters.search}|${initialCategory}`, page: initialPage.meta.page });
    const page = paging.filterKey === filterKey ? paging.page : 1;
    const setPage = (next) => setPaging({ filterKey, page: next });

    // null = closed, {} = add, { product } = edit
    const [dialog, setDialog] = useState(null);

    const { data: categories } = useCategoryOptions(initialCategories);
    const params = { page, search: debouncedSearch, category: category === ALL ? '' : category };
    const isInitial = page === initialPage.meta.page && filterKey === `${filters.search}|${initialCategory}`;
    const { data, isPlaceholderData } = useProducts(params, isInitial ? initialPage : undefined);
    const products = data.data;

    useEffect(() => {
        replaceUrl('/products', { ...params, page: page > 1 ? page : '' });
    }, [page, debouncedSearch, category]);

    const editing = dialog?.product;

    return (
        <Layout title="Produk">
            <div className="mb-3 flex items-center gap-2">
                <h1 className="flex-1 text-xl font-bold">Produk</h1>
                <Button onClick={() => setDialog({})}>
                    <Plus />
                    Tambah produk
                </Button>
            </div>

            <div className="mb-3 grid gap-2 sm:grid-cols-[1fr_16rem]">
                <div className="relative">
                    <Search className="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        type="search"
                        className="pl-9"
                        placeholder="Cari produk…"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
                <Select value={category} onValueChange={setCategory}>
                    <SelectTrigger aria-label="Filter kategori">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value={ALL}>Semua kategori</SelectItem>
                        <SelectSeparator />
                        {categories.map((item) => (
                            <SelectItem key={item.id} value={String(item.id)}>
                                {item.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            </div>

            <Card className={cn('gap-0 py-0 transition-opacity', isPlaceholderData && 'opacity-60')}>
                {products.length === 0 ? (
                    <Empty>
                        <EmptyDescription>
                            {debouncedSearch || category !== ALL ? 'Produk tidak ditemukan.' : 'Belum ada produk.'}
                        </EmptyDescription>
                    </Empty>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead className="hidden md:table-cell">Kategori</TableHead>
                                <TableHead className="text-right">Harga jual</TableHead>
                                <TableHead className="hidden text-right sm:table-cell">Harga beli</TableHead>
                                <TableHead className="text-right">Stok</TableHead>
                                <TableHead className="w-0">
                                    <span className="sr-only">Aksi</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {products.map((product) => (
                                <TableRow key={product.id}>
                                    <TableCell className="max-w-40 sm:max-w-none">
                                        <div className="truncate font-semibold">{product.name}</div>
                                        {/* Phones have no category column, so it sits under the name. */}
                                        <div className="truncate text-xs text-muted-foreground md:hidden">
                                            {product.category_name ?? 'Tanpa kategori'}
                                        </div>
                                    </TableCell>
                                    <TableCell className="hidden md:table-cell">
                                        {product.category_name ? (
                                            <Badge variant="secondary">{product.category_name}</Badge>
                                        ) : (
                                            <Badge variant="warning">Tanpa kategori</Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="text-right tabular-nums">
                                        {formatPrice(product.sell_price, product.unit)}
                                    </TableCell>
                                    <TableCell className="hidden text-right text-muted-foreground tabular-nums sm:table-cell">
                                        {product.cost_price > 0 ? formatPrice(product.cost_price, product.unit) : '—'}
                                    </TableCell>
                                    <TableCell
                                        className={cn('text-right font-bold tabular-nums', product.stock <= 0 && 'text-destructive')}
                                    >
                                        {formatQty(product.stock, product.unit)}
                                    </TableCell>
                                    <TableCell className="py-1">
                                        <Button variant="ghost" size="icon" onClick={() => setDialog({ product })}>
                                            <Pencil />
                                            <span className="sr-only">Edit {product.name}</span>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </Card>

            <DataPagination meta={data.meta} onPageChange={setPage} />

            <Dialog open={dialog !== null} onOpenChange={(open) => !open && setDialog(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{editing ? 'Ubah produk' : 'Tambah produk'}</DialogTitle>
                        <DialogDescription>
                            {editing ? `Perbarui nama atau harga ${editing.name}.` : 'Isi data produk baru.'}
                        </DialogDescription>
                    </DialogHeader>
                    <ProductForm product={editing} categories={categories} onDone={() => setDialog(null)} />
                </DialogContent>
            </Dialog>
        </Layout>
    );
}
