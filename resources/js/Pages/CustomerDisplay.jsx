import { Head, usePage } from '@inertiajs/react';
import { Maximize, ShoppingBasket, Store } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/format';
import { useCustomerDisplay } from '@/lib/customer-display';
import { formatPrice, formatQty, isMeasured } from '@/lib/units';
import { cn } from '@/lib/utils';

// How long "Terima kasih" stays up after a sale, unless the next one starts.
const THANK_YOU_MS = 15_000;

const orDash = (value) => value?.trim() || '-';

function Amount({ label, value, className }) {
    return (
        <div className="flex items-baseline justify-between gap-4">
            <span className="text-xl text-muted-foreground">{label}</span>
            <span className={cn('text-3xl font-bold tabular-nums', className)}>{value}</span>
        </div>
    );
}

// The paid amount and the change, once the cashier has typed what was handed over.
function Payment({ total, paid }) {
    if (paid == null) return null;
    const change = paid - total;

    return (
        <div className="space-y-3 border-t pt-4">
            <Amount label="Dibayar" value={formatRupiah(paid)} />
            {change >= 0 ? (
                <Amount label="Kembalian" value={formatRupiah(change)} className="text-primary" />
            ) : (
                <Amount label="Kurang" value={formatRupiah(-change)} className="text-destructive" />
            )}
        </div>
    );
}

function Welcome({ storeName }) {
    return (
        <div className="flex flex-1 flex-col items-center justify-center gap-4 text-center">
            <span className="flex size-24 items-center justify-center rounded-2xl bg-primary text-primary-foreground">
                <Store className="size-12" />
            </span>
            <p className="text-2xl text-muted-foreground">Selamat datang di</p>
            <p className="text-5xl font-bold">{storeName}</p>
        </div>
    );
}

function ThankYou({ done }) {
    return (
        <div className="flex flex-1 flex-col items-center justify-center gap-8 text-center">
            <p className="text-6xl font-bold text-primary">Terima kasih!</p>
            <div className="w-full max-w-md space-y-3 text-left">
                <Amount label="Total" value={formatRupiah(done.total)} />
                <Payment total={done.total} paid={done.paid} />
            </div>
            <p className="text-xl text-muted-foreground">Selamat datang kembali.</p>
        </div>
    );
}

function Cart({ state }) {
    const highlighted = useRef(null);

    // Keep what the cashier just added in view, however long the list.
    useEffect(() => {
        highlighted.current?.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }, [state.highlight, state.lines]);

    return (
        <div className="grid min-h-0 flex-1 gap-6 lg:grid-cols-[1fr_28rem]">
            <div className="flex min-h-0 flex-col rounded-xl border bg-card">
                <div className="flex items-center gap-2 border-b px-6 py-4 text-xl font-semibold">
                    <ShoppingBasket className="size-6" />
                    Belanjaan Anda
                    <span className="ml-auto text-muted-foreground">{state.count} item</span>
                </div>
                <ul className="min-h-0 flex-1 divide-y overflow-y-auto">
                    {state.lines.map((line) => {
                        const isNew = line.id === state.highlight;
                        return (
                            <li
                                key={line.id}
                                ref={isNew ? highlighted : undefined}
                                className={cn('flex items-center gap-4 px-6 py-4 transition-colors', isNew && 'bg-primary/10')}
                            >
                                <div className="min-w-0 flex-1">
                                    <div className="truncate text-2xl font-medium">{line.name}</div>
                                    <div className="text-lg text-muted-foreground">
                                        {isMeasured(line.unit) ? formatQty(line.qty, line.unit) : `${line.qty} ×`}{' '}
                                        {formatPrice(line.price, line.unit)}
                                    </div>
                                </div>
                                <div className="text-2xl font-semibold tabular-nums">{formatRupiah(line.total)}</div>
                            </li>
                        );
                    })}
                </ul>
            </div>

            <div className="flex flex-col justify-end gap-4 rounded-xl border bg-card p-6">
                <div className="flex items-baseline justify-between gap-4">
                    <span className="text-2xl text-muted-foreground">Total</span>
                    <span className="text-6xl font-bold tabular-nums">{formatRupiah(state.total)}</span>
                </div>
                <Payment total={state.total} paid={state.paid} />
            </div>
        </div>
    );
}

// The second monitor, facing the buyer: what is being rung up, the total, what
// was paid and the change. It only shows what the cashier window sends.
export default function CustomerDisplay() {
    const store = usePage().props.auth?.store;
    const state = useCustomerDisplay();
    const [thanking, setThanking] = useState(false);

    useEffect(() => {
        if (!state.done) return;
        setThanking(true);
        const timer = setTimeout(() => setThanking(false), THANK_YOU_MS);
        return () => clearTimeout(timer);
    }, [state.done?.code]);

    const fullscreen = () => document.documentElement.requestFullscreen?.();

    let content;
    if (state.lines.length > 0) content = <Cart state={state} />;
    else if (thanking && state.done) content = <ThankYou done={state.done} />;
    else content = <Welcome storeName={orDash(store?.name)} />;

    return (
        <div className="flex h-dvh flex-col gap-6 overflow-hidden bg-background p-6">
            <Head title="Layar pelanggan" />
            <header className="flex items-center gap-4">
                <span className="flex size-12 items-center justify-center rounded-lg bg-primary text-primary-foreground">
                    <Store className="size-6" />
                </span>
                <div className="min-w-0">
                    <div className="truncate text-2xl font-bold">{orDash(store?.name)}</div>
                    <div className="truncate text-base text-muted-foreground">{orDash(store?.address)}</div>
                </div>
                {/* Browsers only allow full screen after a click; F11 works too. */}
                <Button variant="ghost" size="icon" className="ml-auto text-muted-foreground" onClick={fullscreen}>
                    <Maximize />
                    <span className="sr-only">Layar penuh</span>
                </Button>
            </header>
            {content}
        </div>
    );
}
