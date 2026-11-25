// Ndihma të vogla për format: butoni çaktivizohet pas dërgimit (pa dërgim të dyfishtë)
// dhe fushat e mysafirit të ri fshihen kur zgjidhet një mysafir ekzistues.
(function () {
    'use strict';

    document.querySelectorAll('form[data-submit-once]').forEach((form) => {
        form.addEventListener('submit', () => {
            const button = form.querySelector('button[type="submit"]');
            if (button) {
                button.disabled = true;
                if (button.dataset.loadingText) button.textContent = button.dataset.loadingText;
            }
        });
    });

    const guestSelect = document.getElementById('guest_id');
    const newGuest = document.querySelector('[data-new-guest]');
    if (guestSelect && newGuest) {
        const toggle = () => { newGuest.hidden = guestSelect.value !== 'new'; };
        guestSelect.addEventListener('change', toggle);
        toggle();
    }

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (!window.confirm(form.dataset.confirm)) event.preventDefault();
        });
    });
})();
