import { test, expect } from '@playwright/test';

test.beforeEach(async ({page}) => {
    await page.goto('/admin/settings/languages');
    if (await page.locator('html').getAttribute('lang') !== 'en') {
        await page.locator('.admin-topbar [data-panel-open=languages]').click();
        await page.locator('[data-cms-locale=en]').click();
        await expect(page.locator('html')).toHaveAttribute('lang', 'en');
        await page.keyboard.press('Escape');
    }
});

// Use the isolated installation-router fixture; never run this against production.
test('language CRUD preserves drafts, validates and supports atomic selection actions', async ({ page, baseURL }) => {
const errors = []; page.on('pageerror', error => errors.push(error.message));
const base = new URL('/admin/settings/languages', baseURL).href;
await page.goto(base+'/create');await page.evaluate(()=>window.originalDocument=document);
await page.locator('[data-crud-action=save]').click();await expect(page.locator('[data-error-for=name]')).not.toBeEmpty();await expect(page.locator('[name=name]')).toHaveAttribute('aria-invalid','true');
await page.locator('[name=name]').fill('Browser test language');await page.locator('#language-locale').fill('zz');
await page.route('**/settings/languages',async route=>{if(route.request().method()==='POST')await new Promise(r=>setTimeout(r,500));await route.continue();});
await page.locator('[data-crud-action=save]').click();await expect(page.locator('[data-crud-action=save]')).toBeDisabled();await expect(page.locator('[data-crud-action=save]')).toHaveText('Loading…');await expect(page).toHaveURL(/\/\d+\/edit$/);expect(await page.evaluate(()=>document===window.originalDocument)).toBe(true);
const id=page.url().match(/\/(\d+)\/edit$/)[1];await page.locator('[name=name]').fill('Edited language');await page.locator('[name=first_day_of_week]').selectOption('4', {force:true});await page.locator('[data-crud-action=save]').click();await expect(page.locator('[data-language-form]')).not.toHaveAttribute('aria-busy','true');
await page.locator('.crud-split-save summary').click();await page.locator('[data-crud-action=close]').click();await expect(page).toHaveURL(base);await expect(page.locator('#language-table tbody')).toContainText('Edited language');
await page.locator('[data-open-filters]').click();await page.locator('[data-filter-form] [name=status]').selectOption('0', {force:true});await page.locator('[data-filter-form] button[type=submit]').click();await expect(page.locator('[data-table-empty]')).toBeVisible();
await page.locator('[data-open-filters]').click();await page.locator('[data-filter-form] button[type=reset]').click();await expect(page.locator('#language-table tbody')).toContainText('Edited language');
await page.locator(`[data-row-id="${id}"]`).check();await page.locator('[data-crud-toolbar] summary').click();await page.locator('[data-crud-action=delete]').click();await expect(page.locator('[data-crud-dialog]')).toBeVisible();await page.locator('[data-dialog-confirm]').click();await expect(page.locator('[data-table-loading]')).toBeHidden();await expect(page.locator('#language-table tbody')).not.toContainText('Edited language');

expect(errors).toEqual([]);
});

// Seed only isolated fixture records and remove precisely those IDs afterwards.
test('selection survives AJAX pages and dialogs protect batch actions', async ({ page, baseURL }) => {
    const base = new URL('/admin/settings/languages', baseURL).href;
    await page.goto(base);
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': token };
    const ids = [];
    try {
        const response = await page.request.get(`${base}/data?length=100`);
        const used = new Set((await response.json()).data.data.map(row => row.locale));
        for (const suffix of 'abcdefghijklmnopqrstuvwxyz') {
            const locale = `x${suffix}`;
            if (used.has(locale)) continue;
            const response = await page.request.post(base, { headers, data: {
                name: `Selection fixture ${suffix}`, locale, direction: 'ltr',
                calendar: 'gregorian', first_day_of_week: 0, status: true,
            } });
            expect(response.ok(), await response.text()).toBeTruthy();
            ids.push((await response.json()).data.id);
            if (ids.length === 12) break;
        }
        expect(ids).toHaveLength(12);
        await page.route('**/settings/languages/data?**', async route => {
            await new Promise(resolve => setTimeout(resolve, 300));
            await route.continue();
        });
        await page.locator('[data-search]').fill('Selection fixture');
        await expect(page.locator('[data-row-id]')).toHaveCount(10);
        await expect(page.locator('#language-table tbody')).toContainText('Selection fixture');
        await page.locator(`[data-row-id="${ids[0]}"]`).check();
        await page.locator('#language-table_next a').click();
        await expect(page.locator('[data-table-loading]')).toBeVisible();
        await expect(page.locator('[data-crud-toolbar]')).toHaveAttribute('inert', '');
        await expect(page.locator('[data-row-id]')).toHaveCount(2);
        await page.locator('[data-row-id]').first().check();
        await page.locator('#language-table_previous a').click();
        await expect(page.locator(`[data-row-id="${ids[0]}"]`)).toBeChecked();
        await page.locator('[data-show-selected]').click();
        await expect(page.locator('.crud-dialog-item')).toHaveCount(2);
        await page.keyboard.press('Escape');
        await page.locator('[data-crud-toolbar] summary').click();
        await page.locator('[data-crud-action=activate]').click();
        await expect(page.locator('.crud-dialog-item')).toHaveCount(2);
        await page.locator('[data-dialog-cancel]').click();
        await expect(page.locator('[data-show-selected]')).toBeVisible();
        await page.locator('[data-crud-toolbar] summary').click();
        await page.locator('[data-crud-action=help]').click();
        await expect(page.locator('[data-dialog-title]')).toContainText('guide');
        await page.keyboard.press('Escape');
        await page.locator('#language-table tbody details summary').first().click();
        await expect(page.locator('#language-table tbody details').first()).toHaveAttribute('open', '');
        await page.locator('#language-table tbody details summary').first().click();
        await expect(page.locator('#language-table tbody details').first()).not.toHaveAttribute('open');
    } finally {
        if (ids.length) {
            const response = await page.request.post(`${base}/bulk`, {headers, data: {ids, operation: 'delete'}});
            expect(response.ok(), await response.text()).toBeTruthy();
        }
    }
});


test('shared dialogs dismiss on backdrop and read-only records expose editing', async ({page}) => {
    await page.goto('/admin/settings/languages');
    await expect(page.locator('[data-table-loading]')).toBeHidden();
    const id = await page.locator('[data-row-id]').first().getAttribute('data-row-id');
    async function dismiss(selector) {
        const dialog = page.locator(selector);
        await expect(dialog).toBeVisible();
        await dialog.evaluate(el => Promise.all(el.getAnimations().map(animation => animation.finished)));
        const bounds = await dialog.boundingBox();
        await page.mouse.click(bounds.x > 40 ? 5 : 1200, 400);
        await expect(dialog).toBeHidden();
    }
    await page.locator('[data-open-filters]').click();
    await dismiss('[data-filter-dialog]');
    await page.locator('[data-row-id]').first().check();
    await page.locator('[data-show-selected]').click();
    await dismiss('[data-crud-dialog]');
    await page.locator('[data-crud-toolbar] summary').click();
    await page.locator('[data-crud-action=help]').click();
    await expect(page.locator('.crud-help-section')).toHaveCount(5);
    await page.locator('.crud-help-section summary').nth(1).click();
    await expect(page.locator('.crud-help-section').nth(1)).toHaveAttribute('open', '');
    await dismiss('[data-crud-dialog]');
    await page.goto(`/admin/settings/languages/${id}`);
    await page.locator('[data-crud-action=edit]').click();
    await expect(page).toHaveURL(new RegExp(`/languages/${id}/edit$`));
    const cancel = await page.locator('[data-crud-action=cancel]').boundingBox();
    const save = await page.locator('[data-crud-action=save]').boundingBox();
    expect(Math.abs(cancel.y - save.y)).toBeLessThan(2);
    await expect(page.locator('.crud-toolbar h1')).toHaveText('Edit language');
    const toggle = await page.locator('.crud-split-save summary').boundingBox();
    expect(toggle.height).toBe(save.height);
    expect(toggle.y).toBe(save.y);
    await page.locator('[data-crud-action=save]').hover();
    await expect(page.locator('[data-crud-action=save]')).toHaveCSS('transform', 'none');
    await page.locator('#language-flag + .select2 .select2-selection').click();
    await expect(page.locator('.select2-results .cms-select-flag img').first()).toBeVisible();
    await page.keyboard.press('Escape');
    const saved = page.waitForResponse(response => response.request().method() === 'PUT' && response.url().includes('/appearance'));
    await page.locator('.admin-topbar [data-theme-toggle]').click();
    await saved;
    await expect(page.locator('[data-ui-toast]')).toBeHidden();
});

test('unsaved edits require confirmation and successful saves populate history', async ({page}) => {
    await page.goto('/admin/settings/languages/create');
    await page.locator('[name=name]').fill('Unsaved language');
    await page.locator('[data-crud-action=cancel]').click();
    await expect(page.locator('[data-dialog-title]')).toHaveText('Unsaved changes');
    await page.locator('[data-dialog-cancel]').click();
    await expect(page.locator('[name=name]')).toHaveValue('Unsaved language');
    await page.locator('[data-crud-action=cancel]').click();
    await page.locator('[data-dialog-confirm]').click();
    await expect(page).toHaveURL(/\/settings\/languages$/);
    const id = await page.locator('[data-row-id]').first().getAttribute('data-row-id');
    await page.goto(`/admin/settings/languages/${id}/edit`);
    const name = await page.locator('[name=name]').inputValue();
    await page.locator('[name=name]').fill(name + ' history');
    await page.locator('[data-crud-action=save]').click();
    await expect(page.locator('[data-language-form]')).not.toHaveAttribute('aria-busy','true');
    await expect(page.locator('[data-history-entries]')).toContainText('Updated');
    await page.locator('[data-history-entries] summary').first().click();
    await expect(page.locator('[data-history-entries]')).toContainText(name + ' history');
    await page.locator('[name=name]').fill(name);
    await page.locator('[data-crud-action=save]').click();
    await expect(page.locator('[data-language-form]')).not.toHaveAttribute('aria-busy','true');
    await page.locator('[data-crud-action=cancel]').click();
    await expect(page).toHaveURL(/\/settings\/languages$/);
});

test('stale form preserves drafts and reports a concurrent edit conflict', async ({page}) => {
    await page.goto('/admin/settings/languages');
    const id = await page.locator('[data-row-id]').first().getAttribute('data-row-id');
    await page.goto(`/admin/settings/languages/${id}/edit`);
    const original = await page.locator('[name=name]').inputValue();
    const values = await page.locator('[data-language-form]').evaluate(form => Object.fromEntries(new FormData(form)));
    const changed = await page.request.post(`/admin/settings/languages/${id}`, {form: {...values, name: original + ' concurrent'}, headers:{Accept:'application/json'}});
    expect(changed.ok(), await changed.text()).toBeTruthy();
    await page.locator('[name=name]').fill('My unsaved draft');
    await page.locator('[data-crud-action=save]').click();
    await expect(page.locator('[data-ui-toast]')).toContainText('another administrator');
    await expect(page.locator('[name=name]')).toHaveValue('My unsaved draft');
    await expect(page.locator('[data-crud-action=save]')).toBeEnabled();
    const current = await changed.json();
    const restored = await page.request.post(`/admin/settings/languages/${id}`, {form:{...values, _version:current.data.version, name:original}, headers:{Accept:'application/json'}});
    expect(restored.ok(), await restored.text()).toBeTruthy();
});
