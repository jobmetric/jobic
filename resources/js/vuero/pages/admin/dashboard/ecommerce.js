import $ from '../../../admin/jquery';

const field = (label, name, type = 'text', value = '') => `<label class="dashboard-action-field"><span>${label}</span><input name="${name}" type="${type}" value="${value}"></label>`;
const clean = (value) => String(value || '').replace(/\s+/g, ' ').trim();

const closePopover = () => $('.ecommerce-popover').remove();

const openWidgetMenu = ($trigger, actions) => {
    closePopover();
    const rect = $trigger.get(0).getBoundingClientRect();
    const $menu = $('<div class="ecommerce-popover" role="menu"><button type="button" data-widget-action="details">View details</button><button type="button" data-widget-action="download">Download report</button><button type="button" data-widget-action="refresh">Refresh data</button></div>');
    $('body').append($menu);
    const width = $menu.outerWidth();
    $menu.css({ top: rect.bottom + 7, left: Math.max(10, Math.min(window.innerWidth - width - 10, rect.right - width)) });
    $menu.data('widget-title', clean($trigger.closest('.ecommerce-widget').find('h2').first().text()));
    $menu.data('actions', actions);
};

const reportBody = `
    <section class="dashboard-report-summary">
        <div><span>Store performance</span><strong>84.6%</strong><small>+6.4% from last period</small></div>
        <div class="dashboard-report-bars" aria-hidden="true">${[38,55,43,72,64,81,68,92,76,88,70,96].map((value) => `<i style="--value:${value}%"></i>`).join('')}</div>
    </section>
    <h3>Ecommerce overview</h3>
    <div class="dashboard-report-list"><p><span>Gross revenue</span><strong>$75,648.43</strong></p><p><span>New orders</span><strong>371</strong></p><p><span>Conversion</span><strong>8.7%</strong></p><p><span>Returning customers</span><strong>2,841</strong></p></div>`;

export const initializeEcommerce = ($dashboard, actions) => {
    const $page = $dashboard.find('[data-ecommerce="ecommerce-1"]');
    if (!$page.length) return;

    window.requestAnimationFrame(() => window.requestAnimationFrame(() => $page.addClass('is-animated')));

    $page.on('click.ecommerce', '[data-ecommerce-report]', function () {
        actions.openDrawer({ eyebrow: 'Store analytics', title: 'Ecommerce report', body: reportBody });
    });

    $page.on('click.ecommerce', '[data-ecommerce-shop]', function () {
        actions.openDialog({
            trigger: this,
            eyebrow: 'Store manager',
            title: 'Manage your store',
            icon: 'briefcase',
            confirmText: 'Save changes',
            body: `<div class="dashboard-action-grid">${field('Store name','store_name','text','Vuero Store')}${field('Notification email','email','email','erik@vuero.io')}</div><label class="dashboard-action-switch"><input type="checkbox" checked><span></span> Accept new orders automatically</label>`,
            onConfirm: () => { actions.closeDialog(); actions.announce('Store settings saved.'); },
        });
    });

    $page.on('click.ecommerce', '[data-ecommerce-widget-menu]', function (event) {
        event.preventDefault();
        event.stopPropagation();
        openWidgetMenu($(this), actions);
    });

    $page.on('change.ecommerce', '[data-order-period]', function () {
        const days = Number($(this).val());
        const $rows = $page.find('[data-order-row]');
        $rows.addClass('is-filtered');
        window.setTimeout(() => {
            $rows.each(function (index) {
                const visible = days !== 7 || index < 2;
                $(this).prop('hidden', !visible).toggleClass('is-filtered', !visible);
                if (visible) window.setTimeout(() => $(this).removeClass('is-filtered'), index * 55);
            });
            $page.find('[data-orders-empty]').prop('hidden', $rows.filter(':not([hidden])').length > 0);
        }, 210);
        actions.announce(`${clean($(this).find(':selected').text())} orders loaded.`);
    });

    $page.on('click.ecommerce', '[data-view-order]', function () {
        const $row = $(this).closest('[data-order-row]');
        const id = $row.data('order-id');
        const customer = $row.data('order-customer');
        const amount = $row.data('order-amount');
        const status = $row.data('order-status');
        actions.openDrawer({
            eyebrow: `Order ${actions.escapeHtml(id)}`,
            title: actions.escapeHtml(customer),
            body: `<div class="dashboard-report-list"><p><span>Order total</span><strong>$${actions.escapeHtml(amount)}</strong></p><p><span>Payment status</span><strong>${actions.escapeHtml(status)}</strong></p><p><span>Fulfilment</span><strong>Processing</strong></p><p><span>Delivery estimate</span><strong>2–4 business days</strong></p></div><button type="button" class="vuero-button is-primary" data-ecommerce-mark-shipped>Mark as shipped</button>`,
        });
    });

    $page.on('click.ecommerce', '[data-order-tracking]', function () {
        const code = clean($(this).text());
        const customer = clean($(this).closest('[data-order-row]').find('.ecommerce-order-customer strong').text());
        actions.openDialog({ trigger: this, eyebrow: 'Shipment tracking', title: code, icon: 'box', body: `<p class="dashboard-action-copy">${actions.escapeHtml(customer)}'s package is in transit and is scheduled for delivery in 2–4 business days.</p><div class="dashboard-feature-pills"><span>Label created</span><span>In transit</span><span>Insured</span></div>` });
    });

    $(document)
        .off('click.ecommercePopover keydown.ecommercePopover')
        .on('click.ecommercePopover', function (event) {
            if ($(event.target).closest('[data-ecommerce-mark-shipped]').length) {
                actions.closeDrawer();
                actions.announce('Order marked as shipped.');
                return;
            }
            const $action = $(event.target).closest('[data-widget-action]');
            if ($action.length) {
                const $menu = $action.closest('.ecommerce-popover');
                const title = $menu.data('widget-title') || 'Widget';
                const action = $action.data('widget-action');
                if (action === 'details') actions.openDrawer({ eyebrow: 'Live store metric', title, body: reportBody });
                if (action === 'download') actions.announce(`${title} report download prepared.`);
                if (action === 'refresh') { $page.removeClass('is-animated'); window.requestAnimationFrame(() => $page.addClass('is-animated')); actions.announce(`${title} data refreshed.`); }
                closePopover();
                return;
            }
            if (!$(event.target).closest('.ecommerce-popover,[data-ecommerce-widget-menu]').length) closePopover();
        })
        .on('keydown.ecommercePopover', (event) => { if (event.key === 'Escape') closePopover(); });
};
