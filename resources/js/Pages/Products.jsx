import { Link } from '@inertiajs/react';
import { Pencil, Plus, Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import DataPagination from '@/components/DataPagination';
import Layout from '@/components/Layout';
import { Button } from '@/components/ui/button';
import { Card } from '@/components/ui/card';
import { Empty, EmptyDescription } from '@/components/ui/empty';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectSeparator, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useCategoryOptions, useProducts } from '@/api/hooks';
import { useDebouncedValue } from '@/hooks/use-debounced-value';
import { formatPrice, formatQty } from '@/lib/units';
import { replaceUrl } from '@/lib/url';
import { cn } from '@/lib/utils';

const ALL = 'all';

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

    const { data: categories } = useCategoryOptions(initialCategories);
    const params = { page, search: debouncedSearch, category: category === ALL ? '' : category };
    const isInitial = page === initialPage.meta.page && filterKey === `${filters.search}|${initialCategory}`;
    const { data, isPlaceholderData } = useProducts(params, isInitial ? initialPage : undefined);
    const products = data.data;

    useEffect(() => {
        replaceUrl('/products', { ...params, page: page > 1 ? page : '' });
    }, [page, debouncedSearch, category]);

    return (
        <Layout title="Produk">
            <div className="mb-3 flex items-center gap-2">
                <h1 className="flex-1 text-xl font-bold">Produk</h1>
                <Button asChild>
                    <Link href="/products/create">
                        <Plus />
                        Tambah produk
                    </Link>
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
                                        <Button asChild variant="ghost" size="icon">
                                            <Link href={`/products/${product.id}/edit`}>
                                                <Pencil />
                                                <span className="sr-only">Edit {product.name}</span>
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </Card>

            <DataPagination meta={data.meta} onPageChange={setPage} />
        </Layout>
    );
}
