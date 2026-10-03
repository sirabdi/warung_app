import { Link, usePoll } from '@inertiajs/react';
import { CircleAlert, Loader2 } from 'lucide-react';
import AuthCard from '@/components/AuthCard';
import { Button } from '@/components/ui/button';
import { formatRupiah } from '@/lib/format';

// Back from the payment page before the gateway's webhook confirmed it. The page
// asks again every few seconds; once paid the server redirects to the till.
export default function PaymentFinish({ payment }) {
    const pending = payment.status === 'pending';
    usePoll(3000, {}, { autoStart: pending });

    if (!pending) {
        return (
            <AuthCard icon={CircleAlert} title="Tagihan kedaluwarsa" description="Tagihan ini tidak dibayar sampai batas waktunya.">
                <Button asChild size="lg" className="w-full">
                    <Link href="/subscription">Pilih paket lagi</Link>
                </Button>
            </AuthCard>
        );
    }

    return (
        <AuthCard
            icon={Loader2}
            title="Menunggu konfirmasi"
            description={`Paket ${payment.planLabel} · ${formatRupiah(payment.amount)}`}
        >
            <p className="text-center text-sm text-muted-foreground">
                Kalau sudah membayar, halaman ini pindah sendiri begitu pembayaran terkonfirmasi. Biasanya hanya
                beberapa detik.
            </p>
            <div className="grid gap-2">
                {payment.checkoutUrl && (
                    <Button asChild size="lg">
                        <a href={payment.checkoutUrl}>Buka lagi halaman bayar</a>
                    </Button>
                )}
                <Button asChild variant="outline" size="lg">
                    <Link href="/subscription">Kembali ke paket</Link>
                </Button>
            </div>
        </AuthCard>
    );
}
