import { cn } from '@/lib/utils';

const appName = import.meta.env.VITE_APP_NAME || 'Warung';

// Raise with each release; shown so a store can say which version it runs.
export const APP_VERSION = '1.0';

// One line at the bottom of every page except the two counter screens (Kasir
// and the customer display). The login side also says what the app is for.
export default function Footer({ tagline = false, className }) {
    return (
        <footer className={cn('px-4 py-4 text-center text-xs text-muted-foreground', className)}>
            © {new Date().getFullYear()} {appName}
            {tagline && ' · Kasir & stok untuk warung'} · v{APP_VERSION}
        </footer>
    );
}
