import '../../css/crud.css';
import '../../css/crud-fields.css';
import { loadingButton } from '../common/page';
import { sendSettings } from '../admin/settings';
import { announce } from '../common/toast';

const form = document.querySelector('[data-taxonomy-form]');
form?.addEventListener('submit', async event => {
    event.preventDefault();
    if (form.dataset.saving) return;
    form.dataset.saving = 'true';
    const restore = loadingButton(event.submitter);
    try { const result = await sendSettings(form.action, new FormData(form)); announce(result.message); location.assign(result.data.edit_url); }
    catch (error) { announce(error.status ? error.message : 'Server error', true); }
    finally { restore(); delete form.dataset.saving; }
});
