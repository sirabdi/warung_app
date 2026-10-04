import './bootstrap';
import '../css/app.css';

import { createInertiaApp, router } from '@inertiajs/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createRoot } from 'react-dom/client';
import { Toaster } from '@/components/ui/sonner';
import { appName } from '@/lib/app';

// One cache for the whole app. Inertia still renders the pages; TanStack Query
// owns the data inside them.
const queryClient = new QueryClient({
    defaultOptions: {
        queries: {
            retry: 1,
            refetchOnWindowFocus: true,
            refetchOnReconnect: true,
        },
        mutations: { retry: 0 },
    },
});

// Logout and login are Inertia visits, not page loads, so the cache would carry
// one store's data into the next account. Drop it once the signed-in user is gone
// or replaced; at that point no page that reads it is on screen.
let currentUserId;
router.on('navigate', (event) => {
    const userId = event.detail.page.props.auth?.user?.id ?? null;
    if (currentUserId != null && userId !== currentUserId) {
        queryClient.clear();
    }
    currentUserId = userId;
});

createInertiaApp({
    title: (title) => (title ? `${title} · ${appName}` : appName),
    resolve: (name) => resolvePageComponent(`./Pages/${name}.jsx`, import.meta.glob('./Pages/**/*.jsx')),
    setup({ el, App, props }) {
        currentUserId = props.initialPage.props.auth?.user?.id ?? null;
        createRoot(el).render(
            <QueryClientProvider client={queryClient}>
                <App {...props} />
                {/* Outside the pages on purpose: Layout remounts on every visit, and a
                    remounted Toaster replays toasts that already closed. */}
                <Toaster position="top-center" richColors />
            </QueryClientProvider>,
        );
    },
    progress: { color: '#047857' },
});
