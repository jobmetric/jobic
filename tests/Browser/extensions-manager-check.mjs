import {chromium} from '@playwright/test';
import {apacheSandbox} from './extensions-apache.mjs';
import fs from 'node:fs';
import os from 'node:os';
import path from 'node:path';
import assert from 'node:assert/strict';

const flatten = rows => rows.flatMap(row => [row, ...flatten(row.children || [])]);
const browser = await chromium.launch();
try {
    for (const locale of ['en', 'fa']) {
        const directory = fs.mkdtempSync(path.join(os.tmpdir(), 'jobic-extensions-'));
        for (const part of ['storage/framework/views','storage/framework/sessions','storage/framework/cache','storage/logs']) fs.mkdirSync(path.join(directory, part), {recursive: true});
        fs.closeSync(fs.openSync(path.join(directory, 'database.sqlite'), 'w'));
        const sandbox = apacheSandbox(directory, locale);
        const page = await browser.newPage({viewport: {width:1440,height:1000}, ignoreHTTPSErrors: true});
        const errors = [];
        page.on('pageerror', error => errors.push(error.message));
        page.setDefaultTimeout(30000);
        const base = sandbox.applicationBase+'/admin/settings/extensions';
        await page.context().addCookies([sandbox.cookie]);
        await page.route('https://fonts.googleapis.com/**', route => route.abort());
        async function loaded() {await page.waitForFunction(() => document.querySelector('[data-table-loading]')?.hidden === true);}
        async function data() {return (await (await page.request.get(base+'/data?draw=1&start=0&length=100')).json()).data.data;}
        async function operation(row, name) {
            const button = page.locator(`[data-operation=${name}][data-id="${row.id}"]`);
            const menu = button.locator('xpath=ancestor::details[1]/summary');
            if (await menu.count()) await menu.click();
            await button.click(); const response=page.waitForResponse(r=>r.request().method()==='POST' && (r.url().endsWith('/commands/'+name)||r.url().endsWith('/bulk/'+name))); await page.locator('[data-dialog-confirm]').click(); const result=await response;assert.equal(result.status(),200,await result.text());await loaded();
        }
        async function select(rows) {for(const row of rows) await page.locator(`[data-row-id="${row.id}"]`).check();}
        async function available(keys) {
            await page.locator('[data-crud-toolbar] summary').click();
            for(const name of ['activate','deactivate','delete']) assert.equal(await page.locator(`[data-crud-action=${name}]`).isVisible(), keys.includes(name));
            await page.locator('[data-crud-toolbar] summary').click();
        }
        async function batch(name) {
            await page.locator('[data-crud-toolbar] summary').click();
            await page.locator(`[data-crud-action=${name}]`).click();const response=page.waitForResponse(r=>r.request().method()==='POST'&&(r.url().endsWith('/bulk/'+name)||r.url().endsWith('/plugins/bulk')));await page.locator('[data-dialog-confirm]').click();const result=await response;assert.equal(result.status(),200,await result.text());await loaded();
        }
        async function post(url, value) {
            const token = await page.locator('meta[name=csrf-token]').getAttribute('content');
            const result = await page.request.post(url, {headers:{Accept:'application/json','X-CSRF-TOKEN':token},data:value});
            assert.equal(result.status(),200,await result.text());return (await result.json()).data;
        }
        try {
            assert.equal((await page.goto(base)).status(),200);await loaded();
            const initial=await data();let alpha=initial.find(row=>row.name==='Alpha fixture'),beta=initial.find(row=>row.name==='Beta fixture');
            assert.ok(alpha&&beta);
            assert.equal(await page.locator('[data-quick-filter]').count(),5);
            assert.equal(await page.locator('[data-quick-filter=all]').getAttribute('aria-pressed'),'true');
            await page.locator('[data-quick-filter=available]').click();await loaded();assert.ok(page.url().includes('view=available'));
            await page.reload();await loaded();assert.equal(await page.locator('[data-quick-filter=available]').getAttribute('aria-pressed'),'true');
            await page.locator('[data-quick-filter=all]').click();await loaded();
            await available([]);
            await page.locator('[data-open-filters]').click();assert.equal(await page.locator('[data-filter-form] select').count(),7);
            await page.locator('[name=source]').selectOption('local');await page.locator('[data-filter-form] [type=submit]').click();await loaded();assert.equal(await page.locator('[data-table-empty]').isVisible(),true);assert.equal(await page.locator('[data-empty-clear]').isVisible(),true);
            await page.locator('[data-empty-clear]').click();await loaded();
            await operation(alpha,'install');alpha=(await data()).find(row=>row.name==='Alpha fixture');
            await operation(beta,'install');beta=(await data()).find(row=>row.name==='Beta fixture');
            await operation(alpha,'activate');alpha=(await data()).find(row=>row.name==='Alpha fixture');
            await select([alpha,beta]);await available([]);
            await page.locator('[data-show-selected]').click();assert.equal(await page.locator('.extension-selection-group').count(),2);
            await page.screenshot({path:path.join(directory,`${locale}-mixed-selection.png`)});
            await page.locator('.crud-dialog-item').filter({hasText:alpha.name}).locator('button').click();
            assert.equal(await page.locator('.extension-selection-group').count(),1);await page.locator('[data-dialog-cancel]').click();
            await available(['activate']);await batch('activate');
            await select([alpha,beta]);await available(['deactivate']);await batch('deactivate');
            assert.ok((await data()).filter(row=>[alpha.id,beta.id].includes(row.id)).every(row=>row.status==='inactive'));
            beta=(await data()).find(row=>row.id===beta.id);
            assert.equal(await page.locator(`a.crud-icon-button[href="${beta.plugins_url}"]`).count(),0);
            await operation(beta,'activate');beta=(await data()).find(row=>row.id===beta.id);
            const gear=page.locator(`a.crud-icon-button[href="${beta.plugins_url}"]`);assert.equal(await gear.locator('svg circle').count(),1);await gear.click();await loaded();
            assert.equal(await page.locator('.crud-title-back').getAttribute('href'),base);assert.equal(await page.locator('[data-extension-paused]').isVisible(),false);
            assert.equal(await page.locator('.crud-toolbar .crud-taxonomy-heading p').count(),0);await page.locator('[data-open-filters]').click();assert.equal(await page.locator('[data-filter-form] select').count(),3);await page.locator('[data-close-filters]').click();
            assert.equal(new Set(await page.locator('.crud-stats svg').evaluateAll(nodes=>nodes.map(node=>node.dataset.icon))).size,4);
            await post(beta.plugins_url,{name:'Enabled instance',status:1,fields:{label:'A'}});
            await post(beta.plugins_url,{name:'Disabled instance',status:0,fields:{label:'B'}});
            await page.reload();await loaded();await page.locator('[data-select-all]').check();await available([]);
            assert.equal(await page.locator('#extension-table tbody tr').filter({hasText:'Enabled instance'}).locator('[data-operation=delete]').count(),1);
            await page.locator('[data-show-selected]').click();assert.equal(await page.locator('.extension-selection-group').count(),2);await page.locator('[data-dialog-cancel]').click();
            await page.locator('[data-select-all]').uncheck();
            const inactive=page.locator('#extension-table tbody tr').filter({hasText:'Disabled instance'});await inactive.locator('[data-row-id]').check();await available(['activate','delete']);await batch('delete');assert.equal(await page.locator('#extension-table tbody tr').filter({hasText:'Disabled instance'}).count(),0);
            await page.locator('.crud-title-back').click();await loaded();
            await page.goto(base+'/create');await page.locator('[name=archive]').waitFor();await page.locator('[name=archive]').setInputFiles('examples/extensions/dist/JobicSystemExample.zip');await page.locator('[data-archive-review]').waitFor();await page.waitForFunction(()=>!document.querySelector('[data-upload-form]').dataset.saving);
            await page.locator('[data-crud-action=save]').click();await page.locator('[data-dialog-confirm]').click();await page.waitForFunction(()=>!document.querySelector('[data-upload-form]').dataset.saving);await page.locator('[data-archive-finish]').click();await loaded();
            let root=(await data()).find(row=>row.name==='Jobic System Example');assert.ok(root);assert.ok(root.children.every(child=>child.actions.length===0));await operation(root,'install');root=(await data()).find(row=>row.id===root.id);
            assert.equal(root.children.length,3);assert.equal((await data()).some(row=>row.owner),false);
            await page.locator(`[data-toggle-children="${root.id}"]`).click();assert.equal(await page.locator('.extension-child-row').count(),3);
            let child=flatten(root.children).find(row=>row.type==='Module');assert.equal(await page.locator(`[data-operation=activate][data-id="${child.id}"]`).count(),0);
            await operation(root,'activate');root=(await data()).find(row=>row.id===root.id);child=flatten(root.children).find(row=>row.type==='Module');assert.ok(root.children.every(row=>row.status==='active'));await operation(child,'deactivate');assert.equal((await data()).find(row=>row.id===root.id).status,'active');await operation(child,'activate');
            await page.goto(sandbox.applicationBase+'/admin/settings');assert.equal(await page.locator('a.account-menu-item[href*="/admin/extension-examples/"]').count(),0);await page.goto(base);await loaded();await page.locator(`[data-toggle-children="${root.id}"]`).click();
            assert.equal((await page.request.get(sandbox.applicationBase+'/admin/extension-examples/system')).status(),404);
            await operation(root,'deactivate');root=(await data()).find(row=>row.id===root.id);child=flatten(root.children).find(row=>row.type==='Module');assert.equal(child.status,'inactive');assert.equal(child.configured_active,false);assert.ok(child.state_reason);assert.equal((await page.request.get(sandbox.applicationBase+'/admin/extension-examples/system')).status(),404);
            await operation(root,'activate');root=(await data()).find(row=>row.id===root.id);assert.equal(flatten(root.children).find(row=>row.type==='Module').status,'active');
            assert.equal(root.plugins_url,null);
            assert.equal(await page.locator(`[data-operation=deactivate][data-id="${child.id}"]`).count(),1);
            const singleChild=flatten(root.children).find(row=>row.type==='Theme');
            assert.ok(singleChild.plugins_url.endsWith('/edit'));
            const singleEdit=page.locator(`a.crud-icon-button[href="${singleChild.plugins_url}"]`);assert.equal(await singleEdit.locator('svg circle').count(),0);await singleEdit.click();await page.locator('[data-extension-form]').waitFor();
            await page.locator('[data-crud-action=cancel]').click();await loaded();assert.equal(await page.locator(`[data-toggle-children="${root.id}"]`).getAttribute('aria-expanded'),'true');
            await page.locator(`[data-details="${root.id}"]`).locator('xpath=ancestor::details[1]/summary').click();await page.locator(`[data-details="${root.id}"]`).click();assert.equal(await page.locator('.extension-details__facts > div').count(),8);assert.ok((await page.locator('.extension-details pre').textContent()).includes('JobMetric'));await page.waitForTimeout(400);await page.screenshot({path:path.join(directory,`${locale}-details-desktop.png`)});
            await page.setViewportSize({width:390,height:844});await page.evaluate(()=>document.documentElement.dataset.theme='dark');await page.waitForTimeout(350);await page.screenshot({path:path.join(directory,`${locale}-details-mobile.png`)});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));await page.locator('[data-dialog-cancel]').click();await page.screenshot({path:path.join(directory,`${locale}-children-mobile.png`)});
            await page.setViewportSize({width:1440,height:1000});await operation(root,'deactivate');await select([root]);await available(['activate','delete']);await batch('delete');assert.equal((await data()).some(row=>row.id===root.id),false);assert.equal(fs.existsSync(path.join(directory,'owned','Component','JobicSystemExample')),false);
            assert.deepEqual(errors,[]);console.log('PASS manager details, seven filters, mixed selection, extension/instance bulk actions, nested family rows, single-instance direct editing, active-only management, parent-wide activation and disabled-parent guards, back links, icons, mobile/dark',locale,directory);
        } catch(error) {console.error('SANDBOX',directory,'ERRORS',errors);throw error;} finally {await page.close();sandbox.server.kill();}
    }
} finally {await browser.close();}
