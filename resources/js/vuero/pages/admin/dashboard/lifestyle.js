import $ from '../../../admin/jquery';

const text = ($element) => String($element.text() || '').replace(/\s+/g, ' ').trim();

const field = (label, name, type = 'text', value = '') => `<label class="dashboard-action-field"><span>${label}</span><input name="${name}" type="${type}" value="${value}"></label>`;

const openDetails = (actions, trigger, eyebrow, title, copy) => actions.openDialog({
    trigger,
    eyebrow,
    title,
    icon: 'content',
    body: `<p class="dashboard-action-copy">${copy}</p><div class="dashboard-feature-pills"><span>Active</span><span>Updated recently</span><span>Shared</span></div>`,
});

const closePopover = () => {
    $('.lifestyle-popover').each(function () { $($(this).data('trigger')).attr('aria-expanded', 'false'); }).remove();
    $('.lifestyle-achievement-popover').remove();
};

const openPopover = ($trigger, actions) => {
    closePopover();
    const rect = $trigger.get(0).getBoundingClientRect();
    const top = Math.min(window.innerHeight - 190, rect.bottom + 6);
    const left = Math.max(12, Math.min(window.innerWidth - 205, rect.right - 190));
    const $menu = $(`<div class="lifestyle-popover" role="menu" style="top:${top}px;left:${left}px"><button type="button" role="menuitem" data-menu-action="view">View details</button><button type="button" role="menuitem" data-menu-action="edit">Edit item</button><button type="button" role="menuitem" data-menu-action="share">Share</button><button type="button" role="menuitem" class="is-danger" data-menu-action="remove">Remove</button></div>`).appendTo(document.body);
    $menu.data('trigger', $trigger.get(0));
    window.requestAnimationFrame(() => $menu.find('button').first().trigger('focus'));
    $menu.on('click', 'button', function () {
        const action = $(this).data('menu-action');
        const source = $menu.data('trigger');
        closePopover();
        if (action === 'remove') {
            actions.announce('Item moved to trash.');
            return;
        }
        openDetails(actions, source, 'Item options', `${action.charAt(0).toUpperCase()}${action.slice(1)} item`, 'This reusable action is ready to connect to the matching CMS endpoint.');
    });
};

const influencerMenuItem = (action, icon, title, description) => `<button type="button" role="menuitem" data-influencer-action="${action}"><span>${iconMarkup(icon, 18)}</span><span><strong>${title}</strong><small>${description}</small></span></button>`;

const openInfluencerMenu = ($trigger, actions) => {
    closePopover();
    const rect = $trigger.get(0).getBoundingClientRect();
    const items = [
        ['reports', 'activity', 'Reports', 'View detailed reports'],
        ['edit', 'edit', 'Edit', 'Edit this profile'],
        ['message', 'message', 'Message', 'Send a direct message'],
        ['gift', 'award', 'Gift', 'Send a gift'],
        ['settings', 'settings', 'Settings', 'Configure settings'],
    ];
    const $menu = $(`<div class="lifestyle-popover is-influencer" role="menu">${items.map((item) => influencerMenuItem(...item)).join('')}</div>`).appendTo(document.body);
    const width = $menu.outerWidth();
    const height = $menu.outerHeight();
    $menu.css({ top: Math.max(10, Math.min(window.innerHeight - height - 10, rect.bottom + 6)), left: Math.max(10, Math.min(window.innerWidth - width - 10, rect.right - width)) });
    $menu.data('trigger', $trigger.get(0));
    $trigger.attr('aria-expanded', 'true');
    window.requestAnimationFrame(() => $menu.find('button').first().trigger('focus'));
    $menu.on('click', '[data-influencer-action]', function () {
        const action = $(this).data('influencer-action');
        const trigger = $menu.data('trigger');
        closePopover();
        if (action === 'reports') {
            actions.openDrawer({ eyebrow: 'Influencer reports', title: 'Clarissa Geller', body: '<div class="dashboard-report-list"><p><span>Followers</span><strong>38.3K</strong></p><p><span>Engagement</span><strong>53%</strong></p><p><span>Reputation growth</span><strong>65%</strong></p><p><span>Lifetime earnings</span><strong>$23.4K</strong></p></div>' });
            return;
        }
        if (action === 'edit') {
            actions.openDialog({ trigger, eyebrow: 'Influencer profile', title: 'Edit Clarissa', icon: 'edit', confirmText: 'Save profile', body: `<div class="dashboard-action-grid">${field('Display name','display_name','text','Clarissa Geller')}${field('Username','username','text','sweet_candy13')}</div><label class="dashboard-action-field"><span>Biography</span><textarea rows="4">Artist, musician, songwriter, influencer.</textarea></label>`, onConfirm: () => { actions.closeDialog(); actions.announce('Influencer profile saved.'); } });
            return;
        }
        if (action === 'message') {
            actions.openDialog({ trigger, eyebrow: 'Direct message', title: 'Message Clarissa', icon: 'message', confirmText: 'Send message', body: `${field('Subject','subject','text','Collaboration proposal')}<label class="dashboard-action-field"><span>Message</span><textarea rows="4" placeholder="Write your message..."></textarea></label>`, onConfirm: () => { actions.closeDialog(); actions.announce('Message sent to Clarissa.'); } });
            return;
        }
        if (action === 'gift') {
            actions.openDialog({ trigger, eyebrow: 'Creator gift', title: 'Send a gift', icon: 'award', confirmText: 'Send gift', body: '<div class="dashboard-feature-pills"><span>Spotlight</span><span>Super fan</span><span>Creator boost</span></div><label class="dashboard-action-field"><span>Note</span><textarea rows="3" placeholder="Add a personal note..."></textarea></label>', onConfirm: () => { actions.closeDialog(); actions.announce('Gift sent to Clarissa.'); } });
            return;
        }
        actions.openDialog({ trigger, eyebrow: 'Profile settings', title: 'Influencer settings', icon: 'settings', confirmText: 'Save settings', body: '<label class="dashboard-action-switch"><input type="checkbox" checked><span></span> Show activity notifications</label><label class="dashboard-action-switch"><input type="checkbox" checked><span></span> Include private campaign metrics</label>', onConfirm: () => { actions.closeDialog(); actions.announce('Influencer settings saved.'); } });
    });
};

const openAchievementPopover = ($trigger) => {
    closePopover();
    const rect = $trigger.get(0).getBoundingClientRect();
    const title = $trigger.data('achievement');
    const copy = $trigger.data('achievement-copy');
    const icon = $trigger.find('[data-icon]').first().attr('data-icon') || 'award';
    const $popover = $(`<aside class="lifestyle-achievement-popover" role="tooltip"><header><span>${iconMarkup(icon, 18)}</span><h4>${title}</h4></header><p>${copy}</p></aside>`).appendTo(document.body);
    const width = $popover.outerWidth();
    $popover.css({ top: Math.min(window.innerHeight - $popover.outerHeight() - 10, rect.bottom + 8), left: Math.max(10, Math.min(window.innerWidth - width - 10, rect.left + rect.width / 2 - width / 2)) });
};

const openPlayer = ($card, actions) => {
    const title = actions.escapeHtml($card.data('video-title') || text($card.find('h3')) || 'Live match');
    const poster = $card.css('--video-poster') || '';
    const $modal = $(`<div class="lifestyle-video-modal" role="dialog" aria-modal="true" aria-label="${title}"><div class="lifestyle-video-modal__dialog"><div class="lifestyle-video-modal__stage"><button type="button" data-player-toggle aria-label="Play">${iconMarkup('play', 29)}</button></div><footer><h2>${title}</h2><button type="button" class="lifestyle-video-modal__close" data-player-close aria-label="Close">${iconMarkup('x', 18)}</button></footer></div></div>`).appendTo(document.body);
    if (poster && poster !== 'none') $modal.find('.lifestyle-video-modal__stage').css('background-image', poster);
    $('body').css('overflow', 'hidden');
    $modal.find('[data-player-close]').trigger('focus');
    const close = () => { $modal.remove(); $('body').css('overflow', ''); $card.find('button').first().trigger('focus'); };
    $modal.on('click', '[data-player-close]', close).on('click', function (event) { if (event.target === this) close(); }).on('click', '[data-player-toggle]', function () { $(this).toggleClass('is-playing'); actions.announce($(this).hasClass('is-playing') ? 'Playback started.' : 'Playback paused.'); });
    $(document).one('keydown.lifestylePlayer', (event) => { if (event.key === 'Escape') close(); });
};

const initializeInfluencer = ($dashboard, actions) => {
    $dashboard.on('click.lifestyle', '[data-influencer-tabs] button', function () {
        const panel = $(this).data('tab');
        $dashboard.find('[data-lifestyle="influencer"]').attr('data-active-tab', panel);
        $(this).addClass('is-active').attr('aria-selected', 'true').siblings('button').removeClass('is-active').attr('aria-selected', 'false');
        $dashboard.find('[data-panel]').prop('hidden', true).removeClass('is-active').filter(`[data-panel="${panel}"]`).prop('hidden', false).addClass('is-active');
        actions.announce(`${text($(this))} tab selected.`);
    });
    $dashboard.on('keydown.lifestyle', '[data-influencer-tabs] button', function (event) {
        if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
        event.preventDefault();
        const $tabs = $(this).parent().children('button');
        const direction = event.key === 'ArrowRight' ? 1 : -1;
        $tabs.eq(($(this).index() + direction + $tabs.length) % $tabs.length).trigger('focus').trigger('click');
    });
    $dashboard.on('click.lifestyle', '[data-influencer-menu]', function (event) { event.preventDefault(); event.stopPropagation(); openInfluencerMenu($(this), actions); });
    $dashboard.on('change.lifestyle', '[data-influencer-period]', function () {
        const multiplier = Number($(this).val()) / 30;
        $dashboard.find('[data-influencer-stats]').addClass('is-switching');
        window.setTimeout(() => {
            $dashboard.find('.influencer-stat-card').each(function () {
                const raw = String($(this).data('base-value'));
                const number = Number(raw.replace(/[^0-9.]/g, ''));
                if (!Number.isFinite(number)) return;
                const suffix = raw.includes('K') ? 'K' : '';
                $(this).children('strong').text(`${(number * multiplier).toFixed(suffix ? 1 : 0)}${suffix}`);
            });
            $dashboard.find('[data-influencer-stats]').removeClass('is-switching');
        }, 220);
    });
    $dashboard.on('click.lifestyle', '[data-influencer-follow]', function () {
        $(this).toggleClass('is-following');
        $(this).attr('aria-pressed', $(this).hasClass('is-following') ? 'true' : 'false');
        actions.announce($(this).hasClass('is-following') ? 'You are now following Clarissa.' : 'Clarissa was unfollowed.');
    });
    $dashboard.on('click.lifestyle', '[data-achievement]', function () {
        openAchievementPopover($(this));
    });
    $dashboard.on('click.lifestyle', '.hashtag-cloud button', function () { $(this).toggleClass('is-active'); actions.announce(`${text($(this))} filter ${$(this).hasClass('is-active') ? 'enabled' : 'removed'}.`); });
    $dashboard.on('click.lifestyle', '[data-post-preview]', function () { const $item = $(this).closest('article'); openDetails(actions, this, 'Instagram content', text($item.find('h3,header span').first()), 'Preview engagement, comments, publication history and campaign attribution for this post.'); });
    $dashboard.on('change.lifestyle', '[data-content-period]', function () {
        const period = Number($(this).val());
        const factor = period / 30;
        const $posts = $dashboard.find('.influencer-content-post');
        $posts.addClass('is-changing');
        window.setTimeout(() => {
            $dashboard.find('[data-content-payouts] section').each(function () {
                const source = String($(this).data('base-amount'));
                const amount = Number(source.replace(/[^0-9.]/g, ''));
                if (!Number.isFinite(amount)) return;
                const scaled = amount * factor;
                const suffix = source.includes('K') ? 'K' : '';
                $(this).children('strong').text(`$${suffix ? scaled.toFixed(1) : scaled.toFixed(2)}${suffix}`);
            });
            $posts.removeClass('is-changing');
        }, 240);
        actions.announce(`${text($(this).find(':selected'))} content statistics loaded.`);
    });
    $dashboard.on('click.lifestyle', '[data-content-heatmap] button', function () {
        const level = Number($(this).attr('class').match(/heat-(\d)/)?.[1] || 0);
        const hour = Number($(this).data('hour'));
        const displayHour = hour === 0 ? '12am' : hour < 12 ? `${hour}am` : hour === 12 ? '12pm' : `${hour - 12}pm`;
        actions.announce(`${$(this).data('day')} at ${displayHour}: ${level ? `activity level ${level}` : 'no posting activity'}.`);
    });
    $dashboard.on('click.lifestyle', '[data-content-thumbnail]', function () {
        const $card = $(this).closest('.influencer-content-post');
        const $hero = $card.children('[data-post-preview]').find('img');
        const thumbnail = $(this).find('img').attr('src');
        const previous = $hero.attr('src');
        $hero.addClass('is-switching');
        window.setTimeout(() => {
            $hero.attr('src', thumbnail).removeClass('is-switching');
            $(this).find('img').attr('src', previous);
        }, 180);
    });
};

const filterHobbies = ($dashboard, query) => {
    const needle = String(query || '').toLowerCase().replace(/^#/, '');
    const $items = $dashboard.find('[data-hobby-card],[data-hobby-row]');
    $items.each(function () {
        const $item = $(this).stop(true, true);
        const show = !needle || String($item.data('search')).includes(needle);
        if (show) $item.fadeIn(180); else $item.fadeOut(180);
    });
    const visible = $items.filter(function () { return !needle || String($(this).data('search')).includes(needle); }).length;
    $dashboard.find('[data-hobby-empty]').prop('hidden', visible > 0);
};

const initializeHobbies = ($dashboard, actions) => {
    $dashboard.on('input.lifestyle', '[data-hobby-search]', function () { filterHobbies($dashboard, $(this).val()); });
    $dashboard.on('click.lifestyle', '[data-hobby-submit]', function () { filterHobbies($dashboard, $dashboard.find('[data-hobby-search]').val()); actions.announce('Hobby search updated.'); });
    $dashboard.on('click.lifestyle', '[data-hobby-tag]', function () { const value = $(this).data('hobby-tag') || text($(this)); $dashboard.find('[data-hobby-search]').val(value); filterHobbies($dashboard, value); });
    $dashboard.on('click.lifestyle', '[data-hobbies-view-all],[data-list-view-all]', function () { $dashboard.find('[data-hobby-search]').val(''); filterHobbies($dashboard, ''); $('html,body').animate({scrollTop: Math.max(0,$dashboard.offset().top - 90)}, 400); });
    $dashboard.on('click.lifestyle', '[data-hobby-card] > button,[data-hobby-open]', function () { const $item = $(this).closest('[data-hobby-card],[data-hobby-row]'); openDetails(actions, this, 'Hobby activity', text($item.find('strong').first()), 'Review schedule, participants, difficulty, equipment and nearby meeting points.'); });
    $dashboard.on('click.lifestyle', '[data-open-messages]', function () { openDetails(actions, this, 'Inbox', 'Unread hobby messages', 'You have 10 unread messages from activity organizers and participants.'); });
};

const initializeHealth = ($dashboard, actions) => {
    $dashboard.on('click.lifestyle', '[data-health-tip]', function () { const title = text($(this).closest('.health-chart-card').find('h2')); openDetails(actions, this, 'Health guidance', `Improve ${title}`, 'Your recent measurements are compared with your personal baseline. Consistent sleep, hydration and moderate activity can improve this score.'); });
    $dashboard.on('click.lifestyle', '[data-health-topic]', function () { $dashboard.find('[data-health-search]').val(text($(this)).replace('#','')); $dashboard.find('.health-chart-card').addClass('is-highlighted'); window.setTimeout(() => $dashboard.find('.health-chart-card').removeClass('is-highlighted'), 650); actions.announce(`${text($(this))} resources selected.`); });
    $dashboard.on('click.lifestyle', '[data-health-submit]', function () { const query = $dashboard.find('[data-health-search]').val(); if (!query) { $dashboard.find('[data-health-search]').trigger('focus'); return; } openDetails(actions, this, 'Health search', `Results for ${actions.escapeHtml(query)}`, 'Relevant health reports, measurements and guidance are ready to connect to the CMS search endpoint.'); });
    $dashboard.on('keydown.lifestyle', '[data-health-search]', function (event) { if (event.key === 'Enter') { event.preventDefault(); $dashboard.find('[data-health-submit]').trigger('click'); } });
};

const initializeWriter = ($dashboard, actions) => {
    $dashboard.on('click.lifestyle', '[data-new-article]', function () { actions.openDialog({ trigger: this, eyebrow: 'Writer workspace', title: 'Start a new article', icon: 'edit', confirmText: 'Create draft', body: `${field('Article title','title','text','Untitled story')}<label class="dashboard-action-field"><span>Topic</span><select><option>Product</option><option>Lifestyle</option><option>Business</option></select></label><label class="dashboard-action-field"><span>Summary</span><textarea rows="3" placeholder="What is this story about?"></textarea></label>`, onConfirm: () => { actions.closeDialog(); actions.announce('Article draft created.'); } }); });
    $dashboard.on('click.lifestyle', '[data-writer-tabs] button', function () { const filter = $(this).data('article-filter'); $(this).addClass('is-active').attr('aria-selected','true').siblings('button').removeClass('is-active').attr('aria-selected','false'); const $feed = $dashboard.find('[data-writer-feed]').addClass('is-switching'); window.setTimeout(() => { $feed.find('article').prop('hidden', true).filter(`[data-article-type="${filter}"]`).prop('hidden', false); $feed.removeClass('is-switching'); }, 180); });
    $dashboard.on('click.lifestyle', '[data-save-article]', function () { $(this).toggleClass('is-saved'); actions.announce($(this).hasClass('is-saved') ? 'Article saved.' : 'Article removed from saved items.'); });
    $dashboard.on('click.lifestyle', '[data-open-article]', function () { openDetails(actions, this, 'Writer feed', text($(this).closest('article').find('h3')), 'Open the complete article, continue reading, leave a comment or share it with your editorial team.'); });
    $dashboard.on('click.lifestyle', '[data-writer-author]', function () { openDetails(actions, this, 'Featured author', text($(this).find('strong')), `Role: ${text($(this).find('p small'))}. Review published articles, readership and collaboration history.`); });
    $dashboard.on('click.lifestyle', '[data-update-item]', function () { $(this).fadeOut(150, function () { $(this).remove(); }); actions.announce('Update marked as read.'); });
    $dashboard.on('click.lifestyle', '[data-authors-view-all],[data-updates-view-all],[data-articles-view-all]', function () { openDetails(actions, this, 'Writer workspace', text($(this).closest('article,aside').find('h2').first()) || 'Writer activity', 'Browse the complete, searchable collection with CMS pagination and permission-aware actions.'); });
};

const initializeVideo = ($dashboard, actions) => {
    $dashboard.on('click.lifestyle', '[data-video-play]', function () { openPlayer($(this).closest('[data-video-title]'), actions); });
    $dashboard.on('click.lifestyle', '[data-video-author]', function () { openDetails(actions, this, 'Video author', text($(this)), 'View the member profile, published videos, workspace role and recent activity.'); });
    $dashboard.on('click.lifestyle', '[data-video-comments]', function () { openDetails(actions, this, 'Video discussion', text($(this).closest('[data-video-title]').find('h3')), `${text($(this))} are available for moderation and replies.`); });
    $dashboard.on('click.lifestyle', '[data-add-video-member]', function () { actions.openDialog({ trigger: this, eyebrow: 'Workspace members', title: 'Invite a member', icon: 'user-plus', confirmText: 'Send invitation', body: `${field('Email address','email','email','name@company.com')}<label class="dashboard-action-field"><span>Role</span><select><option>Viewer</option><option>Editor</option><option>Administrator</option></select></label>`, onConfirm: () => { actions.closeDialog(); actions.announce('Workspace invitation sent.'); } }); });
    $dashboard.on('click.lifestyle', '[data-video-member],[data-video-members-all]', function () { openDetails(actions, this, 'Workspace members', text($(this)) || 'Member profile', 'Review profile, permissions, recent uploads and workspace activity.'); });
    $dashboard.on('click.lifestyle', '[data-video-load-more]', function () { const $button = $(this).prop('disabled', true).text('Loading...'); window.setTimeout(() => { $button.text('All videos loaded'); actions.announce('All available videos are visible.'); }, 650); });
};

const initializeSoccer = ($dashboard, actions) => {
    $dashboard.on('click.lifestyle', '[data-soccer-tabs] button', function () { const filter = $(this).data('match-filter'); $(this).addClass('is-active').attr('aria-selected','true').siblings('button').removeClass('is-active').attr('aria-selected','false'); const $matches = $dashboard.find('[data-match-status]'); let count = 0; $matches.each(function () { const $match = $(this).stop(true,true); const show = filter === 'all' || $match.data('match-status') === filter; if (show) { count += 1; $match.slideDown(220); } else { $match.slideUp(220); } }); $dashboard.find('[data-soccer-empty]').prop('hidden', count > 0); actions.announce(`${count} ${filter} matches shown.`); });
    $dashboard.on('click.lifestyle', '[data-watch-match]', function () { const $card = $(this).closest('.soccer-live-card').attr('data-video-title','Barcelona vs PSG').css('--video-poster','linear-gradient(135deg,#172344,#145446)'); openPlayer($card, actions); });
    $dashboard.on('click.lifestyle', '[data-soccer-league]', function () { $dashboard.find('[data-soccer-league]').removeClass('is-active'); $(this).addClass('is-active'); actions.announce(`${$(this).data('soccer-league')} selected.`); });
    $dashboard.on('click.lifestyle', '[data-soccer-contest]', function () { actions.openDialog({ trigger: this, eyebrow: 'Vuero contest', title: 'Predict the next match', icon: 'ticket', confirmText: 'Submit prediction', body: '<div class="dashboard-action-grid">'+field('Home score','home','number','2')+field('Away score','away','number','1')+'</div><p class="dashboard-action-hint">Predictions close 30 minutes before kickoff.</p>', onConfirm: () => { actions.closeDialog(); actions.announce('Your prediction was submitted.'); } }); });
    $dashboard.on('click.lifestyle', '[data-match-details]', function () { const $match = $(this).closest('[data-match-status]'); openDetails(actions, this, 'Match details', text($match.find('.soccer-score')), `Kickoff: ${text($match.find('.soccer-match-time strong'))}. Stadium: ${text($match.find('.soccer-stadium'))}.`); });
};

export const initializeLifestyle = ($dashboard, actions) => {
    const slug = $dashboard.find('[data-lifestyle]').data('lifestyle');
    $dashboard.on('click.lifestyle', '[data-lifestyle-menu]', function (event) { event.preventDefault(); event.stopPropagation(); openPopover($(this), actions); });
    $(document).off('click.lifestyleMenu').on('click.lifestyleMenu', (event) => { if (!$(event.target).closest('.lifestyle-popover,.lifestyle-achievement-popover,[data-lifestyle-menu],[data-influencer-menu],[data-achievement]').length) closePopover(); });
    $(document).off('keydown.lifestyleMenu').on('keydown.lifestyleMenu', (event) => { if (event.key === 'Escape') closePopover(); });
    if (slug === 'influencer') initializeInfluencer($dashboard, actions);
    if (slug === 'hobbies') initializeHobbies($dashboard, actions);
    if (slug === 'health') initializeHealth($dashboard, actions);
    if (slug === 'writer') initializeWriter($dashboard, actions);
    if (slug === 'video') initializeVideo($dashboard, actions);
    if (slug === 'soccer') initializeSoccer($dashboard, actions);
};
