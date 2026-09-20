import { announce } from '../common/toast';
import '../../css/admin-shell.css';
import { bindSettingsForms, refreshPresentation } from './settings';

const shell = document.querySelector('[data-cms-shell]');
if (shell) {
    let preferences = JSON.parse(shell.dataset.preferences);
    let saved = { ...preferences };
    let pending = Promise.resolve();
    let revision = 0;
    const mediaTheme = window.matchMedia('(prefers-color-scheme: dark)');
    function apply(syncForm = false) {
        document.documentElement.dataset.theme = preferences.theme === 'system' ? (mediaTheme.matches ? 'dark' : 'light') : preferences.theme;
        document.documentElement.dataset.motion = preferences.motion;
        const form = document.querySelector('[data-appearance-form]');
        if (syncForm && form) Object.entries(preferences).forEach(([key, value]) => { if (form.elements[key]) form.elements[key].value = value; });
    }
    function persist(changes) {
        preferences = { ...preferences, ...changes };
        apply(true);
        const snapshot = { ...preferences };
        const currentRevision = ++revision;
        pending = pending.then(async () => {
            try {
                const response = await fetch(shell.dataset.preferencesUrl, {
                    method: 'PUT', credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                    body: JSON.stringify(snapshot),
                });
                const result = await response.json();
                if (!response.ok || !result.ok) throw new Error();
                saved = { ...snapshot };
                if (Object.keys(changes).some(key => key !== 'theme')) announce(shell.dataset.saved);
                if (snapshot.locale !== document.documentElement.lang) await refreshPresentation().catch(() => {});
            } catch {
                if (currentRevision === revision) {
                    preferences = { ...saved };
                    apply(true);
                    window.dispatchEvent(new CustomEvent('cms:apply-layout', { detail: saved }));
                }
                announce(shell.dataset.saveFailed);
            }
        });
    }
    window.addEventListener('cms:preference', (event) => persist(event.detail));
    document.querySelectorAll('[data-cms-locale]').forEach((button) => button.addEventListener('click', () => persist({ locale: button.dataset.cmsLocale })));
    document.querySelector('[data-cms-search]')?.addEventListener('input', (event) => {
        const query = event.target.value.trim().toLocaleLowerCase();
        const items = [...document.querySelectorAll('[data-cms-search-item]')];
        items.forEach((item) => { item.hidden = !item.textContent.toLocaleLowerCase().includes(query); });
        document.querySelector('[data-cms-search-empty]').hidden = items.some((item) => !item.hidden);
    });
    mediaTheme.addEventListener('change', () => { if (preferences.theme === 'system') apply(); });
    document.querySelectorAll('[data-password-toggle]').forEach((button) => button.addEventListener('click', () => {
        const input = document.getElementById(button.dataset.passwordToggle);
        const visible = input.type === 'password';
        input.type = visible ? 'text' : 'password';
        button.setAttribute('aria-pressed', String(visible));
    }));
    document.querySelectorAll('.cms-password-strength').forEach((meter) => {
        const input = document.getElementById(meter.id.replace(/-strength$/, '')) || meter.closest('.cms-settings-control')?.querySelector('input[type=password]');
        input?.addEventListener('input', (event) => {
            const password = event.target.value;
            meter.value = Number(password.length >= 12) + Number(/[a-z]/i.test(password) && /[0-9]/.test(password)) + Number(/[^a-z0-9]/i.test(password)) + Number(password.length >= 16);
        });
    });
    bindSettingsForms({ announce, applyPreferences: (values) => {
        preferences = { ...values }; saved = { ...values }; apply(true);
        window.dispatchEvent(new CustomEvent('cms:apply-layout', { detail: values }));
    } });
    apply();
    const flash = document.querySelector('[data-flash-message]')?.dataset.flashMessage;
    if (flash) announce(flash);
    requestAnimationFrame(() => document.querySelectorAll('[data-cms-reveal]').forEach((element) => element.classList.add('is-ready')));
}

const dashboard = document.querySelector('[data-cms-shell] [data-dashboard-page]');
if (dashboard) {
    requestAnimationFrame(() => {
        dashboard.classList.add('is-ready');
        requestAnimationFrame(() => dashboard.querySelector('.ecommerce-v1')?.classList.add('is-animated'));
    });
    dashboard.querySelector('[data-order-period]')?.addEventListener('change', (event) => {
        const count = event.target.value === '7' ? 2 : 5;
        dashboard.querySelectorAll('[data-order-row]').forEach((row, index) => { row.hidden = index >= count; });
    });
}
