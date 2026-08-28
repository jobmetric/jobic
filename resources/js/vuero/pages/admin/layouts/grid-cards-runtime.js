import $ from '../../../admin/jquery';
import { initFormMultiselects } from './form-layout-runtime';

export const initGridCards = (selector, options = {}) => {
    $(() => {
        const $root = $(selector);
        if (!$root.length) {
            return;
        }

        initFormMultiselects($root);

        const $filter = $root.find('[data-grid-filter]');
        const $clear = $root.find('[data-grid-filter-clear]');
        const $items = $root.find('[data-grid-item]');
        const $placeholder = $root.find('[data-layout-placeholder="search"]');
        let toastTimer = null;

        const announce = (message) => {
            const $toast = $('[data-ui-toast]');
            if (!$toast.length) {
                return;
            }

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
            let visible = 0;

            $items.each(function () {
                const matches = !query || String($(this).data('search')).includes(query);
                $(this).prop('hidden', !matches);
                if (matches) {
                    visible += 1;
                }
            });

            $clear.prop('hidden', !query);
            $placeholder.prop('hidden', visible !== 0 || !query);
        };

        $filter.on('input', filterItems);
        $clear.on('click', () => $filter.val('').trigger('input').trigger('focus'));

        $root.on('click', '[data-grid-cta]', () => {
            announce(options.ctaMessage ?? 'Create dialog opened.');
        });

        $root.on('click', '[data-grid-action]', function (event) {
            event.preventDefault();
            const action = String($(this).data('grid-action'));
            const label = String($(this).data('grid-label') ?? 'item');
            announce(`${action.charAt(0).toUpperCase()}${action.slice(1)} opened for ${label}.`);
        });

        $root.on('click', '[data-grid-post]', function (event) {
            event.preventDefault();
            announce(`Post opened: ${String($(this).data('grid-label'))}.`);
        });

        $root.on('click', '[data-grid-dropdown-action]', function () {
            const action = String($(this).data('grid-dropdown-action'));
            announce(`${action.charAt(0).toUpperCase()}${action.slice(1)} action selected.`);
            $(this).closest('[data-dropdown]').removeClass('is-open')
                .find('[data-dropdown-toggle]').attr('aria-expanded', 'false');
            $(this).closest('[data-dropdown-menu]').prop('hidden', true);
        });

        $root.on('change', '[data-grid-lock]', function () {
            const locked = $(this).is(':checked');
            announce(locked ? 'Project locked.' : 'Project unlocked.');
        });

        if (typeof options.initialize === 'function') {
            options.initialize($root, announce, filterItems);
        }

        requestAnimationFrame(() => $root.addClass('is-ready'));
    });
};
