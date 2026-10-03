import { formatNumber, formatRupiah } from '@/lib/format';

// Mirrors App\Domain\Shared\ValueObject\Unit. Quantities from the API are
// whole steps: pieces, or grams/ml for kg/liter (1,52 kg = 1520).
export const UNITS = {
    pcs: { scale: 1, measured: false, label: 'pcs', option: 'pcs — per buah/bungkus' },
    kg: { scale: 1000, measured: true, label: 'kg', option: 'kg — ditimbang' },
    liter: { scale: 1000, measured: true, label: 'liter', option: 'liter — ditakar' },
};

const PRICE_STEP = 100; // weighed goods are rounded to the nearest Rp 100

export const unitOf = (unit) => UNITS[unit] ?? UNITS.pcs;

export const isMeasured = (unit) => unitOf(unit).measured;

const decimal = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3 });
const plainDecimal = new Intl.NumberFormat('id-ID', { maximumFractionDigits: 3, useGrouping: false });

// 1520, 'kg' -> "1,52 kg" ; 12, 'pcs' -> "12"
export function formatQty(qty, unit) {
    const { scale, measured, label } = unitOf(unit);
    return measured ? `${decimal.format((qty || 0) / scale)} ${label}` : formatNumber(qty);
}

// 14000, 'kg' -> "Rp 14.000/kg"
export function formatPrice(price, unit) {
    return isMeasured(unit) ? `${formatRupiah(price)}/${unitOf(unit).label}` : formatRupiah(price);
}

// Same rounding as Unit::charge(): exact for pieces, nearest Rp 100 when weighed.
export function lineTotal(price, qty, unit) {
    const { scale, measured } = unitOf(unit);
    return measured ? Math.round((price * qty) / scale / PRICE_STEP) * PRICE_STEP : price * qty;
}

// The threshold is in display units: 5 means 5 pcs, or 5 kg (Product::isLowStock()).
export const isLowStock = (stock, unit, threshold) => stock <= threshold * unitOf(unit).scale;

// A weighed line counts as one item, pieces one by one (Unit::itemCount()).
export const itemCount = (qty, unit) => (isMeasured(unit) ? 1 : qty);

// "Minyak 10 ribu": how much to weigh for that amount of money.
export function qtyForAmount(amount, price, unit) {
    if (!amount || !price) return '';
    return Math.round((amount * unitOf(unit).scale) / price);
}

// Steps -> text for an input: 1520 -> "1,52"
export const qtyToText = (qty, unit) =>
    qty === '' || qty == null ? '' : plainDecimal.format(qty / unitOf(unit).scale);

// Text from an input -> steps: "1,52" or "1.52" -> 1520 ; "" -> ''
export function parseQty(text, unit) {
    const { scale } = unitOf(unit);
    let s = String(text ?? '').trim();
    if (s === '') return '';
    s = s.includes(',') ? s.replace(/\./g, '').replace(',', '.') : s;
    const value = Number(s.replace(/[^\d.]/g, ''));
    return Number.isFinite(value) && value > 0 ? Math.round(value * scale) : '';
}
