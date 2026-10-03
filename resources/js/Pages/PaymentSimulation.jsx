import { Link, router, usePage } from '@inertiajs/react';
import { AlertCircle, FlaskConical } from 'lucide-react';
import { useState } from 'react';
import AuthCard from '@/components/AuthCard';
import { Alert, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/format';

// PAYMENT_DRIVER=fake: stands in for Xendit's invoice page while developing.
export default function PaymentSimulation({ payment }) {
    const { errors } = usePage().props;
    const [processing, setProcessing] = useState(false);
    const url = `/pay/simulate/${payment.externalId}`;
    const options = { onStart: () => setProcessing(true), onFinish: () => setProcessing(false) };

    const closed = payment.status === 'paid' || payment.status === 'expired' || payment.expired;

    return (
        <AuthCard icon={FlaskConical} title="Simulasi pembayaran" description="Mode uji: pengganti halaman Xendit, tidak ada uang yang berpindah.">
            <dl className="divide-y rounded-md border text-sm">
                {[
                    ['Toko', payment.storeName],
                    ['Paket', payment.planLabel],
                    ['No. tagihan', payment.externalId],
                ].map(([label, value]) => (
                    <div key={label} className="flex justify-between gap-3 px-3 py-2">
                        <dt className="text-muted-foreground">{label}</dt>
                        <dd className="truncate font-medium">{value}</dd>
                    </div>
                ))}
                <div className="flex items-baseline justify-between gap-3 px-3 py-3">
                    <dt className="text-muted-foreground">Total</dt>
                    <dd className="text-2xl font-bold tabular-nums">{formatRupiah(payment.amount)}</dd>
                </div>
            </dl>

            {errors.payment && (
                <Alert variant="destructive">
                    <AlertCircle />
                    <AlertTitle className="line-clamp-none">{errors.payment}</AlertTitle>
                </Alert>
            )}

            {closed ? (
                <>
                    <p className="text-center text-sm text-muted-foreground">
                        {payment.status === 'paid' ? 'Tagihan ini sudah dibayar.' : 'Tagihan ini sudah tidak berlaku.'}
                    </p>
                    <Button asChild size="lg" className="w-full">
                        <Link href="/subscription">Ke halaman langganan</Link>
                    </Button>
                </>
            ) : (
                <div className="grid grid-cols-2 gap-2">
                    <Button variant="outline" size="lg" disabled={processing} onClick={() => router.post(`${url}/cancel`, {}, options)}>
                        Batalkan
                    </Button>
                    <Button size="lg" disabled={processing} onClick={() => router.post(url, {}, options)}>
                        {processing ? 'Memproses…' : 'Bayar sekarang'}
                    </Button>
                </div>
            )}
        </AuthCard>
    );
}
