import $ from '../../../admin/jquery';

export const initializeListView = (selector, options = {}) => {
    $(() => {
        const $view = $(selector);
        if (!$view.length) return;

        const $filter = $view.find('[data-layout-filter]');
        const $clear = $view.find('[data-layout-filter-clear]');
        let toastTimer = null;

        const announce = (message) => {
            const $toast = $('[data-ui-toast]');
            if (!$toast.length) return;
            window.clearTimeout(toastTimer);
            $toast.find('[data-ui-toast-message]').text(message);
            $toast.prop('hidden', false);
            requestAnimationFrame(() => $toast.addClass('is-visible'));
            toastTimer = window.setTimeout(() => {
                $toast.removeClass('is-visible');
                window.setTimeout(() => $toast.prop('hidden', true), 220);
            }, 2800);
        };

        const filterItems = () => {
            const query = String($filter.val()).trim().toLocaleLowerCase();
            const $panel = $view.find('[data-layout-panel]:not([hidden])');
            const $items = $panel.find('[data-layout-item]');
            let visible = 0;
            $items.each(function () {
                const matches = !query || String($(this).data('search')).includes(query);
                $(this).prop('hidden', !matches);
                if (matches) visible += 1;
            });
            $clear.prop('hidden', !query);
            $panel.find('[data-layout-placeholder="search"]').prop('hidden', visible !== 0 || !query);
            $panel.find('[data-layout-table]').prop('hidden', visible === 0 && !!query);
            $panel.find('[data-layout-pagination]').prop('hidden', visible <= 5);
        };

        $filter.on('input', filterItems);
        $clear.on('click', () => $filter.val('').trigger('input').trigger('focus'));

        $view.on('click', '[data-layout-tab]', function () {
            const tab = String($(this).data('layout-tab'));
            $view.find('[data-layout-tab]').removeClass('is-active').attr('aria-selected', 'false');
            $(this).addClass('is-active').attr('aria-selected', 'true');
            $view.find('[data-layout-panel]').prop('hidden', true);
            $view.find(`[data-layout-panel="${tab}"]`).prop('hidden', false);
            if (typeof options.onTabChange === 'function') options.onTabChange(tab, $view, announce);
            filterItems();
        });

        $view.on('click', '[data-layout-page]', function () {
            const page = Number($(this).data('layout-page'));
            const $pagination = $(this).closest('[data-layout-pagination]');
            $pagination.find('[aria-current]').removeClass('is-current').removeAttr('aria-current');
            const $target = $pagination.find(`[data-layout-page="${page}"]`).not('.pagination-edge').first();
            if ($target.length) $target.addClass('is-current').attr('aria-current', 'page');
            announce(`Page ${page} selected.`);
        });

        if (typeof options.initialize === 'function') options.initialize($view, announce, filterItems);
        requestAnimationFrame(() => $view.addClass('is-ready'));
    });
};
