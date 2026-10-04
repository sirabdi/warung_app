import { Head, Link, router, usePage } from '@inertiajs/react';
import { Fragment, useEffect, useState } from 'react';
import { BarChart3, CalendarClock, CreditCard, LogOut, PackageOpen, PackagePlus, PanelLeft, ShoppingCart, Store, Tags } from 'lucide-react';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { daysUntil, formatDate } from '@/lib/format';
import { cn } from '@/lib/utils';
import { toast } from '@/toast';

const menu = [
    { href: '/', label: 'Kasir', icon: ShoppingCart },
    { href: '/products', label: 'Produk', icon: PackageOpen },
    { href: '/categories', label: 'Kategori', icon: Tags },
    { href: '/stock-in', label: 'Stok Masuk', icon: PackagePlus },
    { href: '/report', label: 'Laporan', icon: BarChart3 },
];

// Not in the bottom bar on phones (five is its limit): the header has an icon.
const subscriptionItem = { href: '/subscription', label: 'Langganan', icon: CreditCard };

// The reminder emails go out H-7 and H-1; the app says it every day of that week.
const WARN_DAYS = 7;

// Remembered per browser. Layout remounts on every visit, so the choice has to
// live outside React state; storage may be blocked, hence the try/catch.
const STORAGE_KEY = 'warung:sidebar-collapsed';

function readCollapsed() {
    try {
        return window.localStorage.getItem(STORAGE_KEY) === '1';
    } catch {
        return false;
    }
}

function saveCollapsed(collapsed) {
    try {
        window.localStorage.setItem(STORAGE_KEY, collapsed ? '1' : '0');
    } catch {
        // Not remembered, but the sidebar still works.
    }
}

// Icon only when collapsed (name in a tooltip), icon + name when expanded.
function SidebarItem({ collapsed, active, label, ...props }) {
    // Controlled on purpose. Radix closes a tooltip when the pointer leaves by
    // tracking it toward the tooltip box; while expanded there is no box, so an
    // uncontrolled tooltip would stay "open" and pop up later on collapse.
    const [open, setOpen] = useState(false);

    const button = (
        <Button
            variant="ghost"
            className={cn(
                'w-full justify-start gap-3 px-3 font-medium text-muted-foreground hover:text-foreground',
                collapsed && 'size-10 justify-center px-0',
                active && 'bg-accent text-accent-foreground hover:bg-accent hover:text-accent-foreground',
            )}
            {...props}
        />
    );

    return (
        <Tooltip open={collapsed && open} onOpenChange={(next) => setOpen(collapsed && next)}>
            <TooltipTrigger asChild>{button}</TooltipTrigger>
            <TooltipContent side="right" sideOffset={8}>
                {label}
            </TooltipContent>
        </Tooltip>
    );
}

// "/products/create" still belongs to the Produk menu; "/" only matches itself.
const isActive = (path, href) => path === href || (href !== '/' && path.startsWith(`${href}/`));

// The trail after the store: the menu the page belongs to, then the page itself
// when it is a sub-page (Produk › Tambah produk). A page may pass its own.
function defaultTrail(path, title) {
    const section = [...menu, subscriptionItem].find((item) => isActive(path, item.href));
    if (!section) return [{ label: title }];
    if (path === section.href) return [{ label: section.label }];
    return [{ label: section.label, href: section.href }, { label: title }];
}

// Store › section › page. Phones show only the current page: the header is narrow.
function PageBreadcrumb({ storeName, trail }) {
    return (
        <Breadcrumb className="min-w-0">
            <BreadcrumbList className="flex-nowrap">
                <BreadcrumbItem className="hidden md:inline-flex">
                    <BreadcrumbLink asChild>
                        <Link href="/">{storeName}</Link>
                    </BreadcrumbLink>
                </BreadcrumbItem>
                {trail.map((crumb, i) => {
                    const last = i === trail.length - 1;
                    return (
                        <Fragment key={i}>
                            <BreadcrumbSeparator className="hidden md:block" />
                            <BreadcrumbItem className={cn('min-w-0', !last && 'hidden md:inline-flex')}>
                                {last || !crumb.href ? (
                                    <BreadcrumbPage className={cn('truncate', last && 'font-semibold')}>
                                        {crumb.label}
                                    </BreadcrumbPage>
                                ) : (
                                    <BreadcrumbLink asChild>
                                        <Link href={crumb.href}>{crumb.label}</Link>
                                    </BreadcrumbLink>
                                )}
                            </BreadcrumbItem>
                        </Fragment>
                    );
                })}
            </BreadcrumbList>
        </Breadcrumb>
    );
}

const orDash = (value) => value?.trim() || '-';

export default function Layout({ title, breadcrumbs, children }) {
    const { url, props } = usePage();
    const path = url.split('?')[0];
    const userName = props.auth?.user?.name;
    const store = props.auth?.store;
    const endsAt = store?.subscription_ends_at;
    const daysLeft = endsAt ? daysUntil(endsAt) : null;
    const [collapsed, setCollapsed] = useState(readCollapsed);

    const toggle = () =>
        setCollapsed((current) => {
            saveCollapsed(!current);
            return !current;
        });

    // Ctrl/Cmd + B, the same shortcut as shadcn's sidebar.
    useEffect(() => {
        const onKey = (e) => {
            if (e.key.toLowerCase() === 'b' && (e.metaKey || e.ctrlKey)) {
                e.preventDefault();
                toggle();
            }
        };
        window.addEventListener('keydown', onKey);
        return () => window.removeEventListener('keydown', onKey);
    }, []);

    // Messages mostly arrive from mutations (JSON); Inertia flash is the fallback.
    useEffect(() => {
        toast(props.flash?.success);
    }, [props.flash]);

    return (
        <div className="min-h-dvh lg:flex">
            <Head title={title} />

            {/* Sidebar from 1024px; phones and tablets keep the bottom bar */}
            <aside
                className={cn(
                    'sticky top-0 hidden h-dvh shrink-0 flex-col border-r bg-card transition-[width] duration-200 lg:flex',
                    collapsed ? 'w-16' : 'w-60',
                )}
            >
                <div className={cn('flex h-14 items-center gap-2 border-b px-3', collapsed && 'justify-center px-0')}>
                    <span className="flex size-8 shrink-0 items-center justify-center rounded-md bg-primary text-primary-foreground">
                        <Store className="size-4" />
                    </span>
                    {!collapsed && <span className="truncate text-lg font-bold">Warung</span>}
                </div>

                <nav className={cn('flex flex-1 flex-col gap-1 p-3', collapsed && 'items-center px-0')}>
                    {menu.map((item) => (
                        <SidebarItem
                            key={item.href}
                            asChild
                            collapsed={collapsed}
                            active={isActive(path, item.href)}
                            label={item.label}
                        >
                            <Link href={item.href} aria-current={isActive(path, item.href) ? 'page' : undefined}>
                                <item.icon className="size-5" />
                                {collapsed ? <span className="sr-only">{item.label}</span> : item.label}
                            </Link>
                        </SidebarItem>
                    ))}
                </nav>

                <div className={cn('flex flex-col gap-1 border-t p-3', collapsed && 'items-center px-0')}>
                    <SidebarItem
                        asChild
                        collapsed={collapsed}
                        active={path === subscriptionItem.href}
                        label={subscriptionItem.label}
                    >
                        <Link href={subscriptionItem.href}>
                            <subscriptionItem.icon className="size-5" />
                            {collapsed ? <span className="sr-only">{subscriptionItem.label}</span> : subscriptionItem.label}
                        </Link>
                    </SidebarItem>
                    <SidebarItem collapsed={collapsed} label={`Keluar (${userName})`} onClick={() => router.post('/logout')}>
                        <LogOut className="size-5" />
                        {collapsed ? (
                            <span className="sr-only">Keluar</span>
                        ) : (
                            <span className="truncate">Keluar ({userName})</span>
                        )}
                    </SidebarItem>
                </div>
            </aside>

            <div className="min-w-0 flex-1 pb-20 lg:pb-0">
                <header className="sticky top-0 z-20 border-b bg-card/90 backdrop-blur">
                    <div className="flex h-14 items-center gap-2 px-3 lg:px-4">
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <Button variant="ghost" size="icon" onClick={toggle} className="hidden lg:inline-flex">
                                    <PanelLeft />
                                    <span className="sr-only">{collapsed ? 'Lebarkan menu' : 'Kecilkan menu'}</span>
                                </Button>
                            </TooltipTrigger>
                            <TooltipContent side="bottom">
                                {collapsed ? 'Lebarkan menu' : 'Kecilkan menu'} (Ctrl+B)
                            </TooltipContent>
                        </Tooltip>

                        {/* Phones have no sidebar: the brand sits in the header. */}
                        <span className="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground lg:hidden">
                            <Store className="size-4" />
                        </span>

                        <Separator orientation="vertical" className="hidden !h-5 lg:block" />
                        <PageBreadcrumb
                            storeName={props.auth?.store?.name ?? 'Warung'}
                            trail={breadcrumbs ?? defaultTrail(path, title)}
                        />

                        {/* Which store this is, top right. Phones are too narrow: there it is left out. */}
                        {/* The address in full on one line: the breadcrumb gives way instead. */}
                        <div className="ml-auto hidden shrink-0 text-right leading-tight sm:block">
                            <div className="text-sm font-semibold whitespace-nowrap">{orDash(store?.name)}</div>
                            <div className="text-xs whitespace-nowrap text-muted-foreground">{orDash(store?.address)}</div>
                        </div>

                        <Button asChild variant="ghost" size="icon" className="ml-auto text-muted-foreground sm:ml-0 lg:hidden">
                            <Link href={subscriptionItem.href}>
                                <subscriptionItem.icon />
                                <span className="sr-only">{subscriptionItem.label}</span>
                            </Link>
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            onClick={() => router.post('/logout')}
                            className="text-muted-foreground lg:hidden"
                        >
                            <LogOut />
                            <span className="sr-only">Keluar</span>
                        </Button>
                    </div>
                </header>

                {daysLeft !== null && daysLeft <= WARN_DAYS && path !== subscriptionItem.href && (
                    <div className="flex items-center gap-2 border-b bg-warning/15 px-3 py-2 text-sm lg:px-4">
                        <CalendarClock className="size-4 shrink-0" />
                        <span className="min-w-0 flex-1">
                            Langganan berakhir {formatDate(endsAt)}, sisa {daysLeft} hari.
                        </span>
                        <Button asChild size="sm" variant="outline" className="h-8 shrink-0">
                            <Link href={subscriptionItem.href}>Perpanjang</Link>
                        </Button>
                    </div>
                )}

                <main className="mx-auto max-w-6xl p-3 md:p-4">{children}</main>
            </div>

            {/* Bottom navigation for phones */}
            <nav className="fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t bg-card pb-[env(safe-area-inset-bottom)] lg:hidden">
                {menu.map((item) => (
                    <Link
                        key={item.href}
                        href={item.href}
                        className={cn(
                            'flex flex-col items-center gap-1 py-2 text-xs font-medium text-muted-foreground',
                            isActive(path, item.href) && 'text-primary',
                        )}
                    >
                        <item.icon className="size-5" />
                        {item.label}
                    </Link>
                ))}
            </nav>
        </div>
    );
}
