import { test, expect } from '@playwright/test';

test('settings use toast, original toolbar, Persian font and mirrored navigation', async ({ page }) => {
    await page.goto('/admin/settings/appearance');
    await page.locator('select[name="layout"]').selectOption('sidebar-default');
    await page.locator('select[name="locale"]').selectOption('en');
    await page.locator('[data-appearance-form] button[type="submit"]').click();
    await expect(page.locator('[data-ui-toast]')).toBeVisible();
    await expect(page.locator('.cms-settings-status')).toHaveCount(0);
    await expect(page.locator('.admin-topbar .admin-toolbar > button')).toHaveCount(3);
    await page.locator('.admin-topbar [data-panel-open="languages"]').click();
    await expect(page.locator('.cms-language-option')).toHaveCount(2);
    await page.locator('[data-cms-locale="fa"]').click();
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await page.goto('/admin/settings/security');
    await page.evaluate(() => document.fonts.ready);
    expect(await page.evaluate(async () => (await document.fonts.load('14px IRANSans')).length)).toBeGreaterThan(0);
    expect(await page.locator('body').evaluate((el) => getComputedStyle(el).fontFamily)).toContain('IRANSans');
    const sidebar = await page.locator('.admin-sidebar').boundingBox();
    expect(sidebar.x).toBeGreaterThan(1100);
    await page.locator('#new-password').fill('strong-password-123!');
    await expect(page.locator('.cms-password-strength')).toHaveJSProperty('value', 4);
    await page.locator('[data-password-toggle="new-password"]').click();
    await expect(page.locator('#new-password')).toHaveAttribute('type', 'text');
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: 'test-results/security-persian-desktop.png', fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.reload();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: 'test-results/security-persian-mobile.png', fullPage: true });
    await page.locator('[data-sidebar-toggle]').click();
    await expect(page.locator('.admin-sidebar')).toBeInViewport();
    await page.locator('[data-sidebar-toggle]').click();
    await page.setViewportSize({ width: 1280, height: 720 });
    for (const layout of ['sideblock-color', 'navbar-default', 'sidebar-float', 'sidebar-default']) {
        await page.goto('/admin/settings/appearance');
        await page.locator('select[name="layout"]').selectOption(layout);
        await page.locator('[data-appearance-form] button[type="submit"]').click();
        await expect(page.locator('[data-admin-shell]')).toHaveAttribute('data-selected-layout', layout);
        expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    }
    await page.locator('select[name="locale"]').selectOption('en');
    await page.locator('[data-appearance-form] button[type="submit"]').click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
});
