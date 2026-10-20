import { pageData } from '../common/page';
import { announce } from '../common/toast';
import { enhanceSelects } from '../common/selects';
import { sendSettings } from '../admin/settings';
import { loadingButton } from '../common/page';
import { iconMarkup } from '../common/icons';
import { bindMediaManager } from '../taxonomies/media-manager';
import '../../css/crud.css';
import '../../css/crud-fields.css';

const config = {...pageData().postPage};
Object.defineProperty(config, 'labels', {get: () => pageData().posts});
Object.defineProperty(config, 'statuses', {get: () => pageData().postPage.statuses});
Object.defineProperty(config, 'languages', {get: () => pageData().postPage.languages});
Object.defineProperty(config, 'activeLocale', {get: () => pageData().postPage.activeLocale});
for (const key of ['workflowLabel', 'helpTitle', 'helpIntro', 'help', 'editor']) {
    Object.defineProperty(config, key, {get: () => pageData().postPage[key]});
}
const $ = window.jQuery;
const table = $ ? $('#posts-table') : null;
const selected = new Map();
let rows = [];

function escape(value) {
    const node = document.createElement('span');
    node.textContent = String(value ?? '');
    return node.innerHTML;
}

function icon(name) {
    return iconMarkup(name, 18);
}

function dialog({title, description, items = [], confirm = null, onRemove = null, accordion = false}) {
    const node = document.querySelector('[data-crud-dialog]');
    const heading = node.querySelector('[data-dialog-title]');
    const body = node.querySelector('[data-dialog-body]');
    heading.textContent = title;
    body.replaceChildren();
    const intro = document.createElement('p'); intro.textContent = description; body.append(intro);
    items.forEach((item) => {
        if (accordion) {
            const index = body.querySelectorAll('.crud-help-section').length;
            const section = document.createElement('details'); section.className = 'crud-help-section';
            const summary = document.createElement('summary'); summary.textContent = Object.values(pageData().crudHelp || {})[index] || '';
            const content = document.createElement('p'); content.textContent = item;
            section.append(summary, content); section.open = index === 0; body.append(section);
            return;
        }
        const row = document.createElement('div'); row.className = 'crud-dialog-item';
        const name = document.createElement('span'); name.textContent = item.title || item.name || String(item); row.append(name);
        if (onRemove) {
            const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'crud-icon-button';
            remove.setAttribute('aria-label', config.labels.unselect); remove.innerHTML = icon('x');
            remove.addEventListener('click', () => {
                onRemove(item.id); row.remove();
                const count = body.querySelectorAll('.crud-dialog-item').length;
                heading.textContent = config.labels.selected.replace(':count', count);
                if (!count) node.close();
            });
            row.append(remove);
        }
        body.append(row);
    });
    const accept = node.querySelector('[data-dialog-confirm]');
    accept.hidden = !confirm; accept.textContent = confirm || '';
    node.querySelector('[data-dialog-cancel]').textContent = confirm ? config.labels.cancel : config.labels.dismiss;
    let accepted = false;
    return new Promise((resolve) => {
        accept.onclick = () => { accepted = true; node.close(); };
        node.querySelectorAll('[data-dialog-close], [data-dialog-cancel]').forEach((button) => { button.onclick = () => node.close(); });
        node.addEventListener('close', () => resolve(accepted), {once: true});
        node.showModal();
    });
}

function confirmDelete() {
    const dialog = document.querySelector('[data-crud-dialog]');
    dialog.querySelector('[data-dialog-title]').textContent = config.labels.delete;
    const body = dialog.querySelector('[data-dialog-body]');
    body.replaceChildren();
    const description = document.createElement('p');
    description.textContent = config.labels.confirm_delete;
    body.append(description);
    const confirm = dialog.querySelector('[data-dialog-confirm]');
    confirm.hidden = false;
    confirm.textContent = config.labels.delete;
    dialog.querySelector('[data-dialog-cancel]').textContent = config.labels.cancel;

    return new Promise((resolve) => {
        let accepted = false;
        confirm.onclick = () => { accepted = true; dialog.close(); };
        dialog.querySelectorAll('[data-dialog-close], [data-dialog-cancel]').forEach((button) => {
            button.onclick = () => dialog.close();
        });
        dialog.addEventListener('close', () => resolve(accepted), {once: true});
        dialog.showModal();
    });
}

if (table?.length && $.fn?.DataTable) {
    const card = document.querySelector('[data-table-card]');
    const empty = document.querySelector('[data-table-empty]');
    const footer = document.querySelector('[data-table-footer]');
    const filterDialog = document.querySelector('[data-filter-dialog]');
    const filterForm = document.querySelector('[data-filter-form]');
    const selectionButton = document.querySelector('[data-show-selected]');
    const selectAll = document.querySelector('[data-select-all]');
    let loading = false;
    let controller;
    let searchTimer;

    function setLoading(value) {
        loading = value;
        card.setAttribute('aria-busy', String(loading));
        document.querySelector('[data-table-loading]').hidden = !loading;
        document.querySelector('.crud-table-scroll').inert = loading;
        document.querySelectorAll('[data-list-controls]').forEach((node) => { node.inert = loading; });
        filterForm.inert = loading;
    }

    function updateSelection() {
        selectionButton.hidden = selected.size === 0;
        selectionButton.innerHTML = `${icon('check')}${escape(config.labels.selected.replace(':count', selected.size))}`;
        const visible = [...document.querySelectorAll('[data-post-row]')];
        visible.forEach((checkbox) => { checkbox.checked = selected.has(Number(checkbox.dataset.postRow)); });
        selectAll.checked = visible.length > 0 && visible.every((checkbox) => checkbox.checked);
        selectAll.indeterminate = visible.some((checkbox) => checkbox.checked) && !selectAll.checked;
    }

    function selectPost(id) {
        if (selected.size >= 100 && !selected.has(id)) { announce(config.labels.selection_limit, true); return; }
        const row = rows.find((item) => Number(item.id) === id);
        if (row) selected.set(id, {id, title: row.title, editLocked: Boolean(row.edit_lock)});
    }

    const create = document.createElement('a');
    create.href = config.createUrl;
    create.className = 'vuero-button is-primary';
    create.dataset.crudAction = 'create';
    const createLabel = () => config.type === 'page' ? config.labels.create_page : config.labels.create;
    create.innerHTML = `${icon('plus')}${escape(createLabel())}`;
    document.querySelector('[data-crud-toolbar]').append(create);
    if (config.workflowUrl) {
        const workflow = document.createElement('a');
        workflow.href = config.workflowUrl;
        workflow.className = 'vuero-button';
        workflow.dataset.postWorkflowLink = '';
        workflow.innerHTML = `${icon('list-tree')}${escape(config.workflowLabel)}`;
        document.querySelector('[data-crud-toolbar]').append(workflow);
    }
    const operations = document.createElement('details'); operations.className = 'crud-dropdown post-list-operations';
    const operationsToggle = document.createElement('summary'); operationsToggle.className = 'vuero-button'; operationsToggle.innerHTML = `${escape(config.labels.actions)}${icon('chevron-down')}`;
    const operationsMenu = document.createElement('div'); operationsMenu.className = 'crud-dropdown__menu';
    (config.operations || ['delete']).forEach((action) => {
        const button = document.createElement('button'); button.type = 'button'; button.dataset.postAction = action; button.dataset.crudAction = action; button.textContent = config.labels[action] || action; operationsMenu.append(button);
    });
    operations.append(operationsToggle, operationsMenu); document.querySelector('[data-crud-toolbar]').append(operations);
    window.addEventListener('cms:presentation-refreshed', () => {
        create.innerHTML = `${icon('plus')}${escape(createLabel())}`;
        const workflow = document.querySelector('[data-post-workflow-link]');
        if (workflow) workflow.innerHTML = `${icon('list-tree')}${escape(config.workflowLabel)}`;
        operationsToggle.innerHTML = `${escape(config.labels.actions)}${icon('chevron-down')}`;
        operationsMenu.querySelectorAll('[data-post-action]').forEach((button) => {
            const action = button.dataset.postAction;
            button.textContent = config.labels[action] || action;
        });
        const columnLabels = {
            media: config.labels.media_column,
            title: config.labels.title_field,
            status: config.labels.status,
            updated_at: config.labels.updated_at,
            translations: config.labels.translations,
            actions: config.labels.actions,
        };
        document.querySelectorAll('#posts-table thead th[data-column]').forEach((heading) => {
            const label = columnLabels[heading.dataset.column];
            if (label) heading.querySelector('[data-post-column-label]').textContent = label;
        });
        document.querySelector('#posts-table [data-select-all]')?.setAttribute('aria-label', config.labels.select_all);
        const language = pageData().language?.datatable || {};
        const settings = dataTable.settings()[0];
        Object.assign(settings.oLanguage, language, {
            sInfo: `${config.labels.page} _PAGE_ _PAGES_`,
            sInfoEmpty: `${config.labels.page} 0 0`,
            sLengthMenu: `${config.labels.length} _MENU_`,
        });
        settings.oLanguage.oPaginate = {...settings.oLanguage.oPaginate,
            sPrevious: config.labels.previous, sNext: config.labels.next};
        const lengthLabel = document.querySelector('#posts-table_length label');
        if (lengthLabel) {
            let first = true;
            [...lengthLabel.childNodes].filter(node => node.nodeType === Node.TEXT_NODE).forEach(node => {
                node.textContent = first ? `${config.labels.length} ` : '';
                first = false;
            });
        }
        const previous = document.querySelector('#posts-table_previous');
        const next = document.querySelector('#posts-table_next');
        if (previous) previous.textContent = config.labels.previous;
        if (next) next.textContent = config.labels.next;
        dataTable.ajax.reload(null, false);
    });

    $.fn.dataTable.ext.errMode = 'none';
    const dataTable = table.DataTable({
        serverSide: true,
        processing: false,
        searching: false,
        pageLength: 10,
        lengthMenu: [10, 25, 50, 100],
        dom: 'rt<"crud-table-footer"lip>',
        language: {
            ...pageData().language?.datatable,
            emptyTable: '', zeroRecords: '', info: `${config.labels.page} _PAGE_ _PAGES_`, infoEmpty: `${config.labels.page} 0 0`,
            lengthMenu: `${config.labels.length} _MENU_`,
            paginate: {previous: config.labels.previous, next: config.labels.next},
        },
        ajax: async (request, callback) => {
            controller?.abort();
            const current = new AbortController();
            controller = current;
            setLoading(true);
            empty.hidden = true;
            document.querySelector('[data-table-error]').hidden = true;
            const params = new URLSearchParams({
                draw: request.draw,
                start: request.start,
                length: request.length,
                'search[value]': document.querySelector('[data-post-search]').value,
                'order[0][column]': config.hasMedia ? Math.max(1, (request.order[0]?.column ?? 2) - 1) : (request.order[0]?.column ?? 1),
                'order[0][dir]': request.order[0]?.dir ?? 'asc',
            });
            new FormData(filterForm).forEach((value, key) => { if (value) params.set(key, value); });
            try {
                const response = await fetch(`${config.dataUrl}?${params}`, {headers: {Accept: 'application/json'}, signal: current.signal});
                if (!response.ok) throw new Error();
                const result = await response.json();
                if (!result.ok) throw new Error();
                rows = result.data.data;
                rows.forEach((row) => { if (selected.has(Number(row.id))) selected.set(Number(row.id), {id: Number(row.id), title: row.title}); });
                callback(result.data);
                empty.hidden = result.data.recordsFiltered !== 0;
                card.append(empty);
            } catch (error) {
                if (error.name === 'AbortError') return;
                callback({draw: request.draw, data: [], recordsTotal: 0, recordsFiltered: 0});
                document.querySelector('[data-table-error]').hidden = false;
            } finally {
                if (controller === current) {
                    setLoading(false);
                    enhanceSelects(footer);
                }
            }
        },
        columns: [
            {data: 'id', orderable: false, searchable: false, render: (id) => `<input type="checkbox" data-post-row="${Number(id)}" aria-label="${escape(config.labels.select_post)}">`},
            ...(config.hasMedia ? [{data: 'media', orderable: false, searchable: false, className: 'taxonomy-column-media', render: (value) => value ? `<img class="crud-taxonomy-thumb" src="${escape(value)}" alt="">` : '—'}] : []),
            {data: 'title', className: 'posts-column-title', render: $.fn.dataTable.render.text()},
            {data: 'status', render: (status) => `<span class="crud-status ${status === 'publish' ? 'is-active' : ''}">${escape(config.statuses[status] || status)}</span>`},
            {data: 'updated_at', render: (value) => value || '—'},
            {
                data: 'translations', orderable: false, searchable: false, className: 'posts-column-translations',
                render: (translations, mode, row) => {
                    const active = config.languages[config.activeLocale] || Object.values(config.languages)[0];
                    const activeFlag = active?.flag_url ? `<img src="${escape(active.flag_url)}" alt="">` : `<span class="crud-locale-code">${escape(config.activeLocale.toUpperCase())}</span>`;
                    const languages = Object.entries(config.languages).map(([locale, language]) => {
                        const complete = Boolean(String(translations?.[locale]?.title || '').trim());
                        const flag = language.flag_url ? `<img src="${escape(language.flag_url)}" alt="">` : `<span class="crud-locale-code">${escape(locale.toUpperCase())}</span>`;
                        return `<button type="button" class="${complete ? 'is-complete' : ''}" data-post-translation-id="${Number(row.id)}" data-post-translation-locale="${escape(locale)}">${flag}<span>${escape(language.name)}</span>${complete ? icon('check') : icon('plus')}</button>`;
                    }).join('');
                    return `<details class="crud-dropdown crud-locale-dropdown"><summary class="crud-icon-button" aria-label="${escape(config.labels.translations)}">${activeFlag}${icon('chevron-down')}</summary><div class="crud-dropdown__menu">${languages}</div></details>`;
                },
            },
            {
                data: null, orderable: false, searchable: false, className: 'posts-column-actions',
                render: (row) => {
                    const lock = row.edit_lock;
                    const lockHint = lock ? config.labels.edit_lock_owner.replace(':user', lock.user_name).replace(':time', lock.locked_at) : '';
                    const lockButton = lock ? `<button type="button" class="crud-icon-button taxonomy-edit-lock" aria-label="${escape(lockHint)}" data-tooltip="${escape(lockHint)}" ${config.canUnlockEditLocks && !lock.owned ? `data-unlock-post-lock="${Number(row.id)}"` : 'aria-disabled="true"'}>${icon('lock')}</button>` : '';
                    const edit = lock && !lock.owned ? '' : `<a class="crud-icon-button" href="${escape(row.edit_url)}" aria-label="${escape(config.labels.edit)}" data-tooltip="${escape(config.labels.edit)}">${icon('edit')}</a>`;
                    return `<div class="crud-row-actions">${lockButton}${edit}<details class="crud-dropdown"><summary class="crud-icon-button" aria-label="${escape(config.labels.actions)}">${icon('more-horizontal')}</summary><div class="crud-dropdown__menu"><button type="button" data-post-delete="${Number(row.id)}" data-url="${escape(row.delete_url)}">${icon('trash')}${escape(config.labels.delete)}</button></div></details></div>`;
                },
            },
        ],
        createdRow(row, data) {
            row.classList.toggle('post-row--disabled', data.status === 'disabled');
        },
        order: [[config.hasMedia ? 2 : 1, 'asc']],
        drawCallback() {
            const tableFooter = document.querySelector('#posts-table_wrapper .crud-table-footer');
            if (tableFooter && tableFooter.parentElement !== footer) footer.append(tableFooter);
            const pageInfo = this.api().page.info();
            const info = document.querySelector('#posts-table_info');
            if (info) info.textContent = config.labels.page.replace(':page', pageInfo.pages ? pageInfo.page + 1 : 0).replace(':pages', pageInfo.pages);
            updateSelection();
        },
    });

    const translationDialog = document.querySelector('[data-post-translation-dialog]');
    const translationForm = translationDialog.querySelector('[data-post-translation-form]');
    bindMediaManager(document.body);
    const translationFields = translationDialog.querySelector('[data-post-translation-fields]');
    const translationTabs = translationDialog.querySelector('[data-post-translation-tabs]');
    const translationSubmit = translationForm.querySelector('button[type=submit]');
    let translationRecord = null;
    let translationLocale = null;
    let translationEditor = null;
    let translationWorkbench = null;

    const editorConfig = {...pageData().postEditor};
    for (const key of ['messages', 'workbench', 'templates', 'tools']) {
        Object.defineProperty(editorConfig, key, {get: () => pageData().postEditor[key]});
    }

    const refreshTranslationPresentation = () => {
        if (!translationDialog.open || !translationRecord || !translationLocale) return;
        const language = config.languages[translationLocale];
        translationDialog.querySelector('[data-post-translation-title]').textContent = language?.name || translationLocale;
        translationDialog.querySelector('[data-post-translation-subtitle]').textContent = config.labels.translation_for.replace(':item', translationRecord.title || `#${translationRecord.id}`);
        translationSubmit.textContent = config.labels.save_translation;
        translationDialog.querySelectorAll('[data-close-post-translation]').forEach(button => button.setAttribute('aria-label', config.labels.dismiss));
        translationFields.querySelectorAll('[data-translation-field]').forEach(label => {
            const field = label.dataset.translationField;
            label.querySelector('[data-translation-label]').textContent = field === 'title' ? config.labels.title_field : (config.labels.fields?.[field] || config.labels[field] || field);
            const hint = label.querySelector('[data-translation-hint]');
            if (hint) hint.textContent = config.labels.seo_hints?.[field] || '';
        });
        translationTabs.querySelector('[data-post-translation-tab="content"]').textContent = config.labels.content_tab;
        translationTabs.querySelector('[data-post-translation-tab="seo"]').textContent = config.labels.seo_tab;
    };
    window.addEventListener('cms:presentation-refreshed', refreshTranslationPresentation);
    let translationEditorInterfaceLocale = document.documentElement.lang;
    window.addEventListener('cms:presentation-refreshed', async () => {
        if (translationEditorInterfaceLocale === document.documentElement.lang) return;
        translationEditorInterfaceLocale = document.documentElement.lang;
        if (!translationDialog.open || !translationEditor) return;
        const holder = translationFields.querySelector('[data-editor]');
        const input = translationFields.querySelector('[data-editor-content]');
        if (!holder || !input) return;
        const workbench = holder.closest('[data-editor-workbench]');
        workbench.inert = true;
        const locale = translationLocale;
        const postId = translationRecord.id;
        try {
            input.value = JSON.stringify(await translationEditor.save());
            translationWorkbench?.destroy();
            translationEditor.destroy();
            holder.replaceChildren();
            const {createPostEditor} = await import('./editor-runtime');
            const editor = await createPostEditor(holder, editorConfig, {
                type: config.type, locale, postId,
                data: JSON.parse(input.value), direction: config.languages[locale]?.direction,
            });
            if (!translationDialog.open || translationLocale !== locale || translationRecord?.id !== postId) { editor?.destroy(); return; }
            if (!editor) return;
            translationEditor = editor;
            const {bindEditorWorkbench} = await import('./editor-workbench');
            translationWorkbench = bindEditorWorkbench(holder, editor, editorConfig);
        } catch { announce(editorConfig.messages.unavailable, true); }
        finally { if (workbench.isConnected) workbench.inert = false; }
    });

    function translationEndpoint(template) {
        return template.replace('__ID__', translationRecord.id).replace('__LOCALE__', encodeURIComponent(translationLocale));
    }

    translationDialog.addEventListener('close', () => {
        translationWorkbench?.destroy();
        translationWorkbench = null;
        translationEditor?.destroy();
        translationEditor = null;
        translationFields.replaceChildren();
        translationRecord = null;
        translationLocale = null;
    });
    translationDialog.querySelectorAll('[data-close-post-translation]').forEach((button) => button.addEventListener('click', () => translationDialog.close()));
    translationTabs.addEventListener('click', event => {
        const tab = event.target.closest('[data-post-translation-tab]');
        if (!tab) return;
        translationTabs.querySelectorAll('[data-post-translation-tab]').forEach(item => {
            const active = item === tab;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', String(active));
        });
        translationFields.querySelectorAll('[data-post-translation-panel]').forEach(panel => {
            panel.hidden = panel.dataset.postTranslationPanel !== tab.dataset.postTranslationTab;
        });
    });

    async function openTranslation(id, locale) {
        const row = rows.find((item) => Number(item.id) === Number(id));
        const language = config.languages[locale];
        if (!row || !language) return;
        translationRecord = row;
        translationLocale = locale;
        translationDialog.querySelector('[data-post-translation-title]').textContent = language.name;
        translationDialog.querySelector('[data-post-translation-subtitle]').textContent = config.labels.translation_for.replace(':item', row.title || `#${row.id}`);
        translationFields.textContent = config.labels.loading;
        translationSubmit.disabled = true;
        translationDialog.showModal();
        try {
            const response = await fetch(translationEndpoint(config.translationUrl), {headers: {Accept: 'application/json'}});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || config.labels.failed);
            if (!translationDialog.open || translationRecord?.id !== row.id || translationLocale !== locale) return;
            translationForm.dataset.version = result.data.version;
            translationFields.replaceChildren();
            translationTabs.hidden = !config.hasSeo;
            translationTabs.querySelector('[data-post-translation-tab="content"]').click();
            const contentPanel = document.createElement('div');
            contentPanel.dataset.postTranslationPanel = 'content';
            translationFields.append(contentPanel);
            const seoPanel = document.createElement('div');
            seoPanel.dataset.postTranslationPanel = 'seo';
            seoPanel.hidden = true;
            if (config.hasSeo) translationFields.append(seoPanel);
            for (const field of config.translationFields) {
                const value = result.data.fields[field] ?? '';
                const panel = ['meta_title', 'meta_description', 'meta_keywords'].includes(field) ? seoPanel : contentPanel;
                if (field === 'content') {
                    const fragment = document.querySelector('[data-post-translation-workbench]').content.cloneNode(true);
                    const workbench = fragment.querySelector('[data-editor-workbench]');
                    const holder = workbench.querySelector('[data-editor]');
                    holder.dataset.locale = locale;
                    holder.dir = language.direction || 'auto';
                    workbench.dir = language.direction || 'auto';
                    holder.dataset.content = value;
                    const input = workbench.querySelector('[data-editor-content]');
                    input.dataset.editorContent = locale;
                    input.value = value;
                    panel.append(fragment);
                    let initial;
                    try { initial = JSON.parse(value); } catch { initial = undefined; }
                    const {createPostEditor} = await import('./editor-runtime');
                    const editor = await createPostEditor(holder, editorConfig, {type: config.type, locale, postId: row.id, data: initial, direction: language.direction});
                    if (!translationDialog.open || translationRecord?.id !== row.id || translationLocale !== locale) { editor?.destroy(); return; }
                    translationEditor = editor;
                    continue;
                }
                const label = document.createElement('label');
                label.className = 'crud-field';
                label.dataset.translationField = field;
                const caption = document.createElement('span');
                caption.dataset.translationLabel = '';
                caption.textContent = field === 'title' ? config.labels.title_field : (config.labels.fields[field] || config.labels[field] || field);
                label.append(caption);
                const input = document.createElement(['excerpt', 'meta_description'].includes(field) ? 'textarea' : 'input');
                input.className = 'input';
                input.name = `fields[${field}]`;
                input.value = value;
                input.maxLength = {title: 255, meta_title: 255, meta_description: 500, meta_keywords: 1000}[field] || 10000;
                if (field === 'title') { input.required = true; input.dataset.editorTitleInput = ''; }
                label.append(input);
                if (config.labels.seo_hints?.[field]) {
                    const heading = document.createElement('span');
                    heading.className = 'crud-field-label-row';
                    const hint = document.createElement('small'); hint.dataset.translationHint = field;
                    hint.textContent = config.labels.seo_hints[field];
                    heading.append(caption, hint); label.prepend(heading);
                }
                panel.append(label);
                const error = document.createElement('small');
                error.dataset.translationError = field;
                label.append(error);
            }
            if (translationEditor) {
                const {bindEditorWorkbench} = await import('./editor-workbench');
                translationWorkbench = bindEditorWorkbench(translationFields.querySelector('[data-editor]'), translationEditor, editorConfig);
            }
            translationFields.querySelector('input:not([type=hidden]),textarea')?.focus();
            translationSubmit.disabled = false;
        } catch (error) {
            translationFields.textContent = error.message || config.labels.failed;
        }
    }

    table.on('click', '[data-post-translation-id]', (event) => {
        const button = event.currentTarget;
        openTranslation(button.dataset.postTranslationId, button.dataset.postTranslationLocale);
    });

    translationForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!translationRecord || translationForm.dataset.saving) return;
        translationForm.dataset.saving = 'true';
        const restore = loadingButton(translationForm.querySelector('button[type=submit]'));
        translationFields.querySelectorAll('[data-translation-error]').forEach((node) => { node.textContent = ''; });
        try {
            if (translationEditor) {
                const before = new CustomEvent('jobic:editor:before-save', {cancelable: true, detail: {locale: translationLocale, data: await translationEditor.save()}});
                if (!window.dispatchEvent(before)) return;
                translationForm.elements.namedItem('fields[content]').value = JSON.stringify(before.detail.data);
            }
            const payload = new FormData(translationForm);
            payload.set('_method', 'PUT');
            payload.set('_version', translationForm.dataset.version || '');
            const result = await sendSettings(translationEndpoint(config.translationSaveUrl), payload);
            announce(result.message);
            translationDialog.close();
            dataTable.ajax.reload(null, false);
        } catch (error) {
            let inline = false;
            Object.entries(error.errors || {}).forEach(([key, messages]) => {
                const field = key.replace(/^fields\./, '');
                const node = translationFields.querySelector(`[data-translation-error="${CSS.escape(field)}"]`);
                if (node) { node.textContent = [].concat(messages).join(' '); inline = true; }
            });
            if (!inline) announce(error.message || config.labels.failed, true);
        } finally {
            restore();
            delete translationForm.dataset.saving;
        }
    });

    document.querySelector('[data-post-search]').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => dataTable.ajax.reload(), 250);
    });
    document.querySelector('[data-open-filters]').addEventListener('click', () => filterDialog.showModal());
    document.querySelectorAll('[data-close-filters]').forEach((button) => button.addEventListener('click', () => filterDialog.close()));
    filterForm.addEventListener('submit', (event) => { event.preventDefault(); filterDialog.close(); dataTable.ajax.reload(); });
    filterForm.addEventListener('reset', () => setTimeout(() => dataTable.ajax.reload(), 0));
    document.querySelector('[data-retry]').addEventListener('click', () => dataTable.ajax.reload());

    table.on('change', '[data-post-row]', (event) => {
        const id = Number(event.currentTarget.dataset.postRow);
        event.currentTarget.checked ? selectPost(id) : selected.delete(id);
        updateSelection();
    });
    selectAll.addEventListener('change', () => {
        document.querySelectorAll('[data-post-row]').forEach((checkbox) => {
            const id = Number(checkbox.dataset.postRow);
            if (selectAll.checked) selectPost(id); else selected.delete(id);
        });
        updateSelection();
    });
    selectionButton.addEventListener('click', () => dialog({
        title: config.labels.selected.replace(':count', selected.size), description: config.labels.selection_hint,
        items: [...selected.values()], onRemove: (id) => { selected.delete(id); updateSelection(); },
    }));

    async function mutate(ids, operation) {
        if (operation === 'unlock') ids = ids.filter((id) => (selected.get(id) || rows.find((row) => Number(row.id) === Number(id)))?.editLocked || (rows.find((row) => Number(row.id) === Number(id)))?.edit_lock);
        if (!ids.length) { announce(config.labels.selection_required, true); return; }
        const label = config.labels[operation];
        const confirmed = await dialog({
            title: config.labels.bulk_title.replace(':operation', label),
            description: config.labels.bulk_description.replace(':operation', label).replace(':count', ids.length),
            items: ids.map((id) => selected.get(id) || rows.find((row) => Number(row.id) === Number(id))).filter(Boolean),
            confirm: config.labels.confirm_operation,
        });
        if (!confirmed) return;
        const payload = new FormData();
        if (operation !== 'unlock') payload.set('operation', operation);
        ids.forEach((id) => payload.append('ids[]', id));
        try {
            const url = operation === 'unlock' ? config.editLockUnlockUrl : config.bulkUrl;
            const response = await fetch(url, {method: 'POST', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''}, body: payload});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || config.labels.failed);
            selected.clear(); updateSelection(); announce(result.message); dataTable.ajax.reload(null, false);
        } catch (error) { announce(error.message || config.labels.failed, true); }
    }

    document.querySelector('[data-crud-toolbar]').addEventListener('click', (event) => {
        const action = event.target.closest('[data-post-action]')?.dataset.postAction;
        if (action === 'help') dialog({title: config.helpTitle, description: config.helpIntro, items: config.help, accordion: true});
        else if (action === 'unlock') {
            const lockedIds = [...selected.values()].filter((item) => item.editLocked).map((item) => item.id);
            if (!lockedIds.length) announce(config.labels.no_locked_selected, true);
            else mutate(lockedIds, 'unlock');
        } else if (action) mutate([...selected.keys()], action);
    });

    table.on('click', '[data-unlock-post-lock]', async (event) => {
        const button = event.currentTarget;
        const id = Number(button.dataset.unlockPostLock);
        const row = rows.find((item) => Number(item.id) === id);
        if (!row?.edit_lock) return;
        const confirmed = await dialog({
            title: config.labels.unlock_title,
            description: config.labels.unlock_description.replace(':user', row.edit_lock.user_name).replace(':time', row.edit_lock.locked_at),
            confirm: config.labels.unlock_confirm,
        });
        if (!confirmed) return;
        try {
            const response = await fetch(config.editLockItemUrl.replace('__ID__', id), {method: 'POST', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''}});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || config.labels.failed);
            announce(result.message); dataTable.ajax.reload(null, false);
        } catch (error) { announce(error.message || config.labels.failed, true); }
    });

    table.on('click', '[data-post-delete]', async (event) => {
        const button = event.currentTarget;
        if (!(await confirmDelete())) return;
        button.disabled = true;
        try {
            const response = await fetch(button.dataset.url, {method: 'DELETE', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''}});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || config.labels.failed);
            announce(result.message);
            dataTable.ajax.reload(null, false);
        } catch (error) {
            announce(error.message || config.labels.failed, true);
            button.disabled = false;
        }
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.crud-dropdown[open]').forEach((dropdown) => {
            if (!dropdown.contains(event.target) || event.target.closest('button,a')) dropdown.open = false;
        });
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') document.querySelectorAll('.crud-dropdown[open]').forEach((dropdown) => { dropdown.open = false; });
    });
}
