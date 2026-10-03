const numberFormat = new Intl.NumberFormat('id-ID');

export const formatRupiah = (n) => `Rp ${numberFormat.format(n || 0)}`;

export const formatNumber = (n) => numberFormat.format(n || 0);

// "12.500" / "Rp12500" -> 12500 ; "" -> ''
export const parseNumber = (s) => {
    const digits = String(s ?? '').replace(/\D/g, '');
    return digits === '' ? '' : Number(digits);
};

export const matches = (name, query) => name.toLowerCase().includes(query.trim().toLowerCase());

const dateFormat = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });
const dateTimeFormat = new Intl.DateTimeFormat('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });

// ISO string from the server -> "3 Oktober 2026" / "3 Okt 2026, 14.05"
export const formatDate = (iso) => (iso ? dateFormat.format(new Date(iso)) : '');

export const formatDateTime = (iso) => (iso ? dateTimeFormat.format(new Date(iso)) : '');

// Whole days from now until an ISO date; 0 once it has passed.
export const daysUntil = (iso) => Math.max(0, Math.ceil((new Date(iso) - Date.now()) / 86_400_000));
