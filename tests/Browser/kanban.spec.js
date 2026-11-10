import {test, expect} from '@playwright/test';
import fs from 'node:fs';

function copyFor(locale) {
    const source = fs.readFileSync(`lang/${locale}/kanban.php`, 'utf8');
    return Object.fromEntries([...source.matchAll(/'([^']+)' => '((?:\\.|[^'])*)'/g)].map(match => [match[1], match[2].replaceAll("\\'", "'")]));
}
async function fixture(page, locale = 'en') {
    const copy = copyFor(locale);
    let size = 5;
    let source = Array.from({length: 12}, (_, i) => ({id:i+1, title:locale==='fa'?`محتوای نمونه ${i+1}`:`Article ${i+1}`,author:'Board author',updated_at:'۱۴۰۵/۰۸/۱۸',version:1,revision_id:i+1,state:'draft'}));
    let target = [];
    let failSettings = false;
    let requiresDue = false;
    let previewDelay = 0;
    let pageDelay = 0;
    await page.route('**/admin/posts/post/kanban', async route => {
        const response = await route.fetch();
        let html = await response.text();
        const match = html.match(/(<script type="application\/json" id="jobic-page-data">)([\s\S]*?)(<\/script>)/);
        const data = JSON.parse(match[2]);
        data.kanban.copy = copy; data.ui.loading = copy.loading; data.kanban.editable = true; data.kanban.pageSize = 5;
        html = html.replace(match[0], `${match[1]}${JSON.stringify(data)}${match[3]}`);
        html = html.replace(/<html[^>]*>/, `<html lang="${locale}" dir="${locale==='fa'?'rtl':'ltr'}" data-theme="light">`);
        await route.fulfill({response,body:html});
    });
    await page.route('**/admin/posts/post/kanban/data**', async route => {
        const url = new URL(route.request().url());
        const state = url.searchParams.get('state'); const offset=Number(url.searchParams.get('cursor')||0);
        if (offset && pageDelay) await new Promise(resolve=>setTimeout(resolve,pageDelay));
        const make = (status, items, name) => ({id:status,status,name,restricted:false,version:0,count:items.length,items:items.slice(offset,offset+size),cursor:offset+size<items.length?String(offset+size):null});
        const columns = [make('draft',source,locale==='fa'?'نگارش':'Draft'),make('review',target,locale==='fa'?'بازبینی':'Review'),{id:'private',status:'private',name:locale==='fa'?'محرمانه':'Private',restricted:true,version:0,count:null,items:[],cursor:null}].filter(c=>!state||state===c.status);
        await route.fulfill({json:{ok:true,data:{definition_id:1,columns}}});
    });
    await page.route(/\/admin\/posts\/post\/kanban\/\d+(?:\?.*)?$/, async route => {
        const id=Number(new URL(route.request().url()).pathname.split('/').pop());
        if(previewDelay)await new Promise(resolve=>setTimeout(resolve,previewDelay));
        const card=[...source,...target].find(c=>c.id===id);
        await route.fulfill({json:{ok:true,data:{card,locale,definition_id:1,excerpt:copy.details,content_html:'<p>Safe preview content</p>',edit_url:'/kanban-editor-fixture',destinations:[{transition:'send',label:copy.move,state:'review',status:'ready',checks:[],requires_due:requiresDue},{transition:'private',label:'Private',state:'private',status:'unknown',checks:[],requires_due:false}]}}});
    });
    await page.route('**/kanban-editor-fixture**', route => route.fulfill({contentType:'text/html',body:'<!doctype html><html><body><form><label>Title<input name="title" value="Unchanged"></label><button type="button" id="save">Save</button></form><script>let dirty=false;document.querySelector("input").oninput=()=>dirty=true;window.kanbanEditor={canClose:async()=>!dirty||confirm("Discard changes?")};document.querySelector("#save").onclick=()=>{dirty=false;parent.dispatchEvent(new CustomEvent("jobic:kanban:saved"));};</script></body></html>'}));
    await page.route('**/admin/posts/post/kanban/command', async route => {
        const body=route.request().postData()||'';
        const field=name=>body.match(new RegExp(`name="${name}"\\r?\\n\\r?\\n([^\\r\\n]+)`))?.[1];
        await new Promise(resolve=>setTimeout(resolve,350));
        if(field('operation')==='settings') {
            if(failSettings) return route.fulfill({status:422,json:{ok:false,message:copy.failed,errors:{page_size:[copy.failed]}}});
            size=Number(field('page_size'));return route.fulfill({json:{ok:true,message:copy.saved,data:{page_size:size}}});
        }
        const id=Number(field('post_id'));const before=Number(field('before_id'));
        const card=[...source,...target].find(c=>c.id===id);
        source=source.filter(c=>c.id!==id); target=target.filter(c=>c.id!==id);
        card.state=field('state'); const list=card.state==='draft'?source:target;
        let index=list.findIndex(c=>c.id===before);if(index<0)index=list.length;list.splice(index,0,card);
        await route.fulfill({json:{ok:true,message:copy.moved,data:{id}}});
    });
    return {copy,failSettings: value=>failSettings=value,requireDue: value=>requiresDue=value,delayPreview: value=>previewDelay=value,delayPage: value=>pageDelay=value,seedTarget: count=>target=Array.from({length:count},(_,i)=>({id:101+i,title:`Review ${i+1}`,author:'Board author',updated_at:'2026/11/10',version:1,revision_id:101+i,state:'review'}))};
}

for(const locale of ['en','fa']) for(const mobile of [false,true]) {
    test(`Kanban ${locale} ${mobile?'mobile':'desktop'}: private columns, pagination, settings and modal stay in document`, async({page})=>{
        await page.setViewportSize(mobile?{width:390,height:844}:{width:1440,height:900});
        const {copy,failSettings}=await fixture(page,locale);
        const errors=[];page.on('pageerror',error=>errors.push(error.message));
        await page.goto('/admin/posts/post/kanban');
        await expect(page.locator('[data-kanban-column="draft"] .kb-card')).toHaveCount(5);
        await expect(page.locator('[data-admin-shell]')).toHaveCount(0);
        await page.evaluate(()=>window.__kanbanDocument=document);
        await page.locator('[data-kanban-column="draft"] .kb-column-body').evaluate(element=>{element.scrollTop=element.scrollHeight;});
        await page.locator('[data-kanban-column="draft"] .kb-column-body').dispatchEvent('wheel',{deltaY:600});
        await expect(page.locator('[data-kanban-column="draft"] .kb-card')).toHaveCount(10);
        await expect(page.locator('[data-kanban-column="review"] .kb-card')).toHaveCount(0);
        await expect(page.locator('[data-kanban-column="private"] .kb-count')).toHaveCount(0);
        await page.getByRole('button',{name:copy.settings,exact:true}).click();
        await page.locator('#kb-page-size').selectOption('10',{force:true});
        failSettings(true);
        await page.locator('.kb-settings[open] button[type=submit]').click();
        await expect(page.locator('.kb-settings[open] button[type=submit]')).toBeDisabled();
        await expect(page.locator('#kb-settings-error')).toBeVisible();
        failSettings(false);
        await page.locator('.kb-settings[open] button[type=submit]').click();
        await expect(page.locator('.kb-settings[open]')).toHaveCount(0);
        await expect(page.locator('[data-kanban-column="draft"] .kb-card')).toHaveCount(10);
        expect(await page.evaluate(()=>document===window.__kanbanDocument)).toBe(true);
        await page.locator('[data-kanban-card="1"] .kb-card-title').click();
        await expect(page.locator('.kb-details[open]')).toBeVisible();
        await expect(page.frameLocator('.kb-details iframe').locator('input')).toHaveValue('Unchanged');
        await page.frameLocator('.kb-details iframe').locator('input').fill('Unsaved');
        page.once('dialog',dialog=>dialog.dismiss());
        await page.locator('.kb-details[open]>header button').click();
        await expect(page.locator('.kb-details[open]')).toBeVisible();
        await page.frameLocator('.kb-details iframe').locator('#save').click();
        await page.locator('.kb-details[open]>header button').click();
        await expect(page.locator('.kb-details[open]')).toHaveCount(0);
        expect(await page.evaluate(()=>document===window.__kanbanDocument)).toBe(true);
        await page.screenshot({path:`test-results/kanban-${locale}-${mobile?'mobile':'desktop'}.png`});
        expect(errors).toEqual([]);
    });
}

test('Kanban drag highlights authorized and uncertain destinations, then persists transition and order', async({page})=>{
    await fixture(page);
    await page.setViewportSize({width:1440,height:900});
    await page.goto('/admin/posts/post/kanban');
    const transfer=await page.evaluateHandle(()=>new DataTransfer());
    await page.locator('[data-kanban-card="1"]').dispatchEvent('dragstart',{dataTransfer:transfer});
    await expect(page.locator('[data-kanban-column="review"]')).toHaveClass(/is-eligible/);
    await expect(page.locator('[data-kanban-column="private"]')).toHaveClass(/is-unknown/);
    await page.locator('[data-kanban-column="review"]').dispatchEvent('dragover',{dataTransfer:transfer});
    await page.locator('[data-kanban-column="review"]').dispatchEvent('drop',{dataTransfer:transfer});
    await expect(page.locator('[data-kanban-column="review"] [data-kanban-card="1"]')).toHaveCount(1);
    await page.locator('[data-kanban-card="4"] .kb-card-move').click();
    await page.locator('[data-kanban-card="4"] .kb-move-menu').getByText('Move up',{exact:false}).click();
    await expect(page.locator('[data-kanban-column="draft"] .kb-card').nth(1)).toHaveAttribute('data-kanban-card','4');
});


test('scheduled drops stay in the board and cancellation preserves the card', async({page})=>{
    const {copy,requireDue}=await fixture(page); requireDue(true);
    await page.goto('/admin/posts/post/kanban');
    const drop=async()=>{
        const transfer=await page.evaluateHandle(()=>new DataTransfer());
        await page.locator('[data-kanban-card="1"]').dispatchEvent('dragstart',{dataTransfer:transfer});
        await expect(page.locator('[data-kanban-column="review"]')).toHaveClass(/is-needs-input/);
        await page.locator('[data-kanban-column="review"]').dispatchEvent('drop',{dataTransfer:transfer});
        await expect(page.locator('#kb-due')).toBeVisible();
    };
    await drop();
    await page.locator('.kb-settings[open]>form>header button').click();
    await expect(page.locator('[data-kanban-column="draft"] [data-kanban-card="1"]')).toHaveCount(1);
    await drop();
    await page.locator('#kb-due').fill('2030/12/01 12:34');
    await page.locator('.kb-settings[open] button[type=submit]').click();
    await expect(page.locator('.kb-settings[open] button[type=submit]')).toBeDisabled();
    await expect(page.locator('[data-kanban-column="review"] [data-kanban-card="1"]')).toHaveCount(1);
    await expect(page).toHaveURL(/kanban$/);
});

test('native mouse drag can drop on the empty message inside a column', async ({page}) => {
    await fixture(page);
    await page.setViewportSize({width:1440,height:900});
    await page.goto('/admin/posts/post/kanban');
    await expect(page.locator('[data-kanban-card="1"]')).toBeVisible();
    await page.locator('[data-kanban-card="1"]').dragTo(page.locator('[data-kanban-column="review"] .kb-column-empty'));
    await expect(page.locator('[data-kanban-column="review"] [data-kanban-card="1"]')).toHaveCount(1);
});


test('native drop waits for target pagination after dragend', async ({page}) => {
    const controls=await fixture(page); controls.seedTarget(8); controls.delayPage(450);
    await page.setViewportSize({width:1440,height:1400});
    await page.goto('/admin/posts/post/kanban');
    await expect(page.locator('[data-kanban-column="review"] .kb-card')).toHaveCount(5);
    await page.locator('[data-kanban-card="1"]').dragTo(page.locator('[data-kanban-column="review"] .kb-column-header'));
    await expect(page.locator('[data-kanban-column="review"] [data-kanban-card="1"]')).toHaveCount(0);
    // The moved card follows the five already visible target cards; expand to observe that insertion.
    await page.locator('[data-kanban-column="review"] .kb-more').click();
    await expect(page.locator('[data-kanban-column="review"] .kb-card').nth(5)).toHaveAttribute('data-kanban-card','1');
});

test('native drop can finish while its condition preview is still loading', async ({page}) => {
    const controls=await fixture(page);controls.delayPreview(500);
    await page.goto('/admin/posts/post/kanban');
    await page.locator('[data-kanban-card="1"]').dragTo(page.locator('[data-kanban-column="review"] .kb-column-empty'));
    await expect(page.locator('[data-kanban-column="review"] [data-kanban-card="1"]')).toHaveCount(1);
});

async function workspaceGeometry(page) {
    return page.evaluate(()=>{
        const shape=(selector,props)=>{const style=getComputedStyle(document.querySelector(selector));return Object.fromEntries(props.map(prop=>[prop,style[prop]]));};
        return {header:shape('.rf-header',['padding','backgroundColor','borderBottomColor']),back:shape('.rf-header__back',['width','height','borderRadius','color']),settings:shape('.rf-settings-trigger',['width','height','borderRadius']),title:shape('.rf-header__title',['fontSize','fontWeight'])};
    });
}
async function modalGeometry(page) {
    return page.evaluate(()=>{
        const shape=(selector,props)=>{const style=getComputedStyle(document.querySelector(selector));return Object.fromEntries(props.map(prop=>[prop,style[prop]]));};
        return {panel:shape('.rf-settings-dialog[open]',['width','borderRadius','backgroundColor']),head:shape('.rf-settings-dialog[open] .rf-settings-dialog__head',['padding','borderBottomColor']),title:shape('.rf-settings-dialog[open] h2',['fontSize']),icon:shape('.rf-settings-dialog[open] .rf-settings-dialog__icon',['width','height','borderRadius'])};
    });
}
test('workflow and Kanban share exact header, settings dialog and toast styling',async({page})=>{
    await fixture(page);
    await page.setViewportSize({width:1440,height:900});
    await page.goto('/admin/posts/post/workflow');
    await expect(page.locator('.rf-header')).toBeVisible();
    const header=await workspaceGeometry(page);
    await page.locator('.rf-settings-trigger').click();
    await expect(page.locator('.rf-settings-dialog[open]')).toBeVisible();
    const modal=await modalGeometry(page);
    await page.locator('.rf-settings-dialog[open] .rf-mapping__close').click();
    await page.evaluate(()=>window.dispatchEvent(new CustomEvent('jobic:workflow:toast',{detail:{message:'Workspace success',error:false}})));
    await expect(page.locator('[data-workflow-toast]')).toBeVisible();
    const toast=await page.locator('[data-workflow-toast]').evaluate(node=>{const s=getComputedStyle(node);return {padding:s.padding,borderRadius:s.borderRadius,backgroundColor:s.backgroundColor,fontSize:s.fontSize};});
    await page.goto('/admin/posts/post/kanban');
    await expect(page.locator('.rf-header')).toBeVisible();
    expect(await workspaceGeometry(page)).toEqual(header);
    await page.locator('.rf-settings-trigger').click();
    await expect(page.locator('.rf-settings-dialog[open]')).toBeVisible();
    expect(await modalGeometry(page)).toEqual(modal);
    await page.evaluate(()=>window.dispatchEvent(new CustomEvent('jobic:workflow:toast',{detail:{message:'Workspace error',error:true}})));
    await expect(page.locator('[data-workflow-toast]')).toBeVisible();
    await expect(page.locator('[data-workflow-toast]')).toHaveClass(/is-error/);
    expect(await page.locator('[data-workflow-toast]').evaluate(node=>{const s=getComputedStyle(node);return {padding:s.padding,borderRadius:s.borderRadius,backgroundColor:s.backgroundColor,fontSize:s.fontSize};})).toEqual(toast);
    await page.screenshot({path:'test-results/kanban-settings-workflow-parity.png'});
});


test('native drop reuses a column page request already in flight', async ({page}) => {
    const controls=await fixture(page);controls.seedTarget(8);controls.delayPage(900);
    await page.setViewportSize({width:1440,height:1400});
    await page.goto('/admin/posts/post/kanban');
    await page.locator('[data-kanban-column="review"] .kb-more').click();
    await expect(page.locator('[data-kanban-column="review"] .kb-more')).toBeDisabled();
    await page.locator('[data-kanban-card="1"]').dragTo(page.locator('[data-kanban-column="review"] .kb-column-header'));
    await expect(page.locator('[data-kanban-column="draft"] [data-kanban-card="1"]')).toHaveCount(0);
    await page.locator('[data-kanban-column="review"] .kb-more').click();
    await expect(page.locator('[data-kanban-column="review"] .kb-card').nth(5)).toHaveAttribute('data-kanban-card','1');
});

test('native card targets support cross-column insertion and same-column order', async({page})=>{
    const controls=await fixture(page);controls.seedTarget(3);
    await page.setViewportSize({width:1440,height:1100});
    await page.goto('/admin/posts/post/kanban');
    await page.locator('[data-kanban-card="1"]').dragTo(page.locator('[data-kanban-card="102"]'),{targetPosition:{x:90,y:25}});
    await expect(page.locator('[data-kanban-column="review"] .kb-card').nth(1)).toHaveAttribute('data-kanban-card','1');
    await page.locator('[data-kanban-card="2"]').dragTo(page.locator('[data-kanban-card="4"]'),{targetPosition:{x:90,y:25}});
    await expect(page.locator('[data-kanban-column="draft"] .kb-card').nth(1)).toHaveAttribute('data-kanban-card','2');
});
