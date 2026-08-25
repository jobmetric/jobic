import $ from '../../../admin/jquery';

const normalise = (value) => String(value || '').replace(/\s+/g, ' ').trim();
const lower = (value) => normalise(value).toLocaleLowerCase();

const avatarMarkup = (conversation, extraClass = '') => {
    const media = conversation.avatar
        ? `<img src="/vuero/images/admin/business/avatars/${conversation.avatar}.svg" alt="" draggable="false">`
        : `<span>${conversation.initials || 'VU'}</span>`;
    const status = conversation.status ? `<i class="is-${conversation.status}"></i>` : '';
    return `<span class="messaging-avatar ${extraClass}" aria-hidden="true">${media}${status}</span>`;
};

const createMessageMarkup = (message, conversation, version, isNew = false) => {
    const owner = message.type === 'outgoing';
    const person = owner ? { initials: 'EK', avatar: '22' } : { initials: message.initials || conversation.initials, avatar: message.avatar || conversation.avatar };
    const classes = version === 'v1'
        ? `messaging-v1-message is-${message.type}${isNew ? ' is-new' : ''}`
        : `messaging-v2-message is-${message.type}${isNew ? ' is-new' : ''}`;
    const texts = (message.texts || [message.text]).filter(Boolean).map((text) => `<p>${text}</p>`).join('');
    const body = `<div>${texts}<time>${message.time || 'Sent just now'}</time></div>`;
    const avatar = avatarMarkup(person);
    const tag = version === 'v2' ? 'article' : 'li';
    return owner ? `<${tag} class="${classes}">${body}${avatar}</${tag}>` : `<${tag} class="${classes}">${avatar}${body}</${tag}>`;
};

const initializeComposer = ($root, actions, version, getConversation) => {
    const $composer = $root.find('[data-messaging-composer]');
    const $input = $composer.find('[data-messaging-input]');
    const $thread = $root.find(version === 'v1' ? '[data-v1-thread]' : '[data-v2-thread]');
    let typingTimer = null;

    const closePopovers = () => {
        $composer.find('[data-messaging-attachment-menu], [data-messaging-emoji-menu]').prop('hidden', true);
        $composer.find('[data-messaging-attachment-toggle], [data-messaging-emoji-toggle]').attr('aria-expanded', 'false');
    };

    const appendMessage = (message, isNew = true) => {
        const conversation = getConversation();
        $thread.append(createMessageMarkup(message, conversation, version, isNew));
        $thread.stop(true).animate({ scrollTop: $thread.prop('scrollHeight') }, 360);
    };

    const showTypingReply = () => {
        window.clearTimeout(typingTimer);
        const conversation = getConversation();
        const $typing = $(`<div class="messaging-live-typing">${avatarMarkup(conversation)}<span><i></i><i></i><i></i></span></div>`);
        $thread.append($typing);
        $thread.stop(true).animate({ scrollTop: $thread.prop('scrollHeight') }, 250);
        typingTimer = window.setTimeout(() => {
            $typing.fadeOut(160, () => $typing.remove());
            appendMessage({ type: 'incoming', text: version === 'v1' ? 'Got it, thanks! I will get back to you shortly.' : 'Perfect, thanks for the update 👍', time: 'Sent just now' }, true);
        }, 1250);
    };

    $root
        .on('click.messagingComposer', '[data-messaging-attachment-toggle]', function () {
            const opening = $composer.find('[data-messaging-attachment-menu]').prop('hidden');
            closePopovers();
            $composer.find('[data-messaging-attachment-menu]').prop('hidden', !opening);
            $(this).attr('aria-expanded', String(opening));
        })
        .on('click.messagingComposer', '[data-messaging-emoji-toggle]', function () {
            const opening = $composer.find('[data-messaging-emoji-menu]').prop('hidden');
            closePopovers();
            $composer.find('[data-messaging-emoji-menu]').prop('hidden', !opening);
            $(this).attr('aria-expanded', String(opening));
        })
        .on('click.messagingComposer', '[data-messaging-emoji]', function () {
            $input.val(`${$input.val()}${$(this).text()}`).trigger('focus');
            closePopovers();
        })
        .on('click.messagingComposer', '[data-messaging-attachment]', function () {
            const type = String($(this).attr('data-messaging-attachment'));
            closePopovers();
            if (type === 'video') {
                actions.openDialog({ trigger: this, eyebrow: 'Messaging media', title: 'Embed a video', icon: 'video', confirmText: 'Add video', body: '<label class="dashboard-action-field"><span>Video URL</span><input type="url" placeholder="https://..."></label>', onConfirm: () => { actions.closeDialog(); $composer.find('[data-messaging-file]').text('Video link added').prop('hidden', false); actions.announce('Video added to your message.'); } });
            } else if (type === 'link') {
                actions.openDialog({ trigger: this, eyebrow: 'Messaging link', title: 'Post a link', icon: 'share', confirmText: 'Add link', body: '<label class="dashboard-action-field"><span>Link URL</span><input type="url" placeholder="https://..."></label>', onConfirm: () => { actions.closeDialog(); $composer.find('[data-messaging-file]').text('Link attached').prop('hidden', false); actions.announce('Link added to your message.'); } });
            } else {
                const label = type === 'images' ? '2 images attached' : 'project-brief.pdf attached';
                $composer.find('[data-messaging-file]').text(label).prop('hidden', false);
                actions.announce(label);
            }
        })
        .on('submit.messagingComposer', '[data-messaging-composer]', function (event) {
            event.preventDefault();
            const text = normalise($input.val());
            if (!text) {
                $composer.addClass('is-invalid');
                window.setTimeout(() => $composer.removeClass('is-invalid'), 420);
                actions.announce('Write a message before sending.');
                return;
            }
            appendMessage({ type: 'outgoing', text: actions.escapeHtml(text), time: 'Sent just now' });
            $input.val('');
            $composer.find('[data-messaging-file]').prop('hidden', true).text('');
            actions.announce('Message sent.');
            showTypingReply();
        })
        .on('keydown.messagingComposer', '[data-messaging-input]', function (event) {
            if (event.key === 'Enter' && !event.shiftKey) {
                event.preventDefault();
                $composer.trigger('submit');
            }
        });

    $(document).on(`click.messagingComposer${version}`, (event) => {
        if (!$(event.target).closest('.messaging-composer').length) closePopovers();
    });

    return { closePopovers };
};

const openCall = (actions, trigger, name, type) => {
    const isVideo = type === 'video';
    actions.openDialog({
        trigger,
        eyebrow: isVideo ? 'Video call' : 'Audio call',
        title: `${isVideo ? 'Video call with' : 'Calling'} ${name}`,
        icon: isVideo ? 'video' : 'phone',
        confirmText: 'End call',
        body: `<div class="messaging-call-stage"><span>${name.split(/\s+/).map((part) => part[0]).slice(0, 2).join('')}</span><i></i><p>${isVideo ? 'Camera preview is ready' : 'Connecting securely…'}</p></div>`,
        onConfirm: () => { actions.closeDialog(); actions.announce('Call ended.'); },
    });
};

const openPhotoGallery = (actions, trigger) => actions.openDrawer({
    trigger,
    eyebrow: 'Conversation media',
    title: 'Shared photos',
    body: `<div class="messaging-drawer-gallery">${Array.from({ length: 12 }, (_, index) => `<button type="button" class="is-photo-${index + 1}" aria-label="Shared photo ${index + 1}"><span></span></button>`).join('')}</div>`,
});

export const initializeMessagingV1 = ($dashboard, actions) => {
    const $app = $dashboard.find('[data-messaging-v1]').first();
    if (!$app.length || $app.data('messaging-ready')) return;
    $app.data('messaging-ready', true);

    const source = JSON.parse($app.find('[data-messaging-data]').text() || '[]');
    const conversations = new Map(source.map((item) => [Number(item.id), item]));
    const $thread = $app.find('[data-v1-thread]');
    const $loader = $app.find('[data-messaging-loader]');
    const mobileQuery = window.matchMedia('(max-width: 820px)');
    let activeId = 2;
    let renderTimer = null;

    const setMobileList = (open) => {
        const shouldOpen = Boolean(open) && mobileQuery.matches;
        $app.toggleClass('is-mobile-list-open', shouldOpen);
        $app.find('[data-v1-mobile-list-open]').attr('aria-expanded', String(shouldOpen));
        $app.find('[data-v1-panel]').attr('aria-hidden', String(mobileQuery.matches ? !shouldOpen : !$app.hasClass('is-panel-open')));
    };

    const setMobileDetail = (open) => {
        const shouldOpen = Boolean(open) && mobileQuery.matches;
        $app.find('[data-v1-detail]').toggleClass('is-mobile-active', shouldOpen).attr('aria-hidden', String(mobileQuery.matches && !shouldOpen));
        $app.find('[data-v1-detail-open]').attr('aria-expanded', String(shouldOpen));
    };

    const syncResponsiveState = () => {
        if (mobileQuery.matches) {
            $app.removeClass('is-panel-open');
            $app.find('[data-v1-panel-toggle]').attr('aria-expanded', 'false');
            setMobileList(false);
            setMobileDetail(false);
        } else {
            $app.removeClass('is-mobile-list-open');
            $app.find('[data-v1-mobile-list-open]').attr('aria-expanded', 'false');
            $app.find('[data-v1-panel]').attr('aria-hidden', String(!$app.hasClass('is-panel-open')));
            $app.find('[data-v1-detail]').removeClass('is-mobile-active').removeAttr('aria-hidden');
            $app.find('[data-v1-detail-open]').attr('aria-expanded', 'false');
        }
    };

    const getConversation = () => conversations.get(activeId) || source[0];
    const updateAvatar = ($target, conversation, marker, large = false) => {
        $target.replaceWith($(avatarMarkup(conversation, large ? 'is-large' : '')).attr(marker, ''));
    };

    const renderThread = (conversation) => {
        $thread.empty();
        conversation.messages.forEach((message) => {
            if (message.type === 'day') $thread.append(`<li class="messaging-day"><span>${actions.escapeHtml(message.text)}</span></li>`);
            else $thread.append(createMessageMarkup({ ...message, text: actions.escapeHtml(message.text) }, conversation, 'v1'));
        });
        $thread.scrollTop($thread.prop('scrollHeight'));
    };

    const renderConversation = (id, { animate = true, announce = true } = {}) => {
        const conversation = conversations.get(Number(id));
        if (!conversation) return;
        activeId = Number(id);
        $app.find('[data-v1-conversation]').removeClass('is-active').filter(`[data-v1-conversation="${activeId}"]`).addClass('is-active').find('b').remove();
        $app.find('[data-v1-header-name]').text(conversation.name);
        $app.find('[data-v1-header-role]').text(conversation.role);
        $app.find('[data-v1-detail-name]').text(conversation.name);
        $app.find('[data-v1-detail-role]').text(conversation.role);
        updateAvatar($app.find('[data-v1-header-avatar]'), conversation, 'data-v1-header-avatar');
        updateAvatar($app.find('[data-v1-detail-avatar]'), conversation, 'data-v1-detail-avatar', true);
        window.clearTimeout(renderTimer);
        const finish = () => { renderThread(conversation); $loader.removeClass('is-active'); };
        if (animate) { $loader.addClass('is-active'); renderTimer = window.setTimeout(finish, 180); } else finish();
        setMobileList(false);
        if (announce) actions.announce(`Conversation with ${conversation.name} opened.`);
    };

    const closeNewConversation = () => {
        $app.find('[data-v1-new-form]').prop('hidden', true);
        $app.find('[data-v1-new-search]').val('');
        $app.find('[data-v1-new-results]').prop('hidden', true).empty();
    };

    const openNewConversation = () => {
        $app.find('[data-v1-new-form]').prop('hidden', false);
        $app.find('[data-v1-new-search]').val('').trigger('focus').trigger('input');
        setMobileList(false);
    };

    initializeComposer($app, actions, 'v1', getConversation);

    $app
        .on('click.messagingV1', '[data-v1-panel-toggle]', function () {
            const opening = !$app.hasClass('is-panel-open');
            $app.toggleClass('is-panel-open', opening);
            $(this).attr('aria-expanded', String(opening));
            $app.find('[data-v1-panel]').attr('aria-hidden', String(!opening));
        })
        .on('click.messagingV1', '[data-v1-panel-close]', () => {
            if (mobileQuery.matches) setMobileList(false);
            else $app.removeClass('is-panel-open').find('[data-v1-panel-toggle]').attr('aria-expanded', 'false');
            if (!mobileQuery.matches) $app.find('[data-v1-panel]').attr('aria-hidden', 'true');
        })
        .on('click.messagingV1', '[data-v1-mobile-list-open]', () => {
            if ($('.admin-shell').hasClass('is-sidebar-open')) $('[data-sidebar-toggle]').first().trigger('click');
            setMobileDetail(false);
            setMobileList(true);
        })
        .on('click.messagingV1', '[data-v1-overlay]', () => setMobileList(false))
        .on('click.messagingV1', '[data-v1-conversation]', function () { renderConversation($(this).attr('data-v1-conversation')); })
        .on('click.messagingV1', '[data-v1-new-conversation]', openNewConversation)
        .on('click.messagingV1', '[data-v1-new-close]', closeNewConversation)
        .on('input.messagingV1', '[data-v1-new-search]', function () {
            const query = lower($(this).val());
            const results = source.filter((item) => !query || lower(`${item.name} ${item.role}`).includes(query)).slice(0, 5);
            const $results = $app.find('[data-v1-new-results]').empty();
            results.forEach((item) => $results.append(`<button type="button" data-v1-new-select="${item.id}">${avatarMarkup(item)}<span><strong>${actions.escapeHtml(item.name)}</strong><small>${actions.escapeHtml(item.role)}</small></span></button>`));
            $results.prop('hidden', results.length === 0);
        })
        .on('click.messagingV1', '[data-v1-new-select]', function () { closeNewConversation(); renderConversation($(this).attr('data-v1-new-select')); })
        .on('click.messagingV1', '[data-v1-detail-open]', () => {
            if ($('.admin-shell').hasClass('is-sidebar-open')) $('[data-sidebar-toggle]').first().trigger('click');
            setMobileList(false);
            setMobileDetail(true);
        })
        .on('click.messagingV1', '[data-v1-detail-close]', () => setMobileDetail(false))
        .on('click.messagingV1', '[data-messaging-call]', function () { openCall(actions, this, getConversation().name, String($(this).attr('data-messaging-call'))); })
        .on('click.messagingV1', '[data-messaging-view-photos]', function () { openPhotoGallery(actions, this); })
        .on('click.messagingV1', '[data-messaging-photo]', function () { actions.openDialog({ trigger: this, eyebrow: 'Shared media', title: `Photo ${$(this).attr('data-messaging-photo')}`, icon: 'media', confirmText: 'Close', body: '<div class="messaging-photo-preview"><span></span></div>', onConfirm: actions.closeDialog }); });

    $(document)
        .on('click.messagingV1Navigation', '[data-sidebar-toggle], [data-sidebar-panel-target]', () => {
            if (!mobileQuery.matches) return;
            setMobileList(false);
            setMobileDetail(false);
        })
        .on('keydown.messagingV1', (event) => {
            if (event.key !== 'Escape') return;
            if (!$app.find('[data-v1-new-form]').prop('hidden')) closeNewConversation();
            else if ($app.find('[data-v1-detail]').hasClass('is-mobile-active')) setMobileDetail(false);
            else setMobileList(false);
        });

    mobileQuery.addEventListener('change', syncResponsiveState);

    renderConversation(activeId, { animate: false, announce: false });
    syncResponsiveState();
};

export const initializeMessagingV2 = ($dashboard, actions) => {
    const $app = $dashboard.find('[data-messaging-v2]').first();
    if (!$app.length || $app.data('messaging-ready')) return;
    $app.data('messaging-ready', true);

    const source = JSON.parse($app.find('[data-messaging-data]').text() || '[]');
    const conversations = new Map(source.map((item) => [Number(item.id), item]));
    const $thread = $app.find('[data-v2-thread]');
    const $loader = $app.find('[data-messaging-loader]');
    let activeId = 3;
    let renderTimer = null;

    const getConversation = () => conversations.get(activeId) || source[0];
    const updateDetailAvatar = (conversation) => {
        const $current = $app.find('[data-v2-detail-avatar]');
        const $next = $(avatarMarkup(conversation, 'is-large')).attr('data-v2-detail-avatar', '');
        $current.replaceWith($next);
    };

    const renderMembers = (conversation) => {
        const $members = $app.find('[data-v2-members]').empty();
        if (conversation.group) {
            [{ initials: 'MW', avatar: '25' }, { initials: 'AC', avatar: '9' }, { initials: 'EC', avatar: '18' }].forEach((member) => $members.append(avatarMarkup(member)));
            $members.append('<span>4</span>');
        } else $members.append(avatarMarkup(conversation));
    };

    const renderThread = (conversation) => {
        $thread.empty();
        conversation.messages.forEach((message) => {
            const safe = { ...message, texts: message.texts.map((text) => actions.escapeHtml(text)) };
            $thread.append(createMessageMarkup(safe, conversation, 'v2'));
        });
        $thread.scrollTop($thread.prop('scrollHeight'));
    };

    const renderConversation = (id, { animate = true, announce = true } = {}) => {
        const conversation = conversations.get(Number(id));
        if (!conversation) return;
        activeId = Number(id);
        $app.find('[data-v2-conversation]').removeClass('is-active').filter(`[data-v2-conversation="${activeId}"]`).addClass('is-active');
        $app.find('[data-v2-title], [data-v2-detail-name]').text(conversation.name);
        $app.find('[data-v2-detail-role]').text(conversation.role);
        updateDetailAvatar(conversation);
        renderMembers(conversation);
        window.clearTimeout(renderTimer);
        const finish = () => { renderThread(conversation); $loader.removeClass('is-active'); };
        if (animate) { $loader.addClass('is-active'); renderTimer = window.setTimeout(finish, 180); } else finish();
        $app.removeClass('is-mobile-list-open');
        if (announce) actions.announce(`${conversation.name} opened.`);
    };

    initializeComposer($app, actions, 'v2', getConversation);

    $app
        .on('click.messagingV2', '[data-v2-conversation]', function () { renderConversation($(this).attr('data-v2-conversation')); })
        .on('input.messagingV2', '[data-v2-search]', function () {
            const query = lower($(this).val());
            let visible = 0;
            $app.find('[data-v2-conversation]').each(function () {
                const conversation = conversations.get(Number($(this).attr('data-v2-conversation')));
                const match = !query || lower(`${conversation.name} ${conversation.role} ${conversation.preview}`).includes(query);
                $(this).toggleClass('is-filtered', !match);
                if (match) visible += 1;
            });
            $app.find('[data-v2-search-clear]').prop('hidden', !query);
            $app.find('[data-v2-search-empty]').prop('hidden', visible !== 0);
        })
        .on('click.messagingV2', '[data-v2-search-clear]', () => $app.find('[data-v2-search]').val('').trigger('input').trigger('focus'))
        .on('click.messagingV2', '[data-v2-list-toggle]', () => $app.addClass('is-mobile-list-open').removeClass('is-mobile-detail-open'))
        .on('click.messagingV2', '[data-v2-list-close], [data-v2-overlay]', () => $app.removeClass('is-mobile-list-open is-mobile-detail-open'))
        .on('click.messagingV2', '[data-v2-detail-toggle]', () => $app.addClass('is-mobile-detail-open').removeClass('is-mobile-list-open'))
        .on('click.messagingV2', '[data-v2-detail-close]', () => $app.removeClass('is-mobile-detail-open'))
        .on('click.messagingV2', '[data-messaging-call]', function () { openCall(actions, this, getConversation().name, String($(this).attr('data-messaging-call'))); })
        .on('click.messagingV2', '[data-messaging-view-photos]', function () { openPhotoGallery(actions, this); })
        .on('click.messagingV2', '[data-messaging-photo]', function () { actions.openDialog({ trigger: this, eyebrow: 'Shared media', title: `Photo ${$(this).attr('data-messaging-photo')}`, icon: 'media', confirmText: 'Close', body: '<div class="messaging-photo-preview"><span></span></div>', onConfirm: actions.closeDialog }); });

    $(document).on('keydown.messagingV2', (event) => {
        if (event.key === 'Escape') $app.removeClass('is-mobile-list-open is-mobile-detail-open');
    });

    renderConversation(activeId, { animate: false, announce: false });
};
