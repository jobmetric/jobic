import $ from '../../../admin/jquery';

const escapeHtml = (value) => $('<div>').text(String(value ?? '')).html();

export const createActionController = ($dashboard) => {
    const $layer = $('[data-dashboard-action-layer]');
    const $dialog = $layer.find('.dashboard-action-dialog');
    const $drawer = $('[data-dashboard-drawer]');
    let confirmHandler = null;
    let lastTrigger = null;
    let toastTimer = null;

    const announce = (message) => {
        const $toast = $('[data-ui-toast]');

        $('[data-dashboard-live-region]').text(message);
        if (!$toast.length) return;

        window.clearTimeout(toastTimer);
        $toast.find('[data-ui-toast-message]').text(message);
        $toast.prop('hidden', false);
        window.requestAnimationFrame(() => $toast.addClass('is-visible'));
        toastTimer = window.setTimeout(() => {
            $toast.removeClass('is-visible');
            window.setTimeout(() => $toast.prop('hidden', true), 220);
        }, 3000);
    };

    const closeDialog = () => {
        $layer.removeClass('is-open').attr('aria-hidden', 'true');
        $('body').removeClass('has-dashboard-dialog');
        confirmHandler = null;
        if (lastTrigger) $(lastTrigger).trigger('focus');
        lastTrigger = null;
    };

    const openDialog = ({ title, eyebrow = 'Quick action', body = '', confirmText = 'Continue', icon = 'activity', trigger = null, onConfirm = null }) => {
        lastTrigger = trigger;
        confirmHandler = onConfirm;
        $layer.find('[data-dashboard-action-title]').text(title);
        $layer.find('[data-dashboard-action-eyebrow]').text(eyebrow);
        $layer.find('[data-dashboard-action-body]').html(body);
        $layer.find('[data-dashboard-action-icon]').html(`<svg class="ui-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><use href="#icon-${escapeHtml(icon)}"></use></svg>`);
        $layer.find('[data-dashboard-action-confirm]').text(confirmText).toggle(Boolean(onConfirm));
        $layer.addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('has-dashboard-dialog');
        window.setTimeout(() => $dialog.trigger('focus'), 180);
    };

    const closeDrawer = () => {
        $drawer.removeClass('is-open').attr('aria-hidden', 'true');
        $('body').removeClass('has-dashboard-drawer');
    };

    const openDrawer = ({ title, eyebrow = 'Live overview', body = '' }) => {
        $drawer.find('[data-dashboard-drawer-title]').text(title);
        $drawer.find('[data-dashboard-drawer-eyebrow]').text(eyebrow);
        $drawer.find('[data-dashboard-drawer-body]').html(body);
        $drawer.addClass('is-open').attr('aria-hidden', 'false');
        $('body').addClass('has-dashboard-drawer');
        window.setTimeout(() => $drawer.trigger('focus'), 180);
    };

    $(document)
        .off('.dashboardActions')
        .on('click.dashboardActions', '[data-dashboard-action-close]', closeDialog)
        .on('click.dashboardActions', '[data-dashboard-drawer-close]', closeDrawer)
        .on('click.dashboardActions', '[data-dashboard-action-confirm]', function () {
            if (typeof confirmHandler === 'function') confirmHandler($(this));
        })
        .on('keydown.dashboardActions', (event) => {
            if (event.key !== 'Escape') return;
            if ($layer.hasClass('is-open')) closeDialog();
            if ($drawer.hasClass('is-open')) closeDrawer();
        });

    return { announce, openDialog, closeDialog, openDrawer, closeDrawer, escapeHtml };
};
