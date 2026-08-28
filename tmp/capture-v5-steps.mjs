import { chromium } from 'playwright';
import { join } from 'path';

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });
await page.goto('http://jobic.test/vuero/login', { waitUntil: 'networkidle' });
await page.fill('#email', 'admin@jobic.test');
await page.fill('#password', 'secret');
await page.click('button[type="submit"]');
await page.waitForURL(/admin/);
await page.goto('http://jobic.test/vuero/admin/layouts/form-layout-v5', { waitUntil: 'networkidle' });
await page.waitForSelector('.vuero-form-layout.is-ready', { timeout: 15000 });

const outDir = join(process.cwd(), 'tmp/form-layout-screenshots');

for (let step = 0; step < 4; step += 1) {
    await page.click('[data-step-continue]');
    await page.waitForTimeout(600);
}

await page.screenshot({ path: join(outDir, 'v5-step4-options.png'), fullPage: true });

await page.click('[data-step-continue]');
await page.waitForTimeout(600);
await page.screenshot({ path: join(outDir, 'v5-step5-validation.png'), fullPage: true });

await browser.close();
console.log('saved step screenshots');
