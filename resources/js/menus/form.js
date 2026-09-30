import { toolbar, copy, showDialog } from './shared';
import { loadingButton, pageData } from '../common/page';
import { sendSettings } from '../admin/settings';
import { announce } from '../common/toast';
import { guardUnsaved } from '../common/unsaved';
toolbar('form');
const form = document.querySelector('[data-menu-form]');
form.noValidate = true;
const guard = guardUnsaved(form, () => showDialog({title: copy().unsaved_title, description: copy().unsaved_body, confirm: copy().leave}));
form.addEventListener('submit', async (event) => {
    event.preventDefault();
    if (form.dataset.saving || form.hasAttribute('data-readonly')) return;
    const intent = event.submitter?.value || 'save';
    const data = new FormData(form);
    const submitted = guard.snapshot();
    form.querySelectorAll('[data-error-for]').forEach(e => { e.textContent = ''; });
    form.querySelectorAll('[aria-invalid]').forEach(e => { e.removeAttribute('aria-invalid'); e.removeAttribute('aria-describedby'); });
    const buttons = [...form.querySelectorAll('button[type=submit]')];
    form.querySelectorAll('.crud-dropdown').forEach(menu => { menu.open = false; });
    form.querySelector('.crud-split-save')?.setAttribute('inert', '');
    const restore = buttons.map(loadingButton);
    form.dataset.saving = 'true'; form.setAttribute('aria-busy', 'true');
    try {
        const result = await sendSettings(form.action, data);
        let version = form.querySelector('[name=_version]');
        if (!version) { version = document.createElement('input'); version.type = 'hidden'; version.name = '_version'; form.append(version); }
        version.value = result.data.version;
        form.action = result.data.save_url;
        if (!form.querySelector('[name=_method]')) { const method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'PUT'; form.append(method); }
        history.replaceState({}, '', result.data.edit_url);
        form.querySelector('h1').textContent = copy().edit_title;
        guard.saved(submitted);

        announce(result.message);
        if (intent !== 'save') guard.leave();
        if (intent === 'close') location.assign(pageData().menuRoutes.index);
        else if (intent === 'new') location.assign(`${pageData().menuRoutes.index}/create`);
    } catch (error) {
        Object.entries(error.errors || {}).forEach(([name, messages]) => {
            const field = form.elements.namedItem(name);
            const message = form.querySelector(`[data-error-for="${CSS.escape(name)}"]`);
            if (field && message) { message.textContent = [].concat(messages).join(' '); field.setAttribute('aria-invalid', 'true'); field.setAttribute('aria-describedby', message.id); }
        });
        form.querySelector('[aria-invalid=true]')?.focus();
        announce(error.status ? error.message : copy().failed, true);
    } finally { restore.forEach(fn => fn()); form.querySelector('.crud-split-save')?.removeAttribute('inert'); delete form.dataset.saving; form.removeAttribute('aria-busy'); }
});
// Shared AJAX locale changes preserve the form and refresh page-local actions.
window.addEventListener('cms:presentation-refreshed', () => toolbar('form'));
