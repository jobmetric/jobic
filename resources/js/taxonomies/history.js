import { copy } from './shared';
import { appendHistoryChange } from '../common/history';

export function bindHistory(form) {
    const panel = document.querySelector('[data-record-history]');
    if (!panel || panel.hidden) return { refresh() {} };
    const body = panel.querySelector('[data-history-entries]');
    const previous = panel.querySelector('[data-history-previous]');
    const next = panel.querySelector('[data-history-next]');
    const refresh = panel.querySelector('[data-history-refresh]');
    let page = 1;
    let pages = 1;
    let busy = false;
    const value = (item) => item === null || item === undefined || item === '' ? '—' : typeof item === 'object' ? JSON.stringify(item) : String(item);
    async function load(requested = page) {
        if (busy) return;
        busy = true;
        panel.setAttribute('aria-busy', 'true');
        try {
            const response = await fetch(`${form.action}/history?page=${requested}`, {headers: {Accept: 'application/json'}});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error();
            page = result.data.page;
            pages = result.data.pages;
            body.replaceChildren();
            result.data.entries.forEach((entry) => {
                const section = document.createElement('details');
                section.className = 'crud-help-section';
                const summary = document.createElement('summary');
                summary.textContent = `${copy()['history_' + entry.operation]} · ${entry.actor || copy().history_actor_unknown} · ${new Intl.DateTimeFormat(document.documentElement.lang, {dateStyle:'medium', timeStyle:'short'}).format(new Date(entry.created_at))}`;
                section.append(summary);
                Object.entries(entry.changes).forEach(([field, change]) => {
                    appendHistoryChange(section, {label: copy()[field] || field, beforeLabel: copy().history_before, afterLabel: copy().history_after, before: value(change.before), after: value(change.after)});
                });
                body.append(section);
            });
            if (!result.data.entries.length) body.textContent = copy().history_empty;
            panel.querySelector('[data-history-page]').textContent = copy().page.replace(':page', page).replace(':pages', pages);
            previous.disabled = page <= 1;
            next.disabled = page >= pages;
        } catch {
            body.textContent = copy().failed;
        } finally {
            busy = false;
            panel.removeAttribute('aria-busy');
        }
    }
    previous.onclick = () => load(page - 1);
    next.onclick = () => load(page + 1);
    refresh.onclick = () => load(1);
    load();
    return {refresh: () => load(1)};
}
