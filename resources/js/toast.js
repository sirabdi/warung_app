// Minimal toast store. Mutations now answer over JSON instead of an Inertia
// redirect, so the success message is shown from the client side.
const listeners = new Set();

export function toast(message) {
    if (!message) return;
    listeners.forEach((listener) => listener(message));
}

export function onToast(listener) {
    listeners.add(listener);
    return () => listeners.delete(listener);
}
