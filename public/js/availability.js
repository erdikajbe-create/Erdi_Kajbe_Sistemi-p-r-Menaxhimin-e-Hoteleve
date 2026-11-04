const form = document.querySelector('[data-availability-form]');

if (form) {
    const results = document.querySelector('[data-results]');
    const retryButton = document.querySelector('[data-retry]');
    const searchButton = form.querySelector('[data-search-button]');
    let lastSearch = null;

    const clearErrors = () => {
        form.querySelectorAll('[data-field-error]').forEach((element) => {
            element.textContent = '';
        });
    };

    const setState = (message) => {
        results.replaceChildren();
        const status = document.createElement('p');
        status.className = 'availability-message';
        status.textContent = message;
        results.append(status);
    };

    const validate = (values) => {
        const errors = {};
        const today = new Date();
        const todayValue = [
            today.getFullYear(),
            String(today.getMonth() + 1).padStart(2, '0'),
            String(today.getDate()).padStart(2, '0'),
        ].join('-');

        if (!values.check_in) {
            errors.check_in = 'Zgjidhni datën e mbërritjes.';
        } else if (values.check_in < todayValue) {
            errors.check_in = 'Data e mbërritjes nuk mund të jetë në të kaluarën.';
        }

        if (!values.check_out) {
            errors.check_out = 'Zgjidhni datën e largimit.';
        } else if (values.check_in && values.check_out <= values.check_in) {
            errors.check_out = 'Data e largimit duhet të jetë pas datës së mbërritjes.';
        }

        const guests = Number(values.guests);
        if (!Number.isInteger(guests) || guests < 1 || guests > 6) {
            errors.guests = 'Numri i mysafirëve duhet të jetë nga 1 deri në 6.';
        }

        return errors;
    };

    const renderRooms = (rooms) => {
        if (rooms.length === 0) {
            setState('Nuk ka dhoma të lira për këto data');
            return;
        }

        const grid = document.createElement('div');
        grid.className = 'room-grid';
        rooms.forEach((room) => {
            const card = document.createElement('article');
            card.className = 'room-card';

            const heading = document.createElement('h2');
            heading.textContent = `Dhoma ${room.number}`;

            const details = document.createElement('p');
            details.textContent = `${room.room_type_name} · Kati ${room.floor} · Kapaciteti ${room.capacity}`;

            const price = document.createElement('p');
            price.textContent = `${room.base_price} € / natë`;

            const total = document.createElement('p');
            total.textContent = `${room.nights} netë · Gjithsej ${room.total_amount} €`;

            card.append(heading, details, price, total);
            grid.append(card);
        });

        results.replaceChildren(grid);
    };

    const search = async (values) => {
        clearErrors();
        retryButton.hidden = true;
        searchButton.disabled = true;
        searchButton.textContent = 'Duke kërkuar…';
        setState('Duke kërkuar…');

        try {
            const query = new URLSearchParams(values);
            const response = await fetch(`/api/availability?${query}`, {
                headers: { Accept: 'application/json' },
            });
            const payload = await response.json();

            if (response.status === 422) {
                Object.entries(payload.errors || {}).forEach(([field, message]) => {
                    const error = form.querySelector(`[data-field-error="${field}"]`);
                    if (error) {
                        error.textContent = message;
                    }
                });
                setState('Kontrolloni fushat e shënuara.');
                return;
            }

            if (!response.ok || !Array.isArray(payload.data)) {
                throw new Error('Kërkimi dështoi.');
            }

            renderRooms(payload.data);
        } catch {
            setState('Kërkimi dështoi. Provo përsëri.');
            retryButton.hidden = false;
        } finally {
            searchButton.disabled = false;
            searchButton.textContent = 'Kërko';
        }
    };

    form.addEventListener('submit', (event) => {
        event.preventDefault();
        const values = Object.fromEntries(new FormData(form).entries());
        const errors = validate(values);
        clearErrors();

        if (Object.keys(errors).length > 0) {
            Object.entries(errors).forEach(([field, message]) => {
                const error = form.querySelector(`[data-field-error="${field}"]`);
                if (error) {
                    error.textContent = message;
                }
            });
            setState('Kontrolloni fushat e shënuara.');
            retryButton.hidden = true;
            return;
        }

        lastSearch = values;
        search(values);
    });

    retryButton.addEventListener('click', () => {
        if (lastSearch !== null) {
            search(lastSearch);
        }
    });
}
