import $ from '../../../admin/jquery';
import { iconMarkup } from '../../../../common/icons';

const text = ($element) => $.trim($element.text().replace(/\s+/g, ' '));

const openInfo = (actions, trigger, eyebrow, title, body, icon = 'content') => {
    actions.openDialog({ trigger, eyebrow, title, body, icon });
};

const bindShared = ($dashboard, actions) => {
    $dashboard.find('[data-business-dropdown-trigger]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $dropdown = $(this).closest('[data-business-dropdown]');
        const open = !$dropdown.hasClass('is-open');
        $dashboard.find('[data-business-dropdown]').removeClass('is-open').find('[data-business-dropdown-trigger]').attr('aria-expanded', 'false');
        $dropdown.toggleClass('is-open', open);
        $(this).attr('aria-expanded', String(open));
    });

    $(document).off('click.businessDropdown').on('click.businessDropdown', (event) => {
        if ($(event.target).closest('[data-business-dropdown]').length) return;
        $dashboard.find('[data-business-dropdown]').removeClass('is-open').find('[data-business-dropdown-trigger]').attr('aria-expanded', 'false');
    });

    $dashboard.find('[data-business-menu]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        openInfo(actions, this, 'Widget options', 'Dashboard widget', '<p class="dashboard-action-copy">Configure visibility, refresh frequency and access permissions for this reusable CMS widget.</p>', 'settings');
    });
};

const initializeCompany = ($dashboard, actions) => {
    $dashboard.find('[data-company-follow]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const following = !$(this).hasClass('is-following');
        $(this).toggleClass('is-following', following).attr('aria-label', following ? 'Unfollow U Learning' : 'Follow U Learning');
        this.innerHTML = iconMarkup(following ? 'check' : 'plus', 16);
        actions.announce(following ? 'U Learning is now followed.' : 'U Learning was removed from followed companies.');
    });

    $dashboard.find('[data-ticket-manage]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $ticket = $(this).closest('[data-ticket]');
        actions.openDialog({
            trigger: this,
            eyebrow: 'Support ticket',
            title: text($ticket.find('h3')),
            icon: 'ticket',
            confirmText: 'Assign to me',
            body: `<p class="dashboard-action-copy">${actions.escapeHtml(text($ticket.find('p')))}</p><div class="dashboard-feature-pills"><span>Open</span><span>Normal priority</span><span>${actions.escapeHtml(text($ticket.find('div > span')))}</span></div>`,
            onConfirm: () => { actions.closeDialog(); $ticket.addClass('is-assigned'); $(this).text('Assigned'); actions.announce('Ticket assigned successfully.'); },
        });
    });

    $dashboard.find('[data-ticket-view-all]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const items = $dashboard.find('[data-ticket]').map((_, item) => `<article><strong>${actions.escapeHtml(text($(item).find('h3')))}</strong><span>${actions.escapeHtml(text($(item).find('div > span')))}</span></article>`).get().join('');
        actions.openDrawer({ eyebrow: 'Support queue', title: 'All pending tickets', body: `<div class="dashboard-search-results">${items}</div>` });
    });
};

const initializeCourse = ($dashboard, actions) => {
    $dashboard.find('[data-course-schedule]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        actions.openDialog({ trigger: this, eyebrow: 'Week 6 schedule', title: 'January 6 to 10', icon: 'calendar', confirmText: 'Add reminder', body: '<div class="dashboard-agenda"><article><time>08:00</time><div><strong>Anatomy</strong><span>Lecture hall A · 45 min</span></div></article><article><time>10:30</time><div><strong>Immunology</strong><span>Laboratory 2 · 95 min</span></div></article><article><time>14:30</time><div><strong>Cardiology</strong><span>Lecture hall C · 95 min</span></div></article></div>', onConfirm: () => { actions.closeDialog(); actions.announce('Week 6 reminder added.'); } });
    });

    $dashboard.find('[data-course-open]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $card = $(this).closest('[data-course-card]');
        openInfo(actions, this, 'Course workspace', text($card.find('h2')), `<p class="dashboard-action-copy">${actions.escapeHtml(text($card.find('.course-card__summary')))}</p><div class="dashboard-feature-pills"><span>${actions.escapeHtml($card.data('course'))}</span><span>Week 6</span><span>Active class</span></div>`, 'content');
    });

    $dashboard.find('[data-course-discussion], [data-student-chat]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const person = $(this).closest('[data-student]').find('strong').text() || $(this).closest('[data-course-card]').data('course');
        actions.openDialog({ trigger: this, eyebrow: 'Course messages', title: `Chat · ${person}`, icon: 'message', confirmText: 'Send message', body: '<label class="dashboard-action-field"><span>Message</span><textarea rows="4" placeholder="Write your message..."></textarea></label>', onConfirm: () => { actions.closeDialog(); actions.announce('Message sent.'); } });
    });

    $dashboard.find('[data-course-recording], [data-course-assessment]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        openInfo(actions, this, 'Course material', text($(this).find('strong')), '<p class="dashboard-action-copy">The selected material is ready. Access and download permissions can be connected to the CMS policy layer.</p>', $(this).is('[data-course-recording]') ? 'media' : 'calendar');
    });

    $dashboard.find('[data-course-file], [data-file-menu]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $file = $(this).closest('[data-course-file]');
        actions.openDialog({ trigger: this, eyebrow: 'Course file', title: text($file.find('h3')), icon: 'content', confirmText: 'Download', body: `<p class="dashboard-action-copy">${actions.escapeHtml(text($file.find('p')))}</p>`, onConfirm: () => { actions.closeDialog(); actions.announce('Download prepared.'); } });
    });

    $dashboard.find('[data-file-action]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        const $button = $(this);
        const $file = $button.closest('[data-course-file]');
        const action = $button.data('file-action');
        const title = text($file.find('h3'));
        $button.closest('[data-business-dropdown]').removeClass('is-open').find('[data-business-dropdown-trigger]').attr('aria-expanded', 'false');
        if (action === 'download') { actions.announce(`${title} is ready to download.`); return; }
        if (action === 'upload') {
            actions.openDialog({ trigger: this, eyebrow: 'Course file', title: `Upload a new version`, icon: 'upload', confirmText: 'Upload file', body: `<p class="dashboard-action-copy">Replace <strong>${actions.escapeHtml(title)}</strong> while keeping its version history.</p><label class="dashboard-action-field"><span>Select file</span><input type="file"></label>`, onConfirm: () => { actions.closeDialog(); actions.announce('New file version uploaded.'); } });
            return;
        }
        if (action === 'permissions') { openInfo(actions, this, 'File permissions', title, '<p class="dashboard-action-copy">Manage student, instructor and administrator access to this file.</p><div class="dashboard-feature-pills"><span>Students · View</span><span>Instructors · Edit</span></div>', 'lock'); return; }
        if (action === 'share') { openInfo(actions, this, 'Share course file', title, '<label class="dashboard-action-field"><span>Share link</span><input type="text" value="https://jobic.test/course/files/week-6" readonly></label>', 'share'); return; }
        if (action === 'delete') {
            actions.openDialog({ trigger: this, eyebrow: 'Delete course file', title, icon: 'trash', confirmText: 'Delete file', body: '<p class="dashboard-action-copy">This removes the file from Week 6 resources. The CMS trash policy can keep it recoverable.</p>', onConfirm: () => { actions.closeDialog(); $file.slideUp(220, () => $file.remove()); actions.announce('Course file moved to trash.'); } });
        }
    });

    $dashboard.find('[data-student-search]').on('input.business', function () {
        const query = String($(this).val() || '').trim().toLowerCase();
        let matches = 0;
        $dashboard.find('[data-student]').each(function () {
            const visible = !query || String($(this).data('search')).includes(query);
            $(this).stop(true, true).fadeToggle(180, visible);
            if (visible) matches += 1;
        });
        $dashboard.find('[data-student-empty]').prop('hidden', matches > 0);
    });
};

const initializeFlights = ($dashboard, actions) => {
    const $results = $dashboard.find('[data-flight-result]');
    const refreshCount = () => {
        const count = $results.not('.is-filtered').length;
        $dashboard.find('[data-flight-count]').text(count);
        $dashboard.find('[data-flight-empty]').prop('hidden', count > 0);
    };
    const activeValues = (name) => $dashboard.find(`[data-flight-filter="${name}"] input:checked`).map((_, input) => String(input.value)).get();
    const applyFilters = () => {
        const stops = activeValues('stops');
        const luggage = activeValues('luggage');
        const query = String($dashboard.find('[data-flight-search]').val() || '').trim().toLowerCase();
        $results.addClass('is-refreshing');
        window.setTimeout(() => {
            $results.each(function () {
                const $item = $(this);
                const stopMatch = stops.includes('all') || !stops.length || stops.includes(String($item.data('stops')));
                const luggageMatch = luggage.includes('all') || !luggage.length || luggage.includes(String($item.data('luggage')));
                const searchMatch = !query || String($item.data('search')).includes(query);
                $item.toggleClass('is-filtered', !(stopMatch && luggageMatch && searchMatch)).removeClass('is-refreshing');
            });
            refreshCount();
        }, 220);
    };

    $dashboard.find('[data-flight-filter] input').on('change.business', function () {
        const $fieldset = $(this).closest('[data-flight-filter]');
        if (this.value === 'all' && this.checked) $fieldset.find('input').not(this).prop('checked', false);
        else if (this.checked) $fieldset.find('input[value="all"]').prop('checked', false);
        if (!$fieldset.find('input:checked').length) $fieldset.find('input[value="all"]').prop('checked', true);
        applyFilters();
    });
    $dashboard.find('[data-flight-search]').on('input.business', applyFilters);
    $dashboard.find('[data-flight-search-button]').on('click.business', (event) => { event.preventDefault(); event.stopPropagation(); applyFilters(); });
    $dashboard.find('[data-flight-topic]').on('click.business', function (event) { event.preventDefault(); event.stopPropagation(); $dashboard.find('[data-flight-search]').val($(this).data('flight-topic')); applyFilters(); });

    $dashboard.find('[data-flight-deal]').on('click.business', function (event) {
        event.preventDefault();
        event.stopPropagation();
        $(this).addClass('is-active').siblings().removeClass('is-active');
        const mode = $(this).data('flight-deal');
        const ordered = $results.get().sort((a, b) => mode === 'fastest' ? Number($(b).data('price')) - Number($(a).data('price')) : Number($(a).data('price')) - Number($(b).data('price')));
        $dashboard.find('[data-flight-results]').prepend(ordered);
        $(ordered).each((index, element) => { $(element).css('animation-delay', `${index * 45}ms`).addClass('is-revealed'); });
        actions.announce(`${text($(this).find('b'))} flights selected.`);
    });

    $dashboard.find('[data-flight-action]').on('click.business', function (event) {
        event.preventDefault(); event.stopPropagation();
        const action = $(this).data('flight-action');
        $(this).closest('[data-business-dropdown]').removeClass('is-open').find('[data-business-dropdown-trigger]').attr('aria-expanded', 'false');
        if (action === 'reload') {
            $results.removeClass('is-filtered').addClass('is-refreshing');
            $dashboard.find('[data-flight-filter] input').prop('checked', false).filter('[value="all"]').prop('checked', true);
            $dashboard.find('[data-flight-search]').val('');
            window.setTimeout(() => { $results.removeClass('is-refreshing'); refreshCount(); actions.announce('Flight results refreshed.'); }, 420);
            return;
        }
        if (action === 'save') { $dashboard.find('[data-flight-favorite]').trigger('click'); return; }
        actions.openDialog({ trigger: this, eyebrow: 'Flight search', title: 'Search settings', icon: 'settings', confirmText: 'Apply settings', body: '<label class="dashboard-action-field"><span>Preferred cabin</span><select><option>Business</option><option>Economy</option><option>First class</option></select></label><label class="dashboard-action-field"><span>Passengers</span><input type="number" value="1" min="1"></label>', onConfirm: () => { actions.closeDialog(); actions.announce('Flight settings updated.'); } });
    });

    $dashboard.find('[data-flight-favorite]').on('click.business', function (event) {
        event.preventDefault(); event.stopPropagation();
        const saved = !$(this).hasClass('is-saved');
        $(this).toggleClass('is-saved', saved).find('span').text(saved ? 'Saved To Favorites' : 'Add To Favorites');
        actions.announce(saved ? 'Flight search saved to favorites.' : 'Flight search removed from favorites.');
    });
    $dashboard.find('[data-flight-result]').on('click.business', function (event) {
        event.preventDefault(); event.stopPropagation();
        const start = text($(this).find('.flight-result-card__time').first());
        const end = text($(this).find('.flight-result-card__time').last());
        openInfo(actions, this, 'Flight details', `Paris to New York · $${$(this).data('price')}`, `<div class="dashboard-agenda"><article><time>${actions.escapeHtml(start.split(' ')[0] + ' ' + start.split(' ')[1])}</time><div><strong>Paris ORLY</strong><span>${actions.escapeHtml(end)}</span></div></article></div>`, 'plane');
    });
    $dashboard.find('[data-flight-date-form]').on('change.business', 'input', function () {
        const departure = $dashboard.find('[data-flight-departure]').val();
        const returning = $dashboard.find('[data-flight-return]').val();
        if (departure && returning && returning < departure) { $(this).val(departure); actions.announce('Return date cannot be before departure.'); }
        else actions.announce('Travel dates updated.');
    });
};

const initializeHumanResources = ($dashboard, actions) => {
    $dashboard.find('[data-hr-feed-tabs] button').on('click.business', function (event) {
        event.preventDefault(); event.stopPropagation();
        const filter = text($(this)).toLowerCase();
        $(this).addClass('is-active').siblings().removeClass('is-active');
        $dashboard.find('[data-feed-item]').each(function () { $(this).stop(true, true).fadeToggle(180, filter === 'all' || $(this).data('feed-item') === filter); });
    });
    $dashboard.find('[data-todo-tabs] button').on('click.business', function (event) {
        event.preventDefault(); event.stopPropagation();
        const filter = $(this).data('todo-tab');
        $(this).addClass('is-active').attr('aria-selected', 'true').siblings('[role="tab"]').removeClass('is-active').attr('aria-selected', 'false');
        const $list = $dashboard.find('[data-hr-todos]');
        $dashboard.find('[data-todo]').each(function () { $(this).prop('hidden', filter === 'mine' && $(this).data('mine') !== true); });
        $list.removeClass('is-switching');
        window.requestAnimationFrame(() => $list.addClass('is-switching'));
        window.setTimeout(() => $list.removeClass('is-switching'), 600);
    });
    $dashboard.find('[data-hr-todos] input').on('change.business', function (event) {
        event.stopPropagation();
        const $todo = $(this).closest('[data-todo]');
        $todo.toggleClass('is-complete', this.checked);
        actions.announce(this.checked ? 'Task completed.' : 'Task reopened.');
    });
    $dashboard.find('[data-rookie-search]').on('input.business', function () {
        const query = String($(this).val() || '').trim().toLowerCase(); let count = 0;
        $dashboard.find('[data-rookie]').each(function () { const visible = !query || String($(this).data('search')).includes(query); $(this).stop(true, true).fadeToggle(180, visible); if (visible) count += 1; });
        $dashboard.find('[data-rookie-empty]').prop('hidden', count > 0);
    });
    $dashboard.find('[data-hr-manage-jobs]').on('click.business', function (event) {
        event.preventDefault(); event.stopPropagation();
        actions.openDialog({ trigger: this, eyebrow: 'Recruiting workspace', title: 'Create a new position', icon: 'briefcase', confirmText: 'Publish position', body: '<div class="dashboard-action-grid"><label class="dashboard-action-field"><span>Position title</span><input type="text" value="Senior Product Designer"></label><label class="dashboard-action-field"><span>Location</span><input type="text" value="Los Angeles, CA"></label></div><label class="dashboard-action-field"><span>Application deadline</span><input type="date"></label>', onConfirm: () => { actions.closeDialog(); actions.announce('New position published.'); } });
    });
    $dashboard.find('[data-rookie-add]').on('click.business', function (event) { event.preventDefault(); event.stopPropagation(); openInfo(actions, this, 'Candidate pipeline', 'Add candidate', '<p class="dashboard-action-copy">Import a candidate from applications or invite one by email.</p>', 'user-plus'); });
    $dashboard.find('[data-rookie-action], [data-hr-open]').on('click.business', function (event) {
        event.preventDefault(); event.stopPropagation();
        const title = text($(this).closest('[data-rookie]').find('h3')) || text($(this).closest('article').find('strong'));
        openInfo(actions, this, 'Recruiting details', title, '<p class="dashboard-action-copy">Review the profile, recent activity and permission-aware recruiting actions.</p><div class="dashboard-feature-pills"><span>Available</span><span>Verified</span><span>Team fit</span></div>', 'user');
    });
    $dashboard.find('[data-hr-read-more], [data-rookies-view-all]').on('click.business', function (event) { event.preventDefault(); event.stopPropagation(); actions.openDrawer({ eyebrow: 'Human resources', title: text($(this)), body: '<p class="dashboard-action-copy">The complete recruiting overview is ready to connect to your CMS data source.</p>' }); });
};

const initializeJobs = ($dashboard, actions) => {
    const $cards = $dashboard.find('[data-job-card]');
    const applyFilters = () => {
        const query = String($dashboard.find('[data-job-query]').val() || '').trim().toLowerCase();
        const selected = {};
        $dashboard.find('[data-filter-group]').each(function () { selected[$(this).data('filter-group')] = $(this).find('input:checked').map((_, input) => String(input.value)).get(); });
        $cards.addClass('is-filtering');
        window.setTimeout(() => {
            let count = 0;
            $cards.each(function () {
                const $card = $(this);
                const queryMatch = !query || String($card.data('search')).includes(query);
                const type = selected['type-of-employment'] || [];
                const level = selected['seniority-level'] || [];
                const salary = selected['salary-range'] || [];
                const match = queryMatch && (!type.length || type.includes(String($card.data('type')))) && (!level.length || level.includes(String($card.data('level')))) && (!salary.length || salary.includes(String($card.data('salary'))));
                $card.toggleClass('is-hidden', !match).removeClass('is-filtering');
                if (match) count += 1;
            });
            $dashboard.find('[data-job-count]').text(count);
            $dashboard.find('[data-jobs-empty]').prop('hidden', count > 0);
        }, 220);
    };
    $dashboard.find('[data-jobs-filters] input').on('change.business', applyFilters);
    $dashboard.find('[data-job-search-form]').on('submit.business', function (event) { event.preventDefault(); event.stopPropagation(); applyFilters(); actions.announce('Job search updated.'); });
    $dashboard.find('[data-job-query]').on('input.business', applyFilters);
    $dashboard.find('[data-jobs-view-all]').on('click.business', function (event) { event.preventDefault(); event.stopPropagation(); $dashboard.find('[data-jobs-filters] input').prop('checked', false); $dashboard.find('[data-job-query]').val(''); applyFilters(); actions.announce('All jobs are visible.'); });
    $dashboard.find('[data-job-alert]').on('click.business', function (event) {
        event.preventDefault(); event.stopPropagation();
        const keyword = String($dashboard.find('[data-job-alert-keywords]').val() || 'Product designer');
        actions.openDialog({ trigger: this, eyebrow: 'Job notifications', title: 'Create a job alert', icon: 'bell', confirmText: 'Create alert', body: `<label class="dashboard-action-field"><span>Keywords</span><input type="text" value="${actions.escapeHtml(keyword)}"></label><label class="dashboard-action-field"><span>Location</span><input type="text" value="Remote"></label>`, onConfirm: () => { actions.closeDialog(); $(this).addClass('is-complete').text('Alert Active'); actions.announce('Your job alert is active.'); } });
    });
    $dashboard.find('[data-job-apply]').on('click.business', function (event) { event.preventDefault(); event.stopPropagation(); const $card = $(this).closest('[data-job-card]'); $(this).text('Applied').prop('disabled', true); $card.addClass('is-applied'); actions.announce(`Application submitted for ${text($card.find('h2'))}.`); });
    $dashboard.find('[data-job-message]').on('click.business', function (event) { event.preventDefault(); event.stopPropagation(); const title = text($(this).closest('[data-job-card]').find('h2')); actions.openDialog({ trigger: this, eyebrow: 'Recruiter message', title, icon: 'message', confirmText: 'Send message', body: '<label class="dashboard-action-field"><span>Message</span><textarea rows="4" placeholder="Introduce yourself..."></textarea></label>', onConfirm: () => { actions.closeDialog(); actions.announce('Message sent to the recruiter.'); } }); });
    $dashboard.find('[data-job-filter-drawer]').on('click.business', function (event) { event.preventDefault(); event.stopPropagation(); const $column = $dashboard.find('.jobs-filter-column'); const open = !$column.hasClass('is-open'); $column.toggleClass('is-open', open); $(this).find('span').text(open ? 'Close' : 'Filters'); });
};

export const initializeBusinessDashboard = (slug) => ($dashboard, actions) => {
    bindShared($dashboard, actions);
    if (slug === 'company') initializeCompany($dashboard, actions);
    if (slug === 'course') initializeCourse($dashboard, actions);
    if (slug === 'flights') initializeFlights($dashboard, actions);
    if (slug === 'human-resources') initializeHumanResources($dashboard, actions);
    if (slug === 'jobs') initializeJobs($dashboard, actions);
};
