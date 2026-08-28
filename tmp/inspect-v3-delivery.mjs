import { chromium } from 'playwright';
import { writeFileSync, mkdirSync } from 'fs';
import { join } from 'path';

const outDir = join(process.cwd(), 'tmp', 'form-layout-screenshots');
mkdirSync(outDir, { recursive: true });

const browser = await chromium.launch({ headless: true });
const page = await browser.newPage({ viewport: { width: 1440, height: 900 } });

await page.goto('http://jobic.test/vuero/login', { waitUntil: 'networkidle' });
await page.fill('#email', 'admin@jobic.test');
await page.fill('#password', 'secret');
await page.click('button[type="submit"]');
await page.waitForURL(/admin/);
await page.goto('http://jobic.test/vuero/admin/layouts/form-layout-v3', { waitUntil: 'networkidle' });
await page.waitForSelector('.vuero-form-layout.is-ready');

const deliveryHeading = page.locator('h3', { hasText: 'Delivery' });
await deliveryHeading.scrollIntoViewIfNeeded();
await page.waitForTimeout(400);

const report = await page.evaluate(() => {
    const deliverySection = [...document.querySelectorAll('.form-section')].find((el) =>
        el.querySelector('h3')?.textContent?.trim() === 'Delivery',
    );
    if (!deliverySection) {
        return { error: 'Delivery section not found' };
    }

    const radioBoxes = deliverySection.querySelectorAll('.radio-box');
    const checkboxes = deliverySection.querySelectorAll('.checkbox.is-circle');
    const submitBtn = deliverySection.querySelector('.form-button');
    const deliveryMeta = deliverySection.querySelector('.delivery-meta');
    const sectionInner = deliverySection.querySelector('.form-section-inner');
    const sectionOuter = deliverySection.querySelector('.form-section-outer');

    const boxInfo = (el) => {
        const r = el.getBoundingClientRect();
        const s = getComputedStyle(el);
        return {
            tag: el.tagName.toLowerCase(),
            className: el.className,
            width: Math.round(r.width),
            height: Math.round(r.height),
            top: Math.round(r.top),
            left: Math.round(r.left),
            display: s.display,
            padding: s.padding,
            margin: s.margin,
            background: s.backgroundColor,
            border: s.border,
            fontSize: s.fontSize,
        };
    };

    const feeInfo = deliverySection.querySelector('.fee span');
    const feeAfter = feeInfo ? getComputedStyle(feeInfo, '::after').content : null;

    return {
        section: boxInfo(deliverySection),
        sectionInner: sectionInner ? boxInfo(sectionInner) : null,
        sectionOuter: sectionOuter ? boxInfo(sectionOuter) : null,
        radioBoxes: [...radioBoxes].map(boxInfo),
        radioInners: [...deliverySection.querySelectorAll('.radio-box-inner')].map(boxInfo),
        fees: [...deliverySection.querySelectorAll('.fee')].map((el) => ({
            text: el.textContent?.trim(),
            fontSize: getComputedStyle(el).fontSize,
            color: getComputedStyle(el).color,
        })),
        feeAfterContent: feeAfter,
        deliveryMeta: deliveryMeta ? {
            html: deliveryMeta.innerHTML,
            ...boxInfo(deliveryMeta),
        } : null,
        checkboxes: [...checkboxes].map((el) => ({
            label: el.textContent?.trim(),
            ...boxInfo(el),
            mark: boxInfo(el.querySelector('.checkbox-mark')),
        })),
        submitBtn: submitBtn ? boxInfo(submitBtn) : null,
        checkedRadio: [...deliverySection.querySelectorAll('.radio-box input')].map((input) => ({
            value: input.value,
            checked: input.checked,
            innerBg: getComputedStyle(input.nextElementSibling).backgroundColor,
        })),
    };
});

writeFileSync(join(outDir, 'v3-delivery-inspection.json'), JSON.stringify(report, null, 2));

await page.screenshot({ path: join(outDir, 'v3-delivery-viewport.png') });
const deliverySection = page.locator('.form-section').filter({ has: page.locator('h3', { hasText: 'Delivery' }) });
await deliverySection.screenshot({ path: join(outDir, 'v3-delivery-section.png') });

await browser.close();
console.log(JSON.stringify(report, null, 2));
