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
await page.waitForTimeout(500);
await page.screenshot({ path: join(process.cwd(), 'tmp/form-layout-screenshots/v5-current.png'), fullPage: true });
await browser.close();
console.log('saved v5-current.png');
