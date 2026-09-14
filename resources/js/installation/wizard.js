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
document.querySelectorAll('.installation form').forEach((form) => {
    conditionals(form);
    form.addEventListener('input', () => conditionals(form));
});

const root = document.querySelector('[data-installation]');
if (root) {
    let step = Number(root.dataset.step);
    let available = step;
    let executing = ['running', 'failed'].includes(root.dataset.installStatus);
    let busy = false;
    const notice = root.querySelector('[data-notice]');
    const runButton = root.querySelector('[data-run]');
    const panels = [...root.querySelectorAll('.installation-panel[data-step]')];
    const setNotice = (message, error = false) => {
        notice.textContent = message;
        notice.classList.toggle('is-failed', error);
    };
    function show(number, focus = true) {
        step = number;
        const languageForm = root.querySelector('[data-language-form]');
        if (languageForm) languageForm.hidden = step !== 1 || executing;
        panels.forEach((panel) => { panel.hidden = Number(panel.dataset.step) !== step; });
        root.querySelector('[data-form-progress]').value = step;
        root.querySelector('[data-step-count]').textContent = `${step} / 6`;
        root.querySelectorAll('[data-jump]').forEach((button) => {
            button.disabled = executing || Number(button.dataset.jump) > available || busy;
            if (Number(button.dataset.jump) === step) button.setAttribute('aria-current', 'step');
            else button.removeAttribute('aria-current');
        });
        root.querySelectorAll('[data-back]').forEach((button) => { button.disabled = executing || busy; });
        if (focus) panels.find((panel) => Number(panel.dataset.step) === step)?.querySelector('h2')?.focus();
    }
    function setBusy(value) {
        busy = value;
        root.setAttribute('aria-busy', String(value));
        root.querySelectorAll('button').forEach((button) => {
            if (value) {
                button.dataset.wasDisabled = String(button.disabled);
                button.disabled = true;
            } else {
                button.disabled = button.dataset.wasDisabled === 'true';
            }
        });
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
            const error = new Error(result.message || root.dataset.errorMessage);
            error.fields = result.errors;
            throw error;
        }
        return result;
    }
    function errors(error, form) {
        setNotice(error.message || root.dataset.errorMessage, true);
        if (form && error.fields) {
            Object.entries(error.fields).forEach(([name, messages]) => {
                const target = [...form.querySelectorAll('[data-error]')].find((element) => element.dataset.error === name);
                if (target) target.textContent = Array.isArray(messages) ? messages.join(' ') : messages;
                form.elements.namedItem(name)?.setAttribute('aria-invalid', 'true');
            });
            form.querySelector('[aria-invalid="true"]')?.focus();
        }
    }
    function clearErrors(form) {
        form.querySelectorAll('[data-error]').forEach((element) => { element.textContent = ''; });
        form.querySelectorAll('[aria-invalid]').forEach((element) => element.removeAttribute('aria-invalid'));
    }
    function progress(data) {
        root.querySelector('[data-run-progress]').value = data.percent ?? 100;
        const current = root.querySelector(`[data-task="${data.task}"]`);
        root.querySelector('[data-run-label]').textContent = data.completed ? root.dataset.complete : `${data.percent}% — ${current?.textContent || ''}`;
        root.querySelectorAll('[data-task]').forEach((item) => {
            item.classList.toggle('is-passed', Number(item.dataset.task) < data.task);
            item.classList.toggle('is-current', Number(item.dataset.task) === data.task);
        });
        if (data.completed) {
            root.querySelector('[data-login]').hidden = false;
            root.querySelectorAll('.installation-actions').forEach((element) => { element.hidden = true; });
            setNotice(root.dataset.complete);
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
                const output = form.querySelector(`[data-test-result="${button.dataset.test}"]`);
                output.textContent = root.dataset.working;
                try {
                    const result = await request(root.dataset[button.dataset.test], data);
                    output.textContent = result.message;
                } catch (error) { output.textContent = ''; errors(error, form); }
                finally { setBusy(false); }
            });
        });
        form.addEventListener('input', (event) => {
            form.querySelectorAll('[data-test-result]').forEach((item) => { item.textContent = ''; });
            if (event.target.name === 'DB_CONNECTION') form.elements.DB_PORT.value = event.target.value === 'pgsql' ? '5432' : '3306';
        });
    });
    runButton?.addEventListener('click', async () => {
        if (busy) return;
        setBusy(true);
        setNotice(root.dataset.working);
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
    show(step, false);
}
