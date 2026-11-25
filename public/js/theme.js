document.addEventListener('DOMContentLoaded', () => {
    const key = 'hotelhub-theme';
    const saved = localStorage.getItem(key);
    if (saved === 'light' || saved === 'dark') document.documentElement.dataset.theme = saved;

    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        const updateLabel = () => {
            const dark = document.documentElement.dataset.theme === 'dark'
                || (!document.documentElement.dataset.theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
            button.setAttribute('aria-pressed', String(dark));
            button.setAttribute('aria-label', dark ? 'Aktivizo temën e ndriçuar' : 'Aktivizo temën e errët');
            button.title = dark ? 'Aktivizo temën e ndriçuar' : 'Aktivizo temën e errët';
        };
        updateLabel();
        button.addEventListener('click', () => {
            const dark = document.documentElement.dataset.theme === 'dark'
                || (!document.documentElement.dataset.theme && window.matchMedia('(prefers-color-scheme: dark)').matches);
            const theme = dark ? 'light' : 'dark';
            document.documentElement.dataset.theme = theme;
            localStorage.setItem(key, theme);
            updateLabel();
        });
    });
});
