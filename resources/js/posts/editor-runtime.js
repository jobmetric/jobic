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

const builtInTools = {Header, EditorjsList, Checklist, Quote, Warning, Table, CodeTool, Delimiter, RawTool, Embed, ImageTool};
const loadedScripts = new Map();

function loadScript(url) {
    if (!url) return Promise.resolve();
    if (!loadedScripts.has(url)) {
        loadedScripts.set(url, new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = url;
            script.async = true;
            script.onload = resolve;
            script.onerror = reject;
            document.head.append(script);
        }));
    }
    return loadedScripts.get(url);
}

function messages(locale) {
    if (locale !== 'fa') return {};
    return {
        ui: {toolbar: {toolbox: {Add: 'افزودن بلوک'}}, popover: {Filter: 'جستجو', 'Nothing found': 'موردی یافت نشد'}},
        toolNames: {Text: 'متن', Heading: 'تیتر', List: 'فهرست', Checklist: 'چک‌لیست', Quote: 'نقل‌قول', Warning: 'نکته', Table: 'جدول', Code: 'کد', Delimiter: 'جداکننده', 'Raw HTML': 'HTML خام', Image: 'تصویر', Embed: 'ویدئو'},
        tools: {warning: {Title: 'عنوان', Message: 'پیام'}, image: {Caption: 'توضیح تصویر'}},
    };
}

async function uploadImage(file, uploadUrl, failedMessage) {
    const payload = new FormData();
    payload.set('file', file);
    payload.set('parent_id', '0');
    const response = await fetch(uploadUrl, {
        method: 'POST',
        headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''},
        body: payload,
    });
    const result = await response.json();
    if (!response.ok || !result?.data?.src) throw new Error(result.message || failedMessage);
    return {success: 1, file: {url: result.data.src, id: result.data.id}};
}

/** Create an Editor.js instance from the registered server-side driver configuration. */
export async function createPostEditor(holder, config, {type, locale, postId = null, data = null, direction = 'auto', onChange = null} = {}) {
    await Promise.all(Object.values(config.tools || {}).map((definition) => loadScript(definition.script)));
    const tools = {};
    for (const [key, definition] of Object.entries(config.tools || {})) {
        const Tool = builtInTools[definition.class] || definition.class.split('.').reduce((target, part) => target?.[part], window);
        if (typeof Tool !== 'function') continue;
        const {class: ignoredClass, label: ignoredLabel, script: ignoredScript, ...options} = definition;
        if (key === 'image') options.config = {...(options.config || {}), uploader: {uploadByFile: (file) => uploadImage(file, config.uploadUrl, config.messages.failed)}};
        tools[key] = {class: Tool, ...options};
    }
    const options = {
        holder,
        tools,
        data: data && typeof data === 'object' ? data : undefined,
        minHeight: config.minHeight || 280,
        placeholder: config.messages.placeholder,
        ...(onChange ? {onChange} : {}),
        i18n: {direction: direction === 'auto' ? (['fa', 'ar'].includes(locale) ? 'rtl' : 'ltr') : direction, messages: messages(locale)},
    };
    const before = new CustomEvent('jobic:editor:before-init', {cancelable: true, detail: {type, locale, post_id: postId, holder, tools, options}});
    if (!window.dispatchEvent(before)) return null;
    const editor = new EditorJS(before.detail.options);
    await editor.isReady;
    window.dispatchEvent(new CustomEvent('jobic:editor:ready', {detail: {type, locale, post_id: postId, holder, editor}}));
    return editor;
}
