(function () {
    'use strict';

    document.querySelectorAll('[data-live-search]').forEach((toolbar) => {
        const selector = toolbar.dataset.items;
        const results = document.querySelector('[data-search-results]');
        if (!selector || !results) return;

        const items = Array.from(results.querySelectorAll(selector));
        const input = toolbar.querySelector('[data-search-input]');
        const filter = toolbar.querySelector('[data-search-filter]');
        const counter = toolbar.querySelector('[data-search-count]');
        const empty = results.querySelector('[data-search-empty]');

        const update = () => {
            const query = (input ? input.value : '').trim().toLocaleLowerCase();
            const selectedStatus = filter ? filter.value : '';
            let visible = 0;

            items.forEach((item) => {
                const searchableText = (item.dataset.searchText || item.textContent || '').toLocaleLowerCase();
                const matchesText = query === '' || searchableText.includes(query);
                const matchesStatus = selectedStatus === '' || item.dataset.status === selectedStatus;
                const show = matchesText && matchesStatus;
                item.hidden = !show;
                if (show) visible += 1;
            });

            const singular = toolbar.dataset.countSingular || 'dhomë';
            const plural = toolbar.dataset.countPlural || 'dhoma';
            if (counter) counter.textContent = `${visible} ${visible === 1 ? singular : plural}`;
            if (empty) empty.hidden = visible !== 0;
        };

        input?.addEventListener('input', update);
        filter?.addEventListener('change', update);
        update();
    });
})();
