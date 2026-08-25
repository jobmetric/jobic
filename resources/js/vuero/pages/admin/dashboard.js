import $ from '../../admin/jquery';

$(() => {
    const $dashboard = $('[data-dashboard]');

    if (!$dashboard.length) {
        return;
    }

    window.requestAnimationFrame(() => $dashboard.addClass('is-ready'));
});
