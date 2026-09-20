const numberFormat = new Intl.NumberFormat('id-ID');

export const formatRupiah = (n) => `Rp ${numberFormat.format(n || 0)}`;

export const formatNumber = (n) => numberFormat.format(n || 0);

// "12.500" / "Rp12500" -> 12500 ; "" -> ''
export const parseNumber = (s) => {
    const digits = String(s ?? '').replace(/\D/g, '');
    return digits === '' ? '' : Number(digits);
};

export const matches = (name, query) => name.toLowerCase().includes(query.trim().toLowerCase());
