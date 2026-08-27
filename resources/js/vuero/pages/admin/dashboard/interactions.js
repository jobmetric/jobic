import $ from '../../../admin/jquery';

const normalise = (value) => String(value || '').replace(/\s+/g, ' ').trim();

const reportTemplate = (title) => `
    <section class="dashboard-report-summary">
        <div><span>Current period</span><strong>84.6%</strong><small>+6.4% from last period</small></div>
        <div class="dashboard-report-bars" aria-hidden="true">${[38,55,43,72,64,81,68,92,76,88,70,96].map((value) => `<i style="--value:${value}%"></i>`).join('')}</div>
    </section>
    <h3>${title}</h3>
    <div class="dashboard-report-list">
        <p><span>Organic growth</span><strong>+18.2%</strong></p>
        <p><span>Conversion</span><strong>43.7%</strong></p>
        <p><span>Returning users</span><strong>6,318</strong></p>
        <p><span>Average session</span><strong>08:42</strong></p>
    </div>`;

const field = (label, name, type = 'text', placeholder = '') => `
    <label class="dashboard-action-field"><span>${label}</span><input name="${name}" type="${type}" placeholder="${placeholder}"></label>`;

export const initializeInteractions = ($dashboard, slug, actions, charts) => {
    let orderCount = 0;
    let orderTotal = 0;

    const completeAction = ($button, pending, complete, callback = null) => {
        const initial = $button.text();
        $button.prop('disabled', true).addClass('is-loading').text(pending);
        window.setTimeout(() => {
            $button.removeClass('is-loading').addClass('is-complete').text(complete);
            if (callback) callback();
            window.setTimeout(() => $button.prop('disabled', false).removeClass('is-complete').text(initial), 2200);
        }, 720);
    };

    const contextualTitle = ($trigger) => normalise($trigger.closest('.dashboard-card, section').find('h1,h2,h3').first().text()) || 'Dashboard details';

    const openReport = ($trigger, title = null) => {
        const reportTitle = title || contextualTitle($trigger) || 'Performance report';
        actions.openDrawer({ title: reportTitle, body: reportTemplate(reportTitle) });
    };

    const openManager = ($trigger, mode) => {
        const isShop = mode === 'shop';
        actions.openDialog({
            trigger: $trigger.get(0),
            eyebrow: isShop ? 'Store manager' : 'Workspace manager',
            title: isShop ? 'Manage your store' : 'Manage your workspace',
            icon: isShop ? 'briefcase' : 'settings',
            confirmText: 'Save changes',
            body: `<div class="dashboard-action-grid">${field(isShop ? 'Store name' : 'Workspace name','name','text',isShop ? 'Vuero Store' : 'Product Team')}${field('Notification email','email','email','erik@vuero.io')}</div><label class="dashboard-action-switch"><input type="checkbox" checked><span></span> Accept new activity automatically</label>`,
            onConfirm: ($confirm) => completeAction($confirm, 'Saving…', 'Saved', () => {
                actions.closeDialog();
                actions.announce('Your changes have been saved.');
            }),
        });
    };

    const openSchedule = ($trigger) => actions.openDialog({
        trigger: $trigger.get(0), eyebrow: 'Upcoming schedule', title: 'Your next appointments', icon: 'activity', confirmText: 'Add reminder',
        body: `<div class="dashboard-agenda"><article><time>08:00</time><div><strong>Jonathan K.</strong><span>Product interview · 60 min</span></div></article><article><time>10:30</time><div><strong>Greta K.</strong><span>Design review · 30 min</span></div></article><article><time>14:30</time><div><strong>Team sync</strong><span>Conference room A · 45 min</span></div></article></div>`,
        onConfirm: () => { actions.closeDialog(); actions.announce('Schedule reminder added.'); },
    });

    const openVideo = ($trigger, title) => actions.openDialog({
        trigger: $trigger.get(0), eyebrow: 'Vuero media', title, icon: 'media', confirmText: 'Close player',
        body: `<div class="dashboard-video-stage"><button type="button" aria-label="Play video">▶</button><span></span><p>Loading preview…</p></div>`,
        onConfirm: actions.closeDialog,
    });

    $dashboard.on('click.dashboardPage', '.period-tabs button', function () {
        const range = normalise($(this).text());
        charts.updateForRange(range);
        const $price = $dashboard.find('.crypto-header h1');
        $price.addClass('is-updating');
        window.setTimeout(() => $price.removeClass('is-updating'), 480);
        actions.announce(`${range} chart range selected.`);
    });

    $dashboard.on('click.dashboardPage', '.flight-deals button', function () {
        $(this).addClass('is-active').siblings().removeClass('is-active');
        $dashboard.find('.flight-list article').removeClass('is-revealed').each(function (index) {
            window.setTimeout(() => $(this).addClass('is-revealed'), 70 * index);
        });
    });

    $dashboard.on('change.dashboardPage', '.radio-row input', function () {
        const filter = normalise($(this).closest('label').text());
        const $flights = $dashboard.find('.flight-list article');
        $flights.addClass('is-searching');
        window.setTimeout(() => $flights.removeClass('is-searching').addClass('is-revealed'), 380);
        actions.announce(`${filter} filter applied.`);
    });

    $dashboard.on('click.dashboardPage', '.food-categories button', function () {
        const category = normalise($(this).text());
        $(this).addClass('is-active').siblings().removeClass('is-active');
        $dashboard.find('.restaurant-grid article').each(function (index) {
            const matches = category === 'All' || normalise($(this).text()).toLowerCase().includes(category.toLowerCase());
            $(this).toggleClass('is-category-hidden', !matches);
            if (matches) window.setTimeout(() => $(this).addClass('is-revealed'), index * 45);
        });
    });

    $dashboard.on('click.dashboardPage', '.food-layout > aside > nav button', function () {
        $(this).addClass('is-active').siblings().removeClass('is-active');
        $dashboard.find('.order-card').addClass('is-filtering');
        window.setTimeout(() => $dashboard.find('.order-card').removeClass('is-filtering'), 300);
    });

    $dashboard.on('click.dashboardPage', '.restaurant-grid article', function () {
        const $restaurant = $(this);
        const name = normalise($restaurant.find('strong').first().text());
        orderCount += 1;
        orderTotal += 12.5;
        const $order = $dashboard.find('.order-card');
        $order.find('header span').text(`${orderCount} item${orderCount > 1 ? 's' : ''}`);
        $order.find('footer p strong').text(`$${orderTotal.toFixed(2)}`);
        const $empty = $order.find('.empty-order');
        if ($empty.length) $empty.replaceWith('<div class="order-items"></div>');
        $order.find('.order-items').append(`<p><span>${actions.escapeHtml(name)}</span><strong>$12.50</strong></p>`);
        $order.addClass('has-new-item');
        window.setTimeout(() => $order.removeClass('has-new-item'), 500);
        actions.announce(`${name} added to your order.`);
    });

    $dashboard.on('click.dashboardPage', '.match-table nav button', function () {
        const tab = normalise($(this).text());
        const $rows = $dashboard.find('.match-table > article');
        $rows.each(function (index) {
            const visible = tab === 'All' || (tab === 'Live' && index === 0) || (tab === 'Scheduled' && index > 0) || tab === 'Finished';
            $(this).stop(true, true).slideToggle(220, visible);
        });
    });

    $dashboard.on('click.dashboardPage', '.pill-tabs button', function () {
        const $articles = $dashboard.find('.new-articles article');
        $articles.addClass('is-switching');
        window.setTimeout(() => {
            $articles.removeClass('is-switching');
            if ($(this).index() === 1) $articles.parent().append($articles.get().reverse());
        }, 220);
    });

    $dashboard.on('change.dashboardPage', '.jobs-filters input', function () {
        const activeFilters = $dashboard.find('.jobs-filters input:checked').length;
        const $cards = $dashboard.find('.job-cards article');
        $cards.each(function (index) {
            const visible = !activeFilters || index % (activeFilters + 1) !== activeFilters;
            $(this).stop(true, true).fadeToggle(230, visible);
        });
        actions.announce(activeFilters ? `${activeFilters} job filters applied.` : 'All job filters cleared.');
    });

    $dashboard.on('click.dashboardPage', '.dashboard-calendar__days span', function () {
        $(this).addClass('is-selected').siblings().removeClass('is-selected');
        actions.announce(`October ${normalise($(this).text())} selected.`);
    });

    $dashboard.on('change.dashboardPage', '.todo-list input', function () {
        const $label = $(this).closest('label');
        $label.toggleClass('is-complete', this.checked);
        actions.announce(this.checked ? 'Task completed.' : 'Task reopened.');
    });

    $dashboard.on('click.dashboardPage', '.coin-list button', function () {
        const $button = $(this);
        const following = !$button.hasClass('is-complete');
        $button.toggleClass('is-complete', following).text(following ? '✓' : '＋');
        actions.announce(`${normalise($button.siblings('strong').text())} ${following ? 'added to' : 'removed from'} watchlist.`);
    });

    $dashboard.on('click.dashboardPage', '.search-box button', function () {
        const query = normalise($(this).siblings('input').val());
        if (!query) { $(this).siblings('input').trigger('focus'); actions.announce('Type a search term first.'); return; }
        actions.openDrawer({ title: `Results for “${query}”`, eyebrow: 'Dashboard search', body: `<div class="dashboard-search-results"><p><strong>${actions.escapeHtml(query)}</strong><span>Best matching result</span></p><p><strong>Recent activity</strong><span>Updated 8 minutes ago</span></p><p><strong>Related report</strong><span>Open analytics overview</span></p></div>` });
    });

    $dashboard.on('click.dashboardPage', '.project-list > div, .stock-list > div, .job-feed > article, .course-cards > article, .list-row, .league-list > p, .transaction-list > div, .meeting-card', function (event) {
        if ($(event.target).is('button,a,input') && !$(event.currentTarget).is('.meeting-card')) return;
        const title = normalise($(this).find('strong,h2,h3').first().text()) || normalise($(this).text()).slice(0, 50);
        actions.openDialog({ trigger: this, eyebrow: 'Item details', title, icon: 'content', body: '<p class="dashboard-action-copy">Review activity, participants and recent changes for this item. The final CMS endpoint can be connected here without changing the page component.</p><div class="dashboard-feature-pills"><span>Updated recently</span><span>Active</span><span>Shared</span></div>' });
    });

    $dashboard.on('click.dashboardPage', '.dashboard-calendar header button', function () {
        const $title = $(this).siblings('strong');
        const months = ['September 2020','October 2020','November 2020'];
        let index = months.indexOf(normalise($title.text()));
        index = Math.max(0, index) + ($(this).is(':first-child') ? -1 : 1);
        $title.addClass('is-changing').text(months[(index + months.length) % months.length]);
        window.setTimeout(() => $title.removeClass('is-changing'), 320);
    });

    $dashboard.on('click.dashboardPage', '.card-menu, .project-list button', function (event) {
        event.stopPropagation();
        $('.dashboard-mini-menu').remove();
        const $menu = $('<div class="dashboard-mini-menu"><button type="button">Edit</button><button type="button">Share</button><button type="button">Archive</button></div>');
        $(this).after($menu);
        window.requestAnimationFrame(() => $menu.addClass('is-open'));
    });
    $(document).on('click.dashboardMiniMenu', () => $('.dashboard-mini-menu').remove());

    $dashboard.on('click.dashboardPage', 'button, a', function (event) {
        const $button = $(this);
        if ($button.closest('[data-lifestyle],[data-ecommerce],.vuero-widgets-page').length) return;
        const label = normalise($button.text());
        if (!label || $button.closest('.period-tabs,.flight-deals,.stock-categories,.food-categories,.dashboard-calendar,.match-table nav').length) return;

        if (/^(View Reports|Reports|All Reports)$/.test(label)) { event.preventDefault(); openReport($button); return; }
        if (label === 'View All') { event.preventDefault(); openReport($button, contextualTitle($button)); return; }
        if (/Manage (Store|Shop)/.test(label)) { event.preventDefault(); openManager($button, 'shop'); return; }
        if (label === 'Learn More') { event.preventDefault(); actions.openDialog({ trigger: this, eyebrow: 'Vuero workspace', title: 'One workspace, better teamwork', icon: 'users', body: '<p class="dashboard-action-copy">Coordinate projects, automate repetitive work and keep the whole team focused with a single, calm workspace.</p><div class="dashboard-feature-pills"><span>Live reports</span><span>Team workflows</span><span>Smart notifications</span></div>' }); return; }
        if (label === 'View Schedule') { event.preventDefault(); openSchedule($button); return; }
        if (label === 'View Tasks') { event.preventDefault(); const $target = $dashboard.find('.project-list').first(); if ($target.length) $('html,body').animate({ scrollTop: Math.max(0,$target.offset().top - 100) }, 500, () => { $target.addClass('is-highlighted'); window.setTimeout(() => $target.removeClass('is-highlighted'), 1200); }); return; }
        if (label === 'Manage Jobs') { event.preventDefault(); actions.openDialog({ trigger: this, eyebrow: 'Recruiting workspace', title: 'Create a new position', icon: 'briefcase', confirmText: 'Publish position', body: `<div class="dashboard-action-grid">${field('Position title','position','text','Senior Product Designer')}${field('Location','location','text','Los Angeles, CA')}</div>${field('Application deadline','deadline','date')}`, onConfirm: () => { actions.closeDialog(); actions.announce('New position published.'); } }); return; }
        if (label.includes('New Article')) { event.preventDefault(); actions.openDialog({ trigger: this, eyebrow: 'Writer workspace', title: 'Start a new article', icon: 'edit', confirmText: 'Create draft', body: `${field('Article title','title','text','Untitled story')}<label class="dashboard-action-field"><span>Topic</span><select><option>Product</option><option>Lifestyle</option><option>Business</option></select></label>`, onConfirm: () => { actions.closeDialog(); actions.announce('Article draft created.'); } }); return; }
        if (label === 'Create Job Alert') { event.preventDefault(); actions.openDialog({ trigger: this, eyebrow: 'Job notifications', title: 'Create a job alert', icon: 'bell', confirmText: 'Create alert', body: `${field('Keywords','keywords','text','Product designer')}${field('Location','location','text','Remote')}`, onConfirm: () => { actions.closeDialog(); $button.text('Alert Active').addClass('is-complete'); actions.announce('Your job alert is active.'); } }); return; }
        if (label === 'Apply Now') { event.preventDefault(); completeAction($button, 'Applying…', 'Applied ✓', () => actions.announce('Application submitted successfully.')); return; }
        if (label === 'View Profile' || label === 'View') { event.preventDefault(); const name = normalise($button.closest('article').find('h3,strong').first().text()) || contextualTitle($button); actions.openDialog({ trigger: this, eyebrow: label === 'View Profile' ? 'Candidate profile' : 'Position details', title: name, icon: label === 'View Profile' ? 'user' : 'briefcase', body: '<p class="dashboard-action-copy">Skills, contact information, activity history and permission-aware actions are available from this reusable detail component.</p><div class="dashboard-feature-pills"><span>Available</span><span>Verified</span><span>Team fit</span></div>' }); return; }
        if (label === 'Add To Favorites') { event.preventDefault(); $button.toggleClass('is-complete').text($button.hasClass('is-complete') ? '✓ Added To Favorites' : 'Add To Favorites'); actions.announce($button.hasClass('is-complete') ? 'Flight saved to favorites.' : 'Flight removed from favorites.'); return; }
        if (label === 'Watch Now' || $button.hasClass('video-play')) { event.preventDefault(); openVideo($button, contextualTitle($button) || 'Video preview'); return; }
        if (label === 'Confirm') { event.preventDefault(); completeAction($button, 'Checking…', 'Confirmed ✓', () => actions.announce('Transfer account confirmed.')); return; }
        if (label === 'Send Money') {
            event.preventDefault(); const $input = $dashboard.find('.money-input input'); const amount = normalise($input.val());
            if (!amount) { $input.closest('.money-input').addClass('is-invalid'); $input.trigger('focus'); window.setTimeout(() => $input.closest('.money-input').removeClass('is-invalid'), 500); actions.announce('Enter an amount first.'); return; }
            actions.openDialog({ trigger: this, eyebrow: 'Secure transfer', title: `Send $${actions.escapeHtml(amount)}`, icon: 'credit-card', confirmText: 'Confirm transfer', body: '<p class="dashboard-action-copy">The funds will reach the selected account tomorrow. You can review this transaction in your banking history.</p>', onConfirm: ($confirm) => completeAction($confirm, 'Sending…', 'Sent ✓', () => { actions.closeDialog(); actions.announce(`$${amount} sent successfully.`); }) }); return;
        }
        if (label === 'Buy BTC') { event.preventDefault(); actions.openDialog({ trigger: this, eyebrow: 'Bitcoin purchase', title: 'Buy Bitcoin', icon: 'diamond', confirmText: 'Review purchase', body: `${field('Amount in USD','amount','number','500.00')}<p class="dashboard-action-hint">Estimated BTC will update after entering an amount.</p>`, onConfirm: () => { actions.closeDialog(); actions.announce('Purchase added for review.'); } }); return; }
        if (label === 'Compare') { event.preventDefault(); const $coin = $button.closest('.coin-card'); if (!$coin.next('.coin-comparison').length) $coin.after('<article class="dashboard-card coin-comparison"><span>BTC</span><strong>$10,584</strong><i>vs</i><span>ETH</span><strong>$3,412</strong></article>'); actions.announce('Coin comparison opened.'); return; }
        if (label === '+' || label === '＋') { event.preventDefault(); actions.openDialog({ trigger: this, eyebrow: 'Add item', title: slug.startsWith('banking') ? 'Add a payment card' : 'Create a new item', icon: 'credit-card', confirmText: 'Add item', body: `${field('Display name','name','text','Primary card')}${field('Reference','reference','text','•••• 4986')}`, onConfirm: () => { actions.closeDialog(); actions.announce('New item added.'); } }); return; }
        if (label === 'Search Jobs') { event.preventDefault(); const $cards = $dashboard.find('.job-cards article'); $cards.addClass('is-searching'); window.setTimeout(() => $cards.removeClass('is-searching').addClass('is-revealed'), 520); actions.announce(`${$cards.length} matching positions found.`); return; }
        if (label === 'Start Checkout') { event.preventDefault(); if (!orderCount) { $button.closest('.order-card').addClass('is-shaking'); window.setTimeout(() => $button.closest('.order-card').removeClass('is-shaking'), 480); actions.announce('Your cart is empty.'); } else actions.openDialog({ trigger: this, eyebrow: 'Secure checkout', title: `Checkout · $${orderTotal.toFixed(2)}`, icon: 'credit-card', confirmText: 'Place order', body: `${field('Delivery address','address','text','221B Baker Street')}${field('Phone number','phone','tel','+1 555 0100')}`, onConfirm: () => { actions.closeDialog(); actions.announce('Your order has been placed.'); } }); return; }
        if (label === 'Compose') { event.preventDefault(); actions.openDialog({ trigger: this, eyebrow: 'New message', title: 'Compose email', icon: 'mail', confirmText: 'Send message', body: `${field('Recipient','recipient','email','name@company.com')}${field('Subject','subject','text','Project update')}<label class="dashboard-action-field"><span>Message</span><textarea rows="4" placeholder="Write your message..."></textarea></label>`, onConfirm: () => { actions.closeDialog(); actions.announce('Message sent.'); } }); return; }
        if (['Details','Settings','New','See all Contacts →'].includes(label)) { event.preventDefault(); actions.openDialog({ trigger: this, eyebrow: 'Dashboard details', title: label, icon: 'settings', body: '<p class="dashboard-action-copy">This panel is ready to be connected to your CMS data and permissions.</p>' }); }
    });
};
