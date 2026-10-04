import { Link, router } from "@inertiajs/react";
import {
    AlertTriangle,
    ArrowLeft,
    CircleCheck,
    Loader2,
    PackagePlus,
    Pencil,
    SearchCheck,
} from "lucide-react";
import { useState } from "react";
import Layout from "@/components/Layout";
import NumberInput from "@/components/NumberInput";
import QuantityInput from "@/components/QuantityInput";
import { Alert, AlertDescription, AlertTitle } from "@/components/ui/alert";
import { Button } from "@/components/ui/button";
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from "@/components/ui/card";
import {
    Field,
    FieldDescription,
    FieldError,
    FieldLabel,
} from "@/components/ui/field";
import { Input } from "@/components/ui/input";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import {
    useCategoryOptions,
    useSaveProduct,
    useSimilarProducts,
} from "@/api/hooks";
import { useDebouncedValue } from "@/hooks/use-debounced-value";
import { UNITS, formatQty, isMeasured, unitOf } from "@/lib/units";

const emptyForm = {
    name: "",
    category_id: "",
    unit: "pcs",
    sell_price: "",
    cost_price: "",
    stock: "",
};

// Usually a product typed in again is one whose new goods just arrived: offer
// Stok Masuk for it, or editing it, instead of a second copy.
function ProductActions({ product, onEdit }) {
    return (
        <div className="flex shrink-0 gap-1">
            <Button
                asChild
                type="button"
                size="sm"
                variant="outline"
                className="h-8"
            >
                <Link href={`/stock-in?product=${product.id}`}>
                    <PackagePlus />
                    Tambah stok
                </Link>
            </Button>
            <Button
                type="button"
                size="sm"
                variant="ghost"
                className="h-8"
                onClick={() => onEdit(product)}
            >
                <Pencil />
                <span className="sr-only sm:not-sr-only">Ubah</span>
            </Button>
        </div>
    );
}

// Shown under the name while typing. The same name blocks saving; similar ones
// are only a hint ("Aqua 600 ml" next to "Aqua 600ml"). When editing, the
// product itself is left out on the server.
function DuplicateHint({ exact, similar, onEdit }) {
    if (exact) {
        return (
            <Alert variant="warning">
                <AlertTriangle />
                <AlertTitle>{exact.name} sudah ada</AlertTitle>
                <AlertDescription>
                    <p>
                        Stok sekarang {formatQty(exact.stock, exact.unit)}
                        {exact.category_name && ` · ${exact.category_name}`}.
                        Barangnya baru datang? Tambah stoknya saja.
                    </p>
                    <ProductActions product={exact} onEdit={onEdit} />
                </AlertDescription>
            </Alert>
        );
    }

    if (similar.length === 0) return null;

    return (
        <div className="rounded-md border bg-muted/40 text-sm">
            <div className="px-3 pt-2 text-muted-foreground">
                Mirip dengan produk yang sudah ada:
            </div>
            <ul className="divide-y">
                {similar.map((item) => (
                    <li
                        key={item.id}
                        className="flex items-center gap-2 px-3 py-1.5"
                    >
                        <div className="min-w-0 flex-1">
                            <div className="truncate font-medium">
                                {item.name}
                            </div>
                            <div className="text-xs text-muted-foreground">
                                stok {formatQty(item.stock, item.unit)}
                            </div>
                        </div>
                        <ProductActions product={item} onEdit={onEdit} />
                    </li>
                ))}
            </ul>
        </div>
    );
}

// Add when `product` is empty, edit otherwise. The duplicate check lives on the
// page: `hint` is shown under the name below 1024px, `nameTaken` blocks saving.
function Form({ product, categories, hint, nameTaken, onNameChange, onDone }) {
    const [form, setForm] = useState(
        product
            ? {
                  name: product.name,
                  category_id: product.category_id
                      ? String(product.category_id)
                      : "",
                  unit: product.unit,
                  sell_price: product.sell_price,
                  cost_price: product.cost_price,
                  stock: "",
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
            category_id:
                form.category_id === "" ? null : Number(form.category_id),
            sell_price: form.sell_price === "" ? 0 : form.sell_price,
            cost_price: form.cost_price === "" ? null : form.cost_price,
            // The unit is chosen once; editing leaves it out so it is kept.
            ...(product
                ? {}
                : {
                      unit: form.unit,
                      stock: form.stock === "" ? null : form.stock,
                  }),
        });
    };

    return (
        <form onSubmit={submit} className="grid gap-4">
            <Field>
                <FieldLabel htmlFor="name">Nama produk</FieldLabel>
                <Input
                    id="name"
                    value={form.name}
                    onChange={(e) => {
                        set("name", e.target.value);
                        onNameChange(e.target.value);
                    }}
                    aria-invalid={!!errors.name}
                    autoFocus
                />
                <FieldError>{errors.name}</FieldError>
                {hint && <div className="lg:hidden">{hint}</div>}
            </Field>
            <Field>
                <FieldLabel htmlFor="category_id">Kategori</FieldLabel>
                <Select
                    value={form.category_id}
                    onValueChange={(value) => set("category_id", value)}
                >
                    <SelectTrigger
                        id="category_id"
                        aria-invalid={!!errors.category_id}
                    >
                        <SelectValue placeholder="Pilih kategori" />
                    </SelectTrigger>
                    <SelectContent>
                        {categories.map((category) => (
                            <SelectItem
                                key={category.id}
                                value={String(category.id)}
                            >
                                {category.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
                {categories.length === 0 && (
                    <FieldDescription>
                        Belum ada kategori. Buat dulu di menu Kategori.
                    </FieldDescription>
                )}
                <FieldError>{errors.category_id}</FieldError>
            </Field>
            <Field>
                <FieldLabel htmlFor="unit">Satuan jual</FieldLabel>
                <Select
                    value={form.unit}
                    onValueChange={(value) => {
                        set("unit", value);
                        set("stock", "");
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
                        ? "Satuan tidak bisa diganti. Untuk satuan lain, buat produk baru."
                        : isMeasured(form.unit)
                          ? `Harga ditulis per ${unitOf(form.unit).label}; di kasir bisa isi berat atau nominal uang.`
                          : "Untuk barang yang dijual per buah atau per bungkus."}
                </FieldDescription>
                <FieldError>{errors.unit}</FieldError>
            </Field>
            <div className="grid grid-cols-2 gap-4">
                <Field>
                    <FieldLabel htmlFor="sell_price">
                        Harga jual
                        {isMeasured(form.unit) &&
                            ` per ${unitOf(form.unit).label}`}
                    </FieldLabel>
                    <NumberInput
                        id="sell_price"
                        value={form.sell_price}
                        onChange={(value) => set("sell_price", value)}
                        aria-invalid={!!errors.sell_price}
                    />
                    <FieldError>{errors.sell_price}</FieldError>
                </Field>
                <Field>
                    <FieldLabel htmlFor="cost_price">
                        Harga beli
                        {isMeasured(form.unit) &&
                            ` per ${unitOf(form.unit).label}`}
                    </FieldLabel>
                    <NumberInput
                        id="cost_price"
                        placeholder="Opsional"
                        value={form.cost_price}
                        onChange={(value) => set("cost_price", value)}
                        aria-invalid={!!errors.cost_price}
                    />
                    <FieldError>{errors.cost_price}</FieldError>
                </Field>
            </div>
            {!product && (
                <Field>
                    <FieldLabel htmlFor="stock">
                        Stok awal
                        {isMeasured(form.unit) &&
                            ` (${unitOf(form.unit).label})`}
                    </FieldLabel>
                    <QuantityInput
                        id="stock"
                        unit={form.unit}
                        placeholder={
                            isMeasured(form.unit) ? "misal 25 atau 12,5" : "0"
                        }
                        value={form.stock}
                        onChange={(value) => set("stock", value)}
                        aria-invalid={!!errors.stock}
                    />
                    <FieldDescription>
                        Stok berikutnya dicatat lewat menu Stok Masuk.
                    </FieldDescription>
                    <FieldError>{errors.stock}</FieldError>
                </Field>
            )}
            <div className="flex flex-col-reverse gap-2 border-t pt-4 sm:flex-row sm:justify-end">
                <Button asChild type="button" variant="outline">
                    <Link href="/products">Batal</Link>
                </Button>
                <Button
                    className="sm:min-w-32"
                    disabled={save.isPending || nameTaken}
                >
                    {save.isPending ? "Menyimpan…" : "Simpan"}
                </Button>
            </div>
        </form>
    );
}

const normalizeName = (name) => name.trim().replace(/\s+/g, " ");

// The right column from 1024px: always there, so the page does not jump, and
// says what the name check found. "Belum terdaftar" only once the server has
// answered for exactly what is typed now; until then it is still checking.
function NameCheckPanel({ status, product, hint }) {
    const notes = {
        idle: {
            icon: SearchCheck,
            className: "text-muted-foreground",
            text: "Ketik nama produk. Kami cek apakah sudah ada di inventori.",
        },
        checking: {
            icon: Loader2,
            iconClassName: "animate-spin",
            className: "text-muted-foreground",
            text: "Mengecek nama…",
        },
        error: {
            icon: AlertTriangle,
            className: "text-muted-foreground",
            text: "Nama belum bisa dicek. Coba ketik ulang sebentar lagi.",
        },
        unique: {
            icon: CircleCheck,
            className: "text-primary",
            text: "Belum ada produk dengan nama ini. Aman disimpan.",
        },
        unchanged: {
            icon: CircleCheck,
            className: "text-muted-foreground",
            text: `Nama saat ini: ${product?.name}.`,
        },
    };
    const note = notes[status];

    return (
        <Card className="gap-3 py-4">
            <CardHeader className="px-4">
                <CardTitle className="text-sm">Cek nama produk</CardTitle>
            </CardHeader>
            <CardContent className="px-4 text-sm">
                {note ? (
                    <div className={`flex items-start gap-2 ${note.className}`}>
                        <note.icon
                            className={`mt-0.5 size-4 shrink-0 ${note.iconClassName ?? ""}`}
                        />
                        <p>{note.text}</p>
                    </div>
                ) : (
                    hint
                )}
            </CardContent>
        </Card>
    );
}

// The form plus the name check: duplicates under the name below 1024px, the
// whole check in the right column from 1024px. Keyed by product: "Ubah" on a
// duplicate lands on another product's page, and Inertia keeps the page
// component mounted, so this part has to start over.
function Editor({ product, categories }) {
    const [name, setName] = useState(product?.name ?? "");
    const currentName = normalizeName(name);
    const typedName = useDebouncedValue(currentName);
    const { data, isPlaceholderData, isError } = useSimilarProducts(
        typedName,
        product?.id,
    );
    // The answer counts only when it is for the name typed right now: not
    // mid-debounce, and not the previous name's list kept while loading.
    const answered =
        currentName.length >= 2 &&
        typedName === currentName &&
        data !== undefined &&
        !isPlaceholderData;
    const matches = answered ? data : [];
    const exact = matches.find((item) => item.exact);
    const similar = matches.filter((item) => !item.exact);
    const hint =
        exact || similar.length > 0 ? (
            <DuplicateHint
                exact={exact}
                similar={similar}
                onEdit={(other) => router.visit(`/products/${other.id}/edit`)}
            />
        ) : null;

    let status;
    if (currentName.length < 2) status = "idle";
    else if (hint) status = "found";
    else if (answered)
        status =
            product &&
            currentName.toLowerCase() === product.name.toLowerCase()
                ? "unchanged"
                : "unique";
    else if (isError && typedName === currentName) status = "error";
    else status = "checking";

    return (
        <div className="grid items-start gap-4 lg:grid-cols-10">
            <Card className="px-4 py-4 md:px-6 md:py-6 lg:col-span-6">
                <Form
                    product={product}
                    categories={categories}
                    hint={hint}
                    nameTaken={!!exact}
                    onNameChange={setName}
                    onDone={() => router.visit("/products")}
                />
            </Card>
            <div className="hidden lg:col-span-4 lg:block">
                <NameCheckPanel status={status} product={product} hint={hint} />
            </div>
        </div>
    );
}

// Its own page (/products/create, /products/{id}/edit), back to the list once saved.
export default function ProductForm({
    product,
    categories: initialCategories,
}) {
    const { data: categories } = useCategoryOptions(initialCategories);
    const title = product ? "Ubah produk" : "Tambah produk";

    return (
        <Layout
            title={title}
            breadcrumbs={
                product && [
                    { label: "Produk", href: "/products" },
                    { label: product.name },
                ]
            }
        >
            <div className="mb-3 flex items-center gap-2">
                <Button asChild variant="ghost" size="icon">
                    <Link href="/products">
                        <ArrowLeft />
                        <span className="sr-only">
                            Kembali ke daftar produk
                        </span>
                    </Link>
                </Button>
                <div className="min-w-0">
                    <h1 className="text-xl font-bold">{title}</h1>
                    <p className="truncate text-sm text-muted-foreground">
                        {product
                            ? `Perbarui nama atau harga ${product.name}.`
                            : "Isi data produk baru."}
                    </p>
                </div>
            </div>
            <Editor
                key={product?.id ?? "new"}
                product={product}
                categories={categories}
            />
        </Layout>
    );
}
