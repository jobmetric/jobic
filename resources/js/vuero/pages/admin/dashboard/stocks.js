import $ from '../../../admin/jquery';

const field = (label, name, type = 'text', value = '') => `
    <label class="dashboard-action-field">
        <span>${label}</span>
        <input name="${name}" type="${type}" value="${value}" autocomplete="off">
    </label>`;

const stockContext = ($element) => {
    const $card = $element.closest('[data-stock-symbol], .stock-list-card');
    const symbol = String($card.data('stock-symbol') || $card.find('[data-stock-symbol]').first().data('stock-symbol') || 'Portfolio');
    const name = $card.find('.stock-identity__copy small').first().text().trim() || symbol;
    return { $card, symbol, name };
};

export const initializeStocks = ($dashboard, actions) => {
    const closeMenus = (except = null) => {
        $dashboard.find('[data-stock-menu].is-open').each(function () {
            if (this === except) return;
            $(this).removeClass('is-open').find('[data-stock-menu-toggle]').attr('aria-expanded', 'false');
        });
    };

    $dashboard.on('click.stocks', '[data-stock-menu-toggle]', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $menu = $(this).closest('[data-stock-menu]');
        const opening = !$menu.hasClass('is-open');
        closeMenus($menu[0]);
        $menu.toggleClass('is-open', opening);
        $(this).attr('aria-expanded', String(opening));
        if (opening && event.detail === 0) $menu.find('[role="menuitem"]').first().trigger('focus');
    });

    $dashboard.on('keydown.stocks', '[data-stock-menu]', function (event) {
        const $items = $(this).find('[role="menuitem"]');
        const index = $items.index(document.activeElement);
        if (event.key === 'Escape') {
            event.preventDefault();
            $(this).removeClass('is-open').find('[data-stock-menu-toggle]').attr('aria-expanded', 'false').trigger('focus');
        }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            $items.eq((index + direction + $items.length) % $items.length).trigger('focus');
        }
    });

    $(document).off('click.stocks').on('click.stocks', () => closeMenus());

    $dashboard.on('click.stocks', '[data-stock-action]', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const action = String($(this).data('stock-action'));
        const { $card, symbol, name } = stockContext($(this));
        closeMenus();

        if (action === 'refresh') {
            const $list = $(this).closest('.stock-list-card');
            $list.removeClass('is-refreshing');
            void $list[0].offsetWidth;
            $list.addClass('is-refreshing');
            window.setTimeout(() => $list.removeClass('is-refreshing'), 720);
            actions.announce(`${$list.find('h2').text().trim()} refreshed.`);
            return;
        }

        if (action === 'wallet') {
            actions.openDrawer({
                eyebrow: 'Stock wallet',
                title: `${symbol} wallet`,
                body: `<div class="stock-wallet-preview"><span>Available balance</span><strong>$24,840.35</strong><small>Updated just now</small></div><div class="dashboard-report-list"><p><span>${actions.escapeHtml(symbol)} holdings</span><strong>128 shares</strong></p><p><span>Average price</span><strong>$76.42</strong></p><p><span>Today</span><strong class="is-positive">+4.8%</strong></p></div>`,
            });
            return;
        }

        if (action === 'compare') {
            actions.openDialog({
                trigger: this,
                eyebrow: 'Stock comparison',
                title: `Compare ${symbol}`,
                icon: 'activity',
                body: `<div class="stock-compare-preview"><div><span>${actions.escapeHtml(symbol)}</span><strong>+14.0%</strong><small>${actions.escapeHtml(name)}</small></div><i>VS</i><div><span>NASDAQ</span><strong>+8.4%</strong><small>Market average</small></div></div>`,
            });
            return;
        }

        const isInvest = action === 'invest';
        actions.openDialog({
            trigger: this,
            eyebrow: isInvest ? 'Investment order' : 'Trading opportunity',
            title: `${isInvest ? 'Invest in' : 'Trade'} ${symbol}`,
            icon: isInvest ? 'trending-up' : 'building',
            confirmText: isInvest ? 'Review investment' : 'Open trade',
            body: `${field('Amount in USD', 'amount', 'number', '500.00')}${field('Order type', 'order_type', 'text', isInvest ? 'Market order' : 'Limit order')}<p class="dashboard-action-hint">Live CMS pricing and permissions can be connected to this component.</p>`,
            onConfirm: () => {
                actions.closeDialog();
                actions.announce(`${symbol} ${isInvest ? 'investment' : 'trade'} added for review.`);
            },
        });
    });

    $dashboard.on('click.stocks', '.stock-category', function () {
        const $category = $(this);
        const name = $category.find('.stock-category__name').text().trim();
        const active = !$category.hasClass('is-active');
        $category.toggleClass('is-active', active).attr('aria-pressed', String(active));
        $category.siblings().removeClass('is-active').attr('aria-pressed', 'false');
        const $lists = $dashboard.find('.stock-list-card').addClass('is-filtering');
        window.setTimeout(() => $lists.removeClass('is-filtering'), 360);
        actions.announce(active ? `${name} stocks selected.` : 'Stock category filter cleared.');
    });

    $dashboard.on('click.stocks', '.stock-list__row', function () {
        const { symbol, name } = stockContext($(this));
        const value = $(this).find('.stock-list__value').text().trim();
        actions.openDialog({
            trigger: this,
            eyebrow: 'Stock performance',
            title: `${symbol} · ${name}`,
            icon: 'trending-up',
            confirmText: 'Add to watchlist',
            body: `<div class="stock-detail-preview"><span>Weekly performance</span><strong>${actions.escapeHtml(value)}</strong><small>Market data refreshed a few seconds ago</small></div>`,
            onConfirm: () => {
                actions.closeDialog();
                actions.announce(`${symbol} added to your watchlist.`);
            },
        });
    });
};
