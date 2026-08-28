import { chromium } from 'playwright';
import { writeFileSync } from 'fs';
import { join } from 'path';

const outDir = join(process.cwd(), 'tmp', 'form-layout-screenshots');
const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

await page.goto('http://jobic.test/vuero/login', { waitUntil: 'networkidle' });
await page.fill('#email', 'admin@jobic.test');
await page.fill('#password', 'secret');
await page.click('button[type="submit"]');
await page.waitForURL(/admin/);
await page.goto('http://jobic.test/vuero/admin/layouts/form-layout-v1', { waitUntil: 'networkidle' });
await page.waitForSelector('.vuero-form-layout.is-ready');

const report = [];
for (const y of [0, 200, 400, 800, 1200]) {
    await page.evaluate((yy) => window.scrollTo(0, yy), y);
    await page.waitForTimeout(300);
    report.push(await page.evaluate(() => {
        const header = document.querySelector('[data-form-header]');
        const outer = document.querySelector('.form-outer');
        const content = document.querySelector('.admin-content');
        const h = header.getBoundingClientRect();
        const o = outer.getBoundingClientRect();
        const c = content?.getBoundingClientRect();
        const hs = getComputedStyle(header);
        const cancelBtn = header.querySelector('.form-button.is-light');
        const cs = cancelBtn ? getComputedStyle(cancelBtn) : null;
        return {
            scrollY: window.scrollY,
            headerTop: h.top,
            headerLeft: h.left,
            headerWidth: h.width,
            outerTop: o.top,
            outerWidth: o.width,
            contentLeft: c?.left,
            contentWidth: c?.width,
            classes: header.className,
            position: hs.position,
            topCss: hs.top,
            paddingEnd: hs.paddingInlineEnd,
            marginLeft: hs.marginInlineStart,
            boxShadow: hs.boxShadow,
            borderLeft: hs.borderInlineStartWidth,
            borderRadius: hs.borderRadius,
            cancelBg: cs?.backgroundColor,
            cancelBorder: cs?.border,
        };
    }));
}

writeFileSync(join(outDir, 'v1-inspection.json'), JSON.stringify(report, null, 2));
await page.screenshot({ path: join(outDir, 'v1-deep-scroll.png') });
await browser.close();
console.log(JSON.stringify(report, null, 2));
