import { copy } from './shared';
import { loadingButton } from '../common/page';
import { announce } from '../common/toast';
import { appendHistoryChange, appendHistoryHeading } from '../common/history';

export function bindHistory(form) {
    const panel = document.querySelector('[data-record-history]'); const body = panel.querySelector('[data-history-entries]'); const previous = panel.querySelector('[data-history-previous]'); const next = panel.querySelector('[data-history-next]'); const refresh = panel.querySelector('[data-history-refresh]'); let page = 1, pages = 1, busy = false;
    const value = raw => raw;
    async function load(requested = 1) {
        if (busy || !/\/\d+$/.test(form.action)) return; busy = true; panel.hidden = false; panel.setAttribute('aria-busy', 'true'); const restore = loadingButton(refresh); previous.disabled = next.disabled = true;
        try {
            const response = await fetch(`${form.action}/history?page=${requested}`, {headers: {Accept: 'application/json'}}); const result = await response.json(); if (!response.ok || !result.ok) throw new Error(); page = result.data.page; pages = result.data.pages; body.replaceChildren();
            result.data.entries.forEach(entry => { const section = document.createElement('details'); section.className = 'crud-help-section'; appendHistoryHeading(section, entry, copy()); Object.entries(entry.changes).forEach(([field, change]) => appendHistoryChange(section, {label: copy()[field] || field, beforeLabel: copy().history_before, afterLabel: copy().history_after, before: value(change.before), after: value(change.after)})); body.append(section); });
            if (!result.data.entries.length) body.textContent = copy().history_empty; panel.querySelector('[data-history-page]').textContent = copy().page.replace(':page', page).replace(':pages', pages);
        } catch { announce(copy().failed, true); } finally { busy = false; restore(); panel.removeAttribute('aria-busy'); previous.disabled = page <= 1; next.disabled = page >= pages; }
    }
    refresh.onclick = () => load(); previous.onclick = () => load(page - 1); next.onclick = () => load(page + 1); window.addEventListener('cms:presentation-refreshed', () => load(page)); load(); return {refresh: () => load()};
}
