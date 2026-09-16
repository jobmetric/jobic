import { test, expect } from '@playwright/test';

test('Vuero shell supports every menu family, persisted appearance and settings sections', async ({ page }) => {
    const errors = [];
    page.on('pageerror', (error) => errors.push(error.message));
    await page.goto('/admin/settings');
    await expect(page.locator('.settings-grid .settings-box')).toHaveCount(5);
    await expect(page.locator('.admin-sidebar__footer .profile-dropdown')).toBeVisible();
    await page.locator('.admin-sidebar__footer [data-dropdown-toggle]').click();
    await expect(page.locator('.admin-sidebar__footer [role="menu"]')).toBeVisible();
    await page.locator('.admin-sidebar__footer a[href$="/profile"]').click();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await page.locator('.cms-settings-links a[href$="/appearance"]').click();
    for (const layout of ['sidebar-color-curved', 'sideblock-color', 'navbar-default', 'navbar-dropdown', 'navbar-clean-center', 'sidebar-default']) {
        await page.locator('[data-appearance-form] select[name="layout"]').selectOption(layout);
        const response = page.waitForResponse((response) => response.url().endsWith('/settings/appearance') && response.request().method() === 'POST');
        await page.locator('[data-appearance-form] button[type="submit"]').click();
        await response;
        await expect(page.locator('[data-admin-shell]')).toHaveAttribute('data-selected-layout', layout);
        await expect(page.locator('.cms-settings-links')).toBeVisible();
    }
    await page.locator('[data-appearance-form] select[name="theme"]').selectOption('dark');
    await page.locator('[data-appearance-form] select[name="motion"]').selectOption('reduced');
    await page.locator('[data-appearance-form] button[type="submit"]').click();
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
    await expect(page.locator('html')).toHaveAttribute('data-motion', 'reduced');
    await page.goto('/admin');
    await expect(page.locator('html')).toHaveAttribute('data-theme', 'dark');
    await page.locator('.admin-sidebar__footer [data-modal-open]').click();
    await expect(page.locator('#layout-switcher')).toHaveAttribute('aria-hidden', 'false');
    const saved = page.waitForResponse((response) => response.url().endsWith('/settings/appearance') && response.request().method() === 'PUT');
    await page.locator('[data-layout-option="sidebar-float"]').click();
    await saved;
    await page.reload();
    await expect(page.locator('[data-admin-shell]')).toHaveAttribute('data-selected-layout', 'sidebar-float');
    await page.locator('.admin-sidebar__footer [data-panel-open="search"]').click();
    await page.locator('[data-cms-search]').fill('Email');
    await expect(page.locator('[data-cms-search-item]:visible')).toHaveCount(1);
    await page.locator('[data-cms-search-item]:visible').click();
    await expect(page.locator('#MAIL_MAILER')).toBeVisible();
    await page.screenshot({ path: 'test-results/settings-vuero-dark.png', fullPage: true });
    // Restore defaults for independent tests and verify the settings hub in light mode.
    await page.goto('/admin/settings/appearance');
    await page.locator('select[name="layout"]').selectOption('sidebar-default');
    await page.locator('select[name="theme"]').selectOption('light');
    await page.locator('select[name="motion"]').selectOption('full');
    await page.locator('[data-appearance-form] button[type="submit"]').click();
    await page.goto('/admin/settings');
    await expect(page.locator('.profile-settings')).toHaveClass(/is-ready/);
    await page.screenshot({ path: 'test-results/settings-vuero-hub.png', fullPage: true });
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto('/admin/settings/mail');
    await expect(page.locator('#MAIL_MAILER')).toBeVisible();
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
    await page.screenshot({ path: 'test-results/settings-vuero-mobile.png', fullPage: true });
    expect(errors).toEqual([]);
});
