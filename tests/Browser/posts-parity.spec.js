import {test, expect} from '@playwright/test';

test('post list and form use the shared CRUD layout without browser errors', async ({page}) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));

    await page.goto('/admin/posts/post');
    await expect(page.locator('[data-post-list]')).toBeVisible();
    await expect(page.locator('#posts-table')).toBeVisible();
    await expect(page.locator('[data-crud-toolbar] [data-crud-action="create"]')).toBeVisible();
    const operations = page.locator('[data-crud-toolbar] .post-list-operations');
    await expect(operations.locator('summary')).toBeVisible();
    await expect(operations.locator('[data-post-action]').first()).toHaveAttribute('data-crud-action', 'delete');
    await expect(operations.locator('[data-post-action="help"]')).toHaveCount(1);
    await expect(operations.locator('[data-post-action] svg')).toHaveCount(0);
    await expect(page.locator('[data-table-loading]')).toBeHidden();

    await page.goto('/admin/posts/post/create');
    await expect(page.locator('.crud-taxonomy-layout.has-sidebar')).toBeVisible();
    await expect(page.locator('.crud-translation-group')).toBeVisible();
    await expect(page.locator('.post-media-field')).toBeVisible();
    await expect(page.locator('[data-crud-toolbar] button[type="submit"]')).toHaveCount(3);

    const categories = page.locator('[data-taxonomy-quick-add][data-collection="categories"]');
    await expect(categories).toHaveAttribute('data-multiple', 'true');
    await categories.locator('[data-taxonomy-add-open]').click();
    await expect(categories.locator('[data-taxonomy-dialog]')).toBeVisible();
    await expect(categories.locator('[data-taxonomy-parent]')).toHaveCount(1);
    await categories.locator('[data-taxonomy-dialog-close]').last().click();

    const tags = page.locator('[data-taxonomy-quick-add][data-collection="tags"]');
    await expect(tags).toHaveAttribute('data-hierarchical', 'false');
    await tags.locator('[data-taxonomy-add-open]').click();
    await expect(tags.locator('[data-taxonomy-dialog]')).toBeVisible();
    await expect(tags.locator('[data-taxonomy-parent]')).toHaveCount(0);
    await tags.locator('[data-taxonomy-dialog-close]').last().click();
    expect(errors).toEqual([]);
});

test('switching the interface language updates post list and form without losing a draft', async ({page}) => {
    const switchTo = async locale => {
        await page.locator('.admin-topbar [data-panel-open="languages"]').first().click();
        await page.locator(`[data-cms-locale="${locale}"]`).click();
        await expect(page.locator('html')).toHaveAttribute('lang', locale);
        await page.locator('.language-panel [data-panel-close]').click();
    };

    await page.goto('/admin/posts/post');
    const initialLocale = await page.locator('html').getAttribute('lang');
    const targetLocale = initialLocale === 'fa' ? 'en' : 'fa';
    const copy = targetLocale === 'fa'
        ? {create: 'افزودن نوشته', workflow: 'گردش‌کار محتوا', length: 'تعداد در صفحه', media: 'تصویر', title: 'عنوان', status: 'وضعیت', updated_at: 'آخرین ویرایش', translations: 'ترجمه‌ها', actions: 'عملیات'}
        : {create: 'Add post', workflow: 'Content workflow', length: 'Per page', media: 'Image', title: 'Title', status: 'Status', updated_at: 'Last updated', translations: 'Translations', actions: 'Actions'};

    try {
        await switchTo(targetLocale);
        await expect(page.locator('[data-crud-action="create"]')).toContainText(copy.create);
        await expect(page.locator('[data-post-workflow-link]')).toContainText(copy.workflow);
        await expect(page.locator('#posts-table_length')).toContainText(copy.length);
        for (const column of ['media', 'title', 'status', 'updated_at', 'translations', 'actions']) {
            await expect(page.locator(`#posts-table thead th[data-column="${column}"]`)).toHaveText(copy[column]);
        }

        await page.goto('/admin/posts/post/create');
        const title = page.locator('input[name^="translation["][name$="[title]"]');
        await title.fill('Unsaved language-switch draft');
        await switchTo(initialLocale);
        await expect(title).toHaveValue('Unsaved language-switch draft');
        await expect(page.locator('[data-editor-templates]')).toContainText(initialLocale === 'fa' ? 'الگو' : 'Patterns');
        await expect(page.locator('[data-editor-heading]')).toHaveText(initialLocale === 'fa' ? 'محتوا' : 'Content');
        await expect(page.locator('[data-editor] [data-placeholder-active]').first()).toHaveAttribute('data-placeholder-active', initialLocale === 'fa' ? 'نوشتن محتوا را شروع کنید…' : 'Start writing…');
        await expect(page.locator('[data-editor-tab="blocks"]')).toHaveText(initialLocale === 'fa' ? 'افزودن بلوک' : 'Add block');
        await expect(page.locator('[data-editor-search]')).toHaveAttribute('placeholder', initialLocale === 'fa' ? 'جستجوی بلوک' : 'Search blocks');
    } finally {
        if (await page.locator('html').getAttribute('lang') !== initialLocale) await switchTo(initialLocale);
    }
});

test('list translation editor exposes the same patterns, fullscreen and preview tools', async ({page}) => {
    await page.goto('/admin/posts/post');
    await expect(page.locator('[data-table-loading]')).toBeHidden();
    const translation = page.locator('[data-post-translation-id]').first();
    test.skip(await translation.count() === 0, 'No posts are available in this environment.');

    await page.locator('.posts-column-translations .crud-locale-dropdown summary').first().click();
    await translation.click();
    const dialog = page.locator('[data-post-translation-dialog]');
    await expect(dialog).toBeVisible();
    await expect(dialog.locator('[data-editor-workbench]')).toBeVisible();
    const viewport = page.viewportSize();
    for (const width of [1920, 1024, 390]) {
        await page.setViewportSize({width, height: 900});
        await expect.poll(() => dialog.evaluate(node => node.scrollWidth <= node.clientWidth)).toBe(true);
        await expect.poll(() => dialog.locator('[data-post-translation-fields]').evaluate(node => node.scrollWidth <= node.clientWidth)).toBe(true);
    }
    await page.setViewportSize(viewport);
    await expect(dialog.locator('[data-editor-templates]')).toBeVisible();
    await dialog.locator('[data-editor-templates]').click();
    await expect(page.locator('.editor-pattern-dialog')).toBeVisible();
    await expect(page.locator('.editor-pattern-dialog')).toHaveClass(/is-nested/);
    await expect(page.locator('.editor-pattern-dialog .editor-pattern-list button').first()).toBeVisible();
    await page.locator('.editor-pattern-dialog > footer button').click();
    const replace = page.locator('.editor-workbench-dialog:not(.editor-pattern-dialog):not(.editor-preview-dialog)[open]');
    if (await replace.count()) await replace.locator('button.is-primary').click();
    await expect(page.locator('.editor-pattern-dialog')).toBeHidden();
    await expect.poll(async () => {
        const value = await dialog.locator('[data-editor-content]').inputValue();
        return JSON.parse(value).blocks.length;
    }).toBeGreaterThan(0);

    await dialog.locator('[data-editor-fullscreen]').click();
    const fullscreen = page.locator('.editor-fullscreen-dialog [data-editor-workbench]');
    await expect(fullscreen).toBeVisible();
    await expect(fullscreen.locator('[data-editor-preview]')).toBeVisible();
    const sidebarCount = await fullscreen.locator('[data-editor-sidebar] [data-editor-blocks] .editor-block-tile').count();
    await fullscreen.locator('.ce-toolbar__plus').first().evaluate(button => button.click());
    const blockDialog = page.locator('.editor-block-picker-dialog[open]');
    await expect(blockDialog).toBeVisible();
    await expect(blockDialog.locator('.editor-block-tile')).toHaveCount(sidebarCount);
    await expect(fullscreen.locator('[data-editor-sidebar] [data-editor-blocks] .editor-block-tile')).toHaveCount(sidebarCount);
    const popupWidth = await blockDialog.evaluate(node => getComputedStyle(node).width);
    await blockDialog.locator('[data-editor-search]').fill('zz-not-a-block');
    await expect(blockDialog.locator('.editor-block-tile')).toHaveCount(0);
    await blockDialog.locator('[data-editor-search]').fill('');
    await expect(blockDialog.locator('.editor-block-tile')).toHaveCount(sidebarCount);
    await page.keyboard.press('Escape');
    await fullscreen.locator('[data-editor-sidebar] .editor-block-tile').first().click();
    await expect(page.locator('.editor-fullscreen-dialog[open]')).toBeVisible();
    await fullscreen.evaluate(root => root.dispatchEvent(new CustomEvent('jobic:editor:pick-media', {
        bubbles: true, detail: {groups: ['image'], multiple: false, resolve: () => {}},
    })));
    await expect(page.locator('[data-media-manager][open]')).toBeVisible();
    await page.locator('[data-file-picker-close]').click();
    await expect(fullscreen).toBeVisible();
    await fullscreen.locator('[data-editor-preview]').click();
    await expect(page.locator('.editor-preview-dialog .editor-preview-frame')).toBeVisible();
    await page.keyboard.press('Escape');
    await fullscreen.locator('[data-editor-fullscreen]').click();
    await expect(dialog.locator('[data-editor-workbench]')).toBeVisible();
    await dialog.locator('[data-close-post-translation]').first().click();
    await page.goto('/admin/posts/post/create');
    await expect(page.locator('[data-editor-workbench]')).toBeVisible();
    await page.locator('[data-editor-fullscreen]').click();
    await page.locator('.editor-workbench.is-fullscreen .ce-toolbar__plus').first().evaluate(button => button.click());
    const formPopupWidth = await page.locator('.editor-block-picker-dialog[open]').evaluate(node => getComputedStyle(node).width);
    expect(popupWidth).toBe(formPopupWidth);
});

test('list translation dialog shows SEO fields in the same separate section as the form', async ({page}) => {
    await page.goto('/admin/posts/post');
    await expect(page.locator('[data-table-loading]')).toBeHidden();
    const translation = page.locator('[data-post-translation-id]').first();
    test.skip(await translation.count() === 0, 'No posts are available in this environment.');
    await page.locator('.posts-column-translations .crud-locale-dropdown summary').first().click();
    await translation.click();
    const dialog = page.locator('[data-post-translation-dialog]');
    await expect(dialog.locator('[data-post-translation-tabs]')).toBeVisible();
    await dialog.locator('[data-post-translation-tab="seo"]').click();
    const controlStyle = node => {
        const style = getComputedStyle(node);
        return Object.fromEntries(['minHeight', 'padding', 'borderRadius', 'borderColor', 'backgroundColor', 'color', 'fontSize', 'fontFamily', 'lineHeight'].map(key => [key, style[key]]));
    };
    const dialogStyles = {};
    for (const field of ['meta_title', 'meta_description', 'meta_keywords']) {
        await expect(dialog.locator(`[name="fields[${field}]"]`)).toBeVisible();
        await expect(dialog.locator(`[data-translation-hint="${field}"]`)).toBeVisible();
        dialogStyles[field] = await dialog.locator(`[name="fields[${field}]"]`).evaluate(controlStyle);
    }
    await expect(dialog.locator('[data-editor-workbench]')).toBeHidden();
    const hintStyles = await dialog.locator('[data-translation-hint="meta_title"]').evaluate(node => {
        const style = getComputedStyle(node);
        return {color: style.color, fontSize: style.fontSize, fontWeight: style.fontWeight};
    });
    await dialog.locator('[data-post-translation-tab="content"]').click();
    await expect(dialog.locator('[data-editor-workbench]')).toBeVisible();
    await page.goto('/admin/posts/post/create');
    for (const field of Object.keys(dialogStyles)) {
        const formStyle = await page.locator(`[name$="[${field}]"]`).first().evaluate(controlStyle);
        expect(dialogStyles[field]).toEqual(formStyle);
    }
    const formHintStyles = await page.locator('.crud-field-label-row > small').first().evaluate(node => {
        const style = getComputedStyle(node);
        return {color: style.color, fontSize: style.fontSize, fontWeight: style.fontWeight};
    });
    expect(hintStyles).toEqual(formHintStyles);
});

test('list translation tools use the live interface language after switching it', async ({page}) => {
    await page.goto('/admin/posts/post');
    const initialLocale = await page.locator('html').getAttribute('lang');
    const targetLocale = initialLocale === 'fa' ? 'en' : 'fa';
    const text = targetLocale === 'fa'
        ? {patterns: 'الگو', fullscreen: 'تمام‌صفحه', title: 'الگوهای آماده', insert: 'انتخاب و درج الگو'}
        : {patterns: 'Patterns', fullscreen: 'Fullscreen', title: 'Ready-made patterns', insert: 'Select and insert pattern'};
    const switchTo = async locale => {
        await page.locator('.admin-topbar [data-panel-open="languages"]').first().click();
        await page.locator(`[data-cms-locale="${locale}"]`).click();
        await expect(page.locator('html')).toHaveAttribute('lang', locale);
        await page.locator('.language-panel [data-panel-close]').click();
    };

    try {
        await switchTo(targetLocale);
        await expect(page.locator('[data-table-loading]')).toBeHidden();
        const translation = page.locator('[data-post-translation-id]').first();
        test.skip(await translation.count() === 0, 'No posts are available in this environment.');
        await page.locator('.posts-column-translations .crud-locale-dropdown summary').first().click();
        await translation.click();
        const dialog = page.locator('[data-post-translation-dialog]');
        await expect(dialog.locator('[data-editor-workbench]')).toBeVisible();
        await expect(dialog.locator('[data-editor-templates]')).toContainText(text.patterns);
        await expect(dialog.locator('[data-editor-fullscreen]')).toContainText(text.fullscreen);
        await dialog.locator('[data-editor-templates]').click();
        await expect(page.locator('.editor-pattern-dialog h2')).toHaveText(text.title);
        await expect(page.locator('.editor-pattern-dialog > footer button')).toHaveText(text.insert);
        await page.locator('.editor-pattern-dialog > footer button').click();
        const replace = page.locator('.editor-workbench-dialog:not(.editor-pattern-dialog):not(.editor-preview-dialog)[open]');
        if (await replace.count()) await replace.locator('button.is-primary').click();
        await expect(page.locator('.editor-pattern-dialog')).toBeHidden();
        const beforeSwitch = JSON.parse(await dialog.locator('[data-editor-content]').inputValue());
        await page.evaluate(locale => window.dispatchEvent(new CustomEvent('cms:preference', {detail: {locale}})), initialLocale);
        await expect(page.locator('html')).toHaveAttribute('lang', initialLocale);
        await expect(dialog.locator('[data-editor-templates]')).toContainText(initialLocale === 'fa' ? 'الگو' : 'Patterns');
        await expect(dialog.locator('[data-editor-workbench]')).toHaveAttribute('data-editor-bound-locale', initialLocale);
        await expect.poll(async () => JSON.parse(await dialog.locator('[data-editor-content]').inputValue()).blocks).toEqual(beforeSwitch.blocks);
        await dialog.locator('[data-editor-templates]').click();
        await expect(page.locator('.editor-pattern-dialog[open] h2')).toHaveText(initialLocale === 'fa' ? 'الگوهای آماده' : 'Ready-made patterns');
        await page.keyboard.press('Escape');
        await dialog.locator('[data-close-post-translation]').first().click();
    } finally {
        if (await page.locator('html').getAttribute('lang') !== initialLocale) await switchTo(initialLocale);
    }
});
