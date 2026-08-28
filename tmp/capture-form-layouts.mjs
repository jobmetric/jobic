import { chromium } from 'playwright';
import { mkdirSync } from 'fs';
import { join } from 'path';

const baseUrl = process.env.BASE_URL ?? 'http://jobic.test';
const outDir = join(process.cwd(), 'tmp', 'form-layout-screenshots');
mkdirSync(outDir, { recursive: true });

const pages = [
    { name: 'v1', path: '/vuero/admin/layouts/form-layout-v1' },
    { name: 'v2', path: '/vuero/admin/layouts/form-layout-v2' },
    { name: 'v3', path: '/vuero/admin/layouts/form-layout-v3' },
    { name: 'v4', path: '/vuero/admin/layouts/form-layout-v4' },
    { name: 'v5', path: '/vuero/admin/layouts/form-layout-v5' },
];

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const page = await context.newPage();

await page.goto(`${baseUrl}/vuero/login`, { waitUntil: 'networkidle' });
await page.fill('#email', 'admin@jobic.test');
await page.fill('#password', 'secret');
await page.click('button[type="submit"]');
await page.waitForURL(/\/vuero\/admin/, { timeout: 15000 });

for (const item of pages) {
    await page.goto(`${baseUrl}${item.path}`, { waitUntil: 'networkidle' });
    await page.waitForSelector('.vuero-form-layout.is-ready, .vuero-form-layout', { timeout: 15000 });
    await page.waitForTimeout(500);

    const header = page.locator('[data-form-header]');
    const hasHeader = (await header.count()) > 0;

    await page.screenshot({ path: join(outDir, `${item.name}-top.png`), fullPage: false });

    if (hasHeader) {
        const before = await header.evaluate((el) => {
            const style = getComputedStyle(el);
            const rect = el.getBoundingClientRect();
            return {
                top: rect.top,
                position: style.position,
                classes: el.className,
                scrollY: window.scrollY,
            };
        });
        console.log(`${item.name} before scroll:`, JSON.stringify(before));
    }

    await page.evaluate(() => window.scrollTo(0, 400));
    await page.waitForTimeout(400);

    await page.screenshot({ path: join(outDir, `${item.name}-scrolled.png`), fullPage: false });

    if (hasHeader) {
        const after = await header.evaluate((el) => {
            const style = getComputedStyle(el);
            const rect = el.getBoundingClientRect();
            return {
                top: rect.top,
                position: style.position,
                classes: el.className,
                scrollY: window.scrollY,
            };
        });
        console.log(`${item.name} after scroll:`, JSON.stringify(after));
    }
}

await browser.close();
console.log(`Screenshots saved to ${outDir}`);
