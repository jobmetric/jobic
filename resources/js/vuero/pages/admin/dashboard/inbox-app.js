import $ from '../../../admin/jquery';
import { iconMarkup } from '../../../../common/icons';

const normalize = (value) => String(value || '').trim().toLocaleLowerCase();

export const initializeInboxApp = ($dashboard, actions) => {
    const $app = $dashboard.find('[data-inbox-app]').first();
    if (!$app.length || $app.data('inbox-ready')) return;

    $app.data('inbox-ready', true);

    const source = JSON.parse($app.find('[data-inbox-data]').text() || '[]');
    const messages = new Map(source.map((message) => [Number(message.id), message]));
    const $list = $app.find('[data-inbox-message-list]');
    const $detail = $app.find('[data-inbox-detail]');
    const $loader = $app.find('[data-inbox-loader]');
    const mobileQuery = window.matchMedia('(max-width: 1024px)');
    let activeFolder = 'inbox';
    let activeId = 1;
    let hideRead = false;
    let sortDirection = 1;
    let renderTimer = null;
    let lastComposeTrigger = null;

    const getCards = () => $list.find('[data-inbox-message]');
    const getMessage = (id = activeId) => messages.get(Number(id));
    const isCardVisible = (element) => Boolean(element) && !$(element).is('.is-filtered, .is-read-hidden, .is-deleted');

    const createIcon = (name, size = 13) => iconMarkup(name, size);

    const initials = (name) => String(name || '')
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2)
        .map((part) => part.charAt(0))
        .join('')
        .toUpperCase();

    const syncImageFallbacks = ($scope = $app) => {
        $scope.find('.inbox-avatar img').each(function () {
            const $image = $(this);
            if ($image.data('fallback-bound')) return;
            const source = $image.attr('data-avatar-src');
            if (!source) return;

            $image.data('fallback-bound', true);
            const probe = new Image();
            probe.onload = () => $image.attr('src', source).prop('hidden', false);
            probe.onerror = () => $image.removeAttr('src').prop('hidden', true);
            probe.src = source;
        });
    };

    const closeDropdownContaining = (element) => {
        const $dropdown = $(element).closest('[data-dropdown]');
        $dropdown.removeClass('is-open');
        $dropdown.find('[data-dropdown-toggle]').attr('aria-expanded', 'false');
        $dropdown.find('[data-dropdown-menu]').prop('hidden', true);
    };

    const syncSelectionControl = () => {
        const $visibleChecks = getCards().filter((_, element) => isCardVisible(element)).find('[data-inbox-message-check]');
        const allChecked = $visibleChecks.length > 0 && $visibleChecks.filter(':checked').length === $visibleChecks.length;
        $app.find('[data-inbox-check-all]').attr('aria-pressed', String(allChecked));
    };

    const messageMatchesFolder = (message, folder) => Boolean(message && Array.isArray(message.mailboxes) && message.mailboxes.includes(folder));

    const filterMessages = ({ keepSelection = true } = {}) => {
        const query = normalize($app.find('[data-inbox-search]').val());
        let visibleCount = 0;

        getCards().each(function () {
            const $card = $(this);
            const message = getMessage($card.attr('data-inbox-message'));
            const matchesFolder = messageMatchesFolder(message, activeFolder);
            const matchesQuery = !query || normalize(`${message.name} ${message.email} ${message.subject}`).includes(query);
            const hiddenAsRead = hideRead && !message.unread;
            const deleted = Boolean(message.permanentlyDeleted);

            $card.toggleClass('is-filtered', !matchesFolder || !matchesQuery);
            $card.toggleClass('is-read-hidden', hiddenAsRead);
            $card.toggleClass('is-deleted', deleted);
            if (matchesFolder && matchesQuery && !hiddenAsRead && !deleted) visibleCount += 1;
        });

        $app.find('[data-inbox-search-clear]').prop('hidden', !query);
        $app.find('[data-inbox-list-empty]').prop('hidden', visibleCount !== 0);
        syncSelectionControl();

        const activeCard = getCards().filter(`[data-inbox-message="${activeId}"]`).get(0);
        if (!keepSelection || !isCardVisible(activeCard)) {
            const nextId = Number(getCards().filter((_, element) => isCardVisible(element)).first().attr('data-inbox-message'));
            if (Number.isFinite(nextId)) renderMessage(nextId, { openMobile: false, animate: false });
        }
    };

    const renderAttachments = (attachments = []) => {
        const $attachments = $app.find('[data-inbox-attachments]').empty();
        attachments.forEach((filename) => {
            $('<button>', { type: 'button', 'data-inbox-download': filename })
                .append($('<span>').text(filename))
                .append($('<i>').html(createIcon('download')))
                .appendTo($attachments);
        });
    };

    const applyMessageToDetail = (message) => {
        const $avatar = $app.find('[data-inbox-detail-avatar]');
        const attachmentCount = message.attachments?.length || 0;

        $app.find('[data-inbox-detail-subject]').text(message.subject);
        $app.find('[data-inbox-detail-email]').text(message.email);
        $app.find('[data-inbox-detail-date]').text(message.sent_at);
        $app.find('[data-inbox-detail-greeting]').text(message.greeting);
        $app.find('[data-inbox-detail-sender]').text(message.name);
        $app.find('[data-inbox-detail-attachment-count]').toggleClass('is-empty', attachmentCount === 0).find('b').text(attachmentCount);
        $avatar
            .removeAttr('src')
            .attr('data-avatar-src', `https://media.cssninja.io/content/avatars/${message.avatar}.jpg`)
            .prop('hidden', true)
            .removeData('fallback-bound');
        $avatar.siblings('span').text(initials(message.name));

        const $body = $app.find('[data-inbox-detail-body]').empty();
        (message.body || []).forEach((paragraph) => $('<p>').text(paragraph).appendTo($body));
        renderAttachments(message.attachments || []);
        $app.find('[data-inbox-reply]').val('');
        $app.find('[data-inbox-reply-status]').text('');
        $app.find('[data-inbox-message-action]').removeClass('is-active').attr('aria-pressed', 'false');
        syncImageFallbacks($detail);
    };

    function renderMessage(id, { openMobile = true, animate = true } = {}) {
        const message = getMessage(id);
        if (!message) return;

        activeId = Number(id);
        message.unread = false;
        const $card = getCards().filter(`[data-inbox-message="${activeId}"]`);
        getCards().removeClass('is-selected');
        $card.addClass('is-selected').removeClass('is-unread');

        window.clearTimeout(renderTimer);
        const finish = () => {
            applyMessageToDetail(message);
            $app.find('[data-inbox-detail-message]').removeClass('is-switching');
            $loader.removeClass('is-active');
        };
        if (animate) {
            $loader.addClass('is-active');
            $app.find('[data-inbox-detail-message]').addClass('is-switching');
            renderTimer = window.setTimeout(finish, 170);
        } else finish();

        if (mobileQuery.matches && openMobile) $detail.addClass('is-mobile-active');
        syncSelectionControl();
    }

    const setFolder = (folder) => {
        activeFolder = folder;
        hideRead = false;
        $app.find('[data-inbox-folder]').removeClass('is-active').attr('aria-pressed', 'false');
        $app.find(`[data-inbox-folder="${folder}"]`).addClass('is-active').attr('aria-pressed', 'true');
        $app.find('[data-inbox-search]').val('');
        $app.find('[data-inbox-sidebar]').removeClass('is-mobile-active');
        $detail.removeClass('is-mobile-active');
        filterMessages({ keepSelection: false });
        actions.announce(`${folder.charAt(0).toUpperCase()}${folder.slice(1)} folder opened.`);
    };

    const openCompose = ({ to = '', subject = '', trigger = null } = {}) => {
        const $compose = $app.find('[data-inbox-compose]');
        lastComposeTrigger = trigger;
        $compose.addClass('is-open').attr('aria-hidden', 'false');
        $app.find('[data-inbox-compose-to]').val(to);
        $app.find('[data-inbox-compose-subject]').val(subject);
        $app.find('[data-inbox-compose-body]').val('');
        $app.find('[data-inbox-compose-file]').prop('hidden', true).text('');
        window.setTimeout(() => $app.find(to ? '[data-inbox-compose-subject]' : '[data-inbox-compose-to]').trigger('focus'), 250);
    };

    const closeCompose = () => {
        $app.find('[data-inbox-compose]').removeClass('is-open').attr('aria-hidden', 'true');
        if (lastComposeTrigger) $(lastComposeTrigger).trigger('focus');
        lastComposeTrigger = null;
    };

    const createMessageCard = (message) => {
        const $card = $('<article>', { class: 'inbox-message-card is-new', tabindex: 0, role: 'button', 'data-inbox-message': message.id, 'data-inbox-mailboxes': message.mailboxes.join(','), 'data-inbox-search-text': normalize(`${message.name} ${message.email} ${message.subject}`), 'aria-label': `Open message to ${message.name}` });
        const $checkbox = $('<label>', { class: 'inbox-checkbox', 'aria-label': `Select message to ${message.name}` })
            .append($('<input>', { type: 'checkbox', value: message.id, 'data-inbox-message-check': '' }))
            .append($('<span>').html(createIcon('check', 12)));
        const $meta = $('<span>', { class: 'inbox-message-card__meta' }).append($('<strong>').text(message.name)).append($('<small>').text(`<${message.email}>`)).append($('<span>').text(message.subject));
        return $card.append($checkbox, $meta, $('<time>').text(message.date));
    };

    $app
        .on('click.inboxApp', '[data-inbox-folder]', function () { setFolder(String($(this).attr('data-inbox-folder'))); })
        .on('click.inboxApp', '[data-inbox-message]', function (event) {
            if ($(event.target).closest('.inbox-checkbox').length) return;
            renderMessage(Number($(this).attr('data-inbox-message')));
        })
        .on('keydown.inboxApp', '[data-inbox-message]', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') return;
            if ($(event.target).is('input')) return;
            event.preventDefault();
            $(this).trigger('click');
        })
        .on('click.inboxApp', '.inbox-checkbox', (event) => event.stopPropagation())
        .on('change.inboxApp', '[data-inbox-message-check]', syncSelectionControl)
        .on('click.inboxApp', '[data-inbox-check-all]', function () {
            const selectAll = $(this).attr('aria-pressed') !== 'true';
            getCards().filter((_, element) => isCardVisible(element)).find('[data-inbox-message-check]').prop('checked', selectAll);
            syncSelectionControl();
            actions.announce(selectAll ? 'All visible messages selected.' : 'Message selection cleared.');
        })
        .on('input.inboxApp', '[data-inbox-search]', () => filterMessages())
        .on('click.inboxApp', '[data-inbox-search-clear]', () => $app.find('[data-inbox-search]').val('').trigger('input').trigger('focus'))
        .on('click.inboxApp', '[data-inbox-contact-search-open]', () => {
            $app.find('[data-inbox-contact-title], [data-inbox-contact-search-open]').prop('hidden', true);
            $app.find('[data-inbox-contact-search-wrap], [data-inbox-contact-search-close]').prop('hidden', false);
            $app.find('[data-inbox-contact-search]').trigger('focus');
        })
        .on('click.inboxApp', '[data-inbox-contact-search-close]', () => {
            $app.find('[data-inbox-contact-search]').val('').trigger('input');
            $app.find('[data-inbox-contact-title], [data-inbox-contact-search-open]').prop('hidden', false);
            $app.find('[data-inbox-contact-search-wrap], [data-inbox-contact-search-close]').prop('hidden', true);
        })
        .on('input.inboxApp', '[data-inbox-contact-search]', function () {
            const query = normalize($(this).val());
            let visible = 0;
            $app.find('[data-inbox-contact]').each(function () {
                const match = !query || normalize(`${$(this).attr('data-contact-name')} ${$(this).attr('data-contact-email')}`).includes(query);
                $(this).toggleClass('is-filtered', !match);
                if (match) visible += 1;
            });
            $app.find('[data-inbox-contact-empty]').prop('hidden', visible !== 0);
        })
        .on('click.inboxApp', '[data-inbox-contact]', function () { openCompose({ to: $(this).attr('data-contact-email'), trigger: this }); })
        .on('click.inboxApp', '[data-inbox-compose-open]', function () { openCompose({ trigger: this }); })
        .on('click.inboxApp', '[data-inbox-compose-close]', closeCompose)
        .on('click.inboxApp', '[data-inbox-compose-attach]', () => {
            $app.find('[data-inbox-compose-file]').text('project-document.pdf attached').prop('hidden', false);
            actions.announce('Attachment added to the message.');
        })
        .on('submit.inboxApp', '[data-inbox-compose-form]', function (event) {
            event.preventDefault();
            const to = String($app.find('[data-inbox-compose-to]').val() || '').trim();
            const subject = String($app.find('[data-inbox-compose-subject]').val() || '').trim();
            const body = String($app.find('[data-inbox-compose-body]').val() || '').trim();
            if (!to || !subject || !body) {
                $(this).addClass('is-invalid');
                window.setTimeout(() => $(this).removeClass('is-invalid'), 450);
                actions.announce('Complete the recipient, subject, and message fields.');
                return;
            }
            const id = Date.now();
            const message = { id, name: to.split('@')[0].replace(/[._-]/g, ' '), email: to, avatar: 1, subject, date: 'Now', sent_at: 'Sent just now', unread: false, mailboxes: ['sent'], greeting: `To ${to},`, body: [body], attachments: $app.find('[data-inbox-compose-file]').prop('hidden') ? [] : ['project-document.pdf'] };
            messages.set(id, message);
            $list.prepend(createMessageCard(message));
            closeCompose();
            actions.announce(`Message sent to ${to}.`);
            if (activeFolder === 'sent') filterMessages({ keepSelection: false });
        })
        .on('click.inboxApp', '[data-inbox-sidebar-open]', () => $app.find('[data-inbox-sidebar]').addClass('is-mobile-active'))
        .on('click.inboxApp', '[data-inbox-sidebar-close]', () => $app.find('[data-inbox-sidebar]').removeClass('is-mobile-active'))
        .on('click.inboxApp', '[data-inbox-detail-close]', () => $detail.removeClass('is-mobile-active'))
        .on('click.inboxApp', '[data-inbox-list-action]', function () {
            const action = String($(this).attr('data-inbox-list-action'));
            closeDropdownContaining(this);
            if (action === 'read') {
                getCards().filter((_, element) => isCardVisible(element)).each(function () {
                    const message = getMessage($(this).attr('data-inbox-message'));
                    message.unread = false;
                    $(this).removeClass('is-unread');
                });
                actions.announce('All visible messages marked as read.');
            } else if (action === 'hide-read') {
                hideRead = !hideRead;
                $(this).toggleClass('is-active', hideRead);
                filterMessages({ keepSelection: false });
                actions.announce(hideRead ? 'Read messages hidden.' : 'Read messages are visible.');
            } else {
                const cards = getCards().get();
                sortDirection *= -1;
                cards.sort((left, right) => {
                    const leftMessage = getMessage($(left).attr('data-inbox-message'));
                    const rightMessage = getMessage($(right).attr('data-inbox-message'));
                    return action === 'user' ? leftMessage.name.localeCompare(rightMessage.name) * sortDirection : (Number(leftMessage.id) - Number(rightMessage.id)) * sortDirection;
                });
                $(cards).addClass('is-switching').appendTo($list);
                window.setTimeout(() => $(cards).removeClass('is-switching'), 180);
                $app.find('[data-inbox-list-empty]').appendTo($list);
                actions.announce(action === 'user' ? 'Messages sorted by sender.' : 'Messages sorted by date.');
            }
        })
        .on('click.inboxApp', '[data-inbox-global-action]', function () {
            const action = String($(this).attr('data-inbox-global-action'));
            closeDropdownContaining(this);
            if (action === 'refresh') {
                getCards().addClass('is-switching');
                window.setTimeout(() => getCards().removeClass('is-switching'), 420);
                actions.announce('Inbox refreshed.');
            } else if (action === 'notifications') {
                actions.openDrawer({ title: 'Inbox notifications', eyebrow: 'Latest activity', body: '<div class="dashboard-search-results"><p><strong>2 new messages</strong><span>Received this morning</span></p><p><strong>Project review</strong><span>Reminder for Thursday</span></p><p><strong>Storage</strong><span>78% of your mailbox is available</span></p></div>' });
            } else if (action === 'invite') {
                actions.openDialog({ title: 'Invite people', eyebrow: 'Inbox contacts', body: '<p class="soft-copy">Create a new contact invitation and share access to your workspace.</p>', confirmText: 'Send invitation', icon: 'user-plus', trigger: this, onConfirm: () => { actions.closeDialog(); actions.announce('Invitation sent.'); } });
            } else window.location.assign('/vuero/admin/profile-settings');
        })
        .on('click.inboxApp', '[data-inbox-detail-menu]', function () {
            const action = String($(this).attr('data-inbox-detail-menu'));
            const message = getMessage();
            closeDropdownContaining(this);
            if (!message) return;
            if (action === 'unread') {
                message.unread = true;
                getCards().filter(`[data-inbox-message="${activeId}"]`).addClass('is-unread');
                actions.announce('Message marked as unread.');
            } else if (action === 'archive') {
                if (activeFolder !== 'drafts') {
                    message.mailboxes = message.mailboxes.filter((folder) => folder !== activeFolder);
                    if (!message.mailboxes.includes('drafts')) message.mailboxes.push('drafts');
                    filterMessages({ keepSelection: false });
                    $detail.removeClass('is-mobile-active');
                }
                actions.announce('Message moved to drafts.');
            } else {
                if (activeFolder === 'trash') message.permanentlyDeleted = true;
                else if (!message.mailboxes.includes('trash')) message.mailboxes.push('trash');
                message.mailboxes = message.mailboxes.filter((folder) => folder !== activeFolder || folder === 'trash');
                filterMessages({ keepSelection: false });
                $detail.removeClass('is-mobile-active');
                actions.announce(activeFolder === 'trash' ? 'Message permanently deleted.' : 'Message moved to trash.');
            }
        })
        .on('click.inboxApp', '[data-inbox-message-action]', function () {
            const action = String($(this).attr('data-inbox-message-action'));
            if (action === 'reply' || action === 'comment') {
                $app.find('[data-inbox-reply]').trigger('focus').get(0)?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                actions.announce(action === 'reply' ? 'Reply editor opened.' : 'Comment editor opened.');
            } else if (action === 'note') {
                actions.openDialog({ title: 'Message note', eyebrow: 'Private note', body: '<textarea class="textarea" rows="5" placeholder="Write a private note..."></textarea>', confirmText: 'Save note', icon: 'content', trigger: this, onConfirm: () => { actions.closeDialog(); actions.announce('Private note saved.'); } });
            } else {
                const active = $(this).attr('aria-pressed') !== 'true';
                $(this).toggleClass('is-active', active).attr('aria-pressed', String(active));
                actions.announce(action === 'tag' ? (active ? 'Message tagged.' : 'Tag removed.') : (active ? 'Message locked.' : 'Message unlocked.'));
            }
        })
        .on('click.inboxApp', '[data-inbox-download]', function () {
            const filename = String($(this).attr('data-inbox-download'));
            const blob = new Blob([`Vuero Inbox demo attachment: ${filename}\n`], { type: 'text/plain' });
            const url = URL.createObjectURL(blob);
            const anchor = document.createElement('a');
            anchor.href = url;
            anchor.download = filename;
            anchor.click();
            window.setTimeout(() => URL.revokeObjectURL(url), 500);
            actions.announce(`${filename} downloaded.`);
        })
        .on('submit.inboxApp', '[data-inbox-reply-form]', function (event) {
            event.preventDefault();
            const $reply = $app.find('[data-inbox-reply]');
            const message = String($reply.val() || '').trim();
            if (!message) {
                $(this).addClass('is-invalid');
                window.setTimeout(() => $(this).removeClass('is-invalid'), 420);
                actions.announce('Write a reply before sending.');
                return;
            }
            $reply.val('');
            $app.find('[data-inbox-reply-status]').text('Reply sent just now');
            actions.announce(`Reply sent to ${getMessage()?.name || 'recipient'}.`);
        });

    $(document).on('keydown.inboxApp', (event) => {
        if (event.key !== 'Escape') return;
        if ($app.find('[data-inbox-compose]').hasClass('is-open')) closeCompose();
        else if ($app.find('[data-inbox-sidebar]').hasClass('is-mobile-active')) $app.find('[data-inbox-sidebar]').removeClass('is-mobile-active');
        else if ($detail.hasClass('is-mobile-active')) $detail.removeClass('is-mobile-active');
    });

    syncImageFallbacks();
    renderMessage(activeId, { openMobile: mobileQuery.matches, animate: false });
    filterMessages();
};
