import $ from '../admin/jquery';

const storageKey = 'jobic-theme';

export const initUtilityTheme = ($root) => {
    const $html = $('html');
    const $toggle = $root.find('[data-utility-dark-toggle]');

    const applyTheme = (theme) => {
        $html.attr('data-theme', theme);
        localStorage.setItem(storageKey, theme);
        $toggle.prop('checked', theme === 'light');
    };

    const stored = localStorage.getItem(storageKey)
        || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    applyTheme(stored);

    $toggle.on('change', function () {
        applyTheme(this.checked ? 'light' : 'dark');
    });

    $toggle.closest('.dark-mode').on('keydown', function (event) {
        if (![' ', 'Enter'].includes(event.key)) return;
        event.preventDefault();
        $toggle.prop('checked', !$toggle.prop('checked')).trigger('change');
    });
};

export const initRoadmap = () => {
    $(() => {
        const $root = $('[data-utility-roadmap]');
        if (!$root.length) return;
        initUtilityTheme($root);

        const setTab = (name, focus = false) => {
            const $tabs = $root.find('[data-utility-tab]');
            const $target = $tabs.filter(`[data-utility-tab="${name}"]`);
            const index = $tabs.index($target);
            $tabs.removeClass('is-active').attr({ 'aria-selected': 'false', tabindex: '-1' });
            $target.addClass('is-active').attr({ 'aria-selected': 'true', tabindex: '0' });
            $root.find('.utility-slider-naver').css('transform', `translateX(${index * 100}%)`);
            $root.find('[data-utility-panel]').prop('hidden', true);
            $root.find(`[data-utility-panel="${name}"]`).prop('hidden', false);
            if (focus) $target.trigger('focus');
            const url = new URL(window.location.href);
            url.searchParams.set('tab', name);
            history.replaceState({}, '', url);
        };

        $root.on('click', '[data-utility-tab]', function () {
            setTab(String($(this).data('utility-tab')));
        });

        $root.on('keydown', '[data-utility-tab]', function (event) {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const $tabs = $root.find('[data-utility-tab]');
            const current = $tabs.index(this);
            let next = current;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = $tabs.length - 1;
            if (event.key === 'ArrowLeft') next = (current - 1 + $tabs.length) % $tabs.length;
            if (event.key === 'ArrowRight') next = (current + 1) % $tabs.length;
            setTab(String($tabs.eq(next).data('utility-tab')), true);
        });

        const updateRoadmap = () => {
            const year = String($root.find('[data-roadmap-year]').val());
            const quarter = String($root.find('[data-roadmap-quarter].is-active').data('roadmap-quarter'));
            const $group = $root.find(`[data-roadmap-group="${year}-${quarter}"]`);
            $root.find('[data-roadmap-group]').prop('hidden', true);
            $group.prop('hidden', false);
            const progress = Number($group.data('year-progress')) || 0;
            $root.find('[data-roadmap-year-label]').text(year);
            $root.find('[data-roadmap-quarter-label]').text(quarter);
            $root.find('[data-roadmap-year-progress]').text(`${progress}%`);
            $root.find('[data-roadmap-year-progress-bar]').val(progress).text(`${progress}%`);
            const url = new URL(window.location.href);
            url.searchParams.set('year', year);
            url.searchParams.set('quarter', quarter);
            history.replaceState({}, '', url);
        };

        $root.on('change', '[data-roadmap-year]', updateRoadmap);
        $root.on('click', '[data-roadmap-quarter]', function () {
            $root.find('[data-roadmap-quarter]').removeClass('is-active').attr('aria-pressed', 'false');
            $(this).addClass('is-active').attr('aria-pressed', 'true');
            updateRoadmap();
        });

        $root.on('click', '.roadmap-collapse-head', function () {
            const $button = $(this);
            const $item = $button.closest('.roadmap-item');
            const $body = $item.find('.roadmap-collapse-body');
            const opening = !$item.hasClass('is-open');
            $item.toggleClass('is-open', opening);
            $button.attr('aria-expanded', String(opening));
            if (opening) {
                $body.prop('hidden', false).stop(true, true).hide().slideDown(240);
            } else {
                $body.stop(true, true).slideUp(200, () => $body.prop('hidden', true));
            }
        });

        $root.on('change', '[data-changelog-filter]', function () {
            const type = String($(this).val());
            $root.find('[data-change-type]').each(function () {
                $(this).toggleClass('is-muted', type !== 'All' && String($(this).data('change-type')) !== type);
            });
            const url = new URL(window.location.href);
            if (type === 'All') url.searchParams.delete('type');
            else url.searchParams.set('type', type);
            history.replaceState({}, '', url);
        });

        const params = new URLSearchParams(window.location.search);
        const year = params.get('year');
        const quarter = params.get('quarter');
        const type = params.get('type');
        if (year && $root.find(`[data-roadmap-year] option[value="${year}"]`).length) $root.find('[data-roadmap-year]').val(year);
        if (quarter && $root.find(`[data-roadmap-quarter="${quarter}"]`).length) {
            $root.find('[data-roadmap-quarter]').removeClass('is-active').attr('aria-pressed', 'false');
            $root.find(`[data-roadmap-quarter="${quarter}"]`).addClass('is-active').attr('aria-pressed', 'true');
        }
        if (type) $root.find('[data-changelog-filter]').val(type).trigger('change');
        updateRoadmap();
        setTab(params.get('tab') === 'changelog' ? 'changelog' : 'roadmap');
        requestAnimationFrame(() => $root.addClass('is-ready'));
    });
};

export const initStatus = () => {
    $(() => {
        const $root = $('[data-utility-status]');
        if (!$root.length) return;
        initUtilityTheme($root);
        requestAnimationFrame(() => $root.addClass('is-ready'));
    });
};
