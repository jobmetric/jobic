import $ from '../../admin/jquery';

$(() => {
    const $page = $('[data-profile-settings]');

    if (!$page.length) {
        return;
    }

    window.requestAnimationFrame(() => $page.addClass('is-ready'));

    $page.on('click', '[data-setting-card]', function () {
        $page.find('[data-setting-card]').removeClass('is-active');
        $(this).addClass('is-active');
    });
});
