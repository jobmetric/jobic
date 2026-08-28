import { chromium } from 'playwright';
import { writeFileSync } from 'fs';
import { join } from 'path';

const outDir = join(process.cwd(), 'tmp', 'form-layout-screenshots');
const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

const candidates = [
    'https://vuero.cssninja.io/sidebar/layouts/form-layouts/form-layout-v1',
    'https://vuero.cssninja.io/sidebar/layouts/form-layouts/form-layout-1',
    'https://vuero.cssninja.io/sidebar/layouts/form-layout-v1',
    'https://vuero.cssninja.io/sidebar/layouts/profile-edit',
];

const found = [];
for (const url of candidates) {
    try {
        const res = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 25000 });
        found.push({ url, status: res?.status(), title: await page.title(), hasFormHeader: await page.locator('.form-header').count() });
    } catch (e) {
        found.push({ url, error: String(e) });
    }
}

writeFileSync(join(outDir, 'vuero-demo-probe.json'), JSON.stringify(found, null, 2));
console.log(JSON.stringify(found, null, 2));
await browser.close();
