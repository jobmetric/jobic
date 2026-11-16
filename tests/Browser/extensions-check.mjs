import { chromium } from '@playwright/test';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import { spawn } from 'node:child_process';
import assert from 'node:assert/strict';
import { apacheSandbox } from './extensions-apache.mjs';

const php = process.env.JOBIC_TEST_PHP || 'C:/laragon/bin/php/php-8.5.8-nts-Win32-vs17-x64/php.exe';
const source = process.env.JOBIC_EXTENSION_PACKAGE_SOURCE;
const exampleCatalog = JSON.parse(fs.readFileSync('examples/extensions/catalog.json', 'utf8'));
const hasComposition = Boolean(source) || fs.existsSync(path.resolve('vendor/jobmetric/laravel-extension/src/Support/ExtensionComposition.php'));
const apacheMode = process.env.JOBIC_TEST_WEB_SERVER === 'apache';
const browser = await chromium.launch();
try {
    for (const locale of ['en', 'fa']) {
        const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'jobic-extensions-'));
        for (const part of ['storage/framework/views', 'storage/framework/sessions', 'storage/framework/cache', 'storage/logs']) fs.mkdirSync(path.join(directory, part), {recursive: true});
        fs.closeSync(fs.openSync(path.join(directory, 'database.sqlite'), 'w'));
        assert.equal(path.isAbsolute(path.relative(process.cwd(), directory)), false, 'The isolated test sandbox must be on the application volume.');
        const apache = apacheMode ? apacheSandbox(directory, locale) : null;
        const applicationBase = apache?.applicationBase || 'http://127.0.0.1:8913';
        const server = apache?.server || spawn(php, ['-S', '127.0.0.1:8913', '-t', 'public', 'tests/Browser/extensions-router.php'], {
            env: {...process.env, APP_ENV: 'testing', APP_CONFIG_CACHE: path.relative(process.cwd(), path.join(directory, 'config.php')), APP_ROUTES_CACHE: path.relative(process.cwd(), path.join(directory, 'routes.php')), JOBIC_EXTENSIONS_SANDBOX: directory, JOBIC_EXTENSIONS_LOCALE: locale, ...(source ? {JOBIC_EXTENSION_PACKAGE_SOURCE: source} : {})},
            stdio: ['ignore', 'pipe', 'pipe'],
        });
        let logs = ''; server.stderr.on('data', chunk => { logs += chunk; }); server.stdout.on('data', chunk => { logs += chunk; });
        const page = await browser.newPage({viewport: {width: 1440, height: 1000}, ignoreHTTPSErrors: apacheMode});
        if (apache) {
            await page.context().addCookies([apache.cookie]);
            await page.route(`${new URL(applicationBase).origin}/**`, route => {
                const pathname = new URL(route.request().url()).pathname;
                if (pathname.startsWith(apache.cookie.path+'/') || /^\/(vuero|assets|images|fonts)\//.test(pathname)) return route.continue();
                return route.abort(); // Never send a fixture mutation to the live application's routes.
            });
        }
        await page.route(/^https:\/\/fonts\.(googleapis|gstatic)\.com\//, route => route.abort());
        const errors = []; page.on('pageerror', error => errors.push(error.message));
        page.setDefaultTimeout(20000);
        page.setDefaultNavigationTimeout(60000);
        try {
            await new Promise(resolve => setTimeout(resolve, 800));
            const base = applicationBase+'/admin/settings/extensions';
            const initial = await page.goto(base);
            if (apacheMode) assert.equal(initial.headers()['x-jobic-test-sapi'], 'cgi-fcgi');
            assert.equal(initial.status(), 200, `${page.url()} ${await page.locator('body').innerText()}`);
            assert.equal(await page.locator('html').getAttribute('lang'), locale);
            assert.equal((await page.locator('body').innerText()).includes(locale === 'en' ? 'All languages' : 'تمام زبان‌ها'), false);
            const rejected = await page.request.post(`${base}/commands/install`, {headers: {Accept: 'application/json'}, data: {}});
            assert.equal(rejected.status(), 419);
            await page.screenshot({path: path.join(directory, `${locale}-desktop.png`), fullPage: true});
            await page.locator('#extension-table tbody tr').filter({hasText: 'Alpha fixture'}).waitFor();
            await page.evaluate(() => { window.originalDocument = document; });
            await page.route('**/commands/install', async route => { await new Promise(resolve => setTimeout(resolve, 500)); await route.continue(); });
            async function operate(name, operation) {
                const row = page.locator('#extension-table tbody tr').filter({hasText: name});
                await row.locator('summary').click();
                const button = row.locator(`[data-operation=${operation}]`);
                await button.click();
                await page.locator('[data-dialog-confirm]').click();
                await page.waitForFunction(() => document.querySelector('[data-table-loading]').hidden === false);
                if (operation === 'install') {
                    assert.equal(await button.isDisabled(), true);
                    assert.equal(await button.textContent(), locale === 'en' ? 'Loading…' : 'در حال بارگذاری…');
                }
                await page.locator('[data-table-loading]').waitFor({state: 'hidden'});
                assert.equal(await page.evaluate(() => document === window.originalDocument), true);
            }
            await operate('Alpha fixture', 'install');
            await operate('Alpha fixture', 'activate');
            await operate('Alpha fixture', 'deactivate');
            await operate('Beta fixture', 'install');
            const beta = page.locator('#extension-table tbody tr').filter({hasText: 'Beta fixture'});
            await beta.locator('summary').click();
            await beta.locator('a').filter({hasText: locale === 'en' ? 'Plugin instances' : 'نمونه‌های افزونه'}).click();
            await page.locator('[data-crud-action=create]').click();
            await page.locator('[data-crud-action=save]').click();
            await page.locator('[data-error-for="fields.label"]').filter({hasText: /.+/}).waitFor();
            await page.locator('[data-plugin-fields] [name=label]').fill('Configured browser label');
            await page.locator('[name=api_secret]').fill('browser-private-setting');
            await page.evaluate(() => { window.originalDocument = document; });
            await page.route('**/plugins', async route => { if (route.request().method() === 'POST') await new Promise(resolve => setTimeout(resolve, 500)); await route.continue(); });
            await page.locator('[data-crud-action=save]').click();
            assert.equal(await page.locator('[data-crud-action=save]').isDisabled(), true);
            await page.waitForURL(/\/plugins\/\d+\/edit$/);
            await page.waitForFunction(() => !document.querySelector('[data-extension-form]').dataset.saving);
            assert.equal(await page.evaluate(() => document === window.originalDocument), true);
            assert.equal(await page.locator('[name=api_secret]').inputValue(), '');
            const edit = page.url(); await page.goto(edit);
            assert.equal((await page.content()).includes('browser-private-setting'), false);
            assert.equal(await page.locator('[data-plugin-fields] [name=label]').inputValue(), 'Configured browser label');
            await page.locator('[data-crud-action=cancel]').click();
            await page.locator('#extension-table tbody tr').filter({hasText: 'Beta fixture'}).waitFor();
            for (let index = 0; index < 11; index++) {
                const result = await page.evaluate(async i => {
                    const data = new FormData(); data.set('name', `Instance ${i}`); data.set('status', '1'); data.set('fields[label]', `Value ${i}`);
                    const response = await fetch(location.pathname, {method: 'POST', headers: {Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content}, body: data});
                    return response.status;
                }, index);
                assert.equal(result, 200);
            }
            await page.reload();
            await page.locator('[data-row-id]').first().check();
            await page.locator('#extension-table_next a').click();
            await page.locator('[data-table-loading]').waitFor({state: 'hidden'});
            await page.locator('[data-row-id]').first().check();
            await page.locator('[data-show-selected]').click();
            assert.equal(await page.locator('.crud-dialog-item').count(), 2);
            await page.keyboard.press('Escape');
            await page.locator('[data-crud-toolbar] summary').click();
            await page.locator('[data-crud-action=deactivate]').click();
            assert.equal(await page.locator('.crud-dialog-item').count(), 2);
            const bulkResponse = page.waitForResponse(response => response.url().includes('/plugins/bulk') && response.request().method() === 'POST');
            await page.locator('[data-dialog-confirm]').click();
            assert.equal((await bulkResponse).status(), 200);
            await page.locator('[data-table-loading]').waitFor({state: 'hidden'});
            assert.equal(await page.locator('[data-show-selected]').isHidden(), true);
            await page.locator('[data-open-filters]').click();
            assert.equal(await page.locator('[data-filter-dialog] .select2').count() > 0, true);
            await page.keyboard.press('Escape');
            await page.setViewportSize({width: 390, height: 844});
            await page.goto(base);
            await page.locator('#extension-table tbody tr').filter({hasText: 'Alpha fixture'}).waitFor();
            assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1), true);
            await page.screenshot({path: path.join(directory, `${locale}-mobile.png`), fullPage: true});
            await page.evaluate(() => { document.documentElement.dataset.theme = 'dark'; });
            await page.screenshot({path: path.join(directory, `${locale}-mobile-dark.png`), fullPage: true});
            await page.setViewportSize({width: 1440, height: 1000});
            await page.goto(applicationBase+'/admin/settings/languages/create');
            await page.locator('.crud-split-save').waitFor();
            const reference = await page.locator('[data-crud-toolbar]').boundingBox();
            const referenceCancel = await page.locator('.crud-cancel').boundingBox();

            for (const variant of Object.keys(exampleCatalog).filter(key => hasComposition || key !== 'system')) {
                const sampleName = exampleCatalog[variant].name;
                const sampleTitle = {single: 'Jobic Single Example', multiple: 'Jobic Multiple Example', system: 'Jobic System Example', theme: 'Jobic Theme Example', plugin: 'Jobic Plugin Example'}[variant];
                await page.goto(base+'/create');
                await page.locator('[data-crud-action=cancel]').waitFor();
                assert.equal(await page.locator('[data-crud-action=save]').isVisible(), false);
                const position = await page.locator('[data-crud-toolbar]').boundingBox();
                assert.ok(Math.abs(position.y-reference.y) <= 1, 'Upload toolbar keeps the shared CRUD vertical position');
                assert.ok(Math.abs((locale === 'fa' ? position.x : position.x+position.width) - (locale === 'fa' ? reference.x : reference.x+reference.width)) <= 1, 'Upload actions keep the shared CRUD edge alignment');
                assert.equal(await page.locator('[data-crud-action=new],[data-crud-action=close],.crud-split-save').count(), 0);
                const cancelPosition = await page.locator('[data-crud-action=cancel]').boundingBox();
                assert.equal(cancelPosition.height, referenceCancel.height);
                assert.equal(await page.locator('[data-sample]').count(), Object.keys(exampleCatalog).length);
                assert.equal(await page.locator('[data-sample] a[download]').count(), 0);
                assert.equal(await page.locator('[data-sample] [data-show-example]').count(), Object.keys(exampleCatalog).length);
                if (variant === 'single') {
                    await page.screenshot({path: path.join(directory, `${locale}-upload-examples.png`), fullPage: true});
                    await page.setViewportSize({width: 390, height: 844});
                    assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth+1), true);
                    const headingBox = await page.locator('[data-upload-form] .crud-toolbar h1').boundingBox();
                    const actionBox = await page.locator('[data-crud-action=cancel]').boundingBox();
                    assert.ok(headingBox.y+headingBox.height <= actionBox.y, 'Mobile upload actions must not overlap the heading');
                    await page.screenshot({path: path.join(directory, `${locale}-upload-examples-mobile.png`), fullPage: true});
                    const theme = await page.evaluate(() => document.documentElement.getAttribute('data-theme'));
                    await page.evaluate(() => {document.documentElement.dataset.theme = 'dark';});
                    await page.screenshot({path: path.join(directory, `${locale}-upload-examples-mobile-dark.png`), fullPage: true});
                    await page.evaluate(value => { if (value === null) document.documentElement.removeAttribute('data-theme'); else document.documentElement.setAttribute('data-theme', value); }, theme);
                    await page.setViewportSize({width: 1440, height: 1000});
                }
                await page.locator(`[data-show-example="${variant}"]`).click();
                const sampleDialog = page.locator('[data-crud-dialog]');
                await sampleDialog.locator(`[data-example-content="${variant}"]`).waitFor();
                assert.equal(await sampleDialog.locator('a[download]').count(), 2);
                await page.screenshot({path: path.join(directory, `${locale}-${variant}-details.png`), fullPage: true});
                await page.setViewportSize({width: 390, height: 844});
                assert.equal(await page.evaluate(() => document.querySelector('[data-crud-dialog]').getBoundingClientRect().right <= innerWidth+1), true);
                await page.screenshot({path: path.join(directory, `${locale}-${variant}-details-mobile.png`), fullPage: true});
                await page.setViewportSize({width: 1440, height: 1000});
                const sampleDownload = page.waitForEvent('download');
                await page.locator(`a[href$="/examples/${variant}"]`).click();
                const samplePath = path.join(directory, `${sampleName}.zip`);
                await (await sampleDownload).saveAs(samplePath);
                await sampleDialog.locator('[data-dialog-cancel]').click();
                await page.evaluate(() => {window.originalDocument = document;});
                if (variant === 'single') {
                    await page.emulateMedia({reducedMotion: 'reduce'});
                    await page.locator('[name=archive]').setInputFiles({name: 'invalid.zip', mimeType: 'application/zip', buffer: Buffer.from('This is not a ZIP')});
                    await page.locator('[data-archive-motion][data-state=error]').waitFor();
                    await page.waitForFunction(() => !document.querySelector('[data-extension-form]').dataset.saving);
                    const archiveError = await page.locator('[data-motion-message]').textContent();
                    assert.ok(archiveError.length > 0);
                    assert.equal((await page.locator('body').innerText()).split(archiveError).length - 1, 1);
                    assert.equal(await page.locator('[data-settings-errors]').textContent(), '');
                    assert.equal(await page.locator('[data-error-for=archive]').textContent(), '');
                    assert.equal(await page.locator('[name=archive]').getAttribute('aria-describedby'), 'archive-motion-message');
                    await page.setViewportSize({width: 390, height: 844});
                    assert.ok(await page.locator('[data-archive-motion]').evaluate(node => node.getBoundingClientRect().right <= innerWidth));
                    assert.equal(await page.locator('.extension-archive-motion__file').evaluate(node => getComputedStyle(node).animationName), 'none');
                    await page.screenshot({path: path.join(directory, `${locale}-archive-error.png`)});
                    await page.setViewportSize({width: 1440, height: 1000});
                    await page.locator('[data-motion-retry]').click();
                    await page.waitForFunction(() => document.querySelector('[name=archive]').value === '');
                    assert.equal(await page.locator('[name=archive]').inputValue(), '');
                    assert.equal(await page.evaluate(() => document===window.originalDocument), true);
                    await page.emulateMedia({reducedMotion: 'no-preference'});
                }
                await page.route('**/commands/upload', async route => {await new Promise(resolve => setTimeout(resolve, 600)); await route.continue();});
                await page.locator('[name=archive]').setInputFiles(samplePath);
                await page.locator('[data-archive-motion][data-state=working]').waitFor();
                await page.keyboard.press('Escape');
                assert.equal(await page.locator('[data-archive-motion]').isVisible(), true);
                await page.evaluate(() => document.querySelector('[data-upload-form]').requestSubmit());
                assert.equal(await page.locator('[data-crud-action=save]').isVisible(), false);
                assert.equal(await page.locator('form[data-upload-form]').getAttribute('aria-busy'), 'true');
                await page.screenshot({path: path.join(directory, `${locale}-${variant}-archive-working.png`)});
                await page.locator('[data-archive-review]').waitFor({state: 'visible'});
                await page.waitForFunction(() => !document.querySelector('[data-extension-form]').dataset.saving);
                assert.equal(await page.evaluate(() => document===window.originalDocument), true);
                assert.equal(await page.locator('[data-archive-select]').isVisible(), false);
                assert.equal(await page.locator('[name=archive]').isVisible(), false);
                assert.equal(await page.locator('.extension-examples').isVisible(), false);
                assert.equal(await page.locator('[data-archive-back]').isVisible(), true);
                if (variant === 'single') {
                    await page.locator('[data-archive-back]').click();
                    await page.waitForFunction(() => !document.querySelector('[data-upload-form]').dataset.saving);
                    assert.equal(await page.locator('[data-archive-review]').isVisible(), false);
                    assert.equal(await page.locator('[data-archive-select]').isVisible(), true);
                    assert.equal(await page.locator('[name=archive]').inputValue(), '');
                    assert.equal(await page.evaluate(() => document===window.originalDocument), true);
                    await page.evaluate(({bytes, name}) => {
                        const transfer = new DataTransfer();
                        transfer.items.add(new File([Uint8Array.from(atob(bytes), char => char.charCodeAt(0))], name, {type: 'application/zip'}));
                        document.querySelector('[data-archive-dropzone]').dispatchEvent(new DragEvent('drop', {bubbles: true, cancelable: true, dataTransfer: transfer}));
                    }, {bytes: fs.readFileSync(samplePath).toString('base64'), name: `${sampleName}.zip`});
                    await page.locator('[data-archive-review]').waitFor({state: 'visible'});
                    await page.waitForFunction(() => !document.querySelector('[data-extension-form]').dataset.saving);
                }
                const listed = await page.request.get(`${base}/data?draw=1&start=0&length=10&search[value]=${encodeURIComponent(sampleTitle)}`);
                assert.equal((await listed.json()).data.recordsFiltered, 0, 'Inspection must not place files');
                const reportDownload = page.waitForEvent('download'); await page.locator('[data-download-report]').click();
                const downloaded = await reportDownload;
                const report = JSON.parse(fs.readFileSync(await downloaded.path(), 'utf8'));
                assert.equal(report.manifest.name, sampleName);
                assert.equal(report.manifest.multiple, variant === 'multiple');
                if (variant === 'system') assert.equal(report.composition.length, 4);
                assert.equal(report.file_count, await page.locator('[data-archive-files] tr').count());
                assert.ok(report.files.every(file => /^[a-f0-9]{64}$/.test(file.sha256)));
                const manifestDownload = page.waitForEvent('download'); await page.locator('[data-download-manifest]').click();
                assert.equal(JSON.parse(fs.readFileSync(await (await manifestDownload).path(), 'utf8')).name, sampleName);
                await page.screenshot({path: path.join(directory, `${locale}-${variant}-review.png`), fullPage: true});
                await page.setViewportSize({width: 390, height: 844});
                assert.equal(await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth+1), true);
                await page.screenshot({path: path.join(directory, `${locale}-${variant}-review-mobile.png`), fullPage: true});
                await page.setViewportSize({width: 1440, height: 1000});
                await page.locator('[data-crud-action=save]').click();
                await page.locator('[data-dialog-cancel]').click();
                assert.equal(await page.locator('[data-archive-review]').isVisible(), true);
                await page.locator('[data-crud-action=save]').click();
                await page.route('**/upload/confirm', async route => { await new Promise(resolve => setTimeout(resolve, 350)); await route.continue(); });
                const placed = page.waitForResponse(response => response.url().endsWith('/upload/confirm') && response.request().method()==='POST');
                await page.locator('[data-dialog-confirm]').click();
                placed.catch(() => {});
                await page.waitForFunction(() => document.querySelector('[data-archive-back]').disabled);
                assert.equal(await page.locator('[data-archive-back]').isDisabled(), true);
                const placement = await placed;
                assert.equal(placement.status(), 200, await placement.text());
                await page.waitForFunction(() => !document.querySelector('[data-extension-form]').dataset.saving);
                assert.equal(await page.evaluate(() => document===window.originalDocument), true);
                assert.equal(await page.locator('[name=archive]').isVisible(), false);
                assert.equal(await page.locator('[data-crud-action=save]').isVisible(), false);
                await page.locator('[data-archive-finish]').click();
                await page.locator('#extension-table tbody tr').filter({hasText: sampleTitle}).waitFor();
                await page.evaluate(() => {window.originalDocument = document;});
                await operate(sampleTitle, 'install');
                await operate(sampleTitle, 'activate');
                if (variant === 'system') {
                    for (const childName of ['JobicSystemCards', 'JobicSystemTheme', 'JobicSystemObserver']) {
                        const child = page.locator('#extension-table tbody tr').filter({hasText: childName});
                        assert.equal(await child.locator('[data-operation=uninstall],[data-operation=delete],a[href*="/extensions/upgrade?"]').count(), 0);
                        await operate(childName, 'activate');
                    }
                    await page.locator('#extension-table tbody tr').filter({hasText: 'JobicSystemCards'}).locator('.crud-row-actions > a').click();
                    for (const label of ['Owned A', 'Owned B']) {
                        await page.locator('[data-crud-action=create]').click();
                        await page.locator('[name=name]').fill(label);
                        await page.locator('[data-crud-action=save]').click();
                        await page.waitForURL(/\/plugins\/\d+\/edit$/);
                        await page.waitForFunction(() => !document.querySelector('[data-extension-form]').dataset.saving);
                        await page.locator('[data-crud-action=cancel]').click();
                        await page.locator('#extension-table tbody tr').filter({hasText: label}).waitFor();
                    }
                }
                const previewResponse = await page.goto(`${applicationBase}/admin/extension-examples/${variant}`);
                assert.equal(previewResponse.status(), 200, await page.locator('body').innerText());
                assert.equal(await page.locator('[data-example-upgraded]').count(), 0);
                await page.locator('.crud-toolbar a.is-primary').click();
                if (variant !== 'multiple') {
                    assert.equal(await page.locator('[data-crud-action=create]').count(), 0);
                    await page.locator('#extension-table tbody .crud-row-actions > a').first().click();
                    await page.locator('[name=title]').fill('Browser single title');
                    await page.locator('[name=message]').fill('Single example message');
                    await page.locator('[name=api_secret]').fill('example-browser-secret');
                    await page.locator('[data-crud-action=save]').click();
                    await page.waitForFunction(() => !document.querySelector('[data-extension-form]').dataset.saving);
                    assert.equal(await page.locator('[name=api_secret]').inputValue(), '');
                } else {
                    for (const label of ['A', 'B']) {
                        await page.locator('[data-crud-action=create]').click();
                        await page.locator('[name=name]').fill(`Multiple ${label}`);
                        await page.locator('[name=title]').fill(`Browser multiple ${label}`);
                        await page.locator('[data-crud-action=save]').click();
                        await page.waitForURL(/\/plugins\/\d+\/edit$/);
                        await page.waitForFunction(() => !document.querySelector('[data-extension-form]').dataset.saving);
                        await page.locator('[data-crud-action=cancel]').click();
                        await page.locator('#extension-table tbody tr').filter({hasText: `Multiple ${label}`}).waitFor();
                    }
                }
                await page.goto(`${applicationBase}/admin/extension-examples/${variant}`);
                assert.ok((await page.locator('body').innerText()).includes(variant!=='multiple' ? 'Browser single title' : 'Browser multiple B'));
                assert.equal((await page.content()).includes('example-browser-secret'), false);
                await page.goto(base);
                await page.locator('#extension-table tbody tr').filter({hasText: sampleTitle}).waitFor();
                await page.locator('#extension-table tbody tr').filter({hasText: sampleTitle}).locator('summary').click();
                await page.locator('#extension-table tbody tr').filter({hasText: sampleTitle}).locator('a[href*="/extensions/upgrade?"]').click();
                await page.locator(`[data-show-example="${variant}"]`).click();
                await page.locator(`[data-example-content="${variant}"]`).waitFor();
                const upgradeDownload = page.waitForEvent('download');
                await page.locator(`a[href*="/examples/${variant}?version=1.1.0"]`).click();
                const upgradePath = path.join(directory, `${sampleName}-1.1.0.zip`);
                await (await upgradeDownload).saveAs(upgradePath);
                await page.locator('[data-dialog-cancel]').click();
                await page.locator('[name=archive]').setInputFiles(upgradePath);
                await page.locator('[data-archive-review]').waitFor({state: 'visible'});
                await page.waitForFunction(() => !document.querySelector('[data-extension-form]').dataset.saving);
                assert.equal(await page.locator('[data-review-value=version]').textContent(), '1.1.0');
                await page.locator('[data-crud-action=save]').click();
                const upgradeResponse = page.waitForResponse(response => response.url().endsWith('/upgrade/confirm'));
                await page.locator('[data-dialog-confirm]').click();
                const upgrade = await upgradeResponse;
                assert.equal(upgrade.status(), 200, await upgrade.text());
                await page.waitForFunction(() => !document.querySelector('[data-extension-form]').dataset.saving);
                assert.equal(await page.locator('[name=_version]').inputValue(), (await upgrade.json()).data.version);
                assert.ok(fs.readdirSync(path.join(directory, 'extensions', 'backups')).length >= 1);
                assert.equal(await page.locator('[name=archive]').isVisible(), false);
                assert.equal(await page.locator('[data-crud-action=save]').isVisible(), false);
                await page.locator('[data-archive-finish]').click();
                await page.locator('#extension-table tbody tr').filter({hasText: sampleTitle}).waitFor();
                const upgradedPreview = await page.request.get(`${applicationBase}/admin/extension-examples/${variant}`);
                assert.equal(upgradedPreview.status(), 200);
                assert.ok((await upgradedPreview.text()).includes('data-example-upgraded'), 'The 1.1.0 migration must be observable in the preview');
                await page.evaluate(() => {window.originalDocument = document;});
                if (variant === 'system') {
                    const result = await page.request.get(`${base}/data?draw=1&start=0&length=100`);
                    const members = (await result.json()).data.data.filter(row => row.name.startsWith('JobicSystem') || row.name === sampleTitle);
                    assert.equal(members.length, 4);
                    assert.ok(members.every(row => row.installed_version === '1.1.0'));
                }
                await operate(sampleTitle, 'deactivate');
                assert.equal((await page.request.get(`${applicationBase}/admin/extension-examples/${variant}`)).status(), 404);
                await operate(sampleTitle, 'uninstall');
                await operate(sampleTitle, 'delete');
                assert.equal(await page.locator('#extension-table tbody tr').filter({hasText: sampleTitle}).count(), 0);
            }
            assert.deepEqual(errors, []);
            console.log(`PASS ${locale} (${apacheMode ? "Apache/FastCGI" : "PHP CLI server"}): lifecycle, pending state, validation, document identity, dynamic fields, secrets, CSRF, selection, bulk, Select2, desktop/mobile, dark mode, toolbar parity, automatic ZIP selection/drop, animated pending/error recovery and reduced motion, JSON review/confirmation, actual in-app example downloads, all five Module/Component/Theme/Plugin sample lifecycles and real CLI migration upgrades. ${directory}`);
        } catch (error) {
            console.error('SANDBOX', directory, 'PAGE', page.url(), (await page.locator('body').innerText()).slice(0, 1800), 'ERRORS', errors);
            console.error(logs.slice(-6000));
            throw error;
        } finally {
            await page.close(); server.kill();
            await new Promise(resolve => server.once('exit', resolve));
        }
    }
} finally { await browser.close(); }
