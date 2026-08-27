import $ from '../../admin/jquery';

$(() => {
    const $page = $('[data-starter-page]');
    if (!$page.length) return;

    window.requestAnimationFrame(() => $page.addClass('is-ready'));

    $page.closest('[data-admin-shell]').on('click.starter', '.starter-sideblock__link[href="#"]', (event) => {
        event.preventDefault();
    });
});
