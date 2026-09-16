import { test, expect } from '@playwright/test';

test('save buttons expose translated pending state and dashboard is complete', async ({ page }) => {
    await page.goto('/admin/settings/profile');
    let release;
    await page.route('**/admin/settings/profile', async (route) => {
        if (route.request().method() !== 'POST') return route.continue();
        await new Promise((resolve) => { release = resolve; });
        await route.continue();
    });
    const button = page.locator('form.cms-settings-form button[type="submit"]');
    const label = await button.textContent();
    await button.click();
    await expect(button).toBeDisabled();
    await expect(button).toHaveText('Loading…');
    release();
    await expect(button).toBeEnabled();
    await expect(button).toHaveText(label);
    await page.goto('/admin');
    await expect(page.locator('[data-dashboard-page]')).toHaveCSS('opacity', '1');
    await expect(page.locator('.ecommerce-v1')).toHaveClass(/is-animated/);
    await expect(page.locator('.ecommerce-chart-grid')).toHaveCount(3);
    await expect(page.locator('[data-order-row]')).toHaveCount(5);
    await expect(page.locator('.ecommerce-tile')).toHaveCount(4);
    const data = await page.locator('#jobic-page-data').textContent();
    expect(JSON.parse(data).ui.loading).toBe('Loading…');
    expect(await page.evaluate(() => window.localize.ui.loading)).toBe('Loading…');
    expect(await page.evaluate(() => typeof getLocalize)).toBe('function');
    await page.screenshot({path:'test-results/dashboard-restored.png',fullPage:true});
});
