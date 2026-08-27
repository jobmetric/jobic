import $ from '../../../admin/jquery';

const escape = (value) => $('<div>').text(String(value ?? '')).html();

const initializeCalendar = ($page) => {
    const $calendar = $page.find('[data-widget-calendar]');
    if (!$calendar.length) return;

    let displayed = new Date(2020, 9, 1);
    const todayKey = '2020-10-18';
    const formatter = new Intl.DateTimeFormat('en-US', { month: 'long', year: 'numeric' });

    const render = () => {
        const year = displayed.getFullYear();
        const month = displayed.getMonth();
        const mondayIndex = (new Date(year, month, 1).getDay() + 6) % 7;
        const lastDate = new Date(year, month + 1, 0).getDate();
        const previousLast = new Date(year, month, 0).getDate();
        const cells = [];

        for (let index = 0; index < 35; index += 1) {
            let date = index - mondayIndex + 1;
            let cellMonth = month;
            let cellYear = year;
            let other = false;
            if (date < 1) { date = previousLast + date; cellMonth -= 1; other = true; }
            if (date > lastDate) { date -= lastDate; cellMonth += 1; other = true; }
            if (cellMonth < 0) { cellMonth = 11; cellYear -= 1; }
            if (cellMonth > 11) { cellMonth = 0; cellYear += 1; }
            const key = `${cellYear}-${String(cellMonth + 1).padStart(2, '0')}-${String(date).padStart(2, '0')}`;
            cells.push(`<button type="button" class="${other ? 'is-other' : ''} ${key === todayKey ? 'is-current' : ''}" data-calendar-date="${key}">${date}</button>`);
        }
        $calendar.find('[data-calendar-title]').text(formatter.format(displayed));
        $calendar.find('[data-calendar-grid]').html(cells.join(''));
    };

    $calendar.on('click', '[data-calendar-prev]', () => { displayed = new Date(displayed.getFullYear(), displayed.getMonth() - 1, 1); render(); });
    $calendar.on('click', '[data-calendar-next]', () => { displayed = new Date(displayed.getFullYear(), displayed.getMonth() + 1, 1); render(); });
    $calendar.on('click', '[data-calendar-date]', function () {
        $calendar.find('[data-calendar-date]').removeClass('is-current');
        $(this).addClass('is-current');
    });
    render();
};

export const initializeWidgetInteractions = ($page, actions) => {
    const namespace = `.widgets-${$page.attr('data-dashboard-page')}`;

    const closeWidgetMenus = ({ restoreFocus = false } = {}) => {
        $page.find('[data-widget-menu]').each(function () {
            const $menu = $(this);
            const wasOpen = $menu.find('[data-widget-menu-toggle]').attr('aria-expanded') === 'true';
            $menu.removeClass('is-open');
            $menu.find('[data-widget-menu-toggle]').attr('aria-expanded', 'false');
            $menu.find('[data-widget-menu-dropdown]').prop('hidden', true);
            if (restoreFocus && wasOpen) $menu.find('[data-widget-menu-toggle]').trigger('focus');
        });
    };

    $page.on(`click${namespace}`, '[data-widget-menu-toggle]', function (event) {
        event.stopPropagation();
        const $button = $(this);
        const shouldOpen = $button.attr('aria-expanded') !== 'true';
        closeWidgetMenus();
        const $menu = $button.closest('[data-widget-menu]');
        $menu.toggleClass('is-open', shouldOpen);
        $button.attr('aria-expanded', String(shouldOpen));
        const $dropdown = $button.siblings('[data-widget-menu-dropdown]').prop('hidden', !shouldOpen);
        if (shouldOpen && event.detail === 0) $dropdown.find('[role="menuitem"]').first().trigger('focus');
    });

    $page.on(`click${namespace}`, '[data-widget-menu-dropdown]', (event) => event.stopPropagation());

    $page.on(`keydown${namespace}`, '[data-widget-menu-toggle], [data-widget-menu-dropdown]', function (event) {
        const $menu = $(this).closest('[data-widget-menu]');
        const $items = $menu.find('[role="menuitem"]');
        const current = $items.index(document.activeElement);

        if (event.key === 'Escape') {
            event.preventDefault();
            closeWidgetMenus({ restoreFocus: true });
            return;
        }

        if (!['ArrowDown', 'ArrowUp', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();

        if ($menu.find('[data-widget-menu-toggle]').attr('aria-expanded') !== 'true') {
            $menu.find('[data-widget-menu-toggle]').trigger('click');
            return;
        }

        const next = event.key === 'Home' ? 0
            : event.key === 'End' ? $items.length - 1
                : event.key === 'ArrowUp' ? (current <= 0 ? $items.length - 1 : current - 1)
                    : (current + 1) % $items.length;
        $items.eq(next).trigger('focus');
    });

    $(document).off(`click${namespace}`).on(`click${namespace}`, () => closeWidgetMenus());

    $page.on(`click${namespace}`, '[data-widget-action]', function (event) {
        event.stopPropagation();
        const action = $(this).data('widget-action');
        const $card = $(this).closest('[data-widget-card]');
        closeWidgetMenus();

        if (action === 'reload' || action === 'refresh') {
            $card.addClass('is-refreshing');
            window.setTimeout(() => { $card.removeClass('is-refreshing'); actions.announce('Widget reloaded'); }, 650);
        } else if (action === 'configure') {
            const title = $card.find('h2').first().text() || 'Widget details';
            actions.openDrawer({ title: `Configure ${title}`, eyebrow: 'Widget configuration', body: `<p>Choose how the <strong>${escape(title)}</strong> widget should present its live data.</p><label class="dashboard-action-field"><span>Refresh interval</span><select><option>Real time</option><option>Every 5 minutes</option><option>Every hour</option></select></label>` });
        } else if (action === 'settings' || action === 'details') {
            const title = $card.find('h2').first().text() || 'Widget';
            actions.openDialog({ title: `${title} Settings`, eyebrow: 'Widget settings', body: '<label class="dashboard-action-switch"><input type="checkbox" checked><span></span> Show widget title</label><label class="dashboard-action-switch"><input type="checkbox" checked><span></span> Enable live updates</label>', confirmText: 'Save settings', icon: 'settings', trigger: this, onConfirm: () => { actions.closeDialog(); actions.announce(`${title} settings saved`); } });
        } else if (action === 'remove') {
            const title = $card.find('h2').first().text() || 'Widget';
            actions.openDialog({ title: `Remove ${title}?`, eyebrow: 'Widget action', body: '<p>The widget will be hidden from this demo until the page is refreshed.</p>', confirmText: 'Remove widget', icon: 'trash', trigger: this, onConfirm: () => { actions.closeDialog(); $card.addClass('is-removing'); window.setTimeout(() => $card.prop('hidden', true), 210); actions.announce(`${title} removed`); } });
        }
    });

    $page.on(`click${namespace}`, '[data-widget-tab]', function () {
        const $tabs = $(this).closest('[data-widget-tabs]');
        const name = $tabs.data('widget-tabs');
        const index = Number($(this).data('widget-tab'));
        $tabs.find('[data-widget-tab]').attr('aria-selected', 'false');
        $(this).attr('aria-selected', 'true');
        $page.find(`[data-widget-panel^="${name}:"]`).prop('hidden', true).removeClass('is-entering');
        const $panel = $page.find(`[data-widget-panel="${name}:${index}"]`).prop('hidden', false);
        window.requestAnimationFrame(() => $panel.addClass('is-entering'));
    });

    $page.on(`change${namespace}`, '.widget-todo-row input', function () {
        actions.announce(`${this.value} marked ${this.checked ? 'complete' : 'pending'}`);
    });
    $page.on(`click${namespace}`, '[data-widget-user], [data-widget-topic], [data-widget-file], [data-widget-person], [data-widget-list-action]', function () {
        const label = $(this).data('widget-user') || $(this).data('widget-topic') || $(this).data('widget-file') || $(this).data('widget-person') || $(this).data('widget-list-action');
        actions.announce(`${label} selected`);
    });
    $page.on(`click${namespace}`, '[data-widget-icon-tab], [data-widget-day], [data-widget-social]', function () {
        $(this).siblings('button').removeClass('is-active');
        $(this).addClass('is-active');
    });
    $page.on(`click${namespace}`, '.widget-tags button', function () { $(this).toggleClass('is-active'); });
    $page.on(`click${namespace}`, '[data-widget-notification]', function () { $(this).find('b').text('0'); actions.announce(`${$(this).find('span').text()} notifications marked as read`); });
    $page.on(`submit${namespace}`, '[data-widget-search]', function (event) {
        event.preventDefault();
        const value = $(this).find('input').val().trim();
        $(this).siblings('[data-widget-search-result]').prop('hidden', false).text(value ? `3 results for “${value}”` : 'Type something to search');
    });
    $page.on(`click${namespace}`, '[data-widget-play]', () => { $page.find('[data-widget-lightbox]').prop('hidden', false); $('body').css('overflow', 'hidden'); });
    $page.on(`click${namespace}`, '[data-widget-lightbox-close]', () => { $page.find('[data-widget-lightbox]').prop('hidden', true); $('body').css('overflow', ''); });

    $page.on(`input${namespace}`, '[data-widget-people-search]', function () {
        const query = String($(this).val()).toLowerCase();
        $(this).closest('.widget-avatar-list-card').children('[data-widget-person]').each(function () { $(this).toggle($(this).text().toLowerCase().includes(query)); });
    });
    const updateCart = ($cart) => {
        let total = 0;
        $cart.find('.widget-product').each(function () { total += Number($(this).find('[data-cart-quantity]').text()) * 149; });
        $cart.find('[data-cart-total]').text(`$${total.toFixed(2)}`);
    };
    $page.on(`click${namespace}`, '[data-cart-plus], [data-cart-minus]', function () {
        const $quantity = $(this).siblings('[data-cart-quantity]');
        const next = Math.max(0, Number($quantity.text()) + ($(this).is('[data-cart-plus]') ? 1 : -1));
        $quantity.text(next); updateCart($(this).closest('.widget-cart'));
    });
    $page.on(`click${namespace}`, '[data-widget-checkout]', function () { actions.openDialog({ title: 'Ready to checkout?', eyebrow: 'Shopping cart', body: `<p>Your cart total is <strong>${escape($(this).closest('.widget-cart').find('[data-cart-total]').text())}</strong>.</p>`, confirmText: 'Place order', icon: 'credit-card', trigger: this, onConfirm: () => { actions.closeDialog(); actions.announce('Order placed successfully'); } }); });
    $page.on(`click${namespace}`, '[data-call-action]', function () { const type = $(this).data('call-action'); const $card = $(this).closest('.widget-call-card'); $(this).siblings().removeClass('is-active'); $(this).addClass('is-active'); $card.toggleClass('is-calling', type === 'accept').find('[data-call-title]').text(type === 'accept' ? 'Calling Alice…' : type === 'video' ? 'Starting video…' : 'Opening messages…'); });
    $page.on(`click${namespace}`, '[data-widget-details]', function () { actions.openDrawer({ title: 'Sales Revenue', eyebrow: 'Report details', body: '<p>Revenue is up <strong>5.1%</strong> since last month. The full report is ready for review.</p>' }); });
    $page.on(`click${namespace}`, '[data-widget-add-member]', function () {
        const $tbody = $(this).closest('.widget-member-card').find('[data-widget-members]');
        if ($tbody.find('[data-added-member]').length) { actions.announce('The demo member is already in this table'); return; }
        $tbody.prepend('<tr data-added-member><td><div class="widget-member"><img class="widget-avatar" src="/vuero/images/admin/business/avatars/23.svg" alt="Maya Green" width="38" height="38"><span><strong>Maya Green</strong><small>maya.green@vuero.io</small></span></div></td><td>Aug 2026</td><td>$1,480.00</td><td><span class="widget-skill-stack"><i>JS</i><i>UI</i></span></td><td><span class="widget-rating">★★★★★</span></td><td><span class="widget-status is-pending">Pending</span></td><td></td></tr>');
        actions.announce('Maya Green added to members');
    });

    initializeCalendar($page);
};
