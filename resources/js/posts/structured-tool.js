import EditorJS from '@editorjs/editorjs';
import { iconMarkup } from '../common/icons';

/** Structured controls and nested editors shared by independently registered block drivers. */
export class StructuredTool {
    static get isReadOnlySupported() { return true; }
    constructor({data, config, api, readOnly, block}) {
        // PHP's empty defaults serialize as []; block data must be an object so
        // JSON.stringify retains named fields such as file, caption and sections.
        this.data = structuredClone({...data}); this.config = config; this.api = api; this.readOnly = readOnly;
        this.block = block;
        this.children = []; this.fields = [];
    }
    render() {
        const root = document.createElement('div'); root.className = 'editor-structured-block';
        root.dataset.blockKind = this.config.kind || '';
        const heading = document.createElement('header'); heading.className = 'editor-block-heading';
        heading.innerHTML = iconMarkup(this.config.icon || 'box', 20);
        const name = document.createElement('strong'); name.textContent = this.config.label; heading.append(name); root.append(heading);
        for (const [name, field] of Object.entries(this.config.fields || {})) {
            const label = document.createElement('label'); label.className = 'crud-field'; label.textContent = field.label || name;
            const input = document.createElement(field.type === 'textarea' ? 'textarea' : 'input');
            if (input.tagName === 'INPUT') input.type = field.type || 'text';
            input.value = this.data[name] ?? field.default ?? ''; input.disabled = this.readOnly; input.className = 'input';
            input.addEventListener('input', () => { this.data[name] = input.type === 'number' ? Number(input.value) : input.value; this.block.dispatchChange(); });
            label.append(input); root.append(label); this.fields.push([name, input]);
        }
        if (this.config.media) {
            const preview = document.createElement('div'); preview.className = 'editor-structured-media'; root.append(preview);
            const display = () => {
                preview.replaceChildren();
                const files = this.config.multiple ? this.data.files || [] : this.data.file ? [this.data.file] : [];
                if (!files.some(file => file.url)) {
                    const empty = document.createElement('div'); empty.className = 'editor-media-empty'; empty.innerHTML = iconMarkup(this.config.icon || 'media', 36);
                    const hint = document.createElement('span'); hint.textContent = this.config.emptyMedia; empty.append(hint); preview.append(empty);
                }
                files.filter(file => file.url).forEach(file => {
                    const row = document.createElement('figure'); row.className = 'editor-media-card';
                    if (this.config.media === 'image' && /^https?:\/\//i.test(file.url || '')) {
                        const img = document.createElement('img'); img.src = file.url; img.alt = file.name || ''; row.append(img);
                    }
                    if (['audio', 'video'].includes(this.config.media) && /^https?:\/\//i.test(file.url || '')) {
                        const player = document.createElement(this.config.media); player.src = file.url; player.controls = true; player.preload = 'metadata'; row.append(player);
                    } else if (this.config.media === 'file') { const icon = document.createElement('span'); icon.innerHTML = iconMarkup('file', 32); row.append(icon); }
                    const caption = document.createElement('figcaption');
                    const text = document.createElement('span'); text.textContent = file.name || file.url; text.title = text.textContent; caption.append(text);
                    if (!this.readOnly) {
                        const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'crud-icon-button'; remove.innerHTML = iconMarkup('trash', 18); remove.setAttribute('aria-label', this.config.removeMedia);
                        remove.onclick = () => { if (this.config.multiple) this.data.files = this.data.files.filter(item => item !== file); else this.data.file = []; display(); this.block.dispatchChange(); };
                        caption.append(remove);
                    }
                    row.append(caption); preview.append(row);
                });
            };
            display();
            if (!this.readOnly) {
                const button = document.createElement('button'); button.type = 'button'; button.className = 'vuero-button editor-media-choose'; button.innerHTML = iconMarkup('folder', 18); const label = document.createElement('span'); label.textContent = this.config.chooseMedia; button.append(label);
                button.addEventListener('click', async () => {
                    const items = await new Promise(resolve => root.dispatchEvent(new CustomEvent('jobic:editor:pick-media', {bubbles: true, detail: {resolve, groups: this.config.media === 'file' ? [] : [this.config.media], multiple: this.config.multiple}})));
                    if (!items.length) return;
                    if (this.config.multiple) this.data.files = items; else this.data.file = items[0];
                    display(); this.block.dispatchChange();
                }); root.append(button);
            }
        }
        if (this.config.nested) {
            this.data.sections ||= [{title: '', blocks: []}];
            const sections = document.createElement('div'); sections.className = 'editor-nested-sections'; root.append(sections);
            const tabs = this.config.kind === 'tabs' ? document.createElement('div') : null;
            if (tabs) { tabs.className = 'editor-nested-tabs'; tabs.setAttribute('role', 'tablist'); tabs.setAttribute('aria-label', this.config.label); sections.before(tabs); }
            const addSection = section => {
                const accordion = this.config.kind === 'accordion';
                const wrap = document.createElement(accordion ? 'details' : 'section'); wrap.className = 'editor-nested-section';
                if (accordion) {
                    wrap.open = true;
                    const summary = document.createElement('summary'); summary.textContent = section.title || this.config.sectionLabel; wrap.append(summary);
                }
                const title = document.createElement('input'); title.className = 'input'; title.value = section.title || ''; title.placeholder = this.config.sectionLabel; title.disabled = this.readOnly;
                title.setAttribute('aria-label', this.config.sectionLabel);
                const holder = document.createElement('div'); holder.className = 'editor-nested-content'; wrap.append(title, holder); sections.append(wrap);
                if (tabs) {
                    const tab = document.createElement('button'); tab.type = 'button'; tab.className = 'vuero-button'; tab.setAttribute('role', 'tab'); tab.textContent = section.title || this.config.sectionLabel;
                    const identifier = `editor-tab-${crypto.randomUUID()}`;
                    tab.id = identifier; wrap.id = `${identifier}-panel`;
                    tab.setAttribute('aria-controls', wrap.id); wrap.setAttribute('role', 'tabpanel'); wrap.setAttribute('aria-labelledby', tab.id);
                    const activate = () => {
                        [...tabs.children].forEach(button => { button.setAttribute('aria-selected', String(button === tab)); button.tabIndex = button === tab ? 0 : -1; });
                        [...sections.children].forEach(panel => { panel.hidden = panel !== wrap; });
                    };
                    tab.onclick = activate;
                    tab.onkeydown = event => {
                        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
                        event.preventDefault(); const items = [...tabs.children]; const direction = getComputedStyle(tabs).direction === 'rtl' ? -1 : 1;
                        const next = event.key === 'Home' ? 0 : event.key === 'End' ? items.length - 1 : (items.indexOf(tab) + (event.key === 'ArrowRight' ? direction : -direction) + items.length) % items.length;
                        items[next].click(); items[next].focus();
                    };
                    tabs.append(tab); activate();
                    title.addEventListener('input', () => { tab.textContent = title.value || this.config.sectionLabel; });
                }
                const child = new EditorJS({...this.config.editorOptions(), holder, minHeight: 50, data: {blocks: section.blocks || []}, readOnly: this.readOnly, onChange: () => this.block.dispatchChange()});
                title.addEventListener('input', () => { if (accordion) wrap.querySelector('summary').textContent = title.value || this.config.sectionLabel; this.block.dispatchChange(); });
                this.children.push({child, title});
            };
            this.data.sections.forEach(addSection);
            if (!this.readOnly) {
                const add = document.createElement('button'); add.type = 'button'; add.className = 'vuero-button editor-add-section'; add.innerHTML = iconMarkup('plus', 18); const label = document.createElement('span'); label.textContent = this.config.addSection; add.append(label);
                add.addEventListener('click', () => { addSection({title: '', blocks: []}); this.block.dispatchChange(); }); root.append(add);
            }
        }
        return root;
    }
    async save() {
        for (const [name, input] of this.fields) this.data[name] = input.type === 'number' ? Number(input.value) : input.value;
        if (this.config.nested) this.data.sections = await Promise.all(this.children.map(async ({child, title}) => {
            await child.isReady; return {title: title.value, blocks: (await child.save()).blocks};
        }));
        return this.data;
    }
    async destroy() { for (const {child} of this.children) { await child.isReady; child.destroy(); } }
}
