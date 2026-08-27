import $ from '../../../admin/jquery';

$(() => {
    const $view = $('[data-list-view-v1]');
    if (!$view.length) return;

    const $filter = $view.find('[data-list-filter]');
    const $clear = $view.find('[data-list-filter-clear]');
    const $items = $view.find('[data-list-item]');
    const $empty = $view.find('[data-list-empty]');
    const $pagination = $view.find('[data-list-pagination]');

    const announce = (message) => {
        const $toast = $('[data-ui-toast]');
        $toast.find('[data-ui-toast-message]').text(message);
        $toast.prop('hidden', false);
        requestAnimationFrame(() => $toast.addClass('is-visible'));
        window.clearTimeout($view.data('toast-timer'));
        $view.data('toast-timer', window.setTimeout(() => {
            $toast.removeClass('is-visible');
            window.setTimeout(() => $toast.prop('hidden', true), 220);
        }, 2600));
    };

    const filterItems = () => {
        const query = String($filter.val()).trim().toLocaleLowerCase();
        let visible = 0;
        $items.each(function () {
            const matches = !query || String($(this).data('search')).includes(query);
            $(this).toggleClass('is-filtered', !matches).prop('hidden', !matches);
            if (matches) visible += 1;
        });
        $view.find('[data-list-count]').text(visible);
        $view.find('[data-list-count-label]').text(visible === 1 ? 'record found' : 'records found');
        $clear.prop('hidden', !query);
        $empty.prop('hidden', visible !== 0);
        $pagination.prop('hidden', visible <= 5);
    };

    $filter.on('input', filterItems);
    $clear.on('click', () => $filter.val('').trigger('input').trigger('focus'));
    $view.on('click', '[data-list-approve]', () => announce('10 users have been approved.'));
    $view.on('click', '[data-list-action]', function () {
        const action = String($(this).data('list-action'));
        const name = String($(this).data('user-name'));
        announce(action === 'remove' ? `${name} removed from the list.` : `${$(this).find('strong').text()} opened for ${name}.`);
        $(this).closest('[data-dropdown]').removeClass('is-open').find('[data-dropdown-toggle]').attr('aria-expanded', 'false');
        $(this).closest('[data-dropdown-menu]').prop('hidden', true);
    });
    $view.on('click', '[data-page]', function () {
        const page = Number($(this).data('page'));
        $pagination.find('[aria-current]').removeClass('is-current').removeAttr('aria-current');
        const $target = $pagination.find(`[data-page="${page}"]`).not('.pagination-edge').first();
        if ($target.length) $target.addClass('is-current').attr('aria-current', 'page');
        announce(`Page ${page} selected.`);
    });

    requestAnimationFrame(() => $view.addClass('is-ready'));
});
