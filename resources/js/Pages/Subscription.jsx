import { Head, router, usePage } from '@inertiajs/react';
import { AlertCircle, CalendarCheck, CalendarX, FlaskConical, LogOut, Store } from 'lucide-react';
import { useEffect, useState } from 'react';
import Layout from '@/components/Layout';
import Steps from '@/components/Steps';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { daysUntil, formatDate, formatDateTime, formatRupiah } from '@/lib/format';
import { cn } from '@/lib/utils';
import { toast } from '@/toast';

const paymentStatus = {
    paid: { label: 'Lunas', variant: 'default' },
    pending: { label: 'Menunggu', variant: 'warning' },
    expired: { label: 'Kedaluwarsa', variant: 'secondary' },
};

// Stores that cannot use the app yet (or anymore) get a bare frame without the menu.
function Shell({ children }) {
    const { auth, flash } = usePage().props;

    useEffect(() => {
        toast(flash?.success);
    }, [flash]);

    return (
        <div className="min-h-dvh">
            <Head title="Langganan" />
            <header className="sticky top-0 z-20 border-b bg-card/90 backdrop-blur">
                <div className="mx-auto flex h-14 max-w-4xl items-center gap-2 px-3">
                    <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <Store className="size-4" />
                    </span>
                    <span className="truncate font-semibold">{auth.store?.name}</span>
                    <Button variant="ghost" size="sm" className="ml-auto text-muted-foreground" onClick={() => router.post('/logout')}>
                        <LogOut />
                        Keluar
                    </Button>
                </div>
            </header>
            <main className="mx-auto max-w-4xl p-3 md:p-4">{children}</main>
        </div>
    );
}

function StatusCard({ status, endsAt }) {
    if (status === 'pending') {
        return (
            <Card className="gap-4 px-4 py-4">
                <Steps current={2} />
                <div>
                    <h1 className="text-xl font-bold">Pilih paket langganan</h1>
                    <p className="text-sm text-muted-foreground">
                        Akunmu sudah dibuat. Pilih paket dan selesaikan pembayaran untuk mulai berjualan.
                    </p>
                </div>
            </Card>
        );
    }

    const active = status === 'active';
    const Icon = active ? CalendarCheck : CalendarX;

    return (
        <Card className="flex-row items-center gap-3 px-4 py-4">
            <Icon className={cn('size-8 shrink-0', active ? 'text-primary' : 'text-destructive')} />
            <div className="min-w-0">
                <h1 className="text-lg font-bold">
                    {active ? `Aktif sampai ${formatDate(endsAt)}` : `Langganan habis sejak ${formatDate(endsAt)}`}
                </h1>
                <p className="text-sm text-muted-foreground">
                    {active
                        ? `Sisa ${daysUntil(endsAt)} hari. Perpanjang lebih awal tidak rugi: masa baru ditambahkan setelah tanggal itu.`
                        : 'Pilih paket untuk kembali berjualan. Data tokomu tetap aman.'}
                </p>
            </div>
        </Card>
    );
}

function PlanPicker({ plans, status }) {
    const { errors } = usePage().props;
    // One year is the default: the best deal most stores will want.
    const [selected, setSelected] = useState(plans.find((item) => item.value === 12)?.value ?? plans[0].value);
    const [processing, setProcessing] = useState(false);
    const plan = plans.find((item) => item.value === selected);
    const bestSaving = Math.max(...plans.map((item) => item.saving));

    // The server answers with the gateway's URL; Inertia leaves the app for it.
    const pay = () =>
        router.post(
            '/subscription/checkout',
            { plan: selected },
            { onStart: () => setProcessing(true), onFinish: () => setProcessing(false) },
        );

    return (
        <Card className="gap-4 py-4">
            <CardHeader className="px-4">
                <CardTitle>{status === 'pending' ? 'Paket' : 'Perpanjang langganan'}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4 px-4">
                <div role="radiogroup" aria-label="Paket langganan" className="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                    {plans.map((item) => (
                        <button
                            key={item.value}
                            type="button"
                            role="radio"
                            aria-checked={item.value === selected}
                            onClick={() => setSelected(item.value)}
                            className={cn(
                                'relative flex flex-col items-start gap-1 rounded-md border p-3 text-left transition-colors hover:bg-accent',
                                item.value === selected && 'border-primary bg-primary/5 ring-2 ring-primary hover:bg-primary/5',
                            )}
                        >
                            <span className="font-semibold">{item.label}</span>
                            <span className="text-lg font-bold tabular-nums">{formatRupiah(item.price)}</span>
                            <span className="text-xs text-muted-foreground">{formatRupiah(item.perMonth)}/bulan</span>
                            {item.saving > 0 && (
                                <Badge variant={item.saving === bestSaving ? 'default' : 'outline'} className="mt-1">
                                    {item.saving === bestSaving ? 'Paling hemat' : `Hemat ${formatRupiah(item.saving)}`}
                                </Badge>
                            )}
                        </button>
                    ))}
                </div>

                {errors.plan && (
                    <Alert variant="destructive">
                        <AlertCircle />
                        <AlertTitle className="line-clamp-none">{errors.plan}</AlertTitle>
                    </Alert>
                )}

                <div className="flex flex-col gap-3 border-t pt-4 sm:flex-row sm:items-center">
                    <div className="flex-1 text-sm">
                        <div className="text-muted-foreground">Total</div>
                        <div className="text-2xl font-bold tabular-nums">{formatRupiah(plan.price)}</div>
                        <div className="text-muted-foreground">
                            Paket {plan.label} · QRIS, transfer bank (VA), e-wallet, atau kartu
                        </div>
                    </div>
                    <Button size="lg" className="sm:w-56" disabled={processing} onClick={pay}>
                        {processing ? 'Membuka pembayaran…' : `Bayar ${formatRupiah(plan.price)}`}
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}

function History({ payments }) {
    if (payments.length === 0) return null;

    return (
        <Card className="gap-0 py-0">
            <CardHeader className="px-4 py-4">
                <CardTitle>Riwayat pembayaran</CardTitle>
            </CardHeader>
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Tanggal</TableHead>
                        <TableHead>Paket</TableHead>
                        <TableHead className="text-right">Jumlah</TableHead>
                        <TableHead className="text-right">Status</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {payments.map((payment) => (
                        <TableRow key={payment.external_id}>
                            <TableCell className="text-muted-foreground tabular-nums">
                                {formatDateTime(payment.paid_at ?? payment.created_at)}
                            </TableCell>
                            <TableCell>
                                {payment.plan_label}
                                {payment.period_ends_at && (
                                    <div className="text-xs text-muted-foreground">
                                        s.d. {formatDate(payment.period_ends_at)}
                                    </div>
                                )}
                            </TableCell>
                            <TableCell className="text-right tabular-nums">{formatRupiah(payment.amount)}</TableCell>
                            <TableCell className="text-right">
                                {payment.payable ? (
                                    <Button asChild size="sm" variant="outline">
                                        <a href={payment.checkout_url}>Lanjut bayar</a>
                                    </Button>
                                ) : (
                                    <Badge variant={paymentStatus[payment.status]?.variant}>
                                        {paymentStatus[payment.status]?.label ?? payment.status}
                                    </Badge>
                                )}
                            </TableCell>
                        </TableRow>
                    ))}
                </TableBody>
            </Table>
        </Card>
    );
}

export default function Subscription({ status, endsAt, plans, payments, testMode }) {
    const content = (
        <div className="space-y-3">
            <StatusCard status={status} endsAt={endsAt} />
            {testMode && (
                <Alert>
                    <FlaskConical />
                    <AlertTitle>Mode uji</AlertTitle>
                    <AlertDescription>
                        Pembayaran disimulasikan di aplikasi ini, tidak ada uang yang berpindah. Atur PAYMENT_DRIVER=xendit
                        untuk memakai Xendit.
                    </AlertDescription>
                </Alert>
            )}
            <PlanPicker plans={plans} status={status} />
            <History payments={payments} />
        </div>
    );

    // A paying store keeps its menu; the others only see this page.
    return status === 'active' ? <Layout title="Langganan">{content}</Layout> : <Shell>{content}</Shell>;
}
