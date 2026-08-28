import { chromium } from 'playwright';
import { mkdirSync, writeFileSync } from 'fs';
import { join } from 'path';

const outDir = join(process.cwd(), 'tmp', 'form-layout-screenshots');
mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch({ headless: true });
const context = await browser.newContext({ viewport: { width: 1440, height: 900 } });
const page = await context.newPage();

const demoUrls = [
    'https://vuero.cssninja.io/sidebar/layouts/form-layout-v1',
    'https://vuero.cssninja.io/sidebar/layouts/form-layouts-v1',
    'https://vuero.cssninja.io/sidebar/layouts/form-layout-1',
    'https://vuero.cssninja.io/sidebar/layouts/form-layouts-1',
];

for (const url of demoUrls) {
    try {
        const response = await page.goto(url, { waitUntil: 'domcontentloaded', timeout: 20000 });
        writeFileSync(join(outDir, 'demo-probe.txt'), `${url} -> ${response?.status()}\n`, { flag: 'a' });
    } catch (error) {
        writeFileSync(join(outDir, 'demo-probe.txt'), `${url} -> ${String(error)}\n`, { flag: 'a' });
    }
}

// Local deep inspection
await page.goto('http://jobic.test/vuero/login', { waitUntil: 'networkidle' });
await page.fill('#email', 'admin@jobic.test');
await page.fill('#password', 'secret');
await page.click('button[type="submit"]');
await page.waitForURL(/\/vuero\/admin/, { timeout: 15000 });

await page.goto('http://jobic.test/vuero/admin/layouts/form-layout-v1', { waitUntil: 'networkidle' });
await page.waitForSelector('.vuero-form-layout.is-ready', { timeout: 15000 });

const inspect = async (label) => {
    return page.evaluate((label) => {
        const header = document.querySelector('[data-form-header]');
        const outer = document.querySelector('.form-outer');
        const topbar = document.querySelector('.admin-topbar');
        if (!header || !outer) return { label, error: 'missing nodes' };
        const h = header.getBoundingClientRect();
        const o = outer.getBoundingClientRect();
        const t = topbar?.getBoundingClientRect();
        const hs = getComputedStyle(header);
        const os = getComputedStyle(outer);
        return {
            label,
            scrollY: window.scrollY,
            header: { top: h.top, left: h.left, width: h.width, height: h.height, classes: header.className, position: hs.position, topCss: hs.top, paddingEnd: hs.paddingInlineEnd, boxShadow: hs.boxShadow, borderRadius: hs.borderRadius, zIndex: hs.zIndex, background: hs.backgroundColor },
            outer: { top: o.top, width: o.width, borderRadius: os.borderRadius, overflow: os.overflow },
            topbar: t ? { top: t.top, height: t.height, position: getComputedStyle(topbar).position } : null,
        };
    }, label);
};

const report = [];
report.push(await inspect('initial'));
await page.evaluate(() => window.scrollTo(0, 500));
await page.waitForTimeout(500);
report.push(await inspect('scrolled-500'));
await page.evaluate(() => window.scrollTo(0, 1200));
await page.waitForTimeout(500);
report.push(await inspect('scrolled-1200'));

writeFileSync(join(outDir, 'v1-inspection.json'), JSON.stringify(report, null, 2));
await page.screenshot({ path: join(outDir, 'v1-scrolled-1200.png'), fullPage: false });

await browser.close();
console.log(JSON.stringify(report, null, 2));
