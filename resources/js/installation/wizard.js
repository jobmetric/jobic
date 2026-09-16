import { announce } from '../common/toast';
import { loadingButton, pageData } from '../common/page';
import '../../css/installation.css';

// Conditional fields are shared by setup and the authenticated mail settings form.
function conditionals(form) {
    const read = (name) => form.elements.namedItem(name)?.value;
    form.querySelectorAll('[data-field]').forEach((row) => {
        const name = row.dataset.field;
        let visible = true;
        if (name.startsWith('MAIL_') && name !== 'MAIL_MAILER') visible = read('MAIL_MAILER') === 'smtp';
        if (name === 'LOG_STACK') visible = read('LOG_CHANNEL') === 'stack';
        if (name === 'LOG_DAILY_DAYS') visible = read('LOG_CHANNEL') === 'daily' || (read('LOG_CHANNEL') === 'stack' && read('LOG_STACK')?.split(',').includes('daily'));
        row.hidden = !visible;
    });
}
function initialize(root, restoredStep = null, restoredConnections = null) {
    if (!root) return;
    root.querySelectorAll('form').forEach((form) => {
        conditionals(form);
        form.addEventListener('input', () => conditionals(form));
    });
    const messages = pageData().installation;
    const numbers = new Intl.NumberFormat(document.documentElement.lang);
    const percent = new Intl.NumberFormat(document.documentElement.lang, { style: 'percent' });
    const format = (template, values) => template.replace(/:(\w+)/g, (match, key) => values[key] ?? match);
    const jquery = window.jQuery;
    if (jquery?.fn.select2) {
        root.querySelectorAll('select').forEach((select) => {
            jquery(select).select2({
                width: '100%', dir: document.documentElement.dir,
                dropdownParent: jquery(root),
                minimumResultsForSearch: 6,
                language: { noResults: () => messages.select_empty, searching: () => messages.select_search },
            }).on('change.installation', () => {
                select.dispatchEvent(new Event('input', { bubbles: true }));
            }).on('select2:open', () => {
                const search = root.querySelector('.select2-search__field');
                if (search) { search.setAttribute('aria-label', messages.select_search); search.placeholder = messages.select_search; }
            });
        });
    }
    root.querySelectorAll('input[type=password]').forEach((input) => {
        const wrap = document.createElement('div');
        wrap.className = 'installation-input-wrap';
        input.before(wrap);
        wrap.append(input);
        const toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.tabIndex = -1;
        toggle.addEventListener('mousedown', (event) => event.preventDefault());
        toggle.className = 'installation-password-toggle';
        toggle.innerHTML = '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/><path class="password-eye-slash" d="M3 3 21 21" hidden/></svg>';
        toggle.setAttribute('aria-label', messages.show_password);
        toggle.setAttribute('aria-pressed', 'false');
        toggle.addEventListener('click', () => {
            const visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            toggle.querySelector('.password-eye-slash').toggleAttribute('hidden', !visible);
            toggle.setAttribute('aria-label', visible ? messages.hide_password : messages.show_password);
            toggle.setAttribute('aria-pressed', String(visible));
        });
        wrap.append(toggle);
        if (input.name === 'ADMIN_PASSWORD') {
            const meter = document.createElement('progress');
            meter.className = 'installation-password-meter';
            meter.max = 12;
            meter.setAttribute('aria-label', messages.password_short);
            const hint = document.createElement('small');
            hint.className = 'installation-password-help';
            hint.id = 'administrator-password-hint';
            input.setAttribute('aria-describedby', `${input.getAttribute('aria-describedby') || ''} ${hint.id}`.trim());
            wrap.after(meter, hint);
            const update = () => {
                meter.value = Math.min(input.value.length, 12);
                hint.textContent = input.value.length >= 12 ? messages.password_ready : messages.password_short;
            };
            input.addEventListener('input', update);
            update();
        }
    });
    root.querySelector('[data-language-form]')?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (busy || !event.submitter) return;
        setBusy(true);
        try {
            await request(event.currentTarget.action, { locale: event.submitter.value });
            const response = await fetch(root.dataset.intro, { headers: { Accept: 'text/html' }, credentials: 'same-origin' });
            if (!response.ok) throw new Error(messages.error);
            const documentCopy = new DOMParser().parseFromString(await response.text(), 'text/html');
            const replacement = documentCopy.querySelector('[data-installation]');
            const registry = documentCopy.querySelector('#jobic-page-data');
            if (!replacement || !registry) throw new Error(messages.error);
            // Values live only in memory and the current form, never in browser storage.
            root.querySelectorAll('form[data-step] input:not([type=hidden]), form[data-step] select').forEach((input) => {
                const target = [...replacement.querySelectorAll('input, select')].find((item) => item.name === input.name);
                if (target) target.value = input.value;
            });
            document.documentElement.lang = documentCopy.documentElement.lang;
            document.documentElement.dir = documentCopy.documentElement.dir;
            document.title = documentCopy.title;
            document.querySelector('#jobic-page-data').textContent = registry.textContent;
            window.localize = JSON.parse(registry.textContent);
            document.querySelector('meta[name="csrf-token"]').content = documentCopy.querySelector('meta[name="csrf-token"]').content;
            if (jquery?.fn.select2) jquery(root).find('select.select2-hidden-accessible').select2('destroy');
            root.replaceWith(replacement);
            initialize(replacement, step, connectionStates);
        } catch (error) { errors(error); setBusy(false); }
    });
    let step = restoredStep ?? Number(root.dataset.step);
    let available = Number(root.dataset.step);
    let executing = ['running', 'failed'].includes(root.dataset.installStatus);
    let busy = false;
    const connectionStates = restoredConnections || {
        database: root.dataset.databaseVerified === 'true' ? 'success' : 'idle',
        redis: root.dataset.redisVerified === 'true' ? 'success' : 'idle',
    };
    const connectionRevision = { database: 0, redis: 0 };
    function renderConnection(kind) {
        const card = root.querySelector(`[data-connection="${kind}"]`);
        if (!card) return;
        const state = connectionStates[kind];
        card.dataset.state = state;
        card.querySelector('[data-test-result]').textContent = messages[`connection_${state}`];
        const form = card.closest('form');
        form.querySelector('button[type="submit"]').disabled = busy || state !== 'success';
    }
    function renderConnections() {
        Object.keys(connectionStates).forEach(renderConnection);
    }
    let restoreButton = null;
    const runButton = root.querySelector('[data-run]');
    const panels = [...root.querySelectorAll('.installation-panel[data-step]')];
    const setNotice = announce;
    function show(number, focus = true) {
        step = number;
        panels.forEach((panel) => { panel.hidden = Number(panel.dataset.step) !== step; });
        root.querySelector('[data-form-progress]').value = step;
        root.querySelector('[data-step-count]').textContent = format(messages.step_count, { current: numbers.format(step), total: numbers.format(panels.length) });
        root.querySelectorAll('[data-jump]').forEach((button) => {
            button.disabled = executing || Number(button.dataset.jump) > available || busy;
            button.classList.toggle('is-step-finished', Number(button.dataset.jump) < available);
            button.setAttribute('aria-label', button.textContent.trim());
            if (Number(button.dataset.jump) === step) button.setAttribute('aria-current', 'step');
            else button.removeAttribute('aria-current');
        });
        root.querySelectorAll('[data-back]').forEach((button) => { button.disabled = executing || busy; });
        if (focus) panels.find((panel) => Number(panel.dataset.step) === step)?.querySelector('h2')?.focus();
    }
    function setBusy(value) {
        if (value) {
            const button = document.activeElement;
            root.querySelectorAll('button').forEach((item) => { item.dataset.wasDisabled = String(item.disabled); });
            if (button?.tagName === 'BUTTON' && root.contains(button)) restoreButton = loadingButton(button);
            root.querySelectorAll('button').forEach((item) => { item.disabled = true; });
        } else {
            restoreButton?.();
            restoreButton = null;
            root.querySelectorAll('button').forEach((item) => { item.disabled = item.dataset.wasDisabled === 'true'; });
        }
        busy = value;
        root.setAttribute('aria-busy', String(value));
        renderConnections();
        show(step, false);
    }
    async function request(url, data = null) {
        const response = await fetch(url, {
            method: data === null ? 'GET' : 'POST', credentials: 'same-origin',
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
            ...(data === null ? {} : { body: JSON.stringify(data) }),
        });
        const result = await response.json();
        if (result.data?.completed) return result;
        if (!response.ok || !result.ok) {
            const error = new Error(result.message || messages.error);
            error.fields = result.errors;
            throw error;
        }
        return result;
    }
    function errors(error, form) {
        setNotice(error instanceof TypeError || error instanceof SyntaxError ? messages.error : (error.message || messages.error), true);
        if (form && error.fields) {
            Object.entries(error.fields).forEach(([name, messages]) => {
                const target = [...form.querySelectorAll('[data-error]')].find((element) => element.dataset.error === name);
                if (target) target.textContent = Array.isArray(messages) ? messages.join(' ') : messages;
                form.elements.namedItem(name)?.setAttribute('aria-invalid', 'true');
            });
            const invalid = form.querySelector('[aria-invalid="true"]');
            if (invalid?.classList.contains('select2-hidden-accessible')) jquery(invalid).select2('open');
            else invalid?.focus();
        }
    }
    function clearErrors(form) {
        form.querySelectorAll('[data-error]').forEach((element) => { element.textContent = ''; });
        form.querySelectorAll('[aria-invalid]').forEach((element) => element.removeAttribute('aria-invalid'));
    }
    function progress(data) {
        root.querySelector('[data-run-progress]').value = data.percent ?? 100;
        const current = root.querySelector(`[data-task="${data.task}"]`);
        root.querySelector('[data-run-label]').textContent = data.completed ? messages.complete : format(messages.progress_label, { percent: percent.format((data.percent ?? 0) / 100), task: current?.textContent || '' });
        root.querySelectorAll('[data-task]').forEach((item) => {
            item.classList.toggle('is-passed', Number(item.dataset.task) < data.task);
            item.classList.toggle('is-current', Number(item.dataset.task) === data.task);
        });
        if (data.completed) {
            root.querySelector('[data-login]').hidden = false;
            root.querySelector('[data-language-form]')?.setAttribute('hidden', '');
            root.querySelectorAll('.installation-actions').forEach((element) => { element.hidden = true; });
            setNotice(messages.complete);
        }
    }
    root.querySelectorAll('[data-jump]').forEach((button) => button.addEventListener('click', () => {
        if (!busy && !executing && Number(button.dataset.jump) <= available) show(Number(button.dataset.jump));
    }));
    root.querySelectorAll('[data-back]').forEach((button) => button.addEventListener('click', () => {
        if (!busy && !executing) show(Math.max(1, step - 1));
    }));
    root.querySelectorAll('form[data-step]').forEach((form) => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (busy) return;
            const connection = form.querySelector('[data-test]')?.dataset.test;
            if (connection && connectionStates[connection] !== 'success') { announce(messages.connection_idle, true); return; }
            const data = Object.fromEntries(new FormData(form));
            clearErrors(form);
            setBusy(true);
            try {
                const result = await request(root.dataset.save, data);
                available = Number(result.data.step) + 1;
                setNotice(result.message);
                show(available);
            } catch (error) { errors(error, form); }
            finally { setBusy(false); }
        });
        form.querySelectorAll('[data-test]').forEach((button) => {
            button.addEventListener('click', async () => {
                if (busy) return;
                const data = Object.fromEntries(new FormData(form));
                clearErrors(form);
                setBusy(true);
                const kind = button.dataset.test;
                const revision = connectionRevision[kind];
                connectionStates[kind] = 'pending';
                renderConnection(kind);
                try {
                    await request(root.dataset[kind], data);
                    if (revision === connectionRevision[kind]) {
                        connectionStates[kind] = 'success';
                        announce(messages.connection_success);
                    }
                } catch (error) {
                    if (revision === connectionRevision[kind]) connectionStates[kind] = 'error';
                    errors(error, form);
                }
                finally { setBusy(false); }
            });
        });
        form.addEventListener('input', (event) => {
            const kind = event.target.name?.startsWith('DB_') ? 'database' : event.target.name?.startsWith('REDIS_') ? 'redis' : null;
            if (kind) {
                connectionRevision[kind]++;
                connectionStates[kind] = 'idle';
                renderConnection(kind);
            }
            if (event.target.name === 'DB_CONNECTION') form.elements.DB_PORT.value = event.target.value === 'pgsql' ? '5432' : '3306';
        });
    });
    runButton?.addEventListener('click', async () => {
        if (busy) return;
        setBusy(true);
        setNotice(messages.working);
        try {
            const initial = await request(root.dataset.start, {});
            executing = true;
            show(6);
            progress(initial.data);
            while (!initial.data.completed) {
                const result = await request(root.dataset.next, {});
                progress(result.data);
                if (result.data.completed) break;
            }
        } catch (error) {
            errors(error);
            try { const result = await request(root.dataset.status); progress(result.data); } catch { /* Keep the retry action available. */ }
        } finally { setBusy(false); }
    });
    const initialProgress = root.querySelector('[data-run-progress]');
    if (initialProgress) root.querySelector('[data-run-label]').textContent = percent.format(initialProgress.value / 100);
    renderConnections();
    show(step, false);
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', () => initialize(document.querySelector('[data-installation]')));
else initialize(document.querySelector('[data-installation]'));
