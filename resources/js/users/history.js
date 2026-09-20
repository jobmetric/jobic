import { copy } from './shared';
import { loadingButton } from '../common/page';
import { announce } from '../common/toast';

export function bindHistory(form) {
    const panel = document.querySelector('[data-record-history]');
    const body = panel.querySelector('[data-history-entries]');
    const previous = panel.querySelector('[data-history-previous]');
    const next = panel.querySelector('[data-history-next]');
    const refresh = panel.querySelector('[data-history-refresh]');
    let page = 1, pages = 1, busy = false;
    function value(field, raw) {
        if (raw === null) return '—';
        if (field === 'status') return copy()[raw ? 'active' : 'inactive'];
        if (field === 'email_verified_at') return raw ? copy().verified_yes : copy().verified_no;
        return String(raw);
    }
    async function load(requested = 1) {
        if (busy || !/\/\d+$/.test(form.action)) return;
        busy = true; panel.hidden = false; panel.setAttribute('aria-busy', 'true');
        const restore = loadingButton(refresh); previous.disabled = next.disabled = true;
        try {
            const response = await fetch(`${form.action}/history?page=${requested}`, {headers: {Accept: 'application/json'}});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error();
            page = result.data.page; pages = result.data.pages; body.replaceChildren();
            for (const entry of result.data.entries) {
                const section = document.createElement('details'); section.className = 'crud-help-section';
                const title = document.createElement('summary');
                title.textContent = `${copy()['history_' + entry.operation]} · ${entry.actor || copy().history_actor_unknown} · ${new Intl.DateTimeFormat(document.documentElement.lang, {dateStyle: 'medium', timeStyle: 'short'}).format(new Date(entry.created_at))}`;
                section.append(title);
                for (const [field, change] of Object.entries(entry.changes)) {
                    const line = document.createElement('p');
                    line.textContent = `${copy()[field] || field}: ${copy().history_before}: ${value(field, change.before)} — ${copy().history_after}: ${value(field, change.after)}`;
                    section.append(line);
                }
                body.append(section);
            }
            if (!result.data.entries.length) body.textContent = copy().history_empty;
            panel.querySelector('[data-history-page]').textContent = copy().page.replace(':page', page).replace(':pages', pages);
        } catch { announce(copy().failed, true); }
        finally { busy = false; restore(); panel.removeAttribute('aria-busy'); previous.disabled = page <= 1; next.disabled = page >= pages; }
    }
    refresh.onclick = () => load(); previous.onclick = () => load(page - 1); next.onclick = () => load(page + 1);
    new MutationObserver(() => load(page)).observe(document.documentElement, {attributes:true, attributeFilter:['lang']});
    load();
    return {refresh: () => load()};
}
