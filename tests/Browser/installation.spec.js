import { test, expect } from '@playwright/test';

// Run with the isolated installation-router.php and the separate config.
test('wizard saves its first step, resumes and displays connection errors on mobile', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/');
    await expect(page).toHaveURL(/\/installation$/);
    await expect(page.locator('html')).toHaveAttribute('lang', 'en');
    await expect(page.locator('html')).toHaveAttribute('dir', 'ltr');
    await page.locator('[data-language-form] button[value="fa"]').click();
    await expect(page.locator('html')).toHaveAttribute('lang', 'fa');
    await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
    await expect(page.locator('form[data-step="1"]')).toBeVisible();
    await page.locator('form[data-step="1"] button[type="submit"]').click();
    await expect(page.locator('form[data-step="2"]')).toBeVisible();
    await page.reload();
    await expect(page.locator('form[data-step="2"]')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await page.locator('#DB_USERNAME').fill('browser-test');
    await page.locator('#DB_PORT').fill('1');
    await page.locator('[data-test="database"]').click();
    await expect(page.locator('[data-notice]')).toContainText('اتصال برقرار نشد');
    await expect(page.locator('form[data-step="2"]')).toBeVisible();
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: 'test-results/installation-mobile.png', fullPage: true });

    // Continue in the owning browser through the remaining wizard steps.
    // Rendered server markup is real; deterministic responses exercise all UI states.
    await page.goto('/installation');
    const root = page.locator('[data-installation]');
    let step = Number(await root.getAttribute('data-step'));
    let task = 0;
    await page.route('**/installation/test/*', (route) => route.fulfill({ json: { ok: true, message: 'اتصال با موفقیت برقرار شد.' } }));
    await page.route('**/installation/steps', async (route) => {
        step = Number(route.request().postDataJSON().step);
        await route.fulfill({ json: { ok: true, message: 'ذخیره شد', data: { step } } });
    });
    await page.route('**/installation/start', (route) => route.fulfill({ json: { ok: true, data: { task: 0, percent: 0 } } }));
    await page.route('**/installation/next', async (route) => {
        task++;
        await route.fulfill({ json: { ok: true, data: { task, percent: Math.floor(task / 6 * 100), completed: task === 6 } } });
    });
    if (step === 1) await page.locator('form[data-step="1"] button[type="submit"]').click();
    await page.locator('form[data-step="2"] button[type="submit"]').click();
    await expect(page.locator('form[data-step="3"]')).toBeVisible();
    await expect(page.locator('#MAIL_HOST')).toBeHidden();
    await page.locator('#MAIL_MAILER').selectOption('smtp');
    await expect(page.locator('#MAIL_HOST')).toBeVisible();
    await page.locator('#MAIL_MAILER').selectOption('log');
    await page.locator('[data-test="redis"]').click();
    await expect(page.locator('[data-test-result="redis"]')).toContainText('موفقیت');
    await page.locator('form[data-step="3"] button[type="submit"]').click();
    await page.locator('#LOG_CHANNEL').selectOption('stack');
    await expect(page.locator('#LOG_STACK')).toBeVisible();
    await page.locator('form[data-step="4"] button[type="submit"]').click();
    await page.locator('#APP_URL').fill('https://example.test');
    await page.locator('#ADMIN_NAME').fill('Owner');
    await page.locator('#ADMIN_EMAIL').fill('owner@example.test');
    await page.locator('#ADMIN_PASSWORD').fill('long-browser-password');
    await page.locator('#ADMIN_PASSWORD_confirmation').fill('long-browser-password');
    await page.locator('form[data-step="5"] button[type="submit"]').click();
    await page.locator('[data-run]').click();
    await expect(page.locator('[data-run-progress]')).toHaveAttribute('value', '100');
    await expect(page.locator('[data-login]')).toBeVisible();
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: 'test-results/installation-completed.png', fullPage: true });
});
