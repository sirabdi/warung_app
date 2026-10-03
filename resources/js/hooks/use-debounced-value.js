import { useEffect, useState } from 'react';

// The value, but only after it stopped changing for `delay` ms — one request
// per pause in typing instead of one per keystroke.
export function useDebouncedValue(value, delay = 300) {
    const [debounced, setDebounced] = useState(value);

    useEffect(() => {
        const timer = setTimeout(() => setDebounced(value), delay);
        return () => clearTimeout(timer);
    }, [value, delay]);

    return debounced;
}
