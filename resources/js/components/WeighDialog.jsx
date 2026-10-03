import { Scale, Wallet } from 'lucide-react';
import { useState } from 'react';
import NumberInput from '@/components/NumberInput';
import QuantityInput from '@/components/QuantityInput';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Field, FieldDescription, FieldError, FieldLabel } from '@/components/ui/field';
import { formatRupiah } from '@/lib/format';
import { formatPrice, formatQty, lineTotal, qtyForAmount, unitOf } from '@/lib/units';
import { cn } from '@/lib/utils';

const quickWeights = [0.25, 0.5, 1, 2];
const quickAmounts = [5000, 10000, 20000, 50000];

// Body of the dialog; mounted per opening so it starts from the product's
// current cart quantity.
function WeighForm({ product, initialQty, onConfirm }) {
    const { scale, label } = unitOf(product.unit);
    const [mode, setMode] = useState('weight'); // 'weight' | 'amount'
    const [qty, setQty] = useState(initialQty || '');
    const [amount, setAmount] = useState('');

    // "Minyak 10 ribu": the amount decides how much to weigh.
    const finalQty = mode === 'amount' ? qtyForAmount(amount, product.sell_price, product.unit) : qty;
    const total = finalQty ? lineTotal(product.sell_price, finalQty, product.unit) : 0;
    const tooMuch = finalQty > product.stock;
    const canAdd = finalQty > 0 && !tooMuch;

    const submit = (e) => {
        e.preventDefault();
        if (canAdd) onConfirm(finalQty);
    };

    return (
        <form onSubmit={submit} className="grid gap-4">
            <div className="grid grid-cols-2 gap-2">
                <Button
                    type="button"
                    variant={mode === 'weight' ? 'default' : 'outline'}
                    aria-pressed={mode === 'weight'}
                    onClick={() => setMode('weight')}
                >
                    <Scale />
                    Isi berat
                </Button>
                <Button
                    type="button"
                    variant={mode === 'amount' ? 'default' : 'outline'}
                    aria-pressed={mode === 'amount'}
                    onClick={() => setMode('amount')}
                >
                    <Wallet />
                    Isi nominal
                </Button>
            </div>

            {mode === 'weight' ? (
                <Field>
                    <FieldLabel htmlFor="weigh-qty">Berat ({label})</FieldLabel>
                    <QuantityInput
                        id="weigh-qty"
                        unit={product.unit}
                        value={qty}
                        onChange={setQty}
                        placeholder="0"
                        aria-invalid={tooMuch}
                        className="h-14 text-center text-2xl font-bold md:text-2xl"
                        autoFocus
                    />
                    <div className="grid grid-cols-4 gap-2">
                        {quickWeights.map((w) => (
                            <Button
                                key={w}
                                type="button"
                                variant="outline"
                                size="lg"
                                onClick={() => setQty(Math.round(w * scale))}
                                className="px-0 text-base font-bold"
                            >
                                {w === 0.25 ? '¼' : w === 0.5 ? '½' : w} {label}
                            </Button>
                        ))}
                    </div>
                </Field>
            ) : (
                <Field>
                    <FieldLabel htmlFor="weigh-amount">Uang pembeli (Rp)</FieldLabel>
                    <NumberInput
                        id="weigh-amount"
                        value={amount}
                        onChange={setAmount}
                        placeholder="0"
                        aria-invalid={tooMuch}
                        className="h-14 text-center text-2xl font-bold md:text-2xl"
                        autoFocus
                    />
                    <div className="grid grid-cols-4 gap-2">
                        {quickAmounts.map((a) => (
                            <Button
                                key={a}
                                type="button"
                                variant="outline"
                                size="lg"
                                onClick={() => setAmount(a)}
                                className="px-0 text-base font-bold"
                            >
                                {a / 1000}rb
                            </Button>
                        ))}
                    </div>
                </Field>
            )}

            <div className={cn('rounded-md border bg-card p-3', !finalQty && 'text-muted-foreground')}>
                <div className="flex items-baseline justify-between gap-2">
                    <span className="text-sm">{mode === 'amount' ? 'Timbang' : 'Harga'}</span>
                    <span className="text-2xl font-bold tabular-nums">
                        {mode === 'amount' ? formatQty(finalQty || 0, product.unit) : formatRupiah(total)}
                    </span>
                </div>
                {mode === 'amount' && finalQty > 0 && (
                    <FieldDescription className="text-right">dibulatkan jadi {formatRupiah(total)}</FieldDescription>
                )}
            </div>
            {tooMuch && <FieldError>Stok tinggal {formatQty(product.stock, product.unit)}.</FieldError>}

            <DialogFooter>
                <DialogClose asChild>
                    <Button type="button" variant="outline">
                        Batal
                    </Button>
                </DialogClose>
                <Button disabled={!canAdd}>{initialQty ? 'Simpan' : 'Masukkan keranjang'}</Button>
            </DialogFooter>
        </form>
    );
}

// Weighed goods (rice, bulk oil): asks for a weight or an amount of money
// instead of adding one piece per tap.
export default function WeighDialog({ product, initialQty, onConfirm, onClose }) {
    return (
        <Dialog open={product !== null} onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                {product && (
                    <>
                        <DialogHeader>
                            <DialogTitle>{product.name}</DialogTitle>
                            <DialogDescription>
                                {formatPrice(product.sell_price, product.unit)} · stok{' '}
                                {formatQty(product.stock, product.unit)}
                            </DialogDescription>
                        </DialogHeader>
                        <WeighForm key={product.id} product={product} initialQty={initialQty} onConfirm={onConfirm} />
                    </>
                )}
            </DialogContent>
        </Dialog>
    );
}
