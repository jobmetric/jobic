import { loadingButton, pageData } from '../common/page';
// Save forms without navigation. All core endpoints return PackageCore JSON.
export async function sendSettings(url, data) {
    const response = await fetch(url, {
        method: 'POST', credentials: 'same-origin',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
        body: data,
    });
    const token = response.headers.get('X-CSRF-TOKEN');
    if (token) {
        document.querySelector('meta[name="csrf-token"]').content = token;
        document.querySelectorAll('input[name="_token"]').forEach((input) => { input.value = token; });
    }
    const result = await response.json();
    if (!response.ok || !result.ok) throw Object.assign(new Error(result.message), { errors: result.errors || {}, status: response.status });
    return result;
}

// Copy server-rendered wording without replacing controls, event handlers or drafts.
// Match static element structure; dynamic widgets and extension slots remain intact.
export async function refreshPresentation(selector = '[data-cms-shell]') {
    const response = await fetch(location.href, { headers: { Accept: 'text/html' }, credentials: 'same-origin', cache: 'no-store' });
    if (!response.ok || response.redirected) return;
    const next = new DOMParser().parseFromString(await response.text(), 'text/html');
    document.documentElement.lang = next.documentElement.lang;
    document.documentElement.dir = next.documentElement.dir;
    document.title = next.title;
    document.getElementById('jobic-page-data').textContent = next.getElementById('jobic-page-data')?.textContent || '{}';
    window.localize = JSON.parse(document.getElementById('jobic-page-data').textContent);
    function copy(current, incoming) {
        if (current.nodeType !== incoming.nodeType) return;
        if (current.nodeType === Node.TEXT_NODE) { if (incoming.textContent.trim()) current.textContent = incoming.textContent; return; }
        if (current.nodeType !== Node.ELEMENT_NODE || current.tagName !== incoming.tagName) return;
        if (current.matches('.select2-container, script, style, svg, [data-ui-toast], [data-settings-errors], [data-chart], .apexcharts-canvas')) return;
        ['aria-label', 'title', 'placeholder', 'data-tooltip', 'data-saved', 'data-save-failed'].forEach((attribute) => {
            if (incoming.hasAttribute(attribute)) current.setAttribute(attribute, incoming.getAttribute(attribute));
        });
        if (current.matches('input, textarea')) return;
        if (current.matches('[data-cms-locale]')) {
            current.classList.toggle('is-active', incoming.classList.contains('is-active'));
            current.setAttribute('aria-pressed', incoming.getAttribute('aria-pressed'));
        }
        if (current.matches('.country-flag')) {
            current.className = incoming.className;
            current.style.cssText = incoming.style.cssText;
            current.textContent = incoming.textContent;
            return;
        }
        const children = [...current.childNodes].filter(node => !(node.nodeType === Node.ELEMENT_NODE && node.matches('.select2-container')));
        const replacements = [...incoming.childNodes];
        if (children.length === replacements.length) children.forEach((child, index) => copy(child, replacements[index]));
    }
    const currentRoot = document.querySelector(selector);
    const nextRoot = next.querySelector(selector);
    if (currentRoot && nextRoot) copy(currentRoot, nextRoot);
    window.dispatchEvent(new CustomEvent('cms:presentation-refreshed', {detail: {locale: document.documentElement.lang}}));
}

export function bindSettingsForms({ announce, applyPreferences }) {
    const avatarModal = document.getElementById('modal-avatar-picker');
    if (avatarModal) {
        avatarModal.dataset.avatarModal = '';
        const form = document.querySelector('form[data-avatar-save-url]');
        const input = document.querySelector('[data-avatar-input]');
        const close = () => { avatarModal.classList.remove('show'); avatarModal.setAttribute('aria-hidden', 'true'); document.body.classList.remove('cms-modal-open'); };
        document.querySelector('[data-avatar-open]')?.addEventListener('click', () => { avatarModal.classList.add('show'); avatarModal.setAttribute('aria-hidden', 'false'); document.body.classList.add('cms-modal-open'); });
        avatarModal.querySelectorAll('[data-bs-dismiss="modal"]').forEach((button) => button.addEventListener('click', close));
        avatarModal.addEventListener('click', (event) => { if (event.target === avatarModal) close(); });
        const saveAvatar = async (button) => {
            if (!form || form.dataset.avatarSaving) return;
            form.dataset.avatarSaving = 'true';
            button.disabled = true;
            const data = new FormData();
            data.set('_method', 'PUT');
            data.set('avatar_preset', button.dataset.avatarChoice);
            try {
                const result = await sendSettings(form.dataset.avatarSaveUrl, data);
                input.value = result.data.avatar_preset;
                avatarModal.querySelectorAll('[data-avatar-choice]').forEach((choice) => choice.classList.toggle('is-selected', choice === button));
                document.querySelectorAll('[data-user-avatar]').forEach((avatar) => { avatar.replaceChildren(Object.assign(document.createElement('img'), { className: 'vuero-avatar__image', src: result.data.avatar_url, alt: '' })); });
                close();
                announce(result.message);
            } catch (error) {
                announce(error.status ? error.message : document.querySelector('[data-cms-shell]').dataset.saveFailed);
            } finally {
                delete form.dataset.avatarSaving;
                button.disabled = false;
            }
        };
        avatarModal.querySelectorAll('[data-avatar-choice]').forEach((button) => button.addEventListener('click', () => saveAvatar(button)));
        const gravatarPreview = avatarModal.querySelector('[data-gravatar-preview]');
        gravatarPreview?.addEventListener('error', () => {
            gravatarPreview.hidden = true;
            avatarModal.querySelector('[data-gravatar-fallback]').hidden = false;
            avatarModal.querySelector('[data-avatar-gravatar]').disabled = true;
            avatarModal.querySelector('.cms-gravatar-card')?.classList.add('is-unavailable');
        });
        gravatarPreview?.addEventListener('load', () => { avatarModal.querySelector('[data-gravatar-fallback]').hidden = true; });
        if (gravatarPreview?.complete && !gravatarPreview.naturalWidth) gravatarPreview.dispatchEvent(new Event('error'));
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && avatarModal.classList.contains('show')) close(); });
    }
    const healthModal = document.getElementById('modal-health-guide');
    if (healthModal) {
        const content = healthModal.querySelector('[data-health-guide-content]');
        const close = () => { healthModal.classList.remove('show'); healthModal.setAttribute('aria-hidden', 'true'); document.body.classList.remove('cms-modal-open'); };
        document.querySelectorAll('[data-health-guide]').forEach((button) => button.addEventListener('click', () => {
            const template = document.getElementById(button.getAttribute('aria-controls'));
            content.replaceChildren(template.content.cloneNode(true));
            healthModal.classList.add('show'); healthModal.setAttribute('aria-hidden', 'false'); document.body.classList.add('cms-modal-open');
        }));
        healthModal.querySelectorAll('[data-bs-dismiss="modal"], [data-health-guide-close]').forEach((button) => button.addEventListener('click', close));
        healthModal.addEventListener('click', (event) => { if (event.target === healthModal) close(); });
        document.addEventListener('keydown', (event) => { if (event.key === 'Escape' && healthModal.classList.contains('show')) close(); });
    }
    document.querySelectorAll('form.cms-settings-form').forEach((form) => {
        const update = () => form.querySelectorAll('[data-field]').forEach((row) => {
            const name = row.dataset.field;
            if (name.startsWith('MAIL_') && name !== 'MAIL_MAILER') row.hidden = form.elements.MAIL_MAILER?.value !== 'smtp';
            if (name.startsWith('REDIS_')) row.hidden = form.elements.CACHE_STORE?.value !== 'redis';
        });
        form.addEventListener('change', update);
        update();
    });
    document.querySelectorAll('form.cms-settings-form').forEach((form) => form.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (form.dataset.saving) return;
        const data = new FormData(form);
        const testAction = event.submitter?.dataset.testAction;
        if (testAction) { data.delete('_method'); data.set('action', event.submitter.value); }
        const buttons = [...form.querySelectorAll('[type="submit"]')];
        const errors = document.querySelector('[data-settings-errors]');
        const testResult = form.querySelector('[data-test-result]');
        testResult?.classList.remove('is-success', 'is-error');
        errors.hidden = true;
        errors.querySelector('ul').replaceChildren();
        form.querySelectorAll('[data-field-error]').forEach((message) => message.remove());
        form.querySelectorAll('[aria-invalid]').forEach((input) => {
            input.removeAttribute('aria-invalid');
            const descriptions = (input.getAttribute('aria-describedby') || '').split(' ').filter((id) => !id.startsWith('settings-error-'));
            if (descriptions.length) input.setAttribute('aria-describedby', descriptions.join(' ')); else input.removeAttribute('aria-describedby');
        });
        form.dataset.saving = 'true';
        form.setAttribute('aria-busy', 'true');
        const restoreButtons = buttons.map(loadingButton);
        try {
            const result = await sendSettings(testAction || form.getAttribute('action'), data);
            if (testAction) {
                testResult.textContent = result.message;
                testResult.classList.add('is-success');
                announce(result.message);
                return;
            }
            if (form.matches('[data-two-factor-form]')) {
                form.querySelector('[data-two-factor-content]').innerHTML = result.data.html;
                form.querySelector('input:not([type=hidden])')?.focus();
                announce(result.message);
                return;
            }
            if (form.matches('[data-appearance-form]')) applyPreferences(result.data);
            form.querySelectorAll('input[type="password"], input[data-secret]').forEach((input) => { input.value = ''; });
            form.querySelectorAll('[data-password-toggle]').forEach((button) => { const input = document.getElementById(button.dataset.passwordToggle); input.value = ''; input.type = 'password'; button.setAttribute('aria-pressed', 'false'); });
            const meter = form.querySelector('meter');
            if (meter) meter.value = 0;
            // Refresh wording for locale/name changes; persistence already succeeded.
            restoreButtons.forEach((restore) => restore());
            buttons.forEach((button) => { button.disabled = true; });
            await refreshPresentation().catch(() => {});
            buttons.forEach((button) => { button.dataset.savedLabel = button.textContent; });
            announce(result.message);
        } catch (error) {
            if (testAction) {
                testResult.textContent = error.message;
                testResult.classList.add('is-error');
            }
            const messages = Object.values(error.errors || {}).flat();
            Object.entries(error.errors || {}).forEach(([name, messages]) => {
                const input = form.elements.namedItem(name);
                if (!(input instanceof HTMLElement)) return;
                input.setAttribute('aria-invalid', 'true');
                const message = document.createElement('small');
                message.dataset.fieldError = '';
                message.id = `settings-error-${name.replace(/[^a-z0-9_-]/gi, '-')}`;
                message.textContent = [].concat(messages).join(' ');
                input.setAttribute('aria-describedby', `${input.getAttribute('aria-describedby') || ''} ${message.id}`.trim());
                (input.closest('.cms-password-input') || input).after(message);
            });
            (messages.length ? messages : [error.status ? error.message : document.querySelector('[data-cms-shell]').dataset.saveFailed]).forEach((message) => {
                const item = document.createElement('li'); item.textContent = message; errors.querySelector('ul').append(item);
            });
            errors.hidden = false;
            errors.focus();
            announce(errors.textContent);
        } finally {
            delete form.dataset.saving;
            form.removeAttribute('aria-busy');
            restoreButtons.forEach((restore) => restore());
            buttons.forEach((button) => { if (button.dataset.savedLabel) { button.textContent = button.dataset.savedLabel; delete button.dataset.savedLabel; } });
        }
    }));
}
