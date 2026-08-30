import $ from '../../admin/jquery';

const themeStorageKey = 'jobic-theme';

$(() => {
    const $root = $('html');
    const $toggle = $('[data-error-dark-toggle]');

    const applyTheme = (theme) => {
        $root.attr('data-theme', theme);
        localStorage.setItem(themeStorageKey, theme);
        $toggle.prop('checked', theme === 'light');
    };

    const storedTheme = localStorage.getItem(themeStorageKey)
        || (window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

    applyTheme($root.attr('data-theme') || storedTheme);

    $toggle.on('change', function handleThemeChange() {
        applyTheme(this.checked ? 'light' : 'dark');
    });

    $toggle.on('keydown', function handleThemeKeydown(event) {
        if (event.key === ' ' || event.key === 'Spacebar') {
            event.preventDefault();
            $(this).trigger('click');
        }
    });

    $('[data-error-back]').on('click', function handleBack() {
        const fallback = $(this).data('fallback');

        if (window.history.length > 1) {
            window.history.back();
            return;
        }

        window.location.href = fallback;
    });
});
