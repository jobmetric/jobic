import $ from '../../../admin/jquery';
import { initFormMultiselects } from './form-layout-runtime';

export const initGridTiles = (selector, options = {}) => {
    $(() => {
        const $root = $(selector);
        if (!$root.length) {
            return;
        }

        initFormMultiselects($root);

        const $filter = $root.find('[data-tile-filter]');
        const $clear = $root.find('[data-tile-filter-clear]');
        const $items = $root.find('[data-tile-item]');
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

                const $singleParent = $(this).parent('.tile.is-parent');
                if ($singleParent.length && $singleParent.children('[data-tile-item]').length === 1) {
                    $singleParent.prop('hidden', !matches);
                }

                if (matches) {
                    visible += 1;
                }
            });

            $clear.prop('hidden', !query);
            $placeholder.prop('hidden', visible !== 0 || !query);
        };

        $filter.on('input', filterItems);
        $clear.on('click', () => $filter.val('').trigger('input').trigger('focus'));

        $root.on('click', '[data-tile-cta]', () => {
            announce(options.ctaMessage ?? 'Create dialog opened.');
        });

        $root.on('click keydown', '[data-tile-file]', function (event) {
            if (event.type === 'keydown' && event.key !== 'Enter' && event.key !== ' ') {
                return;
            }

            if (event.type === 'keydown') {
                event.preventDefault();
            }

            if ($(event.target).closest('[data-dropdown]').length) {
                return;
            }

            announce(`File opened: ${String($(this).data('tile-label'))}.`);
        });

        $root.on('click', '[data-tile-post]', function (event) {
            event.preventDefault();
            announce(`Post opened: ${String($(this).data('tile-label'))}.`);
        });

        $root.on('click', '[data-tile-dropdown-action], [data-grid-dropdown-action]', function () {
            const action = String($(this).data('tile-dropdown-action') ?? $(this).data('grid-dropdown-action'));
            announce(`${action.charAt(0).toUpperCase()}${action.slice(1)} action selected.`);
            $(this).closest('[data-dropdown]').removeClass('is-open')
                .find('[data-dropdown-toggle]').attr('aria-expanded', 'false');
            $(this).closest('[data-dropdown-menu]').prop('hidden', true);
        });

        if (typeof options.initialize === 'function') {
            options.initialize($root, announce, filterItems);
        }

        requestAnimationFrame(() => $root.addClass('is-ready'));
    });
};
