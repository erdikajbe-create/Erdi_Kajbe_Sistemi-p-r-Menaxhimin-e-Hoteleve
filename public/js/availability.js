// Përmirësim progresiv i faqes /availability: pa JavaScript forma GET punon njësoj.
(function () {
    'use strict';

    const form = document.getElementById('availability-form');
    const results = document.getElementById('availability-results');
    if (!form || !results || !window.HotelHubApi) {
        return;
    }

    const button = form.querySelector('button[type="submit"]');
    const buttonText = button.textContent;
    // i njëjti format si money() në PHP: 1.234,50 €
    const money = {
        format(value) {
            const [whole, cents] = Number(value).toFixed(2).split('.');
            return whole.replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + cents + ' €';
        },
    };
    const dateAl = (iso) => iso.split('-').reverse().join('.');

    function el(tag, attrs, children) {
        const node = document.createElement(tag);
        Object.entries(attrs || {}).forEach(([key, value]) => {
            if (key === 'text') node.textContent = value;
            else node.setAttribute(key, value);
        });
        (children || []).forEach((child) => child && node.appendChild(child));
        return node;
    }

    function clearFieldErrors() {
        form.querySelectorAll('.field-error').forEach((node) => node.remove());
        form.querySelectorAll('[aria-invalid]').forEach((node) => node.removeAttribute('aria-invalid'));
    }

    function showFieldErrors(errors) {
        Object.entries(errors).forEach(([field, message]) => {
            const input = form.elements.namedItem(field);
            if (!input) return;
            input.setAttribute('aria-invalid', 'true');
            input.insertAdjacentElement('afterend', el('small', { class: 'field-error', text: message }));
        });
    }

    function setState(state, message) {
        button.disabled = state === 'loading';
        button.textContent = state === 'loading' ? button.dataset.loadingText : buttonText;
        results.replaceChildren();

        if (state === 'empty') {
            results.appendChild(el('div', { class: 'panel empty-state' }, [
                el('h2', { text: 'Nuk ka dhoma të lira për këto data' }),
                el('p', { text: 'Provo data të tjera ose një numër më të vogël personash.' }),
            ]));
        } else if (state === 'error') {
            const retry = el('button', { class: 'button', type: 'button', text: 'Provo përsëri' });
            retry.addEventListener('click', () => form.requestSubmit());
            results.appendChild(el('div', { class: 'panel empty-state empty-state--error', role: 'alert' }, [
                el('p', { text: message }),
                retry,
            ]));
        } else if (state === 'loading') {
            results.appendChild(el('p', { class: 'results-summary', text: 'Duke kërkuar dhoma të lira...' }));
        }
    }

    function renderRooms(data) {
        setState('done');
        const nights = data.nights === 1 ? 'natë' : 'netë';
        results.appendChild(el('p', {
            class: 'results-summary',
            text: `${data.rooms.length} dhoma të lira · ${dateAl(data.check_in)} – ${dateAl(data.check_out)} · ${data.nights} ${nights} · ${data.guests} persona`,
        }));

        const grid = el('div', { class: 'room-grid' });
        data.rooms.forEach((room) => {
            const query = new URLSearchParams({
                room_id: room.id, check_in: data.check_in, check_out: data.check_out, guests: data.guests,
            });
            const price = el('span', { class: 'price-tag', text: money.format(Number(room.price_per_night)) });
            price.appendChild(el('small', { text: '/natë' }));
            const chips = room.amenities.length
                ? el('p', { class: 'chips' }, room.amenities.map((name) => el('span', { class: 'chip', text: name })))
                : null;
            const total = el('p', { class: 'room-card__total', text: 'Totali: ' });
            total.appendChild(el('strong', { text: money.format(Number(room.total)) }));

            grid.appendChild(el('article', { class: 'room-card' }, [
                el('div', { class: 'room-card__heading' }, [
                    el('div', {}, [
                        el('p', { class: 'eyebrow', text: room.room_type }),
                        el('h2', { text: `Dhoma ${room.number}` }),
                    ]),
                    price,
                ]),
                el('p', { class: 'muted', text: `Kati ${room.floor} · deri në ${room.capacity} persona` }),
                chips,
                total,
                el('a', { class: 'button', href: '/reservations/create?' + query, text: 'Rezervo' }),
            ]));
        });
        results.appendChild(grid);
    }

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearFieldErrors();

        const params = Object.fromEntries(new FormData(form));
        // validim vetëm për komoditet; serveri validon gjithmonë përsëri
        const clientErrors = {};
        if (!params.check_in) clientErrors.check_in = 'Zgjidhni datën e mbërritjes.';
        if (!params.check_out) clientErrors.check_out = 'Zgjidhni datën e largimit.';
        else if (params.check_in && params.check_out <= params.check_in) {
            clientErrors.check_out = 'Data e largimit duhet të jetë pas mbërritjes.';
        }
        if (Object.keys(clientErrors).length) {
            showFieldErrors(clientErrors);
            return;
        }

        setState('loading');
        history.replaceState(null, '', '/availability?' + new URLSearchParams(params));
        try {
            const data = await window.HotelHubApi.get('/api/availability', params);
            data.rooms.length ? renderRooms(data) : setState('empty');
        } catch (error) {
            if (error.status === 422) {
                setState('done');
                showFieldErrors(error.errors);
            } else {
                setState('error', 'Kërkimi dështoi. Provo përsëri.');
            }
        }
    });

    // largimi s'mund të jetë para mbërritjes
    form.elements.namedItem('check_in').addEventListener('change', (event) => {
        const checkOut = form.elements.namedItem('check_out');
        checkOut.min = event.target.value;
        if (checkOut.value && checkOut.value <= event.target.value) {
            const next = new Date(event.target.value + 'T00:00:00');
            next.setDate(next.getDate() + 1);
            // jo toISOString(): ajo kthen datën në UTC dhe në UTC+1 jep ditën e mëparshme
            const pad = (n) => String(n).padStart(2, '0');
            checkOut.value = `${next.getFullYear()}-${pad(next.getMonth() + 1)}-${pad(next.getDate())}`;
        }
    });
})();
