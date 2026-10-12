import { iconMarkup } from '../common/icons';
import { announce } from '../common/toast';
import { loadingButton } from '../common/page';
import { toolSettings } from './editor-tool-settings';
import '../../css/editor-workbench.css';
import previewStyles from '../../css/editor-preview.css?raw';

export function bindEditorWorkbench(holder, editor, config) {
    const root = holder.closest('[data-editor-workbench]');
    if (!root) return;
    const copy = config.workbench;
    const form = root.closest('form');
    const templates = root.querySelector('[data-editor-templates]');
    const sidebar = root.querySelector('[data-editor-sidebar]');
    const resize = document.createElement('div');
    resize.className = 'editor-sidebar-resize'; resize.tabIndex = 0;
    resize.setAttribute('role', 'separator'); resize.setAttribute('aria-orientation', 'vertical'); resize.setAttribute('aria-label', copy.resize_sidebar);
    sidebar.before(resize);
    const resizeSidebar = width => {
        const available = root.querySelector('.editor-workbench__layout').getBoundingClientRect().width;
        const maximum = Math.floor(available * .4);
        const minimum = Math.min(240, maximum);
        const value = Math.round(Math.max(minimum, Math.min(maximum, width)));
        root.style.setProperty('--editor-sidebar-width', `${value}px`);
        resize.setAttribute('aria-valuemin', String(minimum)); resize.setAttribute('aria-valuemax', String(maximum)); resize.setAttribute('aria-valuenow', String(value));
    };
    resize.addEventListener('pointerdown', event => {
        if (event.button !== 0) return;
        event.preventDefault(); resize.setPointerCapture(event.pointerId);
        root.classList.add('is-resizing');
    });
    resize.addEventListener('pointermove', event => {
        if (!resize.hasPointerCapture(event.pointerId)) return;
        const box = root.querySelector('.editor-workbench__layout').getBoundingClientRect();
        resizeSidebar(getComputedStyle(root).direction === 'rtl' ? event.clientX - box.left : box.right - event.clientX);
    });
    const stopResize = () => root.classList.remove('is-resizing');
    resize.addEventListener('pointerup', stopResize); resize.addEventListener('lostpointercapture', stopResize);
    resize.addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const direction = getComputedStyle(root).direction === 'rtl' ? 1 : -1;
        resizeSidebar(event.key === 'Home' ? 0 : event.key === 'End' ? Infinity : sidebar.getBoundingClientRect().width + (event.key === 'ArrowRight' ? 24 : -24) * direction);
    });
    const input = root.querySelector('[data-editor-content]');
    let busy = false;
    const openDialog = (title, content, confirm = null) => {
        const dialog = document.createElement('dialog'); dialog.className = 'crud-dialog editor-workbench-dialog';
        const header = document.createElement('header');
        const label = document.createElement('h2'); label.textContent = title;
        const close = document.createElement('button'); close.type = 'button'; close.className = 'crud-icon-button'; close.innerHTML = iconMarkup('x'); close.setAttribute('aria-label', copy.close);
        close.onclick = () => dialog.close(); header.append(label, close);
        const body = document.createElement('div'); body.className = 'crud-dialog-body'; body.append(content);
        const footer = document.createElement('footer');
        const cancel = document.createElement('button'); cancel.type = 'button'; cancel.className = 'vuero-button'; cancel.textContent = confirm ? copy.cancel : copy.close; cancel.onclick = () => dialog.close(); footer.append(cancel);
        let accepted = false;
        if (confirm) { const accept = document.createElement('button'); accept.type = 'button'; accept.className = 'vuero-button is-primary'; accept.textContent = confirm; accept.onclick = () => { accepted = true; dialog.close(); }; footer.append(accept); }
        dialog.append(header, body, footer); document.body.append(dialog);
        const result = new Promise(resolve => dialog.addEventListener('close', () => { resolve(accepted); dialog.remove(); }, {once: true}));
        dialog.showModal();
        return {dialog, result};
    };
    const showDialog = ({title, description, confirm}) => {
        const content = document.createElement('p'); content.textContent = description;
        return openDialog(title, content, confirm).result;
    };
    const sync = async () => {
        input.value = JSON.stringify(await editor.save());
        input.dispatchEvent(new Event('input', {bubbles: true}));
    };
    const previewDocument = html => {
        const dark = document.documentElement.dataset.theme === 'dark';
        const direction = getComputedStyle(holder).direction;
        return `<!doctype html><html dir="${direction}" data-theme="${dark ? 'dark' : 'light'}"><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; img-src http: https: data:; media-src http: https:; style-src 'unsafe-inline'"><style>${previewStyles}</style><body><main>${html}</main></body></html>`;
    };
    templates.addEventListener('click', () => {
        const patterns = config.templates || [];
        const layout = document.createElement('div'); layout.className = 'editor-pattern-layout';
        const menu = document.createElement('nav'); menu.className = 'editor-pattern-list'; menu.setAttribute('aria-label', copy.templates);
        const frame = document.createElement('iframe'); frame.className = 'editor-preview-frame'; frame.setAttribute('sandbox', 'allow-same-origin'); frame.title = copy.preview;
        layout.append(menu, frame);
        const {dialog} = openDialog(copy.patterns_title, layout);
        dialog.classList.add('editor-pattern-dialog');
        const footer = dialog.querySelector('footer');
        footer.replaceChildren();
        const hint = document.createElement('p'); hint.className = 'crud-hint'; hint.textContent = copy.pattern_hint;
        footer.append(hint);
        const insert = document.createElement('button'); insert.type = 'button'; insert.className = 'vuero-button is-primary'; insert.textContent = copy.insert_pattern; insert.disabled = !patterns.length;
        footer.append(insert);
        let selected = patterns[0];
        for (const pattern of patterns) {
            const button = document.createElement('button'); button.type = 'button'; button.className = 'vuero-button';
            const symbol = document.createElement('span'); symbol.className = 'editor-pattern-symbol'; symbol.innerHTML = iconMarkup('layout');
            const name = document.createElement('span'); name.textContent = pattern.label;
            button.append(symbol, name);
            button.onclick = () => {
                selected = pattern;
                [...menu.children].forEach(item => item.setAttribute('aria-pressed', String(item === button)));
                frame.title = pattern.label; frame.srcdoc = previewDocument(pattern.preview || '');
            };
            menu.append(button);
        }
        menu.firstElementChild?.click();
        insert.onclick = async () => {
            if (busy || !selected) return;
            busy = true; const restore = loadingButton(insert);
            try {
                const before = await editor.save();
                const filled = before.blocks.some(block => block.type !== 'paragraph' || String(block.data.text || '').replace(/<[^>]*>/g, '').trim() !== '');
                if (filled && !await showDialog({title: copy.replace_title, description: copy.replace_message, confirm: copy.replace})) return;
                try {
                    await editor.blocks.render(structuredClone(selected.document));
                    await sync();
                    dialog.close();
                } catch (error) {
                    await editor.blocks.render(before);
                    throw error;
                }
            } catch { announce(copy.template_failed, true); }
            finally { busy = false; restore(); }
        };
    });
    const fullscreen = root.querySelector('[data-editor-fullscreen]');
    const preview = root.querySelector('[data-editor-preview]');
    const heading = root.querySelector('[data-editor-heading]');
    const originalHeading = heading.textContent;
    const titleInput = form.querySelector(`[name="translation[${holder.dataset.locale}][title]"]`);
    const updateHeading = () => { heading.textContent = root.classList.contains('is-fullscreen') ? titleInput?.value.trim() || originalHeading : originalHeading; };
    titleInput?.addEventListener('input', updateHeading);
    const toggle = root.querySelector('[data-editor-sidebar-toggle]');
    const marker = document.createComment('editor workbench position');
    let previousScroll = 0;
    const setFullscreen = active => {
        if (active) {
            previousScroll = window.scrollY;
            root.before(marker);
            form.append(root);
        }
        root.classList.toggle('is-fullscreen', active);
        document.body.classList.toggle('editor-fullscreen-open', active);
        sidebar.hidden = !active; preview.hidden = !active; toggle.hidden = !active;
        updateHeading();
        if (active) resizeSidebar(sidebar.getBoundingClientRect().width || 300);
        fullscreen.innerHTML = iconMarkup(active ? 'minimize' : 'maximize', 18);
        const fullscreenLabel = document.createElement('span'); fullscreenLabel.textContent = active ? copy.exit : copy.fullscreen;
        fullscreen.append(fullscreenLabel);
        if (!active && marker.parentNode) {
            marker.replaceWith(root);
            window.scrollTo(0, previousScroll);
            fullscreen.focus();
        }
    };
    fullscreen.addEventListener('click', () => setFullscreen(!root.classList.contains('is-fullscreen')));
    toggle.addEventListener('click', () => root.classList.toggle('is-sidebar-open'));
    preview.addEventListener('click', async () => {
        const restore = loadingButton(preview);
        try {
            await editor.isReady;
            const content = await editor.save();
            input.value = JSON.stringify(content);
            input.dispatchEvent(new Event('input', {bubbles: true}));
            // Match the form's JSON field transport: request middleware must not
            // trim code blocks or turn optional empty strings into null.
            const response = await fetch(config.previewUrl, {method: 'POST', cache: 'no-store', headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''}, body: JSON.stringify({content: JSON.stringify(content)})});
            if (!response.ok) throw new Error('Preview failed');
            const {html} = await response.json();
            const frame = document.createElement('iframe'); frame.className = 'editor-preview-frame'; frame.title = copy.preview;
            frame.setAttribute('sandbox', 'allow-same-origin');
            const dark = document.documentElement.dataset.theme === 'dark';
            const direction = getComputedStyle(holder).direction;
            frame.srcdoc = `<!doctype html><html dir="${direction}" data-theme="${dark ? 'dark' : 'light'}"><meta charset="utf-8"><meta http-equiv="Content-Security-Policy" content="default-src 'none'; img-src http: https: data:; media-src http: https:; style-src 'unsafe-inline'"><style>${previewStyles}</style><body><main>${html}</main></body></html>`;
            const {dialog} = openDialog(copy.preview, frame);
            dialog.classList.add('editor-preview-dialog');
            dialog.querySelector('footer').remove();
            const symbol = document.createElement('span');
            symbol.className = 'crud-dialog-symbol';
            symbol.setAttribute('aria-hidden', 'true');
            symbol.innerHTML = iconMarkup('eye');
            dialog.querySelector('header').prepend(symbol);
        } catch { announce(copy.preview_failed, true); }
        finally { restore(); }
    });
    const editorMenuEscape = new WeakSet();
    window.addEventListener('keydown', event => {
        if (event.key === 'Escape' && holder.querySelector('.ce-popover--opened')) editorMenuEscape.add(event);
    }, true);
    document.addEventListener('keydown', event => {
        if (event.key !== 'Escape' || editorMenuEscape.has(event) || !root.classList.contains('is-fullscreen') || document.querySelector('dialog[open]')) return;
        if (root.querySelector('details[open]')) { root.querySelector('details[open]').open = false; return; }
        setFullscreen(false);
    });
    root.querySelectorAll('[data-editor-tab]').forEach(button => button.addEventListener('click', () => {
        root.querySelectorAll('[data-editor-tab]').forEach(item => {
            const active = item === button;
            item.classList.toggle('is-active', active);
            item.setAttribute('aria-selected', String(active));
        });
        root.querySelectorAll('[data-editor-panel]').forEach(panel => { panel.hidden = panel.dataset.editorPanel !== button.dataset.editorTab; });
    }));
    const list = root.querySelector('[data-editor-blocks]');
    const renderTools = query => {
        list.replaceChildren();
        const groups = new Map();
        for (const [key, tool] of Object.entries(config.tools || {})) {
            const label = copy.blocks?.[key] || tool.label || key;
            if (query && !label.toLocaleLowerCase().includes(query.toLocaleLowerCase())) continue;
            const category = tool.category || 'text';
            if (!groups.has(category)) {
                const group = document.createElement('section');
                const title = document.createElement('h3'); title.textContent = copy.categories?.[category] || category;
                group.append(title); list.append(group); groups.set(category, group);
            }
            const button = document.createElement('button'); button.type = 'button'; button.className = 'vuero-button editor-block-tile';
            button.innerHTML = iconMarkup(tool.icon || 'box', 32);
            const caption = document.createElement('span'); caption.textContent = label; button.append(caption);
            button.addEventListener('click', async () => {
                try {
                    editor.blocks.insert(key, structuredClone({...tool.defaults}));
                    await sync();
                    root.classList.remove('is-sidebar-open');
                    list.closest('dialog')?.close();
                } catch { announce(copy.block_failed, true); }
            });
            groups.get(category).append(button);
        }
        if (!groups.size) list.textContent = copy.empty;
    };
    renderTools('');
    const search = root.querySelector('[data-editor-search]');
    const searchBox = document.createElement('div'); searchBox.className = 'editor-block-search';
    searchBox.innerHTML = iconMarkup('search', 20); search.before(searchBox); searchBox.append(search);
    root.querySelector('[data-editor-search]').addEventListener('input', event => renderTools(event.target.value));
    const settings = root.querySelector('[data-editor-settings]');
    holder.addEventListener('mousedown', event => {
        if (!event.target.closest('.ce-toolbar__plus')) return;
        event.preventDefault(); event.stopImmediatePropagation();
    }, true);
    holder.addEventListener('click', async event => {
        const add = event.target.closest('.ce-toolbar__plus');
        if (!add) return;
        event.preventDefault(); event.stopImmediatePropagation();
        if (add) {
            const panel = root.querySelector('[data-editor-panel="blocks"]');
            const marker = document.createComment('block picker'); panel.before(marker);
            const wasHidden = panel.hidden; panel.hidden = false;
            const {dialog} = openDialog(copy.blocks_tab, panel);
            dialog.addEventListener('close', () => { marker.replaceWith(panel); panel.hidden = wasHidden; });
        }
    }, true);
    const renderSettings = async (block, target) => {
        const selectedId = block.id;
        const data = (await block.save())?.data || {};
        target.replaceChildren();
        const definition = config.tools[block.name];
        const fields = definition?.settings || {};
        const renderTunes = (items, container) => {
            if (items instanceof HTMLElement) { container.append(items); return; }
            if (!Array.isArray(items)) return;
            for (const item of items) {
                if (item.type === 'separator') continue;
                if (item.element instanceof HTMLElement) { container.append(item.element); continue; }
                if (item.children?.items) {
                    const group = document.createElement('details');
                    const title = document.createElement('summary'); title.textContent = item.label || item.title || ''; group.append(title);
                    renderTunes(item.children.items, group); container.append(group); continue;
                }
                if (typeof item.onActivate !== 'function') continue;
                const button = document.createElement('button'); button.type = 'button'; button.className = 'vuero-button editor-tune-option';
                button.innerHTML = item.icon || iconMarkup(definition?.icon || 'settings', 32);
                const caption = document.createElement('span'); caption.textContent = item.label || item.title || ''; button.append(caption);
                button.setAttribute('aria-pressed', String(Boolean(item.isActive))); button.disabled = Boolean(item.isDisabled);
                button.onclick = async () => {
                    try { await item.onActivate(); block.dispatchChange(); await sync(); await renderSettings(block, target); }
                    catch { announce(copy.block_failed, true); }
                };
                container.append(button);
            }
        };
        renderTunes(toolSettings(block), target);
        if (!Object.keys(fields).length && !target.childNodes.length) { target.textContent = copy.no_settings; return; }
        for (const [name, field] of Object.entries(fields)) {
            const label = document.createElement('label'); label.className = 'crud-field'; label.textContent = copy.fields?.[name] || field.label;
            const control = document.createElement('input'); control.className = 'input'; control.type = field.type || 'text'; control.value = data[name] ?? '';
            if (field.min !== undefined) control.min = field.min;
            if (field.max !== undefined) control.max = field.max;
            control.addEventListener('change', async () => {
                if (!control.checkValidity()) { control.reportValidity(); return; }
                try {
                const current = editor.blocks.getById(selectedId);
                if (!current) return;
                const saved = await current.save();
                await editor.blocks.update(selectedId, {...saved.data, [name]: control.type === 'number' ? Number(control.value) : control.value});
                await sync();
                } catch { announce(copy.block_failed, true); }
            });
            label.append(control); target.append(label);
        }
    };
    holder.addEventListener('click', async event => {
        const blockNode = event.target.closest('.ce-block');
        if (!blockNode) return;
        const block = editor.blocks.getBlockByElement(blockNode);
        if (!block) return;
        try { await renderSettings(block, settings); }
        catch { announce(copy.block_failed, true); }
    });
}
