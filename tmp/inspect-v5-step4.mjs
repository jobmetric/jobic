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

for (let i = 0; i < 3; i += 1) {
    await page.click('[data-step-continue]');
    await page.waitForTimeout(500);
}

const section = page.locator('#form-step-3');
await section.scrollIntoViewIfNeeded();
await page.waitForTimeout(300);

const outDir = join(process.cwd(), 'tmp/form-layout-screenshots');
await section.screenshot({ path: join(outDir, 'v5-step4-options-crop.png') });
await page.screenshot({ path: join(outDir, 'v5-step4-viewport.png') });

const inspection = await page.evaluate(() => {
    const options = document.querySelector('#form-step-3 .options');
    const cards = [...document.querySelectorAll('#form-step-3 .option')];
    return {
        optionsRect: options?.getBoundingClientRect(),
        optionsStyle: options ? getComputedStyle(options) : null,
        cards: cards.map((card, i) => {
            const inner = card.querySelector('.option-inner');
            const icon = card.querySelector('.lnil-icon, .lnil');
            return {
                index: i,
                cardRect: card.getBoundingClientRect(),
                cardWidth: getComputedStyle(card).width,
                innerRect: inner?.getBoundingClientRect(),
                iconSize: icon ? { w: icon.getBoundingClientRect().width, h: icon.getBoundingClientRect().height } : null,
                title: card.querySelector('h4')?.textContent,
            };
        }),
    };
});

console.log(JSON.stringify(inspection, null, 2));
await browser.close();
