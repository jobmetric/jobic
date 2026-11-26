import {chromium} from '@playwright/test';import {apacheSandbox} from './extensions-apache.mjs';import fs from 'node:fs';import os from 'node:os';import path from 'node:path';import assert from 'node:assert/strict';
const browser=await chromium.launch();
try{for(const locale of ['en','fa']){
 const dir=fs.mkdtempSync(path.join(os.tmpdir(),'jobic-extensions-'));for(const part of ['storage/framework/views','storage/framework/sessions','storage/framework/cache','storage/logs'])fs.mkdirSync(path.join(dir,part),{recursive:true});fs.closeSync(fs.openSync(path.join(dir,'database.sqlite'),'w'));
 const sandbox=apacheSandbox(dir,locale);const page=await browser.newPage({viewport:{width:1440,height:1000},ignoreHTTPSErrors:true});const base=sandbox.applicationBase+'/admin/settings/extensions';
 try{await page.context().addCookies([sandbox.cookie]);await page.route('https://fonts.googleapis.com/**',r=>r.abort());await page.goto(base);await page.locator('#extension-table').waitFor();
 async function data(){return (await (await page.request.get(base+'/data?draw=1&start=0&length=100')).json()).data.data;}
 async function command(row,operation){const result=await page.request.post(base+'/commands/'+operation,{headers:{Accept:'application/json','X-CSRF-TOKEN':await page.locator('meta[name=csrf-token]').getAttribute('content')},data:{namespace:row.namespace,_version:row._version,confirm:1}});assert.equal(result.status(),200,await result.text());}
 let alpha=(await data()).find(row=>row.name==='Alpha fixture');await command(alpha,'install');alpha=(await data()).find(row=>row.id===alpha.id);await command(alpha,'activate');alpha=(await data()).find(row=>row.id===alpha.id);await page.goto(alpha.plugins_url);await page.locator('[data-extension-form]').waitFor();assert.equal(await page.locator('[data-crud-action=cancel]').getAttribute('href'),base);await page.locator('[data-crud-action=cancel]').click();await page.waitForURL(base);
 await page.goto(alpha.plugins_url);
 await page.evaluate(() => { window.pluginFormDocument = document; });
 await page.locator('[name=name]').fill('Saved single instance');
 const saveResponse=()=>page.waitForResponse(r=>r.request().method()==='POST'&&r.url().includes('/plugins/'));
 const response=saveResponse();
 await page.locator('[data-crud-action=save]').click();
 assert.equal((await response).status(),200);
 await page.waitForFunction(()=>!document.querySelector('[data-extension-form]').dataset.saving);
 assert.ok(await page.evaluate(()=>window.pluginFormDocument===document));
 const closeResponse=saveResponse();
 await page.locator('.crud-split-save summary').click();
 await page.locator('[data-crud-action=close]').click();
 assert.equal((await closeResponse).status(),200);
 await page.waitForURL(base);
 let beta=(await data()).find(row=>row.name==='Beta fixture');await command(beta,'install');beta=(await data()).find(row=>row.id===beta.id);assert.equal(beta.plugins_count,0);await command(beta,'activate');beta=(await data()).find(row=>row.id===beta.id);assert.equal(beta.plugins_count,1);
 await page.goto(beta.plugins_url);await page.locator('[data-row-id]').waitFor();const first=await page.locator('[data-row-id]').first().getAttribute('data-row-id');await command(beta,'deactivate');beta=(await data()).find(row=>row.id===beta.id);await command(beta,'activate');await page.reload();await page.locator('[data-row-id]').waitFor();assert.equal(await page.locator('[data-row-id]').count(),1);assert.equal(await page.locator('[data-row-id]').first().getAttribute('data-row-id'),first);
 await page.goto(base);await page.locator(`[data-details="${alpha.id}"]`).locator('xpath=ancestor::details[1]/summary').click();await page.locator(`[data-details="${alpha.id}"]`).click();await page.waitForTimeout(400);await page.screenshot({path:path.join(dir,`${locale}-details-desktop.png`)});await page.setViewportSize({width:390,height:844});await page.evaluate(()=>document.documentElement.dataset.theme='dark');await page.waitForTimeout(350);await page.screenshot({path:path.join(dir,`${locale}-details-mobile.png`)});assert.ok(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth));console.log('PASS single cancel/save return, initial multiple draft, duplicate prevention, details desktop/mobile/dark',locale,dir);
 }finally{await page.close();sandbox.server.kill();}
}}finally{await browser.close();}
