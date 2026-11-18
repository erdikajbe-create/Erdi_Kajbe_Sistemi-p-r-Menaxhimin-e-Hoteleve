document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('#reservation-search');
    if (!form) return;
    const input = form.querySelector('[name="q"]');
    const clear = form.querySelector('.clear-search');
    const status = form.querySelector('.search-status');
    const results = document.querySelector('#reservation-results');
    let timer;
    let request;
    const escapeHtml = (value) => String(value).replace(/[&<>"']/g, (char) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char]);
    const highlight = (value, term) => {
        const safe = escapeHtml(value);
        if (!term) return safe;
        const regex = new RegExp(`(${term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'ig');
        return safe.replace(regex, '<mark>$1</mark>');
    };
    const render = (rows, term) => {
        if (!rows.length) {
            results.innerHTML = `<div class="empty-state"><h2>Nuk u gjet asnjë rezervim për «${escapeHtml(term)}»</h2><p>Ndryshoni kërkimin ose filtrat dhe provoni sërish.</p></div>`;
            return;
        }
        const staff = form.dataset.staff === '1';
        const body = rows.map((row) => `<tr><td data-label="Kodi"><a href="/reservations/${row.id}"><strong>${highlight(row.code, term)}</strong></a></td>${staff ? `<td data-label="Mysafiri">${highlight(row.guest_name || row.guest.name, term)}</td>` : ''}<td data-label="Dhoma">${highlight(row.room.number, term)} · ${escapeHtml(row.room.type)}</td><td data-label="Datat">${escapeHtml(row.check_in)} – ${escapeHtml(row.check_out)}</td><td data-label="Persona">${row.adults + row.children}</td><td data-label="Totali">${escapeHtml(row.total_amount)} €</td><td data-label="Statusi"><span class="status-badge status-badge--${escapeHtml(row.status)}">${escapeHtml(row.status)}</span></td></tr>`).join('');
        results.innerHTML = `<div class="table-wrap"><table class="responsive-table"><thead><tr><th>Kodi</th>${staff ? '<th>Mysafiri</th>' : ''}<th>Dhoma</th><th>Datat</th><th>Persona</th><th>Totali i netëve</th><th>Statusi</th></tr></thead><tbody>${body}</tbody></table></div>`;
    };
    const search = async () => {
        if (request) request.abort();
        request = new AbortController();
        status.textContent = 'Duke kërkuar…';
        results.setAttribute('aria-busy', 'true');
        const query = new URLSearchParams(new FormData(form));
        history.replaceState(null, '', `/reservations?${query.toString()}`);
        try {
            const response = await fetch(`${form.dataset.api}?${query}`, { headers: { Accept: 'application/json' }, signal: request.signal });
            if (!response.ok) throw new Error('Kërkimi dështoi.');
            const payload = await response.json();
            render(payload.data || [], input.value.trim());
            status.textContent = `${(payload.data || []).length} rezervime u gjetën.`;
        } catch (error) {
            if (error.name !== 'AbortError') status.textContent = 'Kërkimi dështoi. Shtypni Enter për ta provuar sërish.';
        } finally {
            results.removeAttribute('aria-busy');
        }
    };
    input.addEventListener('input', () => {
        clear.hidden = input.value === '';
        clearTimeout(timer);
        timer = setTimeout(search, 300);
    });
    clear.addEventListener('click', () => { input.value = ''; clear.hidden = true; search(); input.focus(); });
    form.querySelectorAll('select, input[type="date"]').forEach((control) => control.addEventListener('change', search));
    form.addEventListener('submit', (event) => { event.preventDefault(); clearTimeout(timer); search(); });
});
