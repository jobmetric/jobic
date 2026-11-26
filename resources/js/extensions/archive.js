import {copy, showDialog} from './shared';
import {pageData, loadingButton} from '../common/page';
import {sendSettings} from '../admin/settings';

// The native file input remains the keyboard and screen-reader source of truth.
export function bindArchive(form) {
    const input = form.querySelector('[name=archive]');
    const dropzone = form.querySelector('[data-archive-dropzone]');
    const preview = form.querySelector('[data-archive-review]');
    const prompt = form.querySelector('[data-archive-prompt]');
    const examples = form.querySelector('.extension-examples');
    const selection = form.querySelector('[data-archive-select]');
    const motion = form.querySelector('[data-archive-motion]');
    const motionTitle = motion.querySelector('[data-motion-title]');
    const motionMessage = motion.querySelector('[data-motion-message]');
    let startedAt = 0;
    const reducedMotion = () => matchMedia('(prefers-reduced-motion: reduce)').matches;
    const pause = duration => new Promise(resolve => setTimeout(resolve, reducedMotion() ? 0 : duration));
    async function settle(state, message) {
        await pause(Math.max(0, 2200 - (performance.now() - startedAt)));
        motion.classList.add('is-changing');
        await pause(180);
        motion.dataset.state = state;
        dropzone.dataset.state = state;
        motionTitle.textContent = copy()[state === 'error' ? 'archive_error_title' : 'archive_review_complete'];
        motionMessage.textContent = message;
        motion.classList.remove('is-changing');
        if (state === 'error') {
            motion.querySelector('[data-motion-retry]').hidden = false;
            motion.querySelector('[data-motion-retry]').focus();
        } else {
            await pause(1600);
            motion.classList.add('is-leaving');
            await pause(420);
        }
    }
    let discarding = false;
    async function retry(trigger = motion.querySelector('[data-motion-retry]')) {
        if (form.dataset.saving || confirming || discarding) return;
        discarding = true;
        const restore = loadingButton(trigger);
        form.dataset.saving = 'true'; form.setAttribute('aria-busy', 'true');
        syncToolbar();
        try {
            if (review) {
                const data = new FormData(); data.set('ticket', review.ticket);
                await sendSettings(review.discard_url, data);
            }
            input.value = ''; reset();
            form.querySelectorAll('[data-error-for],[data-settings-errors]').forEach(node => {node.textContent = '';});
            input.removeAttribute('aria-invalid'); input.removeAttribute('aria-describedby');
            input.focus();
        } catch (error) {
            selection.hidden = false; preview.hidden = true; prompt.hidden = true; motion.hidden = false;
            await settle('error', (error.status ? error.message : copy().archive_network_error) || copy().failed);
        } finally {
            restore(); discarding = false; delete form.dataset.saving; form.removeAttribute('aria-busy'); syncToolbar();
        }
    }
    motion.querySelector('[data-motion-retry]').addEventListener('click', () => retry());
    form.addEventListener('keydown', event => {
        if (event.key === 'Escape' && !motion.hidden && motion.dataset.state === 'error') retry();
    });
    // A queued unload request is best effort; scheduled expiry covers closed or disconnected clients.
    window.addEventListener('pagehide', () => {
        if (!review || form.dataset.saving) return;
        const data = new FormData(); data.set('ticket', review.ticket);
        data.set('_token', document.querySelector('meta[name="csrf-token"]').content);
        navigator.sendBeacon(review.discard_url, data);
    });
    window.addEventListener('pageshow', event => {
        if (event.persisted && review) { input.value = ''; reset(); }
    });
    let review = null, confirming = false, generation = 0, downloads = [], step = 'select';
    const number = value => new Intl.NumberFormat(document.documentElement.lang).format(value);
    function syncToolbar() {
        const pending = Boolean(form.dataset.saving);
        const save = form.querySelector('[data-crud-action=save]');
        if (save) {
            save.hidden = step !== 'review';
            if (!pending) save.textContent = step === 'review' ? copy().confirm_archive : copy().review_archive;
        }
        form.querySelector('[data-crud-action=cancel]').hidden = step !== 'select' || !pageData().extensionAccess.view;
        const back = form.querySelector('[data-archive-back]');
        back.hidden = step === 'select';
        back.disabled = pending;
        back.textContent = copy()[step === 'placed' ? 'upload_another' : 'back_to_upload'];
        form.querySelector('[data-archive-finish]').hidden = step !== 'placed' || !pageData().extensionAccess.view;
        form.querySelectorAll('[data-archive-step]').forEach((node, index) => {
            const active = step === 'select' ? 0 : step === 'placed' ? 2 : 1;
            node.toggleAttribute('aria-current', index === active);
            if (index === active) node.setAttribute('aria-current', 'step');
            node.dataset.complete = String(index < active);
        });
    }
    function showStep(value) {
        step = value;
        form.dataset.archiveStep = step;
        selection.hidden = !['select', 'invalid'].includes(step);
        preview.hidden = ['select', 'invalid'].includes(step);
        syncToolbar();
    }
    function reset() {
        motion.hidden = true; prompt.hidden = false;
        dropzone.removeAttribute('data-state');
        if (examples) { examples.hidden = false; examples.inert = false; }
        review = null; generation++;
        downloads.forEach(URL.revokeObjectURL); downloads = [];
        const file = input.files?.[0];
        form.querySelector('[data-archive-filename]').textContent = file ? `${file.name} · ${number(file.size)} ${copy().bytes_unit}` : copy().no_archive;
        dropzone.classList.toggle('has-file', Boolean(file));
        showStep('select');
    }
    form.addEventListener('click', event => {
        const back = event.target.closest('[data-archive-back]');
        if (back) retry(back);
    });
    input.addEventListener('change', () => {
        if (form.dataset.saving || confirming) return;
        reset();
        if (input.files?.length) form.requestSubmit();
    });
    ['dragenter', 'dragover'].forEach(name => dropzone.addEventListener(name, event => {event.preventDefault(); if (!form.dataset.saving) dropzone.classList.add('is-dragging');}));
    ['dragleave', 'drop'].forEach(name => dropzone.addEventListener(name, () => dropzone.classList.remove('is-dragging')));
    dropzone.addEventListener('drop', event => {
        event.preventDefault();
        if (form.dataset.saving || !event.dataTransfer?.files?.length) return;
        const transfer = new DataTransfer(); transfer.items.add(event.dataTransfer.files[0]);
        input.files = transfer.files; input.dispatchEvent(new Event('change', {bubbles: true}));
    });
    function download(selector, value, filename) {
        const url = URL.createObjectURL(new Blob([JSON.stringify(value, null, 2)], {type: 'application/json'}));
        downloads.push(url);
        const link = form.querySelector(selector); link.href = url; link.download = filename;
    }
    function render(report, expires = null, placementMessage = '') {
        const placed = Boolean(placementMessage);
        downloads.forEach(URL.revokeObjectURL); downloads = [];
        showStep(placed ? 'placed' : 'review');
        form.querySelector('[data-review-title]').textContent = placed ? placementMessage : copy().archive_ready;
        form.querySelector('[data-review-expiry]').textContent = expires ? copy().review_expires.replace(':time', expires) : copy().placement_complete;
        const manifest = report.manifest;
        const values = {name: manifest.title, type: manifest.extension, version: manifest.version, mode: copy()[manifest.multiple ? 'multiple' : 'single'], count: number(report.file_count), size: `${number(report.total_bytes)} ${copy().bytes_unit}`};
        Object.entries(values).forEach(([key, value]) => { form.querySelector(`[data-review-value=${key}]`).textContent = value; });
        const composition = report.composition || [];
        form.querySelector('[data-composition-panel]').hidden = composition.length === 0;
        const entries = form.querySelector('[data-composition-entries]'); entries.replaceChildren();
        composition.forEach(item => {
            const row = document.createElement('tr');
            [item.name, item.type, item.owner || '—'].forEach(value => { const cell = document.createElement('td'); cell.textContent = value; row.append(cell); });
            entries.append(row);
        });
        const body = form.querySelector('[data-archive-files]'); body.replaceChildren();
        report.files.forEach(file => {
            const row = document.createElement('tr');
            [file.path, number(file.bytes), file.sha256].forEach(value => { const cell = document.createElement('td'); cell.textContent = value; row.append(cell); });
            body.append(row);
        });
        form.querySelector('[data-archive-manifest]').textContent = JSON.stringify(manifest, null, 2);
        download('[data-download-report]', report, `${manifest.name}-${manifest.version}-report.json`);
        download('[data-download-manifest]', manifest, 'extension.json');
        form.querySelector('[data-review-title]').focus({preventScroll: true});
    }
    syncToolbar();

    return {
        syncToolbar,
        async submission(data) {
            if (confirming || ['placed', 'invalid'].includes(step)) return null;
            if (!review) return {url: form.action, data, generation, confirmed: false};
            confirming = true;
            try {
                const confirmed = await showDialog({title: copy().confirm_archive, description: copy().confirm_archive_hint.replace(':count', number(review.report.file_count)), items: review.report.files.map(file => ({name: file.path})), confirm: copy().confirm_archive});
                if (!confirmed) return null;
                const confirmation = new FormData(); confirmation.set('ticket', review.ticket); confirmation.set('confirm', '1');
                return {url: review.confirm_url, data: confirmation, confirmed: true, generation};
            } finally { confirming = false; }
        },
        async received(result, requestGeneration) {
            if (requestGeneration !== generation) return false;
            review = result;
            await settle('success', copy().archive_review_complete_hint);
            render(result.report, result.expires_at_display);
            preview.classList.remove('is-revealing'); void preview.offsetWidth; preview.classList.add('is-revealing');
            return true;
        },
        async placed(result, message) {
            await settle('success', copy().placement_complete);
            const version = form.elements.namedItem('_version');
            if (version && result.version) version.value = result.version;
            review = null; input.value = ''; generation++; render(result.report, null, message);
        },
        async failed(confirmed, error) {
            const messages = [...new Set(Object.values(error.errors || {}).flatMap(value => [].concat(value)).filter(Boolean))];
            await settle('error', messages.length ? messages.join(' ') : (error.status ? error.message : copy().archive_network_error) || copy().failed);
            input.setAttribute('aria-invalid', 'true');
            input.setAttribute('aria-describedby', motionMessage.id);
            if (confirmed) {
                review = null; showStep('invalid');
                form.querySelector('[data-review-expiry]').textContent = copy().review_invalid;
            }
        },
        busy(value, confirmed) {
            if (examples) examples.inert = value;
            if (value) {
                startedAt = performance.now();
                motion.classList.remove('is-changing', 'is-leaving');
                motion.dataset.state = 'working';
                dropzone.dataset.state = 'working';
                selection.hidden = false; preview.hidden = true;
                prompt.hidden = true; motion.hidden = false;
                if (examples) examples.hidden = true;
                motionTitle.textContent = copy()[confirmed ? 'placing_archive' : 'inspecting_archive'];
                motionMessage.textContent = copy()[confirmed ? 'archive_placing_hint' : 'archive_motion_hint'];
                motion.querySelector('[data-motion-file]').textContent = input.files?.[0]?.name || '';
                motion.querySelector('[data-motion-retry]').hidden = true;
                motionTitle.focus({preventScroll: true});
            }
            syncToolbar();
        },
    };
}
