import $ from '../../../admin/jquery';
import { initFormMultiselects } from './form-layout-runtime';

const randomValue = () => Math.round(Math.random() * 10);

const buildSparklinePath = (values, width, height) => {
    const max = Math.max(...values, 1);
    const min = Math.min(...values, 0);
    const range = max - min || 1;
    const step = width / Math.max(values.length - 1, 1);

    return values.map((value, index) => {
        const x = index * step;
        const y = height - ((value - min) / range) * (height - 4) - 2;

        return `${index === 0 ? 'M' : 'L'}${x.toFixed(1)},${y.toFixed(1)}`;
    }).join(' ');
};

const initSparklines = ($root) => {
    $root.find('[data-user-grid-sparkline]').each(function () {
        const $svg = $(this);
        if ($svg.data('sparkline-ready')) {
            return;
        }

        const values = Array.from({ length: 20 }, randomValue);
        const width = 100;
        const height = 45;
        const $value = $svg.closest('[data-icon-tab-panel="posts"]').find('[data-user-grid-sparkline-value]');

        const render = () => {
            $svg.empty().append(
                $('<path/>', {
                    fill: 'none',
                    stroke: 'var(--primary)',
                    'stroke-width': 1.6,
                    d: buildSparklinePath(values, width, height),
                }),
            );
            $value.text(String(values[values.length - 1]));
        };

        render();
        const timer = window.setInterval(() => {
            values.shift();
            values.push(randomValue());
            render();
        }, 1000);

        $svg.data('sparkline-ready', true);
        $svg.data('sparkline-timer', timer);
    });
};

const initIconTabs = ($root) => {
    $root.on('click', '[data-icon-tab]', function () {
        const $button = $(this);
        const $item = $button.closest('[data-user-grid-tabbed-item]');
        const tab = String($button.data('icon-tab'));

        $item.find('[data-icon-tab]').removeClass('is-active').attr('aria-pressed', 'false');
        $button.addClass('is-active').attr('aria-pressed', 'true');

        $item.find('[data-icon-tab-panel]').each(function () {
            const isActive = String($(this).data('icon-tab-panel')) === tab;
            $(this).toggleClass('is-active', isActive).prop('hidden', !isActive);
        });
    });
};

const initMainTabs = ($root) => {
    $root.on('click', '[data-user-grid-tab]', function () {
        const tab = String($(this).data('user-grid-tab'));

        $root.find('[data-user-grid-tab-item]').removeClass('is-active');
        $root.find(`[data-user-grid-tab-item="${tab}"]`).addClass('is-active');
        $root.find('[data-user-grid-tab]').attr('aria-selected', 'false');
        $(this).attr('aria-selected', 'true');

        $root.find('[data-user-grid-tab-panel]').each(function () {
            const isActive = String($(this).data('user-grid-tab-panel')) === tab;
            $(this).toggleClass('is-active', isActive).prop('hidden', !isActive);
        });
    });
};

export const initUserGrids = (selector, options = {}) => {
    $(() => {
        const $root = $(selector);
        if (!$root.length) {
            return;
        }

        initFormMultiselects($root);
        initIconTabs($root);
        initMainTabs($root);
        initSparklines($root);

        const $filter = $root.find('[data-user-grid-filter]');
        const $clear = $root.find('[data-user-grid-filter-clear]');
        const $items = $root.find('[data-user-grid-item]');
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

        if ($filter.length) {
            $filter.on('input', filterItems);
            $clear.on('click', () => $filter.val('').trigger('input').trigger('focus'));
        }

        $root.on('click', '[data-user-grid-cta]', () => {
            announce(options.ctaMessage ?? 'Add user dialog opened.');
        });

        $root.on('click', '[data-user-grid-action]', function (event) {
            event.preventDefault();
            const action = String($(this).data('user-grid-action'));
            const label = String($(this).data('user-grid-label') ?? 'user');
            announce(`${action.charAt(0).toUpperCase()}${action.slice(1)} opened for ${label}.`);
        });

        $root.on('click', '[data-grid-dropdown-action], [data-tile-dropdown-action]', function () {
            const action = String($(this).data('grid-dropdown-action') ?? $(this).data('tile-dropdown-action'));
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
