import $ from '../../../admin/jquery';

export const createLayoutPageRuntime = () => {
    let toastTimer = null;

    const announce = (message) => {
        const $toast = $('[data-ui-toast]');
        if (!$toast.length) return;
        window.clearTimeout(toastTimer);
        $toast.find('[data-ui-toast-message]').text(message);
        $toast.prop('hidden', false);
        requestAnimationFrame(() => $toast.addClass('is-visible'));
        toastTimer = window.setTimeout(() => {
            $toast.removeClass('is-visible');
            window.setTimeout(() => $toast.prop('hidden', true), 220);
        }, 2800);
    };

    const sleep = (ms = 650) => new Promise((resolve) => window.setTimeout(resolve, ms));

    const setLoading = ($button, isLoading) => {
        if (!$button.length) return;
        $button.toggleClass('is-loading', isLoading).prop('disabled', isLoading);
        if (isLoading) {
            $button.data('layout-original-text', $button.text().trim());
            $button.text('Please wait...');
            return;
        }
        const original = $button.data('layout-original-text');
        if (original) $button.text(original);
    };

    return { announce, sleep, setLoading };
};

export const initCreditCard = ($root) => {
    const $card = $root.find('[data-credit-card]');
    if (!$card.length) return;

    const $number = $root.find('[data-credit-card-number]');
    const $name = $root.find('[data-credit-card-name]');
    const $nameBack = $root.find('[data-credit-card-name-back]');
    const $expiry = $root.find('[data-credit-card-expiry]');
    const $cvc = $root.find('[data-credit-card-cvc]');
    const $logo = $root.find('[data-credit-card-logo]');
    const $icon = $root.find('[data-credit-card-icon]');

    const colors = {
        visa: 'lime',
        mastercard: 'lightblue',
        amex: 'green',
        discover: 'purple',
        diners: 'orange',
        jcb: 'red',
        maestro: 'yellow',
        unionpay: 'cyan',
        unknown: 'grey',
    };

    const detectBrand = (digits) => {
        if (/^3[47]/.test(digits)) return 'amex';
        if (/^4/.test(digits)) return 'visa';
        if (/^(5[1-5]|2[2-7])/.test(digits)) return 'mastercard';
        if (/^6(?:011|5)/.test(digits)) return 'discover';
        if (/^3(?:0[0-5]|[689])/.test(digits)) return 'diners';
        if (/^35/.test(digits)) return 'jcb';
        if (/^(5[0678]|6304|67)/.test(digits)) return 'maestro';
        if (/^62/.test(digits)) return 'unionpay';
        return 'unknown';
    };

    const setColor = (brand) => {
        const color = colors[brand] || colors.unknown;
        $card.attr('data-color', color);
        $card.find('.lightcolor').attr('class', `lightcolor ${color}`);
        $card.find('.darkcolor').attr('class', `darkcolor ${color}dark`);
    };

    const flip = (flipped) => {
        $card.toggleClass('is-flipped', flipped);
    };

    $card.on('click keydown', function (event) {
        if (event.type === 'keydown' && event.key !== ' ' && event.key !== 'Enter') return;
        if (event.type === 'keydown') event.preventDefault();
        flip(!$card.hasClass('is-flipped'));
    });

    $root.on('input', '[data-credit-card-name-input]', function () {
        const value = String($(this).val() || '').toUpperCase() || 'JOHN DOE';
        $name.text(value);
        $nameBack.text(value);
    });

    $root.on('input', '[data-credit-card-number-input]', function () {
        const digits = String($(this).val() || '').replace(/\D/g, '').slice(0, 16);
        const brand = detectBrand(digits);
        setColor(brand);
        const grouped = digits.replace(/(\d{4})(?=\d)/g, '$1 ').trim() || '•••• •••• •••• ••••';
        $number.text(grouped);
        $(this).val(grouped);
        $icon.empty();
    });

    $root.on('input', '[data-credit-card-expiry-input]', function () {
        const digits = String($(this).val() || '').replace(/\D/g, '').slice(0, 4);
        const formatted = digits.length > 2 ? `${digits.slice(0, 2)} / ${digits.slice(2)}` : digits;
        $expiry.text(formatted || '••/••');
        $(this).val(formatted);
    });

    $root.on('input', '[data-credit-card-cvc-input]', function () {
        const value = String($(this).val() || '').replace(/\D/g, '').slice(0, 3);
        $cvc.text(value || '•••');
        $(this).val(value);
    });

    $root.on('focus', '[data-credit-card-name-input], [data-credit-card-number-input], [data-credit-card-expiry-input]', () => flip(false));
    $root.on('focus', '[data-credit-card-cvc-input]', () => flip(true));

    return { flip, setColor };
};

export const initSaasBilling = (selector) => {
    $(() => {
        const $root = $(selector);
        if (!$root.length) return;

        const { announce } = createLayoutPageRuntime();
        const creditCard = initCreditCard($root);
        const testCards = [
            '4000056655665556',
            '5200828282828210',
            '371449635398431',
            '6011000990139424',
            '30569309025904',
            '3566002020360505',
            '6200000000000005',
            '6759649826438453',
        ];

        const updatePlan = (planId) => {
            $root.find('[data-plan-panel]').prop('hidden', true);
            $root.find(`[data-plan-panel="${planId}"]`).prop('hidden', false);
            $root.find('[data-plan-option]').each(function () {
                const isActive = String($(this).find('input').val()) === planId;
                $(this).toggleClass('is-active', isActive);
            });
        };

        $root.on('change', '[data-plan-option] input', function () {
            updatePlan(String($(this).val()));
        });

        $root.on('click keydown', '[data-billing-randomize]', function (event) {
            if (event.type === 'keydown' && event.key !== ' ' && event.key !== 'Enter') return;
            if (event.type === 'keydown') event.preventDefault();
            const current = String($root.find('[data-credit-card-number-input]').val()).replace(/\s/g, '');
            let next = testCards[Math.floor(Math.random() * testCards.length)];
            if (next === current) {
                next = testCards[(testCards.indexOf(next) + 1) % testCards.length];
            }
            $root.find('[data-credit-card-number-input]').val(next).trigger('input');
            announce('Test card number applied.');
        });

        $root.on('click', '[data-billing-add-seat]', () => announce('Add seat dialog opened.'));
        $root.on('click', '[data-billing-save]', (event) => {
            event.preventDefault();
            announce('Payment method saved.');
        });

        updatePlan(String($root.find('[data-plan-option] input:checked').val() || 'starter'));
        if (creditCard) creditCard.setColor('unknown');
        requestAnimationFrame(() => $root.addClass('is-ready'));
    });
};

export const initActionPage = (selector, options = {}) => {
    $(() => {
        const $root = $(selector);
        if (!$root.length) return;

        const { announce, sleep, setLoading } = createLayoutPageRuntime();

        $root.on('click', '[data-action-decline]', () => announce('Invitation declined.'));

        $root.on('click', '[data-action-accept]', async function () {
            const $button = $(this);
            setLoading($button, true);
            announce(options.acceptMessage || 'Invitation accepted.');
            await sleep();
            setLoading($button, false);
        });

        if (options.skipSelector) {
            $root.on('click', options.skipSelector, () => announce(options.skipMessage || 'Skipped.'));
        }

        if (options.upgradeSelector) {
            $root.on('click', options.upgradeSelector, () => announce(options.upgradeMessage || 'Upgrade started.'));
        }

        requestAnimationFrame(() => $root.addClass('is-ready'));
    });
};

export const initConfirmAccount = (selector) => {
    $(() => {
        const $root = $(selector);
        if (!$root.length) return;

        const { announce, sleep, setLoading } = createLayoutPageRuntime();

        $root.on('click', '[data-confirm-account]', async function () {
            const $button = $(this);
            const $box = $root.find('[data-confirm-box]');
            setLoading($button, true);
            $box.addClass('is-loading');
            announce('Your account is confirmed Erik !');
            await sleep(900);
            setLoading($button, false);
            $box.removeClass('is-loading');
        });

        requestAnimationFrame(() => $root.addClass('is-ready'));
    });
};

export const initInvoice = (selector) => {
    $(() => {
        const $root = $(selector);
        if (!$root.length) return;

        const { announce } = createLayoutPageRuntime();
        const usd = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
        const vatRate = 0.1;

        $root.on('mouseenter focusin', '.invoice-user-popover', function () {
            $(this).addClass('is-active');
        });
        $root.on('mouseleave focusout', '.invoice-user-popover', function (event) {
            if (event.type === 'focusout' && this.contains(event.relatedTarget)) return;
            $(this).removeClass('is-active');
        });

        const recalculate = () => {
            let subtotal = 0;
            $root.find('[data-invoice-row]').each(function () {
                const quantity = Number($(this).find('[data-invoice-quantity]').val()) || 0;
                const rate = Number($(this).data('rate')) || 0;
                const rowTotal = quantity * rate;
                subtotal += rowTotal;
                $(this).find('[data-invoice-subtotal]').text(usd.format(rowTotal));
            });

            const taxes = subtotal * vatRate;
            const total = subtotal + taxes;
            $root.find('[data-invoice-subtotal-value]').text(usd.format(subtotal));
            $root.find('[data-invoice-tax-value]').text(usd.format(taxes));
            $root.find('[data-invoice-total-value]').text(usd.format(total));
        };

        $root.on('input', '[data-invoice-quantity]', function () {
            if (Number($(this).val()) < 0) $(this).val(0);
            recalculate();
        });
        $root.on('click', '[data-invoice-action]', function () {
            const action = String($(this).data('invoice-action'));
            if (action === 'print') {
                announce('Opening the print dialog.');
                window.setTimeout(() => window.print(), 80);
                return;
            }

            if (action === 'download') {
                const html = `<!doctype html><html><head><meta charset="utf-8"><title>Invoice FC-456-14</title></head><body>${$root.find('.invoice-card').prop('outerHTML')}</body></html>`;
                const url = URL.createObjectURL(new Blob([html], { type: 'text/html;charset=utf-8' }));
                $('<a>', { href: url, download: 'invoice-FC-456-14.html' }).appendTo('body')[0].click();
                window.setTimeout(() => URL.revokeObjectURL(url), 1000);
                announce('Invoice download started.');
                return;
            }

            if (action === 'mail') {
                announce('Opening your email application.');
                window.location.href = 'mailto:?subject=Invoice%20FC-456-14&body=Please%20find%20invoice%20FC-456-14.';
                return;
            }

            if (action === 'back') {
                if (window.history.length > 1) window.history.back();
                else window.location.href = '/vuero/admin';
            }
        });

        recalculate();
        requestAnimationFrame(() => $root.addClass('is-ready'));
    });
};
