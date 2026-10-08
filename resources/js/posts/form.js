import { createPostEditor } from './editor-runtime';
import { announce } from '../common/toast';
import { guardUnsaved } from '../common/unsaved';
import { loadingButton } from '../common/page';
import { sendSettings } from '../admin/settings';
import { showDialog } from '../taxonomies/shared';
import { bindMediaManager } from '../taxonomies/media-manager';
import { pageData } from '../common/page';
import { bindHistory } from '../taxonomies/history';

const editorConfig = pageData().postEditor;
Object.defineProperty(editorConfig, 'messages', {get: () => pageData().postEditor.messages});

const form = document.querySelector('[data-post-form]');
if (form) {
    form.noValidate = true;
    bindMediaManager(form);
    const labels = new Proxy({}, {get: (_, key) => pageData().postEditor.formLabels[key]});
    let historyPanel = bindHistory(form, () => pageData().posts);
    const toolbar = form.querySelector('[data-crud-toolbar]');
    toolbar.replaceChildren();
    const cancel = document.createElement('a');
    cancel.href = editorConfig.indexUrl;
    cancel.className = 'vuero-button crud-cancel';
    cancel.dataset.crudAction = 'cancel';
    cancel.textContent = labels.cancel;
    const saveGroup = document.createElement('div');
    saveGroup.className = 'crud-split-save';
    const save = document.createElement('button');
    save.type = 'submit'; save.name = 'intent'; save.value = 'save';
    save.className = 'vuero-button is-primary'; save.textContent = labels.save;
    const options = document.createElement('details');
    options.className = 'crud-dropdown';
    const summary = document.createElement('summary');
    summary.className = 'vuero-button'; summary.setAttribute('aria-label', labels.save_options);
    summary.innerHTML = '<svg class="ui-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-chevron-down"></use></svg>';
    const menu = document.createElement('div'); menu.className = 'crud-dropdown__menu';
    [['save-close', labels.save_close], ['save-new', labels.save_new]].forEach(([value, text]) => {
        const option = document.createElement('button'); option.type = 'submit'; option.name = 'intent'; option.value = value; option.textContent = text; menu.append(option);
    });
    options.append(summary, menu); saveGroup.append(save, options); toolbar.append(cancel, saveGroup);
    window.addEventListener('cms:presentation-refreshed', () => {
        cancel.textContent = labels.cancel;
        save.textContent = labels.save;
        summary.setAttribute('aria-label', labels.save_options);
        menu.querySelector('[value="save-close"]').textContent = labels.save_close;
        menu.querySelector('[value="save-new"]').textContent = labels.save_new;
    });
    const editors = new Map();
    const pendingEditors = new Map();
    const guard = guardUnsaved(form, () => showDialog({
        title: labels.unsaved_title,
        description: editorConfig.messages.unsaved,
        confirm: labels.leave,
    }));

    const createEditor = async holder => {
        if (!holder) return;
        const locale = holder.dataset.locale;
        if (editors.has(locale) || pendingEditors.has(locale)) return pendingEditors.get(locale);
        let initial;
        const persisted = form.querySelector(`[data-editor-content="${CSS.escape(locale)}"]`)?.value || holder.dataset.content || '';
        try { initial = JSON.parse(persisted); } catch { initial = undefined; }
        const ready = createPostEditor(holder, editorConfig, {
            type: form.dataset.type, locale, data: initial, direction: holder.closest('[data-locale-panel]')?.dir || 'auto',
            onChange: async (api) => {
                const input = form.querySelector(`[data-editor-content="${CSS.escape(locale)}"]`);
                if (!input) return;
                input.value = JSON.stringify(await api.saver.save());
                input.dispatchEvent(new Event('input', {bubbles: true}));
            },
        }).then((editor) => { if (editor) editors.set(locale, editor); }).catch(() => {
            announce(editorConfig.messages.unavailable, true);
        }).finally(() => pendingEditors.delete(locale));
        pendingEditors.set(locale, ready);
        return ready;
    };
    document.querySelectorAll('[data-locale-tab]').forEach(tab => tab.addEventListener('click', () => {
        document.querySelectorAll('[data-locale-tab]').forEach(item => item.setAttribute('aria-selected', String(item === tab)));
        document.querySelectorAll('[data-locale-panel]').forEach(panel => { panel.hidden = panel.dataset.localePanel !== tab.dataset.localeTab; });
        createEditor(form.querySelector(`[data-editor][data-locale="${CSS.escape(tab.dataset.localeTab)}"]`));
    }));
    createEditor(form.querySelector('[data-locale-panel]:not([hidden]) [data-editor]'));

    form.addEventListener('click', async event => {
        const button = event.target.closest('[data-revision-restore]');
        if (!button || !(await showDialog({title: labels.revision_title, description: editorConfig.messages.confirmRevision, confirm: labels.restore_revision}))) return;
        button.disabled = true;
        try {
            const response = await fetch(button.dataset.url, {method: 'POST', headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':form.querySelector('[name=_token]').value}});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || editorConfig.messages.failed);
            announce(result.message);
            window.location.reload();
        } catch (error) {
            announce(error.message || editorConfig.messages.failed, true);
            button.disabled = false;
        }
    });

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.dataset.saving) return;
        const intent = event.submitter?.value || 'save';
        await Promise.all(pendingEditors.values());
        for (const [locale, editor] of editors) {
            await editor.isReady;
            const beforeSave = new CustomEvent('jobic:editor:before-save', {cancelable: true, detail: {locale, data: await editor.save()}});
            if (!window.dispatchEvent(beforeSave)) return;
            form.querySelector(`[data-editor-content="${CSS.escape(locale)}"]`).value = JSON.stringify(beforeSave.detail.data);
        }
        const submitted = guard.snapshot();
        form.querySelectorAll('[data-error-for]').forEach(node => node.textContent = '');
        form.querySelectorAll('[aria-invalid]').forEach(node => { node.removeAttribute('aria-invalid'); node.removeAttribute('aria-describedby'); });
        const restoreButtons = [...form.querySelectorAll('button[type=submit]')].map(loadingButton);
        saveGroup.setAttribute('inert', ''); form.dataset.saving = 'true'; form.setAttribute('aria-busy', 'true');
        try {
            const result = await sendSettings(form.action, new FormData(form));
            announce(result.message);
            window.dispatchEvent(new CustomEvent('jobic:editor:after-save', {detail: {type: form.dataset.type, post: result.data, response: result}}));
            guard.saved(submitted);
            if (result.data?.update_url) {
                if (!form.querySelector('[name=_method]')) form.insertAdjacentHTML('afterbegin', '<input type="hidden" name="_method" value="PUT">');
                let versionInput = form.querySelector('[name=_version]');
                if (!versionInput) {
                    versionInput = document.createElement('input');
                    versionInput.type = 'hidden';
                    versionInput.name = '_version';
                    form.append(versionInput);
                }
                versionInput.value = result.data.version || '';
                form.action = `${result.data.update_url}?locale=${encodeURIComponent(editorConfig.activeLocale)}`;
                if (result.data.edit_url) history.replaceState({}, '', `${result.data.edit_url}?locale=${encodeURIComponent(editorConfig.activeLocale)}`);
            }
            const historySection = form.querySelector('[data-record-history]');
            if (historySection?.hidden && result.data?.update_url) {
                historySection.hidden = false;
                historyPanel = bindHistory(form, () => pageData().posts);
            }
            historyPanel.refresh();
            if (intent === 'save-close') { guard.leave(); location.assign(editorConfig.indexUrl); }
            else if (intent === 'save-new') { guard.leave(); location.assign(editorConfig.createUrl); }
        } catch (error) {
            let displayed = 0;
            Object.entries(error.errors || {}).forEach(([name, messages]) => {
                const fieldName = name.replace(/\.(\d+|[^.]+)/g, '[$1]');
                const field = form.elements.namedItem(name) || form.elements.namedItem(fieldName);
                const errorNode = [...form.querySelectorAll('[data-error-for]')].find(node => node.dataset.errorFor === name || name.startsWith(`${node.dataset.errorFor}.`));
                if (errorNode) { errorNode.textContent = [].concat(messages).join(' '); errorNode.id ||= `post-error-${name.replaceAll('.', '-')}`; displayed++; }
                if (field && errorNode) { field.setAttribute('aria-invalid', 'true'); field.setAttribute('aria-describedby', errorNode.id); }
            });
            form.querySelector('[aria-invalid=true]')?.focus();
            if (error.status !== 422 || displayed === 0) announce(error.message || editorConfig.messages.failed, true);
        } finally {
            restoreButtons.forEach(restore => restore()); saveGroup.removeAttribute('inert'); delete form.dataset.saving; form.removeAttribute('aria-busy');
        }
    });
}
