import { copy } from './shared';
import { appendHistoryChange, appendHistoryHeading } from '../common/history';

export function bindHistory(form, wording = copy) {
    const panel = document.querySelector('[data-record-history]');
    if (!panel || panel.hidden) return { refresh() {} };
    const body = panel.querySelector('[data-history-entries]');
    const previous = panel.querySelector('[data-history-previous]');
    const next = panel.querySelector('[data-history-next]');
    const refresh = panel.querySelector('[data-history-refresh]');
    let page = 1;
    let pages = 1;
    let busy = false;
    const value = (item) => item;
    async function load(requested = page) {
        if (busy) return;
        busy = true;
        panel.setAttribute('aria-busy', 'true');
        try {
            const historyUrl = new URL(form.action);
            historyUrl.pathname = `${historyUrl.pathname.replace(/\/$/, '')}/history`;
            historyUrl.search = '';
            historyUrl.searchParams.set('page', requested);
            const response = await fetch(historyUrl, {headers: {Accept: 'application/json'}});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error();
            page = result.data.page;
            pages = result.data.pages;
            body.replaceChildren();
            result.data.entries.forEach((entry) => {
                const section = document.createElement('details');
                section.className = 'crud-help-section';
                appendHistoryHeading(section, entry, wording());
                Object.entries(entry.changes).forEach(([field, change]) => {
                    const label = wording()[`history_${field}`] || wording()[field];
                    appendHistoryChange(section, {label: typeof label === 'string' ? label : field, beforeLabel: wording().history_before, afterLabel: wording().history_after, before: value(change.before), after: value(change.after)});
                });
                body.append(section);
            });
            if (!result.data.entries.length) body.textContent = wording().history_empty;
            panel.querySelector('[data-history-page]').textContent = wording().page.replace(':page', page).replace(':pages', pages);
            previous.disabled = page <= 1;
            next.disabled = page >= pages;
        } catch {
            body.textContent = wording().failed;
        } finally {
            busy = false;
            panel.removeAttribute('aria-busy');
        }
    }
    previous.onclick = () => load(page - 1);
    next.onclick = () => load(page + 1);
    refresh.onclick = () => load(1);
    window.addEventListener('cms:presentation-refreshed', () => load(page));
    load();
    return {refresh: () => load(1)};
}
