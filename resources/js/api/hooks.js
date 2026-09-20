import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiFetch } from './client';
import { toast } from '../toast';

export const keys = {
    cashierProducts: ['cashier', 'products'],
    products: ['products'],
    stockInHistory: ['stock-in', 'history'],
    report: (date) => ['report', date],
};

// Shared options: page data comes from Inertia first, then stays fresh on its own.
const listOptions = (initialData) => ({
    initialData,
    staleTime: 30_000,
    refetchOnWindowFocus: true,
});

export function useCashierProducts(initialData) {
    return useQuery({
        queryKey: keys.cashierProducts,
        queryFn: ({ signal }) => apiFetch('/cashier/products', { signal }).then((r) => r.products),
        ...listOptions(initialData),
    });
}

export function useProducts(initialData) {
    return useQuery({
        queryKey: keys.products,
        queryFn: ({ signal }) => apiFetch('/products', { signal }).then((r) => r.products),
        ...listOptions(initialData),
    });
}

export function useStockInHistory(initialData) {
    return useQuery({
        queryKey: keys.stockInHistory,
        queryFn: ({ signal }) => apiFetch('/stock-in/history', { signal }).then((r) => r.history),
        ...listOptions(initialData),
    });
}

export function useReport(date, initialData) {
    return useQuery({
        queryKey: keys.report(date),
        queryFn: ({ signal }) => apiFetch(`/report?date=${date}`, { signal }),
        initialData,
        placeholderData: (previous) => previous, // keep the old day on screen while loading
        staleTime: 15_000,
    });
}

// Anything that changes stock invalidates every list that shows stock.
function useStockChangingMutation(mutationFn, onDone) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn,
        onSuccess: (result, variables) => {
            toast(result.message);
            queryClient.invalidateQueries({ queryKey: keys.cashierProducts });
            queryClient.invalidateQueries({ queryKey: keys.products });
            queryClient.invalidateQueries({ queryKey: keys.stockInHistory });
            queryClient.invalidateQueries({ queryKey: ['report'] });
            onDone?.(result, variables);
        },
    });
}

export function useRecordSale(onDone) {
    return useStockChangingMutation((items) => apiFetch('/sales', { method: 'POST', body: { items } }), onDone);
}

export function useRecordStockIn(onDone) {
    return useStockChangingMutation(
        ({ product_id, qty }) => apiFetch('/stock-in', { method: 'POST', body: { product_id, qty } }),
        onDone,
    );
}

export function useSaveProduct(onDone) {
    return useStockChangingMutation(
        ({ id, ...data }) =>
            id
                ? apiFetch(`/products/${id}`, { method: 'PUT', body: data })
                : apiFetch('/products', { method: 'POST', body: data }),
        onDone,
    );
}
