import $ from '../../../admin/jquery';
import { initializeDashboard } from '../dashboard-runtime';

const STORAGE_KEY = 'jobic.food-delivery.v1';

const initializeFoodDelivery = ($dashboard, actions) => {
    const $root = $dashboard.find('[data-food-delivery]');
    if (!$root.length) return;

    const state = {
        category: 'all', query: '', cart: new Map(), persons: 1,
        address: '625 St Marks Ave', deliveryLabel: 'Now', deliveryMinutes: '35 min',
    };

    const save = () => {
        try {
            sessionStorage.setItem(STORAGE_KEY, JSON.stringify({
                cart: [...state.cart.values()], persons: state.persons,
                address: state.address, deliveryLabel: state.deliveryLabel, deliveryMinutes: state.deliveryMinutes,
            }));
        } catch (_) { /* Storage can be unavailable in restricted contexts. */ }
    };

    const restore = () => {
        try {
            const saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) || 'null');
            if (!saved) return;
            (Array.isArray(saved.cart) ? saved.cart : []).forEach((item) => state.cart.set(item.key, item));
            state.persons = Math.min(8, Math.max(1, Number(saved.persons) || 1));
            state.address = saved.address || state.address;
            state.deliveryLabel = saved.deliveryLabel || state.deliveryLabel;
            state.deliveryMinutes = saved.deliveryMinutes || state.deliveryMinutes;
        } catch (_) {
            try { sessionStorage.removeItem(STORAGE_KEY); } catch (_) { /* No-op. */ }
        }
    };

    const orderCount = () => [...state.cart.values()].reduce((total, item) => total + item.quantity, 0);
    const orderTotal = () => [...state.cart.values()].reduce((total, item) => total + (item.price * item.quantity), 0);

    const renderCart = ({ animate = false } = {}) => {
        const $list = $root.find('[data-food-order-list]').empty();
        [...state.cart.values()].forEach((item) => {
            $list.append(`
                <article class="food-delivery-order-item" data-food-cart-item="${actions.escapeHtml(item.key)}">
                    <span class="food-delivery-order-item__image food-sprite-${Number(item.sprite)}" aria-hidden="true"></span>
                    <div class="food-delivery-order-item__copy"><strong>${actions.escapeHtml(item.dish)}</strong><span>${actions.escapeHtml(item.restaurant)}</span></div>
                    <div class="food-delivery-order-item__controls">
                        <strong>$${(item.price * item.quantity).toFixed(2)}</strong>
                        <div><button type="button" data-food-quantity="minus" aria-label="Remove one ${actions.escapeHtml(item.dish)}">−</button><span>${item.quantity}</span><button type="button" data-food-quantity="plus" aria-label="Add one ${actions.escapeHtml(item.dish)}">+</button></div>
                    </div>
                </article>`);
        });

        const count = orderCount();
        $root.find('[data-food-order-empty]').prop('hidden', count > 0);
        $root.find('[data-food-clear]').prop('hidden', count === 0);
        $root.find('[data-food-checkout]').prop('disabled', count === 0);
        $root.find('[data-food-total]').text(`$${orderTotal().toFixed(2)}`);
        $root.find('[data-food-tool-count]').text(count).attr('data-empty', String(count === 0));
        $root.find('[data-food-persons]').text(state.persons);
        $root.find('[data-food-person-minus]').prop('disabled', state.persons <= 1);
        $root.find('[data-food-person-plus]').prop('disabled', state.persons >= 8);
        $root.find('[data-food-address-label]').text(state.address);
        $root.find('[data-food-order-time]').text(state.deliveryMinutes);
        $root.find('[data-food-time-label]').text(state.deliveryLabel);
        if (animate) {
            const $card = $root.find('.food-delivery-order-card').addClass('has-new-item');
            window.setTimeout(() => $card.removeClass('has-new-item'), 520);
        }
        save();
    };

    const applyFilters = ({ announce = false } = {}) => {
        let matches = 0;
        const query = state.query.trim().toLowerCase();
        $root.find('[data-food-restaurant]').each(function () {
            const $restaurant = $(this);
            const categories = String($restaurant.data('category')).split(/\s+/);
            const searchable = `${$restaurant.data('name')} ${$restaurant.find('p').text()}`.toLowerCase();
            const categoryMatches = state.category === 'all' || categories.includes(state.category);
            const queryMatches = !query || searchable.includes(query);
            const visible = categoryMatches && queryMatches;
            $restaurant.prop('hidden', !visible);
            if (visible) { matches += 1; $restaurant.css('transition-delay', `${Math.min(matches - 1, 5) * 45}ms`); }
        });
        $root.find('[data-food-no-results]').prop('hidden', matches > 0);
        if (announce) actions.announce(`${matches} restaurant${matches === 1 ? '' : 's'} shown.`);
    };

    const selectCategory = ($button, shouldAnnounce = true) => {
        state.category = String($button.data('food-category'));
        $root.find('[data-food-tool]').removeClass('is-active').attr('aria-pressed', 'false').filter('[data-food-tool="cart"]').addClass('is-active').attr('aria-pressed', 'true');
        $button.addClass('is-active').attr('aria-selected', 'true').siblings().removeClass('is-active').attr('aria-selected', 'false');
        applyFilters({ announce: shouldAnnounce });
    };

    const addRestaurant = ($restaurant) => {
        const key = String($restaurant.data('name')).toLowerCase().replace(/[^a-z0-9]+/g, '-');
        const item = state.cart.get(key) || {
            key, restaurant: String($restaurant.data('name')), dish: String($restaurant.data('dish')),
            price: Number($restaurant.data('price')), sprite: Number($restaurant.data('sprite')), quantity: 0,
        };
        item.quantity += 1;
        state.cart.set(key, item);
        $restaurant.addClass('is-adding');
        window.setTimeout(() => $restaurant.removeClass('is-adding'), 520);
        renderCart({ animate: true });
        actions.announce(`${item.dish} added to your order.`);
    };

    const openDeliveryTime = (trigger) => actions.openDialog({
        title: 'Choose delivery time', eyebrow: 'Delivery preference', icon: 'clock', trigger, confirmText: 'Apply time',
        body: `<div class="dashboard-action-options" data-food-time-options>${[['Now','35 min'],['In 30 minutes','55 min'],['In 1 hour','85 min']].map(([label, minutes]) => `<label><input type="radio" name="food-delivery-time" value="${label}" data-minutes="${minutes}" ${state.deliveryLabel === label ? 'checked' : ''}><span><strong>${label}</strong><small>Estimated arrival: ${minutes}</small></span></label>`).join('')}</div>`,
        onConfirm: () => {
            const $selected = $('[name="food-delivery-time"]:checked');
            state.deliveryLabel = String($selected.val() || 'Now');
            state.deliveryMinutes = String($selected.data('minutes') || '35 min');
            renderCart(); actions.closeDialog(); actions.announce(`Delivery time set to ${state.deliveryLabel}.`);
        },
    });

    const openAddress = (trigger) => actions.openDialog({
        title: 'Delivery address', eyebrow: 'Order destination', icon: 'location', trigger, confirmText: 'Save address',
        body: `<label class="dashboard-action-field"><span>Street address</span><input type="text" data-food-address-input value="${actions.escapeHtml(state.address)}" maxlength="80" autocomplete="street-address"></label>`,
        onConfirm: () => {
            const address = String($('[data-food-address-input]').val() || '').trim();
            if (!address) { actions.announce('Enter a delivery address first.'); return; }
            state.address = address; renderCart(); actions.closeDialog(); actions.announce('Delivery address updated.');
        },
    });

    restore(); renderCart(); applyFilters();

    $root
        .on('click.foodDelivery', '[data-food-category]', function () { selectCategory($(this)); })
        .on('keydown.foodDelivery', '[data-food-category]', function (event) {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const $tabs = $root.find('[data-food-category]');
            const current = $tabs.index(this);
            const next = event.key === 'Home' ? 0 : event.key === 'End' ? $tabs.length - 1 : (current + (event.key === 'ArrowRight' ? 1 : -1) + $tabs.length) % $tabs.length;
            $tabs.eq(next).trigger('focus'); selectCategory($tabs.eq(next));
        })
        .on('input.foodDelivery', '[data-food-search]', function () {
            state.query = String($(this).val());
            $root.find('[data-food-search-clear]').prop('hidden', !state.query); applyFilters();
        })
        .on('click.foodDelivery', '[data-food-search-clear]', function () {
            state.query = ''; $root.find('[data-food-search]').val('').trigger('focus'); $(this).prop('hidden', true); applyFilters({ announce: true });
        })
        .on('click.foodDelivery', '[data-food-reset]', function () {
            state.query = ''; state.category = 'all';
            $root.find('[data-food-search]').val(''); $root.find('[data-food-search-clear]').prop('hidden', true);
            selectCategory($root.find('[data-food-category="all"]'));
        })
        .on('click.foodDelivery', '[data-food-category-previous], [data-food-category-next]', function () {
            const direction = $(this).is('[data-food-category-next]') ? 1 : -1;
            $root.find('[data-food-categories]').get(0)?.scrollBy({ left: direction * 290, behavior: 'smooth' });
        })
        .on('click.foodDelivery', '[data-food-restaurant]', function () {
            addRestaurant($(this));
        })
        .on('keydown.foodDelivery', '[data-food-restaurant]', function (event) {
            if (!['Enter', ' '].includes(event.key)) return;
            event.preventDefault();
            addRestaurant($(this));
        })
        .on('click.foodDelivery', '[data-food-quantity]', function () {
            const $item = $(this).closest('[data-food-cart-item]');
            const key = String($item.data('food-cart-item')); const item = state.cart.get(key); if (!item) return;
            item.quantity += $(this).data('food-quantity') === 'plus' ? 1 : -1;
            if (item.quantity <= 0) {
                $item.addClass('is-removing'); window.setTimeout(() => { state.cart.delete(key); renderCart(); }, 190);
            } else { state.cart.set(key, item); renderCart(); }
            actions.announce(`${item.dish} quantity updated.`);
        })
        .on('click.foodDelivery', '[data-food-clear]', function () {
            actions.openDialog({ title: 'Clear your order?', eyebrow: 'Basket', icon: 'trash', trigger: this, confirmText: 'Clear order', body: '<p>All items will be removed from your current basket.</p>', onConfirm: () => { state.cart.clear(); renderCart(); actions.closeDialog(); actions.announce('Your order is now empty.'); } });
        })
        .on('click.foodDelivery', '[data-food-person-minus], [data-food-person-plus]', function () {
            state.persons = Math.min(8, Math.max(1, state.persons + ($(this).is('[data-food-person-plus]') ? 1 : -1)));
            renderCart(); actions.announce(`Order set for ${state.persons} ${state.persons === 1 ? 'person' : 'people'}.`);
        })
        .on('click.foodDelivery', '[data-food-time]', function () { openDeliveryTime(this); })
        .on('click.foodDelivery', '[data-food-address]', function () { openAddress(this); })
        .on('click.foodDelivery', '[data-food-offer]', function () {
            actions.openDialog({ title: '$0 delivery for 30 days', eyebrow: 'Limited offer', icon: 'ticket', trigger: this, confirmText: 'Activate offer', body: '<p>Place an order over $10 and enjoy free delivery for the next 30 days. The offer is applied automatically at checkout.</p>', onConfirm: () => { actions.closeDialog(); actions.announce('Free delivery offer activated.'); } });
        })
        .on('click.foodDelivery', '[data-food-checkout]', function () {
            const count = orderCount(); if (!count) return;
            actions.openDialog({ title: 'Confirm your order', eyebrow: `${count} item${count === 1 ? '' : 's'} · ${state.deliveryMinutes}`, icon: 'check-circle', trigger: this, confirmText: `Pay $${orderTotal().toFixed(2)}`, body: `<p>Your order will be delivered to <strong>${actions.escapeHtml(state.address)}</strong>. This demo checkout safely previews the completed interaction.</p>`, onConfirm: () => { state.cart.clear(); renderCart(); actions.closeDialog(); actions.announce('Order placed successfully.'); } });
        })
        .on('click.foodDelivery', '[data-food-tool]', function () {
            const tool = String($(this).data('food-tool'));
            $root.find('[data-food-tool]').removeClass('is-active').attr('aria-pressed', 'false');
            $(this).addClass('is-active').attr('aria-pressed', 'true');
            if (tool === 'cart') {
                applyFilters();
                $root.find('.food-delivery-order-card').get(0)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            } else if (tool === 'offers') {
                $root.find('[data-food-offer]').trigger('click');
            } else if (tool === 'time') { openDeliveryTime(this); } else { openAddress(this); }
        });
};

initializeDashboard('food-delivery', initializeFoodDelivery);
