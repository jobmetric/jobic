import { test, expect } from '@playwright/test';

test('CMS uses ecommerce dashboard and shared navigation on desktop and mobile', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/admin');
    await expect(page.locator('[data-dashboard-page="ecommerce-1"]')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Welcome back, Layout Test Admin' })).toBeVisible();
    await expect(page.locator('.ecommerce-tile')).toHaveCount(4);
    await expect(page.locator('html')).toHaveCount(1);
    await page.locator('[data-sidebar-panel-target="settings"]').click();
    await page.locator('#sidebar-panel-settings a[href$="/mail"]').click();
    await expect(page).toHaveURL(/\/admin\/settings\/mail$/);
    await expect(page.locator('[data-admin-shell]')).toBeVisible();
    await expect(page.locator('#MAIL_MAILER')).toBeVisible();
    await page.goto('/admin');
    await page.screenshot({ path: 'test-results/cms-ecommerce-dashboard.png', fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.locator('[data-sidebar-toggle]').click();
    await expect(page.locator('[data-admin-shell]')).toHaveClass(/is-sidebar-open/);
    await expect(page.locator('#admin-mobile-navigation')).toBeVisible();
    await page.locator('[data-sidebar-panel-target="settings"]').click();
    await page.locator('#sidebar-panel-settings a[href$="/mail"]').click();
    await expect(page.locator('#MAIL_MAILER')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    expect(errors).toEqual([]);
});
