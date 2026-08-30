/**
 * Vuero layout pages — real browser QA
 * Run: npx playwright test tests/Browser/vuero-layouts-qa.spec.js
 * Report: tests/Browser/reports/vuero-layouts-qa-report.json
 */
import { test, expect } from '@playwright/test';
import fs from 'node:fs';
import path from 'node:path';

const REPORT_DIR = path.join(process.cwd(), 'tests', 'Browser', 'reports');
const REPORT_FILE = path.join(REPORT_DIR, 'vuero-layouts-qa-report.json');

const VIEWPORTS = [
    { name: 'mobile', width: 375, height: 812 },
    { name: 'tablet', width: 768, height: 1024 },
    { name: 'desktop', width: 1280, height: 800 },
];

const PAGES = [
    { slug: 'user-grid-v1', path: '/vuero/admin/layouts/user-grid-v1', root: '[data-user-grid-v1]' },
    { slug: 'user-grid-v2', path: '/vuero/admin/layouts/user-grid-v2', root: '[data-user-grid-v2]' },
    { slug: 'user-grid-v3', path: '/vuero/admin/layouts/user-grid-v3', root: '[data-user-grid-v3]' },
    { slug: 'user-grid-v4', path: '/vuero/admin/layouts/user-grid-v4', root: '[data-user-grid-v4]' },
    { slug: 'profile-view', path: '/vuero/admin/layouts/profile-view', root: '[data-profile-view]' },
    { slug: 'profile-notifications', path: '/vuero/admin/layouts/profile-notifications', root: '[data-profile-notifications]' },
    { slug: 'profile-edit/general', path: '/vuero/admin/layouts/profile-edit/general', root: '[data-profile-edit]' },
    { slug: 'profile-edit/experience', path: '/vuero/admin/layouts/profile-edit/experience', root: '[data-profile-edit]' },
    { slug: 'profile-edit/skills', path: '/vuero/admin/layouts/profile-edit/skills', root: '[data-profile-edit]' },
    { slug: 'profile-edit/settings', path: '/vuero/admin/layouts/profile-edit/settings', root: '[data-profile-edit]' },
    { slug: 'search-results', path: '/vuero/admin/layouts/search-results', root: 'main [data-search-results]' },
    { slug: 'search-empty', path: '/vuero/admin/layouts/search-empty', root: 'main [data-search-empty]' },
    { slug: 'saas-billing', path: '/vuero/admin/layouts/saas-billing', root: '[data-saas-billing]' },
    { slug: 'action-page-v1', path: '/vuero/admin/layouts/action-page-v1', root: '[data-action-page-v1]' },
    { slug: 'action-page-v2', path: '/vuero/admin/layouts/action-page-v2', root: '[data-action-page-v2]' },
    { slug: 'projects-v1', path: '/vuero/admin/layouts/projects-v1', root: '[data-projects-v1]' },
    { slug: 'projects-v2', path: '/vuero/admin/layouts/projects-v2', root: '[data-projects-v2]' },
    { slug: 'projects-v3', path: '/vuero/admin/layouts/projects-v3', root: '[data-projects-v3]' },
    { slug: 'project-details', path: '/vuero/admin/layouts/project-details', root: '[data-project-details]' },
    { slug: 'kanban-board', path: '/vuero/admin/layouts/kanban-board', root: '[data-kanban-board]' },
    { slug: 'utility-account-confirm', path: '/vuero/admin/layouts/utility-account-confirm', root: '[data-utility-account-confirm]' },
    { slug: 'utility-promotion', path: '/vuero/admin/layouts/utility-promotion', root: '[data-utility-promotion]' },
    { slug: 'utility-invoice', path: '/vuero/admin/layouts/utility-invoice', root: '[data-utility-invoice]' },
    { slug: 'utility-roadmap', path: '/vuero/roadmap', root: '[data-utility-roadmap]' },
    { slug: 'utility-status', path: '/vuero/status', root: '[data-utility-status]' },
    { slug: 'onboarding-welcome', path: '/vuero/admin/layouts/onboarding-welcome', root: '[data-onboarding-page]' },
    { slug: 'onboarding-v1', path: '/vuero/admin/layouts/onboarding-v1', root: '[data-onboarding-page]' },
    { slug: 'onboarding-v2', path: '/vuero/admin/layouts/onboarding-v2', root: '[data-onboarding-page]' },
    { slug: 'onboarding-v3', path: '/vuero/admin/layouts/onboarding-v3', root: '[data-onboarding-page]' },
    { slug: 'onboarding-v4', path: '/vuero/admin/layouts/onboarding-v4', root: '[data-onboarding-page]' },
    { slug: 'onboarding-v5', path: '/vuero/admin/layouts/onboarding-v5', root: '[data-onboarding-page]' },
    { slug: 'error-page-1', path: '/vuero/error/page-1', root: '[data-error-page]' },
    { slug: 'error-page-2', path: '/vuero/error/page-2', root: '[data-error-page]' },
    { slug: 'error-page-3', path: '/vuero/error/page-3', root: '[data-error-page]' },
    { slug: 'error-page-4', path: '/vuero/error/page-4', root: '[data-error-page]' },
    { slug: 'error-page-5', path: '/vuero/error/page-5', root: '[data-error-page]' },
];

const INTERACTIVE_SELECTOR = [
    'main button:not([disabled])',
    'main [role="button"]:not([disabled])',
    'main a.button',
    'main label.form-switch',
    'main [data-dropdown-toggle]',
    'main [data-user-grid-tab]',
    'main [data-icon-tab]',
    'main [data-project-tab]',
    'main [data-search-tab]',
    'main [data-plan-option] label',
    'main [data-onboarding-action]',
    'main [data-action-accept]',
    'main [data-action-decline]',
    'main [data-billing-save]',
    'main [data-billing-randomize]',
    'main [data-billing-add-seat]',
    'main [data-confirm-account]',
    'main [data-invoice-action]',
    'main [data-utility-tab]',
    'main [data-roadmap-quarter]',
    'main .roadmap-collapse-head',
    'main [data-profile-save]',
    'main [data-profile-toggle-panel]',
    'main [data-profile-cancel-panel]',
    'main [data-profile-upload-toggle]',
    'main [data-timeline-load-more]',
    'main [data-profile-view-more]',
    'main [data-user-grid-cta]',
    'main [data-user-grid-action]',
    'main [data-grid-dropdown-action]',
    'main [data-tile-dropdown-action]',
    'main [data-projects-new]',
    'main [data-project-edit]',
    'main [data-project-task]',
    'main [data-kanban-new]',
    'main [data-kanban-expand]',
    'main [data-kanban-collapse]',
    'main [data-kanban-approve]',
    'main [data-promotion-skip]',
    'main [data-promotion-upgrade]',
    'main [data-error-dark-toggle]',
    'main [data-error-back]',
    'main input[type="radio"]:not([disabled])',
].join(', ');

const TOAST_ACTION_PATTERN = /data-(action|billing|confirm|invoice|profile-save|user-grid-cta|user-grid-action|grid-dropdown|tile-dropdown|projects-new|project-edit|project-task|kanban-approve|promotion|onboarding-action)/;

/** @type {{ pages: object[], summary: object, generatedAt: string }} */
const qaReport = {
    generatedAt: new Date().toISOString(),
    summary: {
        totalPages: PAGES.length,
        totalChecks: 0,
        failures: 0,
        consoleErrors: 0,
        layoutIssues: 0,
        navigationErrors: 0,
    },
    pages: PAGES.map((p) => ({ slug: p.slug, path: p.path, failures: [], viewportsTested: VIEWPORTS.map((v) => v.name) })),
};

function pageEntry(slug) {
    return qaReport.pages.find((p) => p.slug === slug);
}

function recordFailure(slug, viewport, element, expected, actual, suggestedFix, type = 'interaction') {
    const entry = pageEntry(slug);
    entry.failures.push({ viewport, element, expected, actual, suggestedFix, type });
    qaReport.summary.failures += 1;
    if (type === 'layout') qaReport.summary.layoutIssues += 1;
    if (type === 'console') qaReport.summary.consoleErrors += 1;
    if (type === 'navigation') qaReport.summary.navigationErrors += 1;
}

async function scrollPage(page) {
    await page.evaluate(async () => {
        const delay = (ms) => new Promise((r) => setTimeout(r, ms));
        const step = Math.max(window.innerHeight * 0.7, 200);
        let y = 0;
        const max = document.documentElement.scrollHeight;
        while (y < max) {
            y += step;
            window.scrollTo(0, y);
            await delay(60);
        }
        window.scrollTo(0, 0);
        await delay(100);
    });
}

async function checkLayout(page, slug, viewport) {
    const issues = await page.evaluate(() => {
        const problems = [];
        const vw = window.innerWidth;
        document.querySelectorAll('main *').forEach((el) => {
            const rect = el.getBoundingClientRect();
            if (rect.width > vw + 4 && rect.height > 20) {
                const cls = typeof el.className === 'string' ? el.className.split(' ')[0] : el.tagName;
                problems.push({ element: cls, issue: `overflow ${Math.round(rect.width)}px > ${vw}px` });
            }
        });
        return problems.slice(0, 3);
    });

    for (const issue of issues) {
        recordFailure(slug, viewport.name, issue.element, 'No horizontal overflow', issue.issue, 'Fix responsive CSS widths on mobile/tablet', 'layout');
    }
}

async function getElementDescriptor(locator) {
    return locator.evaluate((el) => {
        const parts = [el.tagName.toLowerCase()];
        if (el.id) parts.push(`#${el.id}`);
        [...el.attributes].filter((a) => a.name.startsWith('data-')).slice(0, 2).forEach((a) => parts.push(`[${a.name}]`));
        const text = (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 35);
        if (text) parts.push(`"${text}"`);
        return parts.join(' ');
    }).catch(() => 'unknown');
}

async function snapshotUiState(page, el) {
    return {
        className: await el.getAttribute('class').catch(() => ''),
        ariaExpanded: await el.getAttribute('aria-expanded').catch(() => null),
        ariaSelected: await el.getAttribute('aria-selected').catch(() => null),
        ariaPressed: await el.getAttribute('aria-pressed').catch(() => null),
        checked: await el.evaluate((node) => (node instanceof HTMLInputElement ? node.checked : null)).catch(() => null),
        activePanel: await page.locator('main [data-user-grid-tab-panel].is-active, main [data-project-panel]:not([hidden]), main [data-search-tab-panel].is-active').count(),
    };
}

function uiStateChanged(before, after, toastVisible, dropdownOpen) {
    return toastVisible
        || dropdownOpen > 0
        || before.className !== after.className
        || before.ariaExpanded !== after.ariaExpanded
        || before.ariaSelected !== after.ariaSelected
        || before.ariaPressed !== after.ariaPressed
        || before.checked !== after.checked
        || before.activePanel !== after.activePanel;
}

async function clickInteractiveElements(page, slug, viewport) {
    const elements = page.locator(INTERACTIVE_SELECTOR);
    const count = Math.min(await elements.count(), 40);

    for (let i = 0; i < count; i += 1) {
        const el = elements.nth(i);
        if (!(await el.isVisible().catch(() => false))) continue;
        if (await el.isDisabled().catch(() => false)) continue;

        const inDisabledTabs = await el.evaluate((node) => !!node.closest('.tabs.is-disabled')).catch(() => false);
        if (inDisabledTabs) continue;

        const descriptor = await getElementDescriptor(el);
        qaReport.summary.totalChecks += 1;

        const href = await el.getAttribute('href').catch(() => null);
        if (href && href.startsWith('/') && !href.includes('#')) continue;

        const invoiceAction = await el.getAttribute('data-invoice-action').catch(() => null);
        if (['print', 'mail', 'back'].includes(invoiceAction)) continue;

        try {
            await el.scrollIntoViewIfNeeded({ timeout: 2_000 });
            const before = await snapshotUiState(page, el);

            await el.click({ timeout: 3_000 });
            await page.waitForTimeout(250);

            const after = await snapshotUiState(page, el);
            const toastVisible = await page.locator('[data-ui-toast].is-visible').isVisible().catch(() => false);
            const dropdownOpen = await page.locator('main [data-dropdown].is-open').count();
            const changed = uiStateChanged(before, after, toastVisible, dropdownOpen);

            const expectsToast = TOAST_ACTION_PATTERN.test(descriptor)
                || /accept|decline|save|randomize|confirm|print|download|mail|view more|load more|new project|approve|upgrade|skip/i.test(descriptor);

            const isTab = /data-user-grid-tab|data-project-tab|data-search-tab|data-icon-tab/.test(descriptor);
            const isAlreadyActive = isTab && before.ariaSelected === 'true';

            if (expectsToast && !changed && !isAlreadyActive) {
                recordFailure(slug, viewport.name, descriptor, 'Toast or UI state change', 'No observable effect after click', 'Verify JS handler binding and [data-ui-toast] in app layout');
            }

            if (dropdownOpen > 0) {
                await page.keyboard.press('Escape').catch(() => {});
                await page.waitForTimeout(100);
            }
        } catch (err) {
            recordFailure(slug, viewport.name, descriptor, 'Clickable element', String(err.message || err).slice(0, 180), 'Check overlay/z-index or use label.form-switch for hidden inputs');
        }
    }
}

async function qaPage(page, pageConfig, viewport) {
    const slug = pageConfig.slug;
    const consoleErrors = [];
    const onConsole = (msg) => { if (msg.type() === 'error') consoleErrors.push(msg.text()); };
    page.on('console', onConsole);

    await page.setViewportSize({ width: viewport.width, height: viewport.height });
    const response = await page.goto(pageConfig.path, { waitUntil: 'load', timeout: 25_000 }).catch(() => null);

    if (!response || response.status() >= 400) {
        recordFailure(slug, viewport.name, pageConfig.path, 'HTTP 200', `HTTP ${response?.status() ?? 'failed'}`, 'Check route and view', 'navigation');
        page.off('console', onConsole);
        return;
    }

    await page.waitForSelector(`${pageConfig.root}, main`, { timeout: 8_000 }).catch(() => {});
    await page.waitForSelector(`${pageConfig.root}.is-ready`, { timeout: 5_000 }).catch(() => {});
    await page.waitForTimeout(300);

    const rootVisible = await page.locator(pageConfig.root).first().isVisible().catch(() => false);
    if (!rootVisible) {
        recordFailure(slug, viewport.name, pageConfig.root, 'Root container visible', 'Missing or hidden', `Ensure ${pageConfig.root} on page wrapper`, 'navigation');
    }

    await scrollPage(page);
    await checkLayout(page, slug, viewport);
    await clickInteractiveElements(page, slug, viewport);

    for (const err of consoleErrors) {
        if (/favicon|404.*\.(png|webp|svg)/i.test(err)) continue;
        recordFailure(slug, viewport.name, 'console.error', 'No JS errors', err.slice(0, 250), 'Fix JS exception or missing asset', 'console');
    }

    page.off('console', onConsole);
}

test.describe('Vuero Layout Pages QA', () => {
    test.beforeAll(() => {
        fs.mkdirSync(REPORT_DIR, { recursive: true });
    });

    test.afterAll(() => {
        fs.writeFileSync(REPORT_FILE, JSON.stringify(qaReport, null, 2), 'utf8');
        console.log(`\nQA report: ${REPORT_FILE}`);
        console.log(`Total failures: ${qaReport.summary.failures}`);
    });

    for (const pageConfig of PAGES) {
        test(`${pageConfig.slug}`, async ({ page }) => {
            for (const viewport of VIEWPORTS) {
                await qaPage(page, pageConfig, viewport);
            }
        });
    }
});
