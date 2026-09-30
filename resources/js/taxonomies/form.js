import { toolbar, copy, showDialog } from './shared';
import { loadingButton, pageData } from '../common/page';
import { sendSettings } from '../admin/settings';
import { announce } from '../common/toast';
import { guardUnsaved } from '../common/unsaved';
toolbar('form');
const form = document.querySelector('[data-taxonomy-form]');
const guard = guardUnsaved(form, () => showDialog(copy().unsaved_title, copy().unsaved_body, copy().leave));
form.addEventListener('submit', async event => {
    event.preventDefault(); if (form.dataset.saving) return;
    const intent = event.submitter?.value || 'save'; const snapshot = guard.snapshot(); const restores = [...form.querySelectorAll('button[type=submit]')].map(loadingButton); form.dataset.saving = 'true';
    try { const result = await sendSettings(form.action, new FormData(form)); guard.saved(snapshot); announce(result.message); if (intent !== 'save') guard.leave(); if (intent === 'close') location.assign(pageData().taxonomyPage.index); else if (intent === 'new') location.assign(`${pageData().taxonomyPage.index}/create`); else location.assign(result.data.edit_url); }
    catch (error) { announce(error.status ? error.message : copy().failed, true); }
    finally { restores.forEach(restore => restore()); delete form.dataset.saving; }
});
