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
import { StructuredTool } from './structured-tool';
import { withToolSettings } from './editor-tool-settings';

const builtInTools = {Header, EditorjsList, Checklist, Quote, Warning, Table, CodeTool, Delimiter, RawTool, Embed, StructuredTool};
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
        ui: {blockTunes: {toggler: {'Click to tune': 'تنظیمات بلوک', 'or drag to move': 'یا برای جابه‌جایی بکشید'}}, inlineToolbar: {converter: {'Convert to': 'تبدیل به'}}, toolbar: {toolbox: {Add: 'افزودن بلوک'}}, popover: {Filter: 'جستجو', 'Nothing found': 'موردی یافت نشد', 'Convert to': 'تبدیل به'}},
        blockTunes: {delete: {Delete: 'حذف', 'Click to delete': 'برای حذف کلیک کنید'}, moveUp: {'Move up': 'انتقال به بالا'}, moveDown: {'Move down': 'انتقال به پایین'}},
        toolNames: {Text: 'متن', Heading: 'تیتر', List: 'فهرست', Checklist: 'چک‌لیست', Quote: 'نقل‌قول', Warning: 'نکته', Table: 'جدول', Code: 'کد', Delimiter: 'جداکننده', 'Raw HTML': 'HTML خام', Image: 'تصویر', Embed: 'ویدئو', Bold: 'ضخیم', Italic: 'مورب', Link: 'پیوند', InlineCode: 'کد درون متن', 'Ordered List': 'فهرست شماره‌دار', 'Unordered List': 'فهرست بدون شماره'},
        tools: {
            warning: {Title: 'عنوان', Message: 'پیام'},
            image: {Caption: 'توضیح تصویر', 'With border': 'با کادر', 'With background': 'با پس‌زمینه', 'Stretch image': 'تصویر تمام‌عرض', 'With caption': 'با توضیح'},
            table: {'With headings': 'با سطر عنوان', 'Without headings': 'بدون سطر عنوان', Stretch: 'تمام‌عرض', Collapse: 'عرض معمولی', Heading: 'عنوان', 'Add column to left': 'افزودن ستون در چپ', 'Add column to right': 'افزودن ستون در راست', 'Delete column': 'حذف ستون', 'Add row above': 'افزودن سطر بالا', 'Add row below': 'افزودن سطر پایین', 'Delete row': 'حذف سطر'},
            list: {Unordered: 'بدون شماره', Ordered: 'شماره‌دار', Checklist: 'چک‌لیست', 'Start with': 'شماره شروع', 'Counter type': 'نوع شماره‌گذاری', Numeric: 'عددی', 'Lower Roman': 'رومی کوچک', 'Upper Roman': 'رومی بزرگ', 'Lower Alpha': 'حروف کوچک', 'Upper Alpha': 'حروف بزرگ'},
            quote: {'Align Left': 'تراز چپ', 'Align Center': 'تراز وسط'},
            header: {Heading: 'تیتر', 'Heading 1': 'تیتر ۱', 'Heading 2': 'تیتر ۲', 'Heading 3': 'تیتر ۳', 'Heading 4': 'تیتر ۴', 'Heading 5': 'تیتر ۵', 'Heading 6': 'تیتر ۶'},
        },
    };
}

/** Create an Editor.js instance from the registered server-side driver configuration. */
export async function createPostEditor(holder, config, {type, locale, postId = null, data = null, direction = 'auto', onChange = null} = {}) {
    await Promise.all(Object.values(config.tools || {}).map((definition) => loadScript(definition.script)));
    const tools = {};
    for (const [key, definition] of Object.entries(config.tools || {})) {
        if (definition.style && !document.querySelector('link[data-editor-style="' + key + '"]')) {
            const link = document.createElement('link'); link.rel = 'stylesheet'; link.href = definition.style; link.dataset.editorStyle = key; document.head.append(link);
        }
        const Tool = definition.module ? (await import(/* @vite-ignore */ definition.module)).default : builtInTools[definition.class] || definition.class.split('.').reduce((target, part) => target?.[part], window);
        if (typeof Tool !== 'function') continue;
        const {class: ignoredClass, label: ignoredLabel, script: ignoredScript, ...options} = definition;
        if (definition.class === 'StructuredTool') {
            const wording = config.workbench || {};
            options.config = {...options.config, kind: key, icon: definition.icon, emptyMedia: wording.empty_media, removeMedia: wording.remove_media};
            options.config = {...options.config, label: wording.blocks?.[key] || definition.label, chooseMedia: wording.choose_media, addSection: wording.add_section, sectionLabel: wording.section, editorOptions: () => ({tools, i18n: {direction, messages: config.workbench?.i18n || messages(locale)}, placeholder: config.messages.placeholder})};
            options.config.fields = Object.fromEntries(Object.entries(options.config.fields || {}).map(([name, field]) => [name, {...field, label: wording.fields?.[name] || name}]));
            options.toolbox = {title: options.config.label, icon: '<svg viewBox="0 0 24 24"><path d="M4 4h16v16H4z" fill="none" stroke="currentColor"/></svg>'};
        }
        tools[key] = {class: withToolSettings(Tool), ...options};
    }
    const pending = [...(data?.blocks || [])];
    while (pending.length) {
        const block = pending.pop();
        if (!tools[block.type] && block.type !== 'paragraph') {
            tools[block.type] = {class: class {
                static get isReadOnlySupported() { return true; }
                constructor({data}) { this.data = structuredClone(data); }
                render() { const node = document.createElement('p'); node.className = 'crud-hint'; node.textContent = config.workbench?.unavailable || config.messages.unavailable; return node; }
                save() { return this.data; }
            }};
        }
        for (const section of block.data?.sections || []) pending.push(...(section.blocks || []));
    }
    const options = {
        holder,
        tools,
        data: data && typeof data === 'object' ? data : undefined,
        minHeight: config.minHeight || 280,
        placeholder: config.messages.placeholder,
        ...(onChange ? {onChange} : {}),
        i18n: {direction: direction === 'auto' ? (['fa', 'ar'].includes(locale) ? 'rtl' : 'ltr') : direction, messages: config.workbench?.i18n || messages(locale)},
    };
    const before = new CustomEvent('jobic:editor:before-init', {cancelable: true, detail: {type, locale, post_id: postId, holder, tools, options}});
    if (!window.dispatchEvent(before)) return null;
    const editor = new EditorJS(before.detail.options);
    await editor.isReady;
    window.dispatchEvent(new CustomEvent('jobic:editor:ready', {detail: {type, locale, post_id: postId, holder, editor}}));
    return editor;
}
