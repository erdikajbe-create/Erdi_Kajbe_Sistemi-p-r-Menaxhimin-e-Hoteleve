document.addEventListener('DOMContentLoaded', () => {
    const header = document.querySelector('.site-header--home');
    const updateHeader = () => header?.classList.toggle('is-scrolled', window.scrollY > 24);
    updateHeader();
    window.addEventListener('scroll', updateHeader, { passive: true });

    const items = document.querySelectorAll('[data-reveal]');
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries, current) => entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                current.unobserve(entry.target);
            }
        }), { threshold: 0.14 });
        items.forEach((item) => observer.observe(item));
    } else {
        items.forEach((item) => item.classList.add('is-visible'));
    }

    const date = document.querySelector('#home-check-in');
    const departure = document.querySelector('#home-check-out');
    date?.addEventListener('change', () => {
        if (!date.value) return;
        const next = new Date(`${date.value}T12:00:00`);
        next.setDate(next.getDate() + 1);
        const value = `${next.getFullYear()}-${String(next.getMonth() + 1).padStart(2, '0')}-${String(next.getDate()).padStart(2, '0')}`;
        departure.min = value;
        if (departure.value <= date.value) departure.value = value;
    });
});
