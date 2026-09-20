import { Head, Link, router, usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';
import { onToast } from '../toast';

const menu = [
    { href: '/', label: 'Kasir', icon: '🛒' },
    { href: '/products', label: 'Produk', icon: '📦' },
    { href: '/stock-in', label: 'Stok Masuk', icon: '📥' },
    { href: '/report', label: 'Laporan', icon: '📊' },
];

export default function Layout({ title, children }) {
    const { url, props } = usePage();
    const path = url.split('?')[0];
    const [toast, setToast] = useState(null);

    // Messages now arrive from mutations (JSON), with Inertia flash as a fallback.
    useEffect(() => onToast(setToast), []);

    useEffect(() => {
        if (props.flash?.success) setToast(props.flash.success);
    }, [props.flash]);

    useEffect(() => {
        if (!toast) return;
        const timer = setTimeout(() => setToast(null), 2500);
        return () => clearTimeout(timer);
    }, [toast]);

    return (
        <div className="min-h-dvh pb-20 md:pb-0">
            <Head title={title} />

            <header className="sticky top-0 z-20 bg-emerald-800 text-white shadow">
                <div className="mx-auto flex max-w-6xl items-center gap-4 px-4 py-2.5">
                    <span className="text-lg font-bold">🏪 Warung</span>
                    <nav className="hidden gap-1 md:flex">
                        {menu.map((item) => (
                            <Link
                                key={item.href}
                                href={item.href}
                                className={`rounded-lg px-3 py-1.5 font-medium ${path === item.href ? 'bg-white/20' : 'hover:bg-white/10'}`}
                            >
                                {item.label}
                            </Link>
                        ))}
                    </nav>
                    <button
                        onClick={() => router.post('/logout')}
                        className="ml-auto rounded-lg px-3 py-1.5 text-sm text-white/80 hover:bg-white/10"
                    >
                        Keluar ({props.auth?.user?.name})
                    </button>
                </div>
            </header>

            <main className="mx-auto max-w-6xl p-3 md:p-4">{children}</main>

            {/* Bottom navigation for phones */}
            <nav className="fixed inset-x-0 bottom-0 z-30 grid grid-cols-4 border-t border-stone-200 bg-white pb-[env(safe-area-inset-bottom)] md:hidden">
                {menu.map((item) => (
                    <Link
                        key={item.href}
                        href={item.href}
                        className={`flex flex-col items-center py-2 text-xs font-medium ${path === item.href ? 'text-emerald-700' : 'text-stone-500'}`}
                    >
                        <span className="text-xl leading-none">{item.icon}</span>
                        {item.label}
                    </Link>
                ))}
            </nav>

            {toast && (
                <div className="fixed inset-x-0 top-16 z-50 flex justify-center px-4">
                    <div className="rounded-xl bg-stone-900 px-5 py-3 font-semibold text-white shadow-lg">✓ {toast}</div>
                </div>
            )}
        </div>
    );
}
