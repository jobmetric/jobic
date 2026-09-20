import { test, expect } from '@playwright/test';
import { execFileSync } from 'node:child_process';
import { createHmac, randomBytes } from 'node:crypto';

const email = `two-factor-browser-${randomBytes(8).toString('hex')}@example.test`;
const password = 'browser-test-password';
const php = process.env.JOBIC_TEST_PHP || 'C:/laragon/bin/php/php-8.5.8-nts-Win32-vs17-x64/php.exe';
function fixture(action) {
    execFileSync(php, ['tests/Browser/fixtures/two-factor.php', action], { env: { ...process.env, JOBIC_2FA_TEST_EMAIL: email } });
}
function otp(secret) {
    const alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    const bits = [...secret].map(c => alphabet.indexOf(c).toString(2).padStart(5, '0')).join('');
    const key = Buffer.from(bits.match(/.{8}/g).map(byte => parseInt(byte, 2)));
    const counter = Buffer.alloc(8);
    counter.writeBigUInt64BE(BigInt(Math.floor(Date.now() / 30000)));
    const hash = createHmac('sha1', key).update(counter).digest();
    const offset = hash[hash.length - 1] & 15;
    return String((hash.readUInt32BE(offset) & 0x7fffffff) % 1000000).padStart(6, '0');
}

test.use({ storageState: { cookies: [], origins: [] } });
test.beforeAll(() => fixture('create'));
test.afterAll(() => fixture('delete'));

test('authenticator setup stays in place, supports both locales, and gates login', async ({ page }) => {
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto('/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.locator('.auth-form__submit').click();
    await page.waitForURL(/\/admin$/);
    for (const locale of ['en', 'fa']) {
        await page.goto('/admin/settings/appearance');
        await page.evaluate(async (locale) => {
            const form = document.querySelector('[data-appearance-form]');
            const data = new FormData(form);
            data.set('locale', locale);
            await fetch(form.action, { method: 'POST', body: data, headers: { Accept: 'application/json' } });
        }, locale);
        for (const width of [1440, 390]) {
            await page.setViewportSize({ width, height: 900 });
            await page.goto('/admin/settings/security');
            await expect(page.locator('html')).toHaveAttribute('lang', locale);
            await expect(page.locator('#new-password')).toBeVisible();
            await expect(page.locator('[data-two-factor-form]')).toBeVisible();
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
            await page.screenshot({ path: `test-results/security-${locale}-${width}.png`, fullPage: true });
        }
    }
    const form = page.locator('[data-two-factor-form]');
    await page.evaluate(() => { window.twoFactorDocument = document; });
    await form.locator('[name=current_password]').fill(password);
    let release;
    await page.route('**/admin/settings/security/two-factor', async route => {
        await new Promise(resolve => { release = resolve; });
        await route.continue();
    });
    const button = form.locator('[type=submit]');
    const label = await button.textContent();
    await button.click();
    await expect(button).toBeDisabled();
    await expect(button).not.toHaveText(label);
    await expect.poll(() => Boolean(release)).toBe(true);
    release();
    await expect(form.locator('img')).toBeVisible();
    await page.unroute('**/admin/settings/security/two-factor');
    const secret = await form.locator('.cms-two-factor-setup code').textContent();
    await expect(form.locator('[name=current_password]')).toHaveValue('');
    await form.locator('[name=current_password]').fill(password);
    await form.locator('[name=code]').fill('123');
    await form.locator('[type=submit]').click();
    await expect(form.locator('[name=code]')).toHaveAttribute('aria-invalid', 'true');
    await expect(form.locator('[type=submit]')).toBeEnabled();
    await form.locator('[name=code]').fill(otp(secret));
    await form.locator('[type=submit]').click();
    await expect(form.locator('.cms-two-factor-recovery code')).toHaveCount(8);
    const recovery = await form.locator('.cms-two-factor-recovery code').first().textContent();
    expect(await page.evaluate(() => window.twoFactorDocument === document)).toBe(true);
    await expect(form.locator('.cms-two-factor-setup')).toHaveCount(0);
    await expect(form.locator('[name=current_password]')).toHaveValue('');
    await page.screenshot({ path: 'test-results/security-enabled-mobile.png', fullPage: true });
    await page.evaluate(async () => {
        await fetch('/logout', { method: 'POST', headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content } });
    });
    await page.goto('/login');
    await page.locator('#email').fill(email);
    await page.locator('#password').fill(password);
    await page.locator('.auth-form__submit').click();
    await page.waitForURL('**/login/two-factor');
    const denied = await page.request.get('/admin', { maxRedirects: 0 });
    expect(denied.status()).toBe(302);
    await page.locator('#code').fill(recovery);
    await page.locator('.auth-form__submit').click();
    await page.waitForURL(/\/admin$/);
    await page.goto('/admin/settings/security');
    await expect(form.locator('.cms-two-factor-recovery')).toHaveCount(0);
    expect(errors).toEqual([]);
});
