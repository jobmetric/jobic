import $ from '../../admin/jquery';
import { iconMarkup } from '../../../common/icons';

$(() => {
    const $wizard = $('[data-project-wizard]');
    if (!$wizard.length) return;

    const totalSteps = Number($wizard.data('total-steps')) || 8;
    const stepTitles = ['Project Type', 'Project Info', 'Project Details', 'Project Files', 'Team Members', 'Project Tools', 'Preview', 'Finish'];
    const today = new Date().toISOString().slice(0, 10);
    const defaultState = {
        step: 1,
        highestStep: 1,
        name: '',
        description: '',
        relatedTo: 'UI/UX Design',
        budget: '< 5K',
        startDate: today,
        endDate: today,
        customer: null,
        teammates: [],
        tools: [],
        attachments: [],
    };

    let state = { ...defaultState };
    let logoDataUrl = '';
    let toastTimer = null;
    let uploadTimer = null;
    let fileSequence = 0;
    const files = new Map();

    const persist = () => {};

    const escapeHtml = (value) => $('<div>').text(String(value ?? '')).html();
    const icon = (name, size = 16) => iconMarkup(name, size);

    const showToast = (message, isError = false) => {
        const $toast = $('[data-wizard-toast]');
        window.clearTimeout(toastTimer);
        $toast.stop(true, true).text(message).toggleClass('is-error', isError).prop('hidden', false);
        toastTimer = window.setTimeout(() => $toast.fadeOut(180, () => $toast.prop('hidden', true).show()), 2800);
    };

    const closeStepMenu = () => {
        $('[data-wizard-step-menu]').removeClass('is-open');
        $('[data-wizard-step-menu-toggle]').attr('aria-expanded', 'false');
        $('[data-wizard-step-menu-dropdown]').prop('hidden', true);
    };

    const closeRelatedSelect = () => {
        $('[data-related-select]').removeClass('is-open');
        $('[data-related-toggle]').attr('aria-expanded', 'false');
        $('[data-related-options]').prop('hidden', true);
    };

    const syncStepMenu = () => {
        $('[data-wizard-jump]').each(function () {
            const number = Number($(this).data('wizard-jump'));
            $(this)
                .prop('disabled', number > state.step)
                .toggleClass('is-active', number === state.step)
                .toggleClass('is-complete', number < state.step)
                .attr('aria-current', number === state.step ? 'step' : null);
        });
    };

    const syncReview = () => {
        const name = state.name.trim();
        const description = state.description.trim();
        $('[data-review-name]').text(name || 'Project Title Goes Here');
        $('[data-review-initial]').text((name.charAt(0) || 'P').toUpperCase());
        $('[data-review-description]').text(description || 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quis negat? Tamen a proposito, inquam, aberramus. Deinde dolorem quem maximum? Quae duo sunt, unum facit. Quod vestri non item.');
        $('[data-review-related]').text(state.relatedTo);
        $('[data-review-budget]').text(state.budget);
        $('[data-review-files]').text(files.size || 'No');

        const formattedDate = state.endDate
            ? new Intl.DateTimeFormat('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).format(new Date(`${state.endDate}T12:00:00`))
            : '—';
        $('[data-review-date]').text(formattedDate);

        $('[data-review-logo]').attr('src', logoDataUrl).prop('hidden', !logoDataUrl);
        $('[data-review-initial]').prop('hidden', Boolean(logoDataUrl));

        const hasCustomer = Boolean(state.customer);
        $('[data-review-customer-empty]').prop('hidden', hasCustomer);
        $('[data-review-customer]').prop('hidden', !hasCustomer);
        if (hasCustomer) {
            $('[data-review-customer-image]').attr('src', state.customer.image);
            $('[data-review-customer-name]').text(state.customer.name);
        }

        const $team = $('[data-review-team]').empty();
        if (!state.teammates.length) {
            $team.append('<span class="wizard-review-empty">No selected teammate</span>');
        } else {
            state.teammates.forEach((member) => {
                $team.append(`<article><img src="${escapeHtml(member.image)}" alt=""><span><strong>${escapeHtml(member.name)}</strong><small>${escapeHtml(member.role.charAt(0).toUpperCase() + member.role.slice(1))}</small></span></article>`);
            });
        }

        const $tools = $('[data-review-tools]').empty();
        if (!state.tools.length) {
            $tools.append('<span class="wizard-review-empty">No selected tools</span>');
        } else {
            state.tools.forEach((tool) => {
                $tools.append(`<article><img src="${escapeHtml(tool.image)}" alt=""><span><strong>${escapeHtml(tool.name)}</strong><small>${escapeHtml(tool.description)}</small></span></article>`);
            });
        }
    };

    const updateStep = (nextStep, options = {}) => {
        const number = Math.min(totalSteps, Math.max(1, Number(nextStep) || 1));
        if (number > state.highestStep && !options.unlock) return;

        const $current = $('[data-wizard-step].is-active');
        const $next = $(`[data-wizard-step="${number}"]`);
        if (!$next.length || ($current.is($next) && !options.force)) return;

        $('[data-wizard-step]').removeClass('is-active is-leaving').prop('hidden', true);
        $next.prop('hidden', false).addClass('is-active');

        state.step = number;
        if (options.unlock) state.highestStep = Math.max(state.highestStep, number);
        $('[data-wizard-step-label]').text(`Step ${number}:`);
        $('[data-wizard-step-title]').text(stepTitles[number - 1]);
        $('[data-wizard-progress]').val((number / totalSteps) * 100).text(`${(number / totalSteps) * 100}%`);

        const canNavigate = number > 1 && number < totalSteps;
        $('[data-wizard-actions]').prop('hidden', !canNavigate);
        window.requestAnimationFrame(() => $('[data-wizard-actions]').toggleClass('is-active', canNavigate));
        $('[data-wizard-previous]')
            .prop('disabled', number === 2)
            .toggleClass('is-disabled wizard-button--secondary', number === 2)
            .toggleClass('wizard-button--primary', number > 2);
        syncStepMenu();
        if (number === 7) syncReview();
        persist();
        const nextUrl = `${window.location.pathname}${window.location.search}#step-${number}`;
        if (options.instant || options.fromHistory) history.replaceState(null, '', nextUrl);
        else history.pushState({ wizardStep: number }, '', nextUrl);
        window.scrollTo({ top: 0, behavior: options.instant ? 'auto' : 'smooth' });
    };

    const validateCurrentStep = () => {
        if (state.step === 2) {
            state.name = String($('[data-project-name]').val() || '');
            state.description = String($('[data-project-description]').val() || '');
            state.relatedTo = String($('[data-project-related]').val() || 'UI/UX Design');
        }
        if (state.step === 3) {
            state.startDate = String($('[data-project-start]').val() || today);
            state.endDate = String($('[data-project-end]').val() || today);
        }
        if (state.step === 5 && String($('[data-team-search]').val() || '').trim()) {
            $('[data-team-search]').trigger('focus');
            return;
        }
        updateStep(state.step + 1, { unlock: true });
    };

    const syncDescriptionHelp = () => {
        const length = String($('[data-project-description]').val() || '').length;
        const remaining = Math.max(0, 50 - length);
        const text = length === 0
            ? 'Minimum of 50 characters'
            : remaining === 1
                ? '1 character remaining'
                : remaining > 0 ? `${remaining} characters remaining` : '';
        $('[data-description-help]').text(text);
    };

    const syncCustomerResults = () => {
        const query = String($('[data-customer-search]').val() || '').trim().toLowerCase();
        const $options = $('[data-customer-option]');
        let visible = 0;
        $options.each(function () {
            const matches = query && `${$(this).data('customer-name')} ${$(this).data('customer-location')}`.toLowerCase().includes(query) && visible < 4;
            $(this).prop('hidden', !matches);
            if (matches) visible += 1;
        });
        $('[data-customer-results]').prop('hidden', !query || visible === 0);
    };

    const selectCustomer = (customer) => {
        state.customer = customer;
        $('[data-customer-search-wrap], [data-customer-results]').prop('hidden', true);
        $('[data-selected-customer]').prop('hidden', false);
        $('[data-selected-customer-image]').attr('src', customer.image);
        $('[data-selected-customer-name]').text(customer.name);
        $('[data-selected-customer-location]').text(customer.location);
        persist();
    };

    const renderTeam = () => {
        const $members = $('[data-team-members]');
        $members.find('article').remove();
        $('[data-team-empty]').prop('hidden', state.teammates.length > 0);
        state.teammates.forEach((member) => {
            const roleLevels = ['reader', 'collaborator', 'manager', 'owner'];
            const level = Math.max(0, roleLevels.indexOf(member.role));
            const buttons = roleLevels.map((role, index) => `<button type="button" class="${index <= level ? 'is-active' : ''}" data-member-role="${role}" title="${role.charAt(0).toUpperCase() + role.slice(1)}" aria-label="Set ${escapeHtml(member.name)} as ${role}"></button>`).join('');
            $members.append(`<article data-team-member="${escapeHtml(member.name)}"><img src="${escapeHtml(member.image)}" alt=""><span><strong>Invited</strong><small>${escapeHtml(member.name)}</small></span><div class="wizard-permissions"><i class="wizard-permissions__fill" style="width:${(level / 3) * 100}%"></i>${buttons}</div><button type="button" class="wizard-member-remove" data-member-remove aria-label="Cancel invite">${icon('x', 14)}</button></article>`);
        });
    };

    const syncTeamResults = () => {
        const query = String($('[data-team-search]').val() || '').trim().toLowerCase();
        let count = 0;
        $('[data-team-option]').each(function () {
            const name = String($(this).data('team-name'));
            const alreadyAdded = state.teammates.some((member) => member.name === name);
            const matches = Boolean(query) && !alreadyAdded && name.toLowerCase().includes(query);
            $(this).prop('hidden', !matches);
            if (matches) count += 1;
        });
        $('[data-team-results]').prop('hidden', !query || count === 0);
    };

    const formatBytes = (bytes) => {
        if (bytes < 1024) return `${bytes} B`;
        if (bytes < 1024 ** 2) return `${(bytes / 1024).toFixed(1)} KB`;
        return `${(bytes / (1024 ** 2)).toFixed(1)} MB`;
    };

    const renderFile = (entry) => {
        const preview = entry.preview
            ? `<img src="${entry.preview}" alt="">`
            : icon('content', 22);
        $('[data-upload-list]').append(`<article class="wizard-upload-item" data-upload-id="${entry.id}"><span class="wizard-upload-item__preview">${preview}</span><span class="wizard-upload-item__meta"><strong>${escapeHtml(entry.file.name)}</strong><small>${formatBytes(entry.file.size)}</small></span><span class="wizard-upload-item__progress"><i data-file-progress></i></span><div class="wizard-upload-item__actions"><button type="button" data-file-start aria-label="Upload ${escapeHtml(entry.file.name)}">${icon('play', 16)}</button><button type="button" data-file-cancel aria-label="Cancel ${escapeHtml(entry.file.name)}">${icon('chevron-left', 16)}</button><button type="button" data-file-delete aria-label="Delete ${escapeHtml(entry.file.name)}" hidden>${icon('trash', 16)}</button></div></article>`);
    };

    const addFiles = (fileList) => {
        Array.from(fileList || []).forEach((file) => {
            const id = `file-${Date.now()}-${fileSequence++}`;
            const entry = { id, file, progress: 0, uploaded: false, preview: '' };
            if (file.type.startsWith('image/')) entry.preview = URL.createObjectURL(file);
            files.set(id, entry);
            renderFile(entry);
        });
        syncReview();
    };

    const simulateUploads = (onlyIds = null) => {
        window.clearInterval(uploadTimer);
        const allowedIds = onlyIds ? new Set(onlyIds) : null;
        const pending = [...files.values()].filter((entry) => !entry.uploaded && (!allowedIds || allowedIds.has(entry.id)));
        if (!pending.length) {
            showToast(files.size ? 'All files are already uploaded.' : 'Add files before starting the upload.');
            return;
        }
        uploadTimer = window.setInterval(() => {
            let complete = true;
            pending.forEach((entry) => {
                if (entry.uploaded || !files.has(entry.id)) return;
                entry.progress = Math.min(100, entry.progress + 8 + Math.round(Math.random() * 12));
                if (entry.progress >= 100) {
                    entry.uploaded = true;
                    $(`[data-upload-id="${entry.id}"]`).addClass('is-uploaded').find('[data-file-start], [data-file-cancel]').prop('hidden', true).end().find('[data-file-delete]').prop('hidden', false);
                }
                else complete = false;
                $(`[data-upload-id="${entry.id}"] [data-file-progress]`).css('width', `${entry.progress}%`);
            });
            const values = [...files.values()];
            const total = values.length ? values.reduce((sum, item) => sum + item.progress, 0) / values.length : 0;
            $('[data-upload-total]').css('width', `${total}%`);
            if (complete) {
                window.clearInterval(uploadTimer);
                showToast('Project files uploaded successfully.');
            }
        }, 180);
    };

    $('[data-project-name]').val(state.name);
    $('[data-project-description]').val(state.description);
    $('[data-project-related]').val(state.relatedTo);
    $('[data-related-current]').text(state.relatedTo || 'Enter something');
    $('[data-related-clear]').prop('hidden', !state.relatedTo);
    $('[data-related-option]').attr('aria-selected', 'false').filter(`[data-related-option="${CSS.escape(state.relatedTo)}"]`).attr('aria-selected', 'true');
    $('[data-project-start]').val(state.startDate || today);
    $('[data-project-end]').val(state.endDate || today);
    $(`[data-project-budget="${CSS.escape(state.budget)}"]`).addClass('is-active').attr('aria-checked', 'true').siblings().removeClass('is-active').attr('aria-checked', 'false');
    state.tools.forEach((tool) => $(`[data-tool-name="${CSS.escape(tool.name)}"]`).prop('checked', true));
    if (state.customer) selectCustomer(state.customer);
    renderTeam();
    syncDescriptionHelp();

    $(document).on('click.wizard', '[data-wizard-step-menu-toggle]', function (event) {
        event.stopPropagation();
        const $menu = $('[data-wizard-step-menu]');
        const open = !$menu.hasClass('is-open');
        $menu.toggleClass('is-open', open);
        $(this).attr('aria-expanded', String(open));
        $('[data-wizard-step-menu-dropdown]').prop('hidden', !open);
    });

    $(document).on('click.wizard', '[data-wizard-step-menu-dropdown]', (event) => event.stopPropagation());
    $(document).on('click.wizard', () => { closeStepMenu(); closeRelatedSelect(); });
    $(document).on('keydown.wizard', (event) => { if (event.key === 'Escape') { closeStepMenu(); closeRelatedSelect(); } });

    $(document).on('click.wizard', '[data-project-type]', function () {
        state.relatedTo = String($(this).data('project-type'));
        $('[data-project-related]').val(state.relatedTo);
        updateStep(2, { unlock: true });
    });

    $(document).on('click.wizard', '[data-wizard-learn-more]', (event) => event.preventDefault());
    $(document).on('click.wizard', '[data-wizard-next]', validateCurrentStep);
    $(document).on('click.wizard', '[data-wizard-previous]', () => { if (state.step > 2) updateStep(state.step - 1); });
    $(document).on('click.wizard', '[data-wizard-jump]', function () { updateStep(Number($(this).data('wizard-jump'))); closeStepMenu(); });
    $(document).on('click.wizard', '[data-wizard-edit]', function () { updateStep(Number($(this).data('wizard-edit'))); });

    $(document).on('input.wizard', '[data-project-name]', function () { state.name = String($(this).val() || ''); persist(); });
    $(document).on('input.wizard', '[data-project-description]', function () { state.description = String($(this).val() || ''); syncDescriptionHelp(); persist(); });
    $(document).on('click.wizard', '[data-related-toggle]', function (event) {
        event.stopPropagation();
        const $select = $('[data-related-select]');
        const isOpen = !$select.hasClass('is-open');
        $select.toggleClass('is-open', isOpen);
        $(this).attr('aria-expanded', String(isOpen));
        $('[data-related-options]').prop('hidden', !isOpen);
    });
    $(document).on('click.wizard', '[data-related-options]', (event) => event.stopPropagation());
    $(document).on('click.wizard', '[data-related-option]', function () {
        state.relatedTo = String($(this).data('related-option'));
        $('[data-project-related]').val(state.relatedTo);
        $('[data-related-current]').text(state.relatedTo);
        $('[data-related-clear]').prop('hidden', false);
        $('[data-related-option]').attr('aria-selected', 'false');
        $(this).attr('aria-selected', 'true');
        closeRelatedSelect();
        persist();
    });
    $(document).on('click.wizard', '[data-related-clear]', function (event) {
        event.stopPropagation();
        state.relatedTo = '';
        $('[data-project-related]').val('');
        $('[data-related-current]').text('Enter something');
        $('[data-related-option]').attr('aria-selected', 'false');
        $(this).prop('hidden', true);
        persist();
    });

    $(document).on('change.wizard', '[data-project-logo]', function () {
        const file = this.files?.[0];
        if (!file) return;
        if (file.size > 2 * 1024 * 1024) {
            this.value = '';
            showToast('Project logo cannot exceed 2MB.', true);
            return;
        }
        const reader = new FileReader();
        reader.onload = () => {
            logoDataUrl = String(reader.result || '');
            $('[data-project-logo-preview]').attr('src', logoDataUrl).prop('hidden', false);
            $('.wizard-logo-upload__empty').prop('hidden', true);
            $('[data-project-logo-remove]').prop('hidden', false);
        };
        reader.readAsDataURL(file);
    });

    $(document).on('click.wizard', '[data-project-logo-remove]', function (event) {
        event.preventDefault();
        event.stopPropagation();
        logoDataUrl = '';
        $('[data-project-logo]').val('');
        $('[data-project-logo-preview], [data-project-logo-remove]').prop('hidden', true);
        $('.wizard-logo-upload__empty').prop('hidden', false);
    });

    $(document).on('input.wizard', '[data-customer-search]', syncCustomerResults);
    $(document).on('click.wizard', '[data-customer-option]', function () {
        selectCustomer({ name: String($(this).data('customer-name')), location: String($(this).data('customer-location')), image: String($(this).data('customer-image')) });
        $('[data-customer-search]').val('');
    });
    $(document).on('click.wizard', '[data-customer-remove]', () => {
        state.customer = null;
        $('[data-selected-customer]').prop('hidden', true);
        $('[data-customer-search-wrap]').prop('hidden', false);
        persist();
    });
    $(document).on('click.wizard', '[data-project-budget]', function () {
        state.budget = String($(this).data('project-budget'));
        $('[data-project-budget]').removeClass('is-active').attr('aria-checked', 'false');
        $(this).addClass('is-active').attr('aria-checked', 'true');
        persist();
    });
    $(document).on('change.wizard', '[data-project-start], [data-project-end]', function () {
        state.startDate = String($('[data-project-start]').val());
        state.endDate = String($('[data-project-end]').val());
        if (state.endDate < state.startDate) {
            state.endDate = state.startDate;
            $('[data-project-end]').val(state.endDate);
        }
        persist();
    });

    $(document).on('click.wizard', '[data-show-uploader]', () => { $('[data-files-placeholder]').prop('hidden', true); $('[data-wizard-uploader]').prop('hidden', false); });
    $(document).on('click.wizard', '[data-add-files]', () => $('[data-project-files]').trigger('click'));
    $(document).on('change.wizard', '[data-project-files]', function () { addFiles(this.files); this.value = ''; });
    $(document).on('dragover.wizard dragenter.wizard', '[data-file-dropzone], [data-logo-dropzone]', function (event) { event.preventDefault(); $(this).addClass('is-dragging'); });
    $(document).on('dragleave.wizard drop.wizard', '[data-file-dropzone], [data-logo-dropzone]', function () { $(this).removeClass('is-dragging'); });
    $(document).on('drop.wizard', '[data-file-dropzone]', function (event) { event.preventDefault(); addFiles(event.originalEvent.dataTransfer.files); });
    $(document).on('click.wizard', '[data-upload-all]', () => simulateUploads());
    $(document).on('click.wizard', '[data-file-start]', function () {
        simulateUploads([String($(this).closest('[data-upload-id]').data('upload-id'))]);
    });
    $(document).on('click.wizard', '[data-file-cancel], [data-file-delete]', function () {
        const id = String($(this).closest('[data-upload-id]').data('upload-id'));
        const entry = files.get(id);
        if (entry?.preview) URL.revokeObjectURL(entry.preview);
        files.delete(id);
        $(this).closest('[data-upload-id]').slideUp(180, function () { $(this).remove(); });
        syncReview();
    });
    $(document).on('click.wizard', '[data-remove-all]', () => {
        files.forEach((entry) => { if (entry.preview) URL.revokeObjectURL(entry.preview); });
        files.clear();
        $('[data-upload-list]').empty();
        $('[data-upload-total]').css('width', 0);
        syncReview();
    });

    $(document).on('click.wizard', '[data-show-team]', () => { $('[data-team-placeholder]').prop('hidden', true); $('[data-wizard-team]').prop('hidden', false); $('[data-team-search]').trigger('focus'); });
    $(document).on('input.wizard', '[data-team-search]', syncTeamResults);
    $(document).on('click.wizard', '[data-team-option]', function () {
        state.teammates.push({ name: String($(this).data('team-name')), image: String($(this).data('team-image')), role: 'reader' });
        $('[data-team-search]').val('');
        $('[data-team-results]').prop('hidden', true);
        renderTeam();
        persist();
    });
    $(document).on('click.wizard', '[data-member-role]', function () {
        const name = String($(this).closest('[data-team-member]').data('team-member'));
        const member = state.teammates.find((item) => item.name === name);
        if (member) member.role = String($(this).data('member-role'));
        renderTeam();
        persist();
    });
    $(document).on('click.wizard', '[data-member-remove]', function () {
        const name = String($(this).closest('[data-team-member]').data('team-member'));
        state.teammates = state.teammates.filter((member) => member.name !== name);
        renderTeam();
        syncTeamResults();
        persist();
    });
    $(document).on('change.wizard', '[data-tool-name]', function () {
        const name = String($(this).data('tool-name'));
        if (this.checked) {
            state.tools.push({ name, description: String($(this).data('tool-description')), image: String($(this).data('tool-image')) });
        } else {
            state.tools = state.tools.filter((tool) => tool.name !== name);
        }
        persist();
    });

    const hashStep = Number((window.location.hash.match(/^#step-(\d+)$/) || [])[1]);
    if (hashStep && hashStep <= totalSteps) {
        state.step = hashStep;
        state.highestStep = hashStep;
    }
    updateStep(state.step, { force: true, instant: true });

    $(window).on('popstate.wizard', () => {
        const historyStep = Number((window.location.hash.match(/^#step-(\d+)$/) || [])[1]);
        if (historyStep && historyStep <= state.highestStep) updateStep(historyStep, { force: true, fromHistory: true });
    });
});
