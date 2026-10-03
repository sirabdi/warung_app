import { Link } from '@inertiajs/react';
import { AlertCircle, Pencil, Plus, Trash2 } from 'lucide-react';
import { useEffect, useState } from 'react';
import DataPagination from '@/components/DataPagination';
import Layout from '@/components/Layout';
import { Alert, AlertTitle } from '@/components/ui/alert';
import {
    AlertDialog,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Badge } from '@/components/ui/badge';
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
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useCategoryPage, useDeleteCategory, useSaveCategory } from '@/api/hooks';
import { formatNumber } from '@/lib/format';
import { replaceUrl } from '@/lib/url';
import { cn } from '@/lib/utils';

// Add when `category` is empty, rename otherwise. Mounted only while open.
function CategoryForm({ category, onDone }) {
    const [name, setName] = useState(category?.name ?? '');
    const save = useSaveCategory(onDone);
    const errors = save.error?.fieldErrors ?? {};

    const submit = (e) => {
        e.preventDefault();
        save.mutate({ id: category?.id, name });
    };

    return (
        <form onSubmit={submit} className="grid gap-4">
            <Field>
                <FieldLabel htmlFor="category-name">Nama kategori</FieldLabel>
                <Input
                    id="category-name"
                    value={name}
                    onChange={(e) => {
                        if (save.isError) save.reset();
                        setName(e.target.value);
                    }}
                    aria-invalid={!!errors.name}
                    maxLength={50}
                    autoFocus
                />
                <FieldError>{errors.name}</FieldError>
            </Field>
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

function DeleteCategoryDialog({ category, onClose }) {
    const remove = useDeleteCategory(onClose);
    const inUse = category?.products_count > 0;

    return (
        <AlertDialog open={category !== null} onOpenChange={(open) => !open && onClose()}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{inUse ? 'Kategori masih dipakai' : `Hapus ${category?.name}?`}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {inUse
                            ? `${category?.name} masih dipakai ${formatNumber(category?.products_count)} produk. Pindahkan produknya ke kategori lain dulu, baru kategori ini bisa dihapus.`
                            : 'Kategori ini belum dipakai produk apa pun. Penghapusan tidak bisa dibatalkan.'}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                {remove.error && (
                    <Alert variant="destructive">
                        <AlertCircle />
                        <AlertTitle className="line-clamp-none">{remove.error.message}</AlertTitle>
                    </Alert>
                )}
                <AlertDialogFooter>
                    <AlertDialogCancel>{inUse ? 'Tutup' : 'Batal'}</AlertDialogCancel>
                    {inUse ? (
                        <Button asChild>
                            <Link href={`/products?category=${category.id}`}>Lihat produknya</Link>
                        </Button>
                    ) : (
                        <Button
                            variant="destructive"
                            disabled={remove.isPending}
                            onClick={() => remove.mutate(category.id)}
                        >
                            {remove.isPending ? 'Menghapus…' : 'Hapus'}
                        </Button>
                    )}
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}

export default function Categories({ categories: initialPage }) {
    const [page, setPage] = useState(initialPage.meta.page);
    const { data, isPlaceholderData } = useCategoryPage({ page }, page === initialPage.meta.page ? initialPage : undefined);
    const categories = data.data;

    useEffect(() => {
        replaceUrl('/categories', { page: page > 1 ? page : '' });
    }, [page]);
    // null = closed, {} = add, { category } = rename
    const [dialog, setDialog] = useState(null);
    const [deleting, setDeleting] = useState(null);

    const editing = dialog?.category;

    return (
        <Layout title="Kategori">
            <div className="mb-3 flex items-center gap-2">
                <h1 className="flex-1 text-xl font-bold">Kategori</h1>
                <Button onClick={() => setDialog({})}>
                    <Plus />
                    Tambah kategori
                </Button>
            </div>

            <Card className={cn('gap-0 py-0 transition-opacity', isPlaceholderData && 'opacity-60')}>
                {categories.length === 0 ? (
                    <Empty>
                        <EmptyDescription>Belum ada kategori.</EmptyDescription>
                    </Empty>
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead className="text-right">Produk</TableHead>
                                <TableHead className="w-0">
                                    <span className="sr-only">Aksi</span>
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {categories.map((category) => (
                                <TableRow key={category.id}>
                                    <TableCell className="max-w-52 truncate font-semibold sm:max-w-none">
                                        {category.name}
                                    </TableCell>
                                    <TableCell className="text-right">
                                        {category.products_count > 0 ? (
                                            <Button asChild variant="link" size="sm" className="h-auto p-0 tabular-nums">
                                                <Link href={`/products?category=${category.id}`}>
                                                    {formatNumber(category.products_count)}
                                                </Link>
                                            </Button>
                                        ) : (
                                            <Badge variant="outline">kosong</Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="py-1">
                                        <div className="flex justify-end gap-1">
                                            <Button variant="ghost" size="icon" onClick={() => setDialog({ category })}>
                                                <Pencil />
                                                <span className="sr-only">Ubah {category.name}</span>
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="text-destructive hover:text-destructive"
                                                onClick={() => setDeleting(category)}
                                            >
                                                <Trash2 />
                                                <span className="sr-only">Hapus {category.name}</span>
                                            </Button>
                                        </div>
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
                        <DialogTitle>{editing ? 'Ubah kategori' : 'Tambah kategori'}</DialogTitle>
                        <DialogDescription>
                            {editing ? 'Nama baru langsung terlihat di semua produknya.' : 'Misalnya: Minuman, Rokok, Sembako.'}
                        </DialogDescription>
                    </DialogHeader>
                    <CategoryForm category={editing} onDone={() => setDialog(null)} />
                </DialogContent>
            </Dialog>

            <DeleteCategoryDialog
                key={deleting?.id ?? 'none'}
                category={deleting}
                onClose={() => setDeleting(null)}
            />
        </Layout>
    );
}
