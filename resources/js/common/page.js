export function pageData() {
    return JSON.parse(document.getElementById('jobic-page-data')?.textContent || '{}');
}
export function loadingButton(button) {
    const disabled = button.disabled;
    const nodes = [...button.childNodes];
    button.disabled = true;
    button.setAttribute('aria-busy', 'true');
    button.textContent = pageData().ui?.loading || (document.documentElement.lang === 'fa' ? 'در حال بارگذاری…' : 'Loading…');
    return () => {
        button.replaceChildren(...nodes);
        button.disabled = disabled;
        button.removeAttribute('aria-busy');
    };
}
