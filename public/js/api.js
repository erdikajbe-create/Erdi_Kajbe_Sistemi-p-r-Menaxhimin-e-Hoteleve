// Thirrjet fetch drejt /api në një vend: lexon formatin e njësuar të gabimeve
// {success:false, error, message, errors?} dhe dërgon token-in CSRF në header.
(function () {
    'use strict';

    const csrfMeta = document.querySelector('meta[name="csrf-token"]');

    class ApiError extends Error {
        constructor(status, body) {
            super((body && body.message) || 'Gabim i serverit');
            this.status = status;
            this.code = body && body.error;
            this.errors = (body && body.errors) || {};
        }
    }

    async function request(method, url, data) {
        const options = {
            method,
            credentials: 'same-origin',
            headers: { Accept: 'application/json' },
        };
        if (method !== 'GET') {
            options.headers['Content-Type'] = 'application/json';
            options.headers['X-CSRF-Token'] = csrfMeta ? csrfMeta.content : '';
            options.body = JSON.stringify(data || {});
        }

        const response = await fetch(url, options);
        const body = response.status === 204 ? null : await response.json().catch(() => null);
        if (!response.ok || (body && body.success === false)) {
            throw new ApiError(response.status, body);
        }

        return body ? body.data : null;
    }

    window.HotelHubApi = {
        ApiError,
        get: (url, params) => request('GET', params ? url + '?' + new URLSearchParams(params) : url),
        post: (url, data) => request('POST', url, data),
    };
})();
