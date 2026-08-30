import $ from '../../../admin/jquery';

const announce = (message) => {
    const $toast = $('[data-ui-toast]');
    if (!$toast.length) {
        return;
    }

    $toast.find('[data-ui-toast-message]').text(message);
    $toast.prop('hidden', false);
    requestAnimationFrame(() => $toast.addClass('is-visible'));
    window.setTimeout(() => {
        $toast.removeClass('is-visible');
        window.setTimeout(() => $toast.prop('hidden', true), 220);
    }, 2800);
};

const initStuckHeader = ($root) => {
    const $header = $root.find('[data-profile-form-head]');
    if (!$header.length || $header.data('stuck-init')) {
        return;
    }

    $header.data('stuck-init', true);
    const headerEl = $header[0];
    const sentinel = document.createElement('div');
    sentinel.className = 'form-header-sentinel';
    sentinel.setAttribute('aria-hidden', 'true');
    headerEl.before(sentinel);

    const syncStuck = (isStuck) => $header.toggleClass('is-stuck', isStuck);

    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver(
            ([entry]) => syncStuck(!entry.isIntersecting),
            { threshold: 0 },
        );
        observer.observe(sentinel);
    } else {
        const updateStuck = () => syncStuck(window.scrollY > 30);
        $(window).on('scroll', updateStuck);
        $(window).on('resize', updateStuck);
        updateStuck();
    }
};

export const initProfileLayout = (selector, options = {}) => {
    $(() => {
        const $root = $(selector);
        if (!$root.length) {
            return;
        }

        initStuckHeader($root);

        $root.on('click', '[data-profile-save]', (event) => {
            event.preventDefault();
            const $button = $(event.currentTarget);
            $button.addClass('is-loading').prop('disabled', true);
            window.setTimeout(() => {
                $button.removeClass('is-loading').prop('disabled', false);
                announce(options.saveMessage ?? 'Your changes have been successfully saved!');
            }, 700);
        });

        $root.on('click', '[data-profile-toggle-panel]', function () {
            const target = $(this).data('profile-toggle-panel');
            $root.find(`[data-profile-panel="${target}"]`).prop('hidden', false);
            $root.find(`[data-profile-panel-list="${target}"]`).prop('hidden', true);
        });

        $root.on('click', '[data-profile-cancel-panel]', function () {
            const target = $(this).data('profile-cancel-panel');
            $root.find(`[data-profile-panel="${target}"]`).prop('hidden', true);
            $root.find(`[data-profile-panel-list="${target}"]`).prop('hidden', false);
        });

        $root.on('click', '[data-profile-upload-toggle]', function () {
            $root.find('[data-profile-upload-view]').prop('hidden', (_, index, elements) => index !== $(this).data('profile-upload-toggle'));
        });

        $root.on('click', '[data-search-tab]', function (event) {
            event.preventDefault();
            const tab = String($(this).data('search-tab'));
            $root.find('[data-search-tab]').closest('li').removeClass('is-active');
            $(this).closest('li').addClass('is-active');
            $root.find('[data-search-tab-panel]').removeClass('is-active');
            $root.find(`[data-search-tab-panel="${tab}"]`).addClass('is-active');
        });

        $root.on('click', '[data-timeline-load-more]', function (event) {
            event.preventDefault();
            const $button = $(this);
            $button.addClass('is-loading').prop('disabled', true);
            window.setTimeout(() => {
                $button.removeClass('is-loading').prop('disabled', false);
                announce('More notifications loaded.');
            }, 700);
        });

        $root.on('click', '[data-profile-view-more]', function (event) {
            event.preventDefault();
            const target = String($(this).data('profile-view-more') || '');
            const $section = $(this).closest('.profile-card-section');

            if (target) {
                $section.find('[data-profile-more-item]').prop('hidden', false);
                $(this).closest('.more-button').prop('hidden', true);
                return;
            }

            $section.find('[data-profile-more-item]').prop('hidden', false);
            $(this).closest('.more-button, .profile-more-button').prop('hidden', true);
        });

        $root.on('change', '[data-profile-two-factor]', function () {
            const enabled = $(this).is(':checked');
            $root.find('[data-profile-two-factor-phone]').prop('hidden', !enabled);
        });

        $root.find('[data-profile-two-factor]').each(function () {
            const enabled = $(this).is(':checked');
            $root.find('[data-profile-two-factor-phone]').prop('hidden', !enabled);
        });

        $root.on('change', '[data-profile-network-switch]', function () {
            const enabled = $(this).is(':checked');
            announce(enabled
                ? 'Network notifications enabled.'
                : 'Network notifications disabled.');
        });

        requestAnimationFrame(() => $root.addClass('is-ready'));
    });
};
