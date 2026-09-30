import '../../css/crud.css';
import '../../css/crud-fields.css';
import { pageData, loadingButton } from '../common/page';
import { sendSettings } from '../admin/settings';
import { announce } from '../common/toast';
import { guardUnsaved } from '../common/unsaved';

const form = document.querySelector('[data-menu-form]');

if (form && !form.hasAttribute('data-readonly')) {
    const copy = pageData().menus;
    const guard = guardUnsaved(form, () => Promise.resolve(window.confirm(copy.unsaved_body)));
    form.noValidate = true;
    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.dataset.saving) return;
        const restore = event.submitter ? loadingButton(event.submitter) : () => {};
        form.dataset.saving = 'true';
        form.querySelectorAll('[data-error-for]').forEach(node => { node.textContent = ''; });
        try {
            const result = await sendSettings(form.action, new FormData(form));
            guard.saved(guard.snapshot());
            announce(result.message);
            location.assign(result.data.edit_url);
        } catch (error) {
            Object.entries(error.errors || {}).forEach(([name, messages]) => {
                const field = form.elements.namedItem(name);
                const message = form.querySelector(`[data-error-for="${CSS.escape(name)}"]`);
                if (field && message) {
                    message.textContent = [].concat(messages).join(' ');
                    field.setAttribute('aria-invalid', 'true');
                }
            });
            announce(error.status ? error.message : copy.failed, true);
        } finally {
            restore();
            delete form.dataset.saving;
        }
    });
}
