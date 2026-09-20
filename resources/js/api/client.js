// Thin fetch wrapper for the JSON API. It reuses the session cookie and the
// XSRF token Laravel already sets, so there is no separate auth layer.

export class ApiError extends Error {
    constructor(message, { status, errors = {} } = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }

    // First message per field, ready to render under an input.
    get fieldErrors() {
        return Object.fromEntries(
            Object.entries(this.errors).map(([field, messages]) => [
                field,
                Array.isArray(messages) ? messages[0] : messages,
            ]),
        );
    }
}

function xsrfToken() {
    const match = document.cookie.match(/(^|;\s*)XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[2]) : null;
}

export async function apiFetch(path, { method = 'GET', body, signal } = {}) {
    const token = xsrfToken();

    const response = await fetch(`/api${path}`, {
        method,
        signal,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(body ? { 'Content-Type': 'application/json' } : {}),
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
        },
        body: body ? JSON.stringify(body) : undefined,
    });

    if (response.status === 401 || response.status === 419) {
        // Session expired: back to the login page.
        window.location.href = '/login';
        throw new ApiError('Sesi berakhir, silakan masuk lagi.', { status: response.status });
    }

    const payload = await response.json().catch(() => ({}));

    if (!response.ok) {
        throw new ApiError(payload.message || 'Gagal menyimpan, coba lagi.', {
            status: response.status,
            errors: payload.errors ?? {},
        });
    }

    return payload;
}
