import { test, expect } from '@playwright/test';

test('settings save without document navigation and use product branding', async ({ page }) => {
    await page.goto('/admin/settings/profile');
    await page.evaluate(() => { window.documentIdentity = 'same-document'; });
    await page.locator('input[name="name"]').fill('Layout Test Admin');
    await page.locator('form.cms-settings-form button[type="submit"]').click();
    await expect(page.locator('[data-ui-toast]')).toBeVisible();
    expect(await page.evaluate(() => window.documentIdentity)).toBe('same-document');
    await expect(page.locator('form.cms-settings-form')).not.toHaveAttribute('aria-busy', 'true');
    await page.locator('input[name="name"]').fill('x'.repeat(300));
    await page.locator('form.cms-settings-form button[type="submit"]').click();
    await expect(page.locator('[data-field-error]')).toBeVisible();
    await expect(page.locator('input[name="name"]')).toHaveAttribute('aria-invalid', 'true');
    expect(await page.evaluate(() => window.documentIdentity)).toBe('same-document');
    for (const section of ['general', 'mail']) {
        await page.goto(`/admin/settings/${section}`);
        await page.evaluate(() => { window.documentIdentity = 'settings-save'; });
        const saved = page.waitForResponse((response) => response.url().endsWith(`/settings/${section}`) && response.request().method() === 'POST');
        await page.locator('form.cms-settings-form button[type="submit"]').click();
        expect((await saved).status()).toBe(200);
        await expect(page.locator('form.cms-settings-form')).not.toHaveAttribute('aria-busy', 'true');
        expect(await page.evaluate(() => window.documentIdentity)).toBe('settings-save');
    }
    await page.goto('/admin/settings/appearance');
    await page.evaluate(() => { window.documentIdentity = 'language-save'; });
    await page.locator('select[name="locale"]').selectOption('fa', {force: true});
    await page.locator('form.cms-settings-form button[type="submit"]').click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'fa');
    expect(await page.evaluate(() => window.documentIdentity)).toBe('language-save');
    await expect(page.locator('.admin-topbar h1')).toContainText('ظاهر');
    await page.locator('select[name="locale"]').selectOption('en', {force: true});
    await page.locator('form.cms-settings-form button[type="submit"]').click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await page.goto('/admin/settings/security');
    await page.evaluate(() => { window.documentIdentity = 'security-error'; });
    await expect(page.locator('#current-password')).toHaveCount(0);
    await page.locator('#new-password').fill('new-password-123!');
    await page.locator('#confirm-password').fill('mismatched-password-123!');
    await page.locator('form.cms-settings-form button[type="submit"]').click();
    await expect(page.locator('[data-settings-errors]')).toBeVisible();
    expect(await page.evaluate(() => window.documentIdentity)).toBe('security-error');
    for (const url of ['/admin', '/admin/settings', '/admin/settings/mail']) {
        await page.goto(url);
        expect(await page.locator('body').innerText()).not.toMatch(/vuero|css ?ninja|Erik Kovalsky/i);
        await expect(page.locator('img[src$="/images/brand/jobmetric-transparent.png"]').first()).toBeAttached();
        expect(await page.locator('img[src$="/images/brand/jobmetric-transparent.png"]').first().evaluate((image) => image.complete && image.naturalWidth > 0)).toBe(true);
    }
});
