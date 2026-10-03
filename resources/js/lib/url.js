// { page: 2, search: '' } -> "?page=2" (empty values are left out)
export function toQueryString(params) {
    const query = new URLSearchParams(Object.entries(params).filter(([, value]) => value !== '' && value != null));
    return query.size ? `?${query}` : '';
}

// Keep the address bar in step with the list, so a refresh lands on the same page.
export function replaceUrl(path, params) {
    window.history.replaceState({}, '', `${path}${toQueryString(params)}`);
}
