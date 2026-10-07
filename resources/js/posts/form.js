import EditorJS from '@editorjs/editorjs';
import Header from '@editorjs/header';
import EditorjsList from '@editorjs/list';
import Checklist from '@editorjs/checklist';
import Quote from '@editorjs/quote';
import Warning from '@editorjs/warning';
import Table from '@editorjs/table';
import CodeTool from '@editorjs/code';
import Delimiter from '@editorjs/delimiter';
import RawTool from '@editorjs/raw';
import Embed from '@editorjs/embed';
import ImageTool from '@editorjs/image';
import { announce } from '../common/toast';
import { guardUnsaved } from '../common/unsaved';
import { bindMediaManager } from '../taxonomies/media-manager';

const form = document.querySelector('[data-post-form]');
if (form) {
    form.noValidate = true;
    bindMediaManager(form);
    const editors = new Map();
    const tools = window.JobicEditorConfig?.tools || {};
    const builtInTools = { Header, EditorjsList, Checklist, Quote, Warning, Table, CodeTool, Delimiter, RawTool, Embed, ImageTool };
    const guard = guardUnsaved(form, () => window.confirm(window.JobicEditorConfig.messages.unsaved || 'تغییرات ذخیره نشده‌اند. خارج می‌شوید؟'));

    const messages = {
        fa: { direction: 'rtl', add: 'افزودن بلوک', text: 'متن', heading: 'تیتر', list: 'فهرست', quote: 'نقل‌قول', image: 'تصویر', table: 'جدول', code: 'کد' },
        en: { direction: 'ltr', add: 'Add block', text: 'Text', heading: 'Heading', list: 'List', quote: 'Quote', image: 'Image', table: 'Table', code: 'Code' },
    };
    const loadTool = definition => {
        if (!definition.script) return Promise.resolve();
        window.JobicLoadedEditorScripts ||= new Set();
        if (window.JobicLoadedEditorScripts.has(definition.script)) return Promise.resolve();
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = definition.script; script.async = true;
            script.onload = () => { window.JobicLoadedEditorScripts.add(definition.script); resolve(); };
            script.onerror = reject;
            document.head.append(script);
        });
    };
    const createEditor = async holder => {
        if (!EditorJS) { holder.textContent = window.JobicEditorConfig.messages.unavailable; return; }
        const locale = holder.dataset.locale;
        if (editors.has(locale)) return;
        let initial;
        const persisted = form.querySelector(`[data-editor-content="${CSS.escape(locale)}"]`)?.value || holder.dataset.content || '';
        try { initial = JSON.parse(persisted); } catch { initial = undefined; }
        try { await Promise.all(Object.values(tools).map(loadTool)); } catch { announce(window.JobicEditorConfig.messages.unavailable, true); return; }
        const configured = {};
        for (const [key, definition] of Object.entries(tools)) {
            const Tool = builtInTools[definition.class] || definition.class.split('.').reduce((target, part) => target?.[part], window);
            if (typeof Tool === 'function') {
                const {class: ignoredClass, label: ignoredLabel, ...options} = definition;
                if (key === 'image') {
                    options.config = {...(options.config || {}), uploader: {uploadByFile: file => uploadEditorImage(file)}};
                }
                configured[key] = {class: Tool, ...options};
            }
        }
        const editorOptions = {
            holder, minHeight: window.JobicEditorConfig.minHeight || 280, autofocus: false, placeholder: window.JobicEditorConfig.messages.placeholder,
            i18n: {
                direction: messages[locale]?.direction || (['fa', 'ar'].includes(locale) ? 'rtl' : 'ltr'),
                messages: editorMessages(locale),
            }, tools: configured,
            ...(initial && typeof initial === 'object' ? {data: initial} : {}),
            onChange: async api => {
                try {
                    const change = new CustomEvent('jobic:editor:before-save', {cancelable: true, detail: {locale, data: await api.saver.save()}});
                    if (!window.dispatchEvent(change)) return;
                    document.querySelector(`[data-editor-content="${CSS.escape(locale)}"]`).value = JSON.stringify(change.detail.data);
                }
                catch (error) { console.error('Editor.js save failed', error); }
            },
        };
        const beforeInit = new CustomEvent('jobic:editor:before-init', {cancelable: true, detail: {type: form.dataset.type, locale, holder, tools: configured, options: editorOptions}});
        if (!window.dispatchEvent(beforeInit)) return;
        const editor = new EditorJS(beforeInit.detail.options);
        editors.set(locale, editor);
        editor.isReady.then(() => window.dispatchEvent(new CustomEvent('jobic:editor:ready', {detail: {type: form.dataset.type, locale, holder, editor}})));
    };
    document.querySelectorAll('[data-locale-tab]').forEach(tab => tab.addEventListener('click', () => {
        document.querySelectorAll('[data-locale-tab]').forEach(item => item.setAttribute('aria-selected', String(item === tab)));
        document.querySelectorAll('[data-locale-panel]').forEach(panel => { panel.hidden = panel.dataset.localePanel !== tab.dataset.localeTab; });
        createEditor(form.querySelector(`[data-editor][data-locale="${CSS.escape(tab.dataset.localeTab)}"]`));
    }));
    createEditor(form.querySelector('[data-locale-panel]:not([hidden]) [data-editor]'));

    form.addEventListener('click', async event => {
        const button = event.target.closest('[data-revision-restore]');
        if (!button || !window.confirm(window.JobicEditorConfig.messages.confirmRevision)) return;
        button.disabled = true;
        try {
            const response = await fetch(button.dataset.url, {method: 'POST', headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':form.querySelector('[name=_token]').value}});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || window.JobicEditorConfig.messages.failed);
            announce(result.message);
            window.location.reload();
        } catch (error) {
            announce(error.message || window.JobicEditorConfig.messages.failed, true);
            button.disabled = false;
        }
    });

    async function uploadEditorImage(file) {
        const payload = new FormData(); payload.set('file', file); payload.set('parent_id', '0');
        const response = await fetch(window.JobicEditorConfig.uploadUrl, {method: 'POST', headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':form.querySelector('[name=_token]').value}, body: payload});
        const result = await response.json();
        const media = result?.data;
        if (!response.ok || !media?.src) throw new Error(result.message || window.JobicEditorConfig.messages.failed);
        return {success: 1, file: {url: media.src, id: media.id}};
    }

    function editorMessages(locale) {
        const fa = locale === 'fa';
        if (!fa) return {};
        return {
            ui: {toolbar: {toolbox: {Add: 'افزودن بلوک'}}, popover: {Filter: 'جستجو', 'Nothing found': 'موردی یافت نشد'}, blockTunes: {toggler: {'Click to tune': 'برای تنظیمات کلیک کنید', 'or drag to move': 'یا برای جابه‌جایی بکشید'}}},
            toolNames: {Text: 'متن', Heading: 'تیتر', List: 'فهرست', Checklist: 'چک‌لیست', Quote: 'نقل‌قول', Warning: 'نکته', Table: 'جدول', Code: 'کد', Delimiter: 'جداکننده', 'Raw HTML': 'HTML خام', Image: 'تصویر', Embed: 'ویدئو'},
            tools: {warning: {Title: 'عنوان', Message: 'پیام'}, image: {Caption: 'توضیح تصویر'}, quote: {'Align Left': 'چپ‌چین', 'Align Center': 'وسط‌چین'}},
            blockTunes: {delete: {Delete: 'حذف', 'Click to delete': 'برای حذف کلیک کنید'}, moveUp: {'Move up': 'انتقال به بالا'}, moveDown: {'Move down': 'انتقال به پایین'}},
        };
    }

    form.addEventListener('submit', async event => {
        event.preventDefault();
        if (form.dataset.saving) return;
        for (const [locale, editor] of editors) {
            await editor.isReady;
            const beforeSave = new CustomEvent('jobic:editor:before-save', {cancelable: true, detail: {locale, data: await editor.save()}});
            if (!window.dispatchEvent(beforeSave)) return;
            form.querySelector(`[data-editor-content="${CSS.escape(locale)}"]`).value = JSON.stringify(beforeSave.detail.data);
        }
        const submitted = guard.snapshot();
        form.querySelectorAll('[data-error-for]').forEach(node => node.textContent = '');
        const button = form.querySelector('button[type=submit]');
        const oldText = button.textContent;
        button.disabled = true; button.textContent = window.JobicEditorConfig.messages.saving; form.dataset.saving = 'true';
        try {
            const response = await fetch(form.action, {method: 'POST', headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':form.querySelector('[name=_token]').value}, body: new FormData(form)});
            const result = await response.json();
            if (!response.ok) {
                if (result.errors) for (const [name, messages] of Object.entries(result.errors)) {
                    const key = name.replace(/\.(\d+|[^.]+)/g, '[$1]').replace(/^translation/, 'translation');
                    const prefix = key.replace(/\[\d+\]$/, '').replace(/\[\]$/, '');
                    const field = form.querySelector(`[name="${CSS.escape(key)}"]`) || form.querySelector(`[name^="${CSS.escape(prefix)}"]`);
                    const error = [...form.querySelectorAll('[data-error-for]')].find(node => node.dataset.errorFor === name || name.startsWith(`${node.dataset.errorFor}.`));
                    if (error) error.textContent = [].concat(messages).join(' ');
                    field?.setAttribute('aria-invalid', 'true');
                }
                throw new Error(result.message || window.JobicEditorConfig.messages.failed);
            }
            announce(result.message);
            window.dispatchEvent(new CustomEvent('jobic:editor:after-save', {detail: {type: form.dataset.type, post: result.data, response: result}}));
            if (!form.querySelector('[name=_method]')) { history.replaceState({}, '', result.data.edit_url); form.action = result.data.edit_url; form.insertAdjacentHTML('afterbegin', '<input type="hidden" name="_method" value="PUT">'); }
            guard.saved(submitted);
        } catch (error) { announce(error.message || window.JobicEditorConfig.messages.failed, true); }
        finally { button.disabled = false; button.textContent = oldText; delete form.dataset.saving; }
    });
}
