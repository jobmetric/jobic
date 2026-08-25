import $ from '../../../admin/jquery';

const formField = (label, name, type = 'text', value = '') => `
    <label class="dashboard-action-field">
        <span>${label}</span>
        <input name="${name}" type="${type}" value="${value}" autocomplete="off">
    </label>`;

export const initializeBanking = ($dashboard, actions, charts) => {
    const closeSelects = (except = null) => {
        $dashboard.find('[data-bank-select].is-open').each(function () {
            if (this === except) return;
            $(this).removeClass('is-open').find('[data-bank-select-trigger]').attr('aria-expanded', 'false');
        });
    };

    const closeMenus = (except = null) => {
        $dashboard.find('[data-bank-menu].is-open').each(function () {
            if (this === except) return;
            $(this).removeClass('is-open').find('[data-bank-menu-toggle]').attr('aria-expanded', 'false');
        });
    };

    const selectOption = ($select, $option, announce = true) => {
        const value = String($option.data('value') ?? '');
        const label = String($option.data('label') ?? $option.text()).trim();
        const icon = $option.data('icon');

        $select.find('[data-bank-select-label]').text(label);
        $select.find('[data-bank-select-input]').val(value).trigger('change');
        $select.find('[role="option"]').attr('aria-selected', 'false');
        $option.attr('aria-selected', 'true');
        if (icon) $select.find('[data-bank-select-icon]').attr('src', icon);
        $select.removeClass('is-open is-invalid').find('[data-bank-select-trigger]').attr('aria-expanded', 'false');

        if ($select.closest('.banking-history-card').length || $select.closest('.banking-panel__balance').length) {
            const range = label.includes('10 days') ? 'Day' : label.includes('month') && !label.includes('months') ? 'Month' : label.includes('3') ? 'Month' : 'Year';
            charts.updateForRange(range);
        }
        if (announce) actions.announce(`${label} selected.`);
    };

    $dashboard.on('click.banking', '[data-bank-select-trigger]', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        const $select = $(this).closest('[data-bank-select]');
        const opening = !$select.hasClass('is-open');
        closeSelects($select[0]);
        closeMenus();
        $select.toggleClass('is-open', opening);
        $(this).attr('aria-expanded', String(opening));
    });

    $dashboard.on('keydown.banking', '[data-bank-select-trigger]', function (event) {
        if (!['ArrowDown', 'ArrowUp'].includes(event.key)) return;
        event.preventDefault();
        const $select = $(this).closest('[data-bank-select]').addClass('is-open');
        $(this).attr('aria-expanded', 'true');
        $select.find('[role="option"]')[event.key === 'ArrowDown' ? 'first' : 'last']().trigger('focus');
    });

    $dashboard.on('keydown.banking', '[data-bank-select-options]', function (event) {
        const $options = $(this).find('[role="option"]');
        const index = $options.index(document.activeElement);
        if (event.key === 'Escape') {
            event.preventDefault();
            $(this).closest('[data-bank-select]').removeClass('is-open').find('[data-bank-select-trigger]').attr('aria-expanded', 'false').trigger('focus');
        }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            $options.eq((index + direction + $options.length) % $options.length).trigger('focus');
        }
    });

    $dashboard.on('click.banking', '[data-bank-select-options] [role="option"]', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        selectOption($(this).closest('[data-bank-select]'), $(this));
    });

    $dashboard.on('click.banking', '[data-bank-select-clear]', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        const $select = $(this).closest('[data-bank-select]');
        selectOption($select, $select.find('[role="option"]').first(), false);
        actions.announce('Account selection cleared.');
    });

    $dashboard.on('click.banking', '[data-bank-menu-toggle]', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        const $menu = $(this).closest('[data-bank-menu]');
        const opening = !$menu.hasClass('is-open');
        closeMenus($menu[0]);
        closeSelects();
        $menu.toggleClass('is-open', opening);
        $(this).attr('aria-expanded', String(opening));
    });

    $dashboard.on('keydown.banking', '[data-bank-menu]', function (event) {
        const $items = $(this).find('[role="menuitem"]');
        const index = $items.index(document.activeElement);
        if (event.key === 'Escape') {
            event.preventDefault();
            $(this).removeClass('is-open').find('[data-bank-menu-toggle]').attr('aria-expanded', 'false').trigger('focus');
        }
        if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
            event.preventDefault();
            const direction = event.key === 'ArrowDown' ? 1 : -1;
            $items.eq((index + direction + $items.length) % $items.length).trigger('focus');
        }
    });

    $dashboard.on('click.banking', '[data-bank-action]', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        const action = String($(this).data('bank-action'));
        const $account = $(this).closest('.banking-card-account');
        const number = $account.find('.banking-credit-card__short-number').text().trim() || 'Card';
        closeMenus();

        if (action === 'wallet') {
            actions.openDrawer({
                eyebrow: 'Secure card wallet',
                title: `${number} wallet`,
                body: `<div class="stock-wallet-preview"><span>Available balance</span><strong>${actions.escapeHtml($account.find('.banking-card-account__meta strong').text())}</strong><small>Updated just now</small></div><div class="dashboard-report-list"><p><span>Status</span><strong>Active</strong></p><p><span>Card type</span><strong>Visa</strong></p><p><span>Security</span><strong>Protected</strong></p></div>`,
            });
            return;
        }

        if (action === 'compare') {
            actions.openDialog({ trigger: this, eyebrow: 'Card comparison', title: `Compare ${number}`, icon: 'activity', body: '<div class="stock-compare-preview"><div><span>Current card</span><strong>1.2%</strong><small>Monthly fees</small></div><i>VS</i><div><span>Premium card</span><strong>0.8%</strong><small>Monthly fees</small></div></div>' });
            return;
        }

        const invest = action === 'invest';
        actions.openDialog({
            trigger: this,
            eyebrow: invest ? 'Investment order' : 'Trading opportunity',
            title: invest ? 'Create an investment' : 'Open a trade',
            icon: invest ? 'trending-up' : 'building',
            confirmText: invest ? 'Review investment' : 'Review trade',
            body: `${formField('Amount in USD', 'amount', 'number', '500.00')}${formField('Source card', 'source', 'text', number)}`,
            onConfirm: () => { actions.closeDialog(); actions.announce(`${invest ? 'Investment' : 'Trade'} added for review.`); },
        });
    });

    $dashboard.on('banking:cardchange', (_, $card) => {
        if (!$card?.length) return;
        const balance = String($card.data('balance'));
        const number = String($card.data('number'));
        const $balance = $dashboard.find('[data-bank-balance]').addClass('is-changing');
        window.setTimeout(() => {
            $balance.find('[data-bank-balance-value]').text(balance);
            $balance.find('[data-bank-balance-number]').text(number);
            $balance.removeClass('is-changing');
        }, 170);
    });

    $dashboard.on('click.banking', '[data-bank-contact]', function (event) {
        event.stopImmediatePropagation();
        $(this).addClass('is-selected').siblings().removeClass('is-selected');
        actions.announce(`${$(this).attr('aria-label').replace('Select ', '')} selected.`);
    });

    $dashboard.on('click.banking', '[data-bank-confirm]', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        const $select = $dashboard.find('#quick-transfer-account-trigger').closest('[data-bank-select]');
        if (!$select.find('[data-bank-select-input]').val()) {
            $select.addClass('is-invalid').find('[data-bank-select-trigger]').trigger('focus');
            actions.announce('Select an account before confirming.');
            return;
        }
        $(this).text('Confirmed ✓').addClass('is-complete');
        actions.announce('Transfer account confirmed.');
    });

    $dashboard.on('click.banking', '[data-bank-edit], [data-bank-details]', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        const details = $(this).is('[data-bank-details]');
        actions.openDialog({
            trigger: this,
            eyebrow: details ? 'Customer level' : 'Card settings',
            title: details ? 'Level 3 progress' : 'Edit card information',
            icon: details ? 'activity' : 'edit',
            confirmText: details ? 'Got it' : 'Save changes',
            body: details
                ? '<p class="dashboard-action-copy">You have completed 65% of Level 3. Continue using your account to unlock rewards and higher transfer limits.</p>'
                : `${formField('Display name', 'display_name', 'text', 'Primary credit card')}${formField('Expiration reminder', 'reminder', 'text', '30 days before')}`,
            onConfirm: () => { actions.closeDialog(); actions.announce(details ? 'Progress details closed.' : 'Card information updated.'); },
        });
    });

    $dashboard.on('click.banking', '[data-bank-contacts]', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        actions.openDrawer({ title: 'All Contacts', eyebrow: 'Send money to', body: '<div class="dashboard-contact-picks"><button type="button">BT Betty</button><button type="button">MK Melany</button><button type="button">JD John</button><button type="button">AC Alice</button></div>' });
    });

    $dashboard.on('click.banking', '[data-bank-transaction]', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        const title = $(this).find('.banking-transaction__copy strong').text().trim();
        const amount = $(this).find('.banking-transaction__amount').text().trim();
        actions.openDialog({ trigger: this, eyebrow: 'Transaction details', title, icon: 'credit-card', body: `<div class="stock-detail-preview"><span>Transaction amount</span><strong>${actions.escapeHtml(amount)}</strong><small>${actions.escapeHtml($(this).find('small').text())}</small></div>` });
    });

    $(document).off('click.banking').on('click.banking', () => { closeSelects(); closeMenus(); });
};
