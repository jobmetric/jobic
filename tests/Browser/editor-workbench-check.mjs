import {chromium} from 'playwright';
import assert from 'node:assert/strict';

// Isolated real Editor.js checks: run against `npx vite --host 127.0.0.1 --port 5178`.
const browser = await chromium.launch();
try {
    const page = await browser.newPage();
    const errors = [];
    page.on('requestfailed', request => console.error('Asset failed:', request.url(), request.failure()?.errorText));
    page.on('pageerror', error => errors.push(error.message));
    await page.route('http://127.0.0.1:5178/editor-check', route => route.fulfill({contentType: 'text/html; charset=utf-8', body: `<!doctype html><html lang="fa" dir="rtl"><meta charset="utf-8"><body>
        <form><input name="translation[fa][title]" value="عنوان آزمایشی">
        <section data-editor-workbench class="editor-workbench">
        <header class="editor-workbench__header"><strong data-editor-heading>محتوا</strong><div>
        <button type="button" data-editor-templates>الگو</button>
        <button type="button" data-editor-fullscreen>تمام صفحه</button><button type="button" data-editor-preview hidden>پیش‌نمایش</button><button type="button" data-editor-sidebar-toggle hidden>ابزارها</button></div></header>
        <div class="editor-workbench__layout"><div data-editor data-locale="fa" class="jobic-editor"></div><aside data-editor-sidebar hidden><div data-editor-panel="blocks"><input data-editor-search><div data-editor-blocks></div></div><div data-editor-panel="settings" hidden><div data-editor-settings></div></div></aside></div>
        <input type="hidden" data-editor-content></section></form><div data-ui-toast hidden><span data-ui-toast-message></span><button data-toast-dismiss></button></div></body></html>`}));
    await page.goto('http://127.0.0.1:5178/editor-check');
    await page.evaluate(async () => {
        const {createPostEditor} = await import('/resources/js/posts/editor-runtime.js');
        const {bindEditorWorkbench} = await import('/resources/js/posts/editor-workbench.js');
        const config = {
            previewUrl: '/draft-preview',
            tools: {paragraph: {class: 'Paragraph', icon: 'align-right', label: 'متن'}, header: {class: 'Header', icon: 'file-text', label: 'تیتر'}},
            messages: {placeholder: 'محتوا', failed: 'FAILED'},
            workbench: {close: 'بستن', cancel: 'انصراف', replace: 'جایگزینی', replace_title: 'تأیید', replace_message: 'جایگزین شود؟', fullscreen: 'تمام صفحه', exit: 'خروج', template_failed: 'TEMPLATE FAILED', preview: 'پیش‌نمایش', blocks_tab: 'بلوک‌ها', settings_tab: 'تنظیمات', move_up: 'بالا', move_down: 'پایین', delete_block: 'حذف'},
            templates: [{label: 'مقاله', preview: '<h2>مقدمه</h2><p>پیش‌نمایش مقاله</p>', document: {blocks: [{type: 'header', data: {text: 'مقدمه', level: 2}}, {type: 'paragraph', data: {text: 'متن نمونه برای ویرایش'}}]}}],
        };
        Object.assign(config.workbench, {templates: 'الگو', patterns_title: 'الگوهای آماده', insert_pattern: 'انتخاب و درج الگو', pattern_hint: 'محتوای زبان فعلی جایگزین می‌شود.'});
        config.tools.audio = {class: 'StructuredTool', icon: 'file-audio', label: 'صوت', config: {fields: {caption: {}}, media: 'audio', multiple: false}};
        config.templates.push({label: 'پرسش و پاسخ', preview: '<h2>پیش‌نمایش پرسش</h2>', document: {blocks: [{type: 'paragraph', data: {text: 'پرسش نمونه'}}]}});
        const holder = document.querySelector('[data-editor]');
        window.editor = await createPostEditor(holder, config, {locale: 'fa', data: {blocks: [{type: 'paragraph', data: {text: 'متن اصلی'}}]}});
        bindEditorWorkbench(holder, window.editor, config);
    });
    await page.locator('[data-editor-templates]').click();
    assert.equal(await page.locator('.editor-pattern-dialog h2').textContent(), 'الگوهای آماده');
    assert.equal(await page.locator('.editor-pattern-dialog footer button').count(), 1);
    for (const width of [390, 1280]) {
        await page.setViewportSize({width, height: 844});
        await page.locator('.editor-pattern-dialog').evaluate(node => {
            const box = node.getBoundingClientRect();
            if (box.width > innerWidth || box.height > innerHeight) throw new Error('Pattern dialog exceeds viewport');
        });
    }
    await page.locator('.editor-pattern-dialog .crud-dialog-body').evaluate(node => {
        if (node.scrollHeight > node.clientHeight + 1) throw new Error('Pattern body must not introduce nested scrolling');
    });
    await page.frameLocator('.editor-pattern-dialog iframe').getByText('پیش‌نمایش مقاله').waitFor();
    await page.locator('.editor-pattern-list button', {hasText: 'پرسش و پاسخ'}).click();
    await page.frameLocator('.editor-pattern-dialog iframe').getByText('پیش‌نمایش پرسش').waitFor();
    await page.locator('.editor-pattern-list button', {hasText: 'مقاله'}).click();
    assert.equal((await page.evaluate(() => window.editor.save())).blocks[0].data.text, 'متن اصلی');
    await page.getByRole('button', {name: 'انتخاب و درج الگو', exact: true}).click();
    await page.locator('dialog button', {hasText: 'انصراف'}).click();
    assert.equal((await page.evaluate(() => window.editor.save())).blocks[0].data.text, 'متن اصلی');
    await page.getByRole('button', {name: 'انتخاب و درج الگو', exact: true}).click();
    await page.locator('dialog button', {hasText: 'جایگزینی'}).click();
    await page.waitForFunction(() => document.querySelector('.ce-header'));
    assert.equal((await page.evaluate(() => window.editor.save())).blocks[1].data.text, 'متن نمونه برای ویرایش');
    assert.equal(await page.locator('[data-ui-toast]').isVisible(), false);
    await page.locator('[data-editor-fullscreen]').click();
    assert.equal(await page.locator('[data-editor-heading]').textContent(), 'عنوان آزمایشی');
    assert.equal(await page.locator('[data-editor-fullscreen] svg').count(), 1);
    assert.equal(await page.locator('[data-editor-preview]').isVisible(), true);
    assert.equal(await page.locator('[data-editor-save]').count(), 0);
    assert.equal(await page.locator('[data-editor-sidebar-toggle]').isVisible(), false);
    let submitted;
    const wav = Buffer.alloc(1644);
    wav.write('RIFF', 0); wav.writeUInt32LE(1636, 4); wav.write('WAVEfmt ', 8); wav.writeUInt32LE(16, 16);
    wav.writeUInt16LE(1, 20); wav.writeUInt16LE(1, 22); wav.writeUInt32LE(8000, 24); wav.writeUInt32LE(16000, 28);
    wav.writeUInt16LE(2, 32); wav.writeUInt16LE(16, 34); wav.write('data', 36); wav.writeUInt32LE(1600, 40);
    await page.route('**/preview-audio.wav', route => route.fulfill({contentType: 'audio/wav', body: wav}));
    await page.route('**/draft-preview', route => {
        submitted = JSON.parse(route.request().postDataJSON().content);
        return route.fulfill({contentType: 'application/json', body: JSON.stringify({html: '<p>Draft preview</p><audio controls preload="auto" src="http://127.0.0.1:5178/preview-audio.wav"></audio>'})});
    });
    await page.locator('.ce-paragraph').fill('تغییر همین لحظه بدون ذخیره');
    await page.evaluate(() => {
        document.querySelector('form').addEventListener('jobic:editor:pick-media', event => event.detail.resolve([{id: 42, url: location.origin + '/preview-audio.wav', name: 'Sample audio'}]), {once: true});
        window.editor.blocks.insert('audio', []);
    });
    await page.locator('[data-block-kind="audio"] .editor-media-choose').click();
    const audioDraft = (await page.evaluate(() => window.editor.save())).blocks.find(block => block.type === 'audio');
    assert.equal(audioDraft.data.file.id, 42);
    await page.locator('[data-editor-preview]').click();
    await page.frameLocator('.editor-preview-frame').getByText('Draft preview').waitFor();
    assert.equal(await page.locator('.editor-preview-dialog header .crud-dialog-symbol svg').count(), 1);
    assert.equal(await page.locator('.editor-preview-dialog footer').count(), 0);
    await page.locator('.editor-preview-dialog .crud-dialog-body').evaluate(node => {
        if (node.scrollHeight > node.clientHeight + 1) throw new Error('Preview body must not introduce nested scrolling');
    });
    await page.frameLocator('.editor-preview-frame').locator('audio').waitFor();
    await page.waitForFunction(() => document.querySelector('.editor-preview-frame').contentDocument.querySelector('audio').readyState >= 2);
    assert.equal(submitted.blocks.find(block => block.type === 'paragraph').data.text, 'تغییر همین لحظه بدون ذخیره');
    assert.equal(submitted.blocks.find(block => block.type === 'audio').data.file.id, 42);
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('.is-fullscreen').count(), 1);
    await page.evaluate(() => window.editor.blocks.delete(window.editor.blocks.getBlocksCount() - 1));
    await page.locator('.ce-header').click();
    await page.locator('.ce-toolbar__plus').dispatchEvent('click');
    await page.locator('dialog .editor-block-tile').first().waitFor();
    assert.equal(await page.locator('dialog .editor-block-tile svg').count(), 3);
    await page.keyboard.press('Escape');
    await page.locator('.ce-header').click();
    await page.locator('.ce-header').hover();
    await page.locator('.ce-toolbar__settings-btn').click({force: true});
    await page.locator('.ce-popover--opened > .ce-popover__container').first().waitFor();
    assert.equal(await page.locator('dialog[open]').count(), 0);
    await page.keyboard.press('Escape');
    const resize = page.locator('.editor-sidebar-resize');
    await resize.focus(); await page.keyboard.press('End');
    const dimensions = await page.evaluate(() => ({sidebar: document.querySelector('[data-editor-sidebar]').getBoundingClientRect().width, layout: document.querySelector('.editor-workbench__layout').getBoundingClientRect().width}));
    assert.ok(dimensions.sidebar <= dimensions.layout * .4 + 1);
    const handle = await resize.boundingBox();
    const dragY = Math.min(handle.y + 40, page.viewportSize().height - 40);
    await page.mouse.move(handle.x + handle.width / 2, dragY);
    await page.mouse.down(); await page.mouse.move(handle.x - 100, dragY); await page.mouse.up();
    assert.ok(await page.locator('[data-editor-sidebar]').evaluate(node => node.getBoundingClientRect().width) < dimensions.sidebar);
    await page.locator('[data-editor-sidebar] .editor-block-tile').first().evaluate(node => {
        const size = node.getBoundingClientRect();
        if (Math.abs(size.width - size.height) > 1) throw new Error('Block tile must be square');
    });
    await page.setViewportSize({width: 390, height: 844});
    assert.equal(await page.locator('[data-editor-sidebar-toggle]').isVisible(), true);
    await page.locator('[data-editor-sidebar-toggle]').click();
    assert.equal(await page.locator('[data-editor-sidebar]').isVisible(), true);
    await page.keyboard.press('Escape');
    assert.equal(await page.locator('[data-editor-heading]').textContent(), 'محتوا');
    assert.equal(await page.locator('[data-editor-fullscreen] svg').count(), 1);
    assert.equal(await page.locator('[data-editor-preview]').isVisible(), false);
    await page.evaluate(async () => {
        const {StructuredTool} = await import('/resources/js/posts/structured-tool.js');
        for (const kind of ['image', 'gallery', 'audio', 'video', 'file', 'cover', 'mediaText', 'playlist']) {
            const multiple = ['gallery', 'playlist'].includes(kind);
            const tool = new StructuredTool({data: [], config: {kind, fields: {caption: {}}, media: 'file', multiple}, block: {dispatchChange() {}}});
            const node = tool.render(); document.body.append(node);
            node.addEventListener('jobic:editor:pick-media', event => event.detail.resolve([{id: 42, url: location.origin + '/preview-audio.wav', name: 'Fixture'}]));
            node.querySelector('.editor-media-choose').click();
            await new Promise(resolve => setTimeout(resolve, 0));
            const output = JSON.parse(JSON.stringify(await tool.save()));
            if (Array.isArray(output) || (multiple ? output.files[0].id : output.file.id) !== 42) throw new Error(kind + ' lost selected media during JSON serialization');
            node.remove();
        }
        window.mediaChanges = 0;
        window.mediaTool = new StructuredTool({data: {files: [{id: 12, url: 'https://example.invalid/photo.png', name: 'Photo'}]}, config: {kind: 'gallery', label: 'گالری', icon: 'media', media: 'image', multiple: true, fields: {}, emptyMedia: 'انتخاب تصویر', removeMedia: 'حذف تصویر', chooseMedia: 'مدیریت فایل'}, block: {dispatchChange() { window.mediaChanges++; }}});
        const section = window.mediaTool.render(); section.id = 'media-design-test'; document.body.append(section);
        section.addEventListener('jobic:editor:pick-media', event => event.detail.resolve([{id: 13, url: 'https://example.invalid/new.png', name: 'Replacement'}]));
    });
    assert.equal(await page.locator('#media-design-test .editor-media-card').count(), 1);
    await page.locator('#media-design-test .crud-icon-button').click();
    assert.equal(await page.locator('#media-design-test .editor-media-empty').count(), 1);
    assert.deepEqual((await page.evaluate(() => window.mediaTool.save())).files, []);
    await page.locator('#media-design-test .editor-media-choose').click();
    assert.equal((await page.evaluate(() => window.mediaTool.save())).files[0].id, 13);
    assert.equal(await page.evaluate(() => window.mediaChanges), 2);
    await page.evaluate(async () => {
        const {StructuredTool} = await import('/resources/js/posts/structured-tool.js');
        window.tabsTool = new StructuredTool({data: {sections: [{title: 'First', blocks: [{type: 'paragraph', data: {text: 'First content'}}]}, {title: 'Second', blocks: [{type: 'paragraph', data: {text: 'Second content'}}]}]}, config: {kind: 'tabs', label: 'Tabs', nested: true, fields: {}, sectionLabel: 'Section', addSection: 'Add', editorOptions: () => ({tools: {}, i18n: {direction: 'rtl'}})}, block: {dispatchChange() {}}});
        const root = window.tabsTool.render(); root.id = 'tabs-design-test'; document.body.append(root);
        await Promise.all(window.tabsTool.children.map(item => item.child.isReady));
    });
    await page.locator('#tabs-design-test [role="tab"]').first().click();
    assert.equal(await page.locator('#tabs-design-test .editor-nested-section:not([hidden])').count(), 1);
    assert.equal((await page.evaluate(() => window.tabsTool.save())).sections[1].blocks[0].data.text, 'Second content');
    assert.deepEqual(errors, []);
    console.log('Editor workbench: templates, preview, compact block dialog, native tunes, sidebar resizing and mobile tools passed.');
} finally {
    await browser.close();
}
