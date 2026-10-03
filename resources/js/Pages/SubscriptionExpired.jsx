import { Link, router } from '@inertiajs/react';
import { CalendarX, LogOut } from 'lucide-react';
import AuthCard from '@/components/AuthCard';
import { Button } from '@/components/ui/button';
import { formatDate } from '@/lib/format';

// Shown instead of the app once a paid period is over. Data stays; the till is locked.
export default function SubscriptionExpired({ storeName, endsAt }) {
    return (
        <AuthCard
            icon={CalendarX}
            title="Langganan Habis"
            description={`Langganan ${storeName} berakhir ${formatDate(endsAt)}. Silakan perpanjang untuk kembali berjualan.`}
        >
            <p className="text-center text-sm text-muted-foreground">
                Data produk, stok, dan laporanmu tetap aman dan langsung bisa dipakai lagi setelah diperpanjang.
            </p>
            <div className="grid grid-cols-2 gap-2">
                <Button variant="outline" size="lg" onClick={() => router.post('/logout')}>
                    <LogOut />
                    Keluar
                </Button>
                <Button asChild size="lg">
                    <Link href="/subscription">Perpanjang</Link>
                </Button>
            </div>
        </AuthCard>
    );
}
