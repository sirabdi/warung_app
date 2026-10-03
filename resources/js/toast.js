import { toast as sonner } from 'sonner';

// Mutations answer over JSON instead of an Inertia redirect, so the success
// message is shown from the client side. Rendered by <Toaster /> in Layout.
export function toast(message) {
    if (!message) return;
    sonner.success(message);
}
