import { toolbar, copy, showDialog } from './shared';
import { loadingButton, pageData } from '../common/page';
import { sendSettings } from '../admin/settings';
import { announce } from '../common/toast';
import { guardUnsaved } from '../common/unsaved';
import { bindHistory } from './history';
import { bindArchive } from './archive';
import { bindExamples } from './examples';

const form = document.querySelector('[data-extension-form]');
if (form) {
    toolbar('form');
    const archive = form.hasAttribute('data-upload-form') ? bindArchive(form) : null;
    if (archive) bindExamples(form);
    const historyPanel = document.querySelector('[data-record-history]') ? bindHistory(form) : null;
    form.noValidate = true;
    const guard = guardUnsaved(form, () => showDialog({title: copy().unsaved_title, description: copy().unsaved_body, confirm: copy().leave}), {normalizeValue: (name, value) => value instanceof File ? (value.name ? `${value.name}:${value.size}:${value.lastModified}` : '') : value});
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.dataset.saving) return;
        const intent = archive ? 'save' : event.submitter?.value || 'save';
        const data = new FormData(form);
        // Package fields keep their own names in the form; the transport scopes them under fields.
        form.querySelectorAll('[data-plugin-fields] [name]').forEach(field => {
            const name = field.name;
            data.delete(name);
            data.delete(`fields[${name}]`);
        });
        const fields = new FormData();
        form.querySelectorAll('[data-plugin-fields] [name]').forEach(field => {
            const name = field.name;
            if (field.disabled || (['checkbox', 'radio'].includes(field.type) && !field.checked)) return;
            if (field.type === 'file') [...field.files].forEach(file => fields.append(name, file));
            else if (field.multiple) [...field.selectedOptions].forEach(option => fields.append(name, option.value));
            else fields.append(name, field.value);
        });
        fields.forEach((value, name) => {
            const bracket = name.indexOf('[');
            const scoped = bracket < 0 ? `fields[${name}]` : `fields[${name.slice(0, bracket)}]${name.slice(bracket)}`;
            data.append(scoped, value);
        });
        const submission = archive ? await archive.submission(data) : {url: form.action, data};
        if (!submission || form.dataset.saving) return;
        const submitted = guard.snapshot();
        form.querySelectorAll('[data-error-for]').forEach(node => { node.textContent = ''; });
        form.querySelectorAll('[aria-invalid]').forEach(node => { node.removeAttribute('aria-invalid'); node.removeAttribute('aria-describedby'); });
        form.querySelector('[data-settings-errors]').textContent = '';
        const restore = [...form.querySelectorAll('[type=submit]')].map(loadingButton);
        form.dataset.saving = 'true'; form.setAttribute('aria-busy', 'true');
        if (!archive) form.querySelectorAll('fieldset,[data-plugin-fields]').forEach(node => { node.inert = true; });
        form.querySelector('.crud-split-save')?.setAttribute('inert', '');
        archive?.busy(true, submission.confirmed);
        try {
            const result = await sendSettings(submission.url, submission.data);
            if (archive && result.data?.phase === 'review') {
                if (await archive.received(result.data, submission.generation)) announce(result.message);
                return;
            }
            if (archive) await archive.placed(result.data);
            if (result.data?.save_url) {
                form.action = result.data.save_url;
                let version = form.elements.namedItem('_version');
                if (!version) { version = document.createElement('input'); version.type = 'hidden'; version.name = '_version'; form.append(version); }
                version.value = result.data.version;
                history.replaceState({}, '', result.data.edit_url);
                form.querySelector('h1').textContent = copy().edit_plugin;
            }
            if (form.hasAttribute('data-upload-form')) form.querySelector('[type=file]').value = '';
            form.querySelectorAll('[type=password]').forEach(field => { field.value = ''; });
            const baseline = JSON.stringify(JSON.parse(submitted).map(([name, value]) => [name, form.elements.namedItem(name)?.type === 'password' ? '' : value]));
            guard.saved(form.hasAttribute('data-upload-form') ? guard.snapshot() : baseline);
            historyPanel?.refresh();
            announce(result.message);
            if (intent === 'close') {
                guard.leave();
                location.assign(pageData().singleInstanceReturn?.url || pageData().extensionRoutes.index);
            }
            if (intent === 'new') { guard.leave(); location.assign(form.dataset.newUrl || pageData().extensionRoutes.create); }
        } catch (error) {
            if (archive) {
                await archive.failed(submission.confirmed, error);
                return;
            }
            Object.entries(error.errors || {}).forEach(([name, messages]) => {
                const errorNode = form.querySelector(`[data-error-for="${CSS.escape(name)}"]`) || form.querySelector(`[data-error-for="${CSS.escape(name.split('.').slice(0, 2).join('.'))}"]`);
                const raw = name.replace(/^fields\./, '');
                const field = form.elements.namedItem(raw) || form.elements.namedItem(name);
                if (errorNode) errorNode.textContent = [].concat(messages).join(' ');
                if (field?.setAttribute) {
                    field.setAttribute('aria-invalid', 'true');
                    if (errorNode) field.setAttribute('aria-describedby', errorNode.id);
                }
            });
            form.querySelector('[data-settings-errors]').textContent = (error.status ? error.message : copy().failed) || copy().failed;
            const invalid = [...form.querySelectorAll('[aria-invalid=true]')].find(node => node.getClientRects().length);
            if (!archive) (invalid || form.querySelector('[data-settings-errors]'))?.focus();
            announce(error.status ? error.message : copy().failed, true);
        } finally {
            restore.forEach(fn => fn()); delete form.dataset.saving; form.removeAttribute('aria-busy');
            form.querySelector('.crud-split-save')?.removeAttribute('inert');
            form.querySelectorAll('fieldset,[data-plugin-fields]').forEach(node => { node.inert = false; });
            archive?.busy(false); archive?.syncToolbar();
        }
    });
    window.addEventListener('cms:presentation-refreshed', () => { toolbar('form'); archive?.syncToolbar(); });
}
