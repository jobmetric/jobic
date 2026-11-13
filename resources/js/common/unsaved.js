// Preserve edits across failed requests and AJAX preference changes.
export function guardUnsaved(form, confirmLeave, {normalizeValue = (name,value) => value, beforeUnload = true} = {}) {
    const snapshot = () => JSON.stringify([...new FormData(form)].filter(([key]) => !['_token', '_method', '_version', '_edit_lock', 'intent'].includes(key)).map(([key,value])=>[key,normalizeValue(key,value)]));
    let baseline = snapshot();
    let leaving = false;
    let prompting = false;
    const dirty = () => !leaving && !form.hasAttribute('data-readonly') && baseline !== snapshot();
    window.addEventListener('beforeunload', event => {
        if (!beforeUnload || leaving || (!dirty() && !form.dataset.saving)) return;
        event.preventDefault(); event.returnValue = '';
    });
    document.addEventListener('click', async event => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0 || event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || link.target === '_blank' || link.hasAttribute('download')) return;
        const url = new URL(link.href, location.href);
        if (url.href.split('#')[0] === location.href.split('#')[0] || !['http:', 'https:'].includes(url.protocol)) return;
        if (!dirty() && !form.dataset.saving) return;
        event.preventDefault();
        if (prompting || form.dataset.saving) return;
        prompting = true;
        try { if (await confirmLeave()) { leaving = true; location.assign(url.href); } }
        finally { prompting = false; }
    });
    return { snapshot, dirty, saved: value => { baseline = value; }, leave: () => { leaving = true; } };
}
