import { useEffect, useRef, useState } from 'react';

// The cashier and the customer display are two windows of the same browser
// (one per monitor), so they talk over a BroadcastChannel: no server, instant,
// and it keeps working without internet. Same browser means same login, so the
// display only ever sees its own store's cart.
const CHANNEL = 'warung-customer-display';

/**
 * What the customer sees.
 *
 * @typedef {{ id: number, name: string, unit: string, qty: number, price: number, total: number }} DisplayLine
 * @typedef {{ lines: DisplayLine[], count: number, total: number, paid: number|null, highlight: number|null,
 *             done: { code: string, total: number, paid: number|null } | null }} DisplayState
 */
export const emptyDisplay = { lines: [], count: 0, total: 0, paid: null, highlight: null, done: null };

const open = () => (typeof BroadcastChannel === 'undefined' ? null : new BroadcastChannel(CHANNEL));

// Cashier side: sends every change, and the whole state again when a display
// (re)opens and says hello. Leaving the cashier page empties the display.
export function useCustomerDisplayFeed(state) {
    const channel = useRef(null);
    const latest = useRef(state);
    latest.current = state;

    useEffect(() => {
        const current = open();
        if (!current) return;
        channel.current = current;
        current.onmessage = (e) => e.data?.type === 'hello' && current.postMessage({ type: 'state', state: latest.current });

        return () => {
            current.postMessage({ type: 'state', state: emptyDisplay });
            current.close();
            channel.current = null;
        };
    }, []);

    const serialized = JSON.stringify(state);
    useEffect(() => {
        channel.current?.postMessage({ type: 'state', state: latest.current });
    }, [serialized]);
}

// Display side: asks for the current cart on open (also after a refresh in the
// middle of a sale), then follows every change.
export function useCustomerDisplay() {
    const [state, setState] = useState(emptyDisplay);

    useEffect(() => {
        const channel = open();
        if (!channel) return;
        channel.onmessage = (e) => e.data?.type === 'state' && setState(e.data.state);
        channel.postMessage({ type: 'hello' });

        return () => channel.close();
    }, []);

    return state;
}

// Opens the display, or brings it forward when it is already open: the window
// name makes the browser reuse it. Drag it to the second monitor and press F11.
export function openCustomerDisplay() {
    window.open('/customer-display', 'warung-customer-display', 'popup=yes,width=1280,height=800');
}

// Banknotes a customer is likely to hand over for $total: the next 5.000,
// 10.000, 50.000 and 100.000 above it, at most three.
export function cashSuggestions(total) {
    if (total <= 0) return [];
    const amounts = [5_000, 10_000, 50_000, 100_000].map((note) => Math.ceil(total / note) * note);

    return [...new Set(amounts)].filter((amount) => amount > total).slice(0, 3);
}
