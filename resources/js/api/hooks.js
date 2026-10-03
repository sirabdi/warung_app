import { useMutation, useQuery, useQueryClient } from '@tanstack/react-query';
import { apiFetch } from './client';
import { toast } from '../toast';
import { toQueryString } from '@/lib/url';

export const keys = {
    cashierProducts: ['cashier', 'products'],
    cashierProductPage: (params) => ['cashier', 'products', params],
    products: ['products'],
    productPage: (params) => ['products', params],
    similarProducts: (name, except) => ['products', 'similar', name, except],
    categories: ['categories'],
    categoryOptions: ['categories', 'options'],
    categoryPage: (params) => ['categories', 'page', params],
    stockInHistory: ['stock-in', 'history'],
    stockInHistoryPage: (params) => ['stock-in', 'history', params],
    report: (params) => ['report', params],
};

// Server-side pages: { data: [...], meta: { page, per_page, total, last_page } }.
// The previous page stays on screen while the next one loads.
const pageOptions = (initialData) => ({
    initialData,
    placeholderData: (previous) => previous,
    staleTime: 30_000,
    refetchOnWindowFocus: true,
});

// Shared options: page data comes from Inertia first, then stays fresh on its own.
const listOptions = (initialData) => ({
    initialData,
    staleTime: 30_000,
    refetchOnWindowFocus: true,
});

// Exported so the till can fetch a search result right away on Enter.
export const cashierProductsQuery = (params) => ({
    queryKey: keys.cashierProductPage(params),
    queryFn: ({ signal }) => apiFetch(`/cashier/products${toQueryString(params)}`, { signal }),
    staleTime: 30_000,
});

export function useCashierProducts(params, initialData) {
    return useQuery({ ...cashierProductsQuery(params), ...pageOptions(initialData) });
}

export function useProducts(params, initialData) {
    return useQuery({
        queryKey: keys.productPage(params),
        queryFn: ({ signal }) => apiFetch(`/products${toQueryString(params)}`, { signal }),
        ...pageOptions(initialData),
    });
}

// Products the name being typed may duplicate. Under the products prefix, so a
// save refreshes it. Names under two letters match too much to be useful.
export function useSimilarProducts(name, except) {
    return useQuery({
        queryKey: keys.similarProducts(name, except),
        queryFn: ({ signal }) =>
            apiFetch(`/products/similar${toQueryString({ name, except })}`, { signal }).then((r) => r.data),
        enabled: name.length >= 2,
        placeholderData: (previous) => previous,
        staleTime: 30_000,
    });
}

// Every category at once, for pickers and filters.
export function useCategoryOptions(initialData) {
    return useQuery({
        queryKey: keys.categoryOptions,
        queryFn: ({ signal }) => apiFetch('/categories/options', { signal }).then((r) => r.categories),
        ...listOptions(initialData),
    });
}

export function useCategoryPage(params, initialData) {
    return useQuery({
        queryKey: keys.categoryPage(params),
        queryFn: ({ signal }) => apiFetch(`/categories${toQueryString(params)}`, { signal }),
        ...pageOptions(initialData),
    });
}

export function useStockInHistory(params, initialData) {
    return useQuery({
        queryKey: keys.stockInHistoryPage(params),
        queryFn: ({ signal }) => apiFetch(`/stock-in/history${toQueryString(params)}`, { signal }),
        ...pageOptions(initialData),
    });
}

// params: { date, low_page, history_page }
export function useReport(params, initialData) {
    return useQuery({
        queryKey: keys.report(params),
        queryFn: ({ signal }) => apiFetch(`/report${toQueryString(params)}`, { signal }),
        initialData,
        placeholderData: (previous) => previous, // keep the old day on screen while loading
        staleTime: 15_000,
    });
}

// Anything that changes stock invalidates every list that shows stock. The keys
// are prefixes, so every cached page of a list is refreshed.
function useStockChangingMutation(mutationFn, onDone) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn,
        onSuccess: (result, variables) => {
            toast(result.message);
            queryClient.invalidateQueries({ queryKey: keys.cashierProducts });
            queryClient.invalidateQueries({ queryKey: keys.products });
            queryClient.invalidateQueries({ queryKey: keys.categories }); // product counts
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

// Categories don't touch stock, but product lists show category names.
function useCategoryMutation(mutationFn, onDone) {
    const queryClient = useQueryClient();

    return useMutation({
        mutationFn,
        onSuccess: (result, variables) => {
            toast(result.message);
            queryClient.invalidateQueries({ queryKey: keys.categories });
            queryClient.invalidateQueries({ queryKey: keys.products });
            onDone?.(result, variables);
        },
    });
}

export function useSaveCategory(onDone) {
    return useCategoryMutation(
        ({ id, name }) =>
            id
                ? apiFetch(`/categories/${id}`, { method: 'PUT', body: { name } })
                : apiFetch('/categories', { method: 'POST', body: { name } }),
        onDone,
    );
}

export function useDeleteCategory(onDone) {
    return useCategoryMutation((id) => apiFetch(`/categories/${id}`, { method: 'DELETE' }), onDone);
}
