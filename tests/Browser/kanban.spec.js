import {test, expect} from '@playwright/test';
import fs from 'node:fs';

function copyFor(locale) {
    const source = fs.readFileSync(`lang/${locale}/kanban.php`, 'utf8');
    return Object.fromEntries([...source.matchAll(/'([^']+)'\s*=>\s*'((?:\\.|[^'])*)'/g)].map(match => [match[1], match[2].replaceAll("\\'", "'")]));
}
async function fixture(page, locale = 'en', datePlugin = false) {
    const copy = copyFor(locale);
    let size = 5;
    let source = Array.from({length: 12}, (_, i) => ({id:i+1, title:locale==='fa'?`محتوای نمونه ${i+1}`:`Article ${i+1}`,author:'Board author',avatar_url:'/vuero/images/admin/avatars/presets/avatar-01.webp',author_id:7,age_days:2,terms:[{id:1,name:'News',collection:'categories'}],updated_at:'۱۴۰۵/۰۸/۱۸',version:1,revision_id:i+1,state:'draft'}));
    let target = [];
    let failSettings = false;
    let requiresDue = false;
    let previewDelay = 0;
    let pageDelay = 0;
    let customVersion = null;let realtimeEnabled=false;let memberCount=7;let cardWork={version:0,assignee:null,due_at:null,dependencies:[]};
    let workspaceOptions={version:0,stale_days:7,limits:[]};
    let workspacePrefs={density:'full',card_fields:['image','terms','author','updated','age','assignee','scheduled','deadline','dependencies'],group_by:'none'};
    await page.route('**/admin/posts/post/kanban', async route => {
        const response = await route.fetch();
        let html = await response.text();
        const match = html.match(/(<script type="application\/json" id="jobic-page-data">)([\s\S]*?)(<\/script>)/);
        const data = JSON.parse(match[2]);
        data.kanban.realtime={enabled:realtimeEnabled,key:'fixture-key',host:'127.0.0.1',port:5185,scheme:'http',authUrl:'/fixture-broadcast-auth',channel:'jobic.kanban.post'};data.kanban.workspace={...workspacePrefs};data.kanban.options=workspaceOptions;data.kanban.filterCatalog={author:{label:copy.authors,fields:[{key:'ids',label:copy.authors,multiple:true,options:[{id:7,name:'Board author'}]}]}};data.kanban.boardVersion=customVersion;data.kanban.boardName=customVersion?'Team board':null;data.kanban.membersUrl=customVersion?`/admin/posts/post/kanban/boards/${customVersion}/members`:'/admin/posts/post/kanban/members';data.kanban.calendarUrl='/admin/posts/post/kanban/calendar';data.kanban.insightsUrl='/admin/posts/post/kanban/insights';data.kanban.copy = copy; data.kanban.calendar = locale==='fa'?'jalali':'gregorian';data.ui.loading = copy.loading; data.kanban.editable = true; data.kanban.pageSize = 5;
        data.kanban.notificationsUrl='/admin/posts/post/kanban/notifications';
        html = html.replace(match[0], `${match[1]}${JSON.stringify(data)}${match[3]}`);
        html = html.replace(/<html[^>]*>/, `<html lang="${locale}" dir="${locale==='fa'?'rtl':'ltr'}" data-theme="light">`);
        if(datePlugin)html=html.replace('</head>',`<link rel="stylesheet" href="/assets/vendor/domi/plugins/jquery-ui-bootstrap/jquery-ui.css"><link rel="stylesheet" href="/assets/vendor/domi/plugins/datetime/jquery-ui-timepicker-addon.css"><script src="/assets/vendor/domi/plugins/jquery-ui/jquery-ui.min.js"></script><script src="/assets/vendor/domi/plugins/datetime/jquery-ui-timepicker-addon.js"></script>${locale==='fa'?'<script src="/assets/vendor/domi/plugins/datetime/jalali.js"></script><script>Date.now=Date.now||function(){return new Date().getTime()}</script>':''}</head>`);
        await route.fulfill({response,body:html});
    });
    await page.route('**/admin/posts/post/kanban/data**', async route => {
        const url = new URL(route.request().url());
        const state = url.searchParams.get('state'); const offset=Number(url.searchParams.get('cursor')||0);
        if (offset && pageDelay) await new Promise(resolve=>setTimeout(resolve,pageDelay));
        const make = (status, items, name) => ({id:status,status,name,restricted:false,version:0,count:items.length,items:items.slice(offset,offset+size),cursor:offset+size<items.length?String(offset+size):null});
        let filtered=source;const search=url.searchParams.get('search');if(search)filtered=filtered.filter(card=>card.title.toLowerCase().includes(search.toLowerCase()));const total=filtered.length+target.length;if(url.searchParams.get('summary')==='stale')filtered=[];
        const columns = [make('draft',filtered,locale==='fa'?'نگارش':'Draft'),make('review',target,locale==='fa'?'بازبینی':'Review'),{id:'private',status:'private',name:locale==='fa'?'محرمانه':'Private',restricted:true,version:0,count:null,items:[],cursor:null}].filter(c=>!state||state===c.status);
        for(const column of columns){column.lane=url.searchParams.get('group_by')==='author'?'7':'all';column.lane_name=column.lane==='7'?'Board author':'';column.capacity=workspaceOptions.limits.find(row=>row.state===column.status);column.capacity_count=column.count;}
        await route.fulfill({json:{ok:true,data:{definition_id:1,columns,summary:{total,scheduled:0,stale:0},options:workspaceOptions}}});
    });
    await page.route(/\/admin\/posts\/post\/kanban(?:\/boards\/\d+)?\/members(?:\?.*)?$/,async route=>{
        const url=new URL(route.request().url());const cursor=Number(url.searchParams.get('member_cursor')||0);const search=(url.searchParams.get('member_search')||'').toLowerCase();
        let all=Array.from({length:memberCount},(_,index)=>({id:index+1,name:`Board member ${index+1}`,avatar_url:index%2?null:'/vuero/images/admin/avatars/presets/avatar-01.webp',access:'role',primary_role:index%2?'Review':'Editorial',roles:[],card_count:index%3})).filter(member=>member.name.toLowerCase().includes(search));
        if(url.searchParams.get('member_role'))all=all.filter(member=>member.primary_role===(url.searchParams.get('member_role')==='1'?'Editorial':'Review'));
        if(url.searchParams.get('member_sort')==='cards')all.sort((a,b)=>b.card_count-a.card_count||a.id-b.id);
        const remaining=all.slice(cursor?Math.max(0,all.findIndex(member=>member.id===cursor)+1):0);const items=remaining.slice(0,20);
        await route.fulfill({json:{ok:true,data:{items,total:all.length,next_cursor:remaining.length>20?items.at(-1).id:null,role_options:[{id:1,name:'Editorial'},{id:2,name:'Review'}]}}});
    });
    await page.route('**/admin/posts/post/kanban/calendar**',async route=>{
        const offset=Number(new URL(route.request().url()).searchParams.get('calendar_offset')||0);
        await route.fulfill({json:{ok:true,data:{month:`2026/${11+offset}`,today:'2026-11-12',days:[{date:'2026-11-12',label:'2026/11/12',weekday:4}],events:[{id:1,date:'2026-11-12',kind:'deadline',title:'Article 1',state:'Draft'}],truncated:false,offset}}});
    });
    await page.route('**/admin/posts/post/kanban/insights**',async route=>route.fulfill({json:{ok:true,data:{states:[{status:'draft',name:'Draft',count:12,average_days:2,stale:1}]}}}));
    await page.route('**/admin/posts/post/kanban/notifications**',async route=>route.fulfill({json:{ok:true,data:{items:[],unread:0,preferences:{}}}}));
    await page.route(/\/admin\/posts\/post\/kanban\/\d+(?:\?.*)?$/, async route => {
        const id=Number(new URL(route.request().url()).pathname.split('/').pop());
        if(previewDelay)await new Promise(resolve=>setTimeout(resolve,previewDelay));
        const card=[...source,...target].find(c=>c.id===id);
        await route.fulfill({json:{ok:true,data:{card,work:cardWork,discussion:{items:[],cursor:null},locale,definition_id:1,excerpt:copy.details,content_html:'<p>Safe preview content</p>',edit_url:'/kanban-editor-fixture',destinations:[{transition:'send',label:copy.move,state:'review',status:'ready',checks:[],requires_due:requiresDue},{transition:'private',label:'Private',state:'private',status:'unknown',checks:[],requires_due:false}]}}});
    });
    await page.route('**/kanban-editor-fixture**', route => route.fulfill({contentType:'text/html',body:'<!doctype html><html><body><form><label>Title<input name="title" value="Unchanged"></label><button type="button" id="save">Save</button></form><script>let dirty=false;document.querySelector("input").oninput=()=>dirty=true;window.kanbanEditor={isDirty:()=>dirty,canClose:async({confirmed=false}={})=>!dirty||confirmed};document.querySelector("#save").onclick=()=>{dirty=false;parent.dispatchEvent(new CustomEvent("jobic:kanban:saved"));};</script></body></html>'}));
    await page.route('**/admin/posts/post/kanban/command', async route => {
        const body=route.request().postData()||'';
        const field=name=>body.match(new RegExp(`name="${name}"\\r?\\n\\r?\\n([^\\r\\n]+)`))?.[1];
        await new Promise(resolve=>setTimeout(resolve,350));
        if(customVersion && Number(field('board_version'))!==customVersion)return route.fulfill({status:409,json:{ok:false,message:'Stale board'}});
        if(field('operation')==='settings') {
            if(failSettings) return route.fulfill({status:422,json:{ok:false,message:copy.failed,errors:{page_size:[copy.failed]}}});
            size=Number(field('page_size'));workspacePrefs={...workspacePrefs,...(field('density')?{density:field('density')}:{}),...(field('card_fields')?{card_fields:JSON.parse(field('card_fields'))}:{}),...(field('group_by')?{group_by:field('group_by')}: {})};return route.fulfill({json:{ok:true,message:copy.saved,data:{page_size:size}}});
        }
        if(field('operation')==='limits'){workspaceOptions={version:workspaceOptions.version+1,stale_days:Number(field('stale_days')),limits:JSON.parse(field('limits'))};return route.fulfill({json:{ok:true,message:copy.saved,data:{options:workspaceOptions}}});}
        if(field('operation')==='card_work'){cardWork={version:cardWork.version+1,assignee:{id:Number(field('assignee_id')),name:'Board member 1'},due_at:field('work_due_local'),dependencies:JSON.parse(field('dependency_ids')).map(id=>({id}))};return route.fulfill({json:{ok:true,message:copy.saved,data:{work:cardWork}}});}
        if(field('operation')==='bulk'){const results=JSON.parse(field('items')).map((item,index)=>{if(index)return {id:item.post_id,ok:false,message:copy.locked,status:423};const card=source.find(row=>row.id===item.post_id);source=source.filter(row=>row.id!==item.post_id);target.push({...card,state:item.state});return {id:item.post_id,ok:true,message:copy.moved,status:200};});return route.fulfill({json:{ok:true,message:copy.bulk_done,data:{results}}});}
        const id=Number(field('post_id'));const before=Number(field('before_id'));
        const card=[...source,...target].find(c=>c.id===id);
        source=source.filter(c=>c.id!==id); target=target.filter(c=>c.id!==id);
        card.state=field('state'); const list=card.state==='draft'?source:target;
        let index=list.findIndex(c=>c.id===before);if(index<0)index=list.length;list.splice(index,0,card);
        await route.fulfill({json:{ok:true,message:copy.moved,data:{id}}});
    });
    return {copy,live:value=>realtimeEnabled=value, customBoard:version=>{customVersion=version;},memberCount:value=>memberCount=value,failSettings: value=>failSettings=value,requireDue: value=>requiresDue=value,delayPreview: value=>previewDelay=value,seedCardWork:()=>{source[0]={...source[0],assignee:{id:1,name:'Board member 1'},scheduled_at:'2026/11/20',due_at:'2026/11/18',dependency_count:2};},seedTarget: count=>target=Array.from({length:count},(_,i)=>({id:101+i,title:`Review ${i+1}`,author:'Board author',updated_at:'2026/11/10',version:1,revision_id:101+i,state:'review'}))};
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
        await page.locator('.kb-details[open]>header button').click();
        await expect(page.locator('.kb-discard-dialog[open]')).toBeVisible();
        await page.locator('.kb-discard-dialog .rf-btn--secondary').click();
        await expect(page.locator('.kb-details[open]')).toBeVisible();
        await page.frameLocator('.kb-details iframe').locator('#save').click();
        await page.locator('.kb-details[open]>header button').click();
        await expect(page.locator('.kb-details[open]')).toHaveCount(0);
        expect(await page.evaluate(()=>document===window.__kanbanDocument)).toBe(true);
        await page.screenshot({path:`test-results/kanban-${locale}-${mobile?'mobile':'desktop'}.png`});
        expect(errors).toEqual([]);
    });
}

for(const locale of ['en','fa']) test(`card work and discussion stay in separate tabs with themed discard confirmation ${locale}`,async({page})=>{
    const {copy}=await fixture(page,locale);
    let nativePrompts=0;page.on('dialog',dialog=>{nativePrompts++;dialog.dismiss();});
    await page.goto('/admin/posts/post/kanban');
    await page.locator('[data-kanban-card="1"] .kb-card-title').click();
    await page.locator('.kb-details[open]>header button').click();
    await expect(page.locator('.kb-details[open],.kb-discard-dialog[open]')).toHaveCount(0);
    await page.locator('[data-kanban-card="1"] .kb-card-title').click();
    await expect(page.locator('#kb-detail-panel-discussion')).toBeVisible();
    await expect(page.locator('#kb-detail-panel-work')).toBeHidden();
    await page.locator('.kb-detail-collapse').click();
    await expect(page.locator('#kb-detail-panel-discussion')).toBeHidden();
    await page.locator('.kb-detail-collapse').click();
    await expect(page.locator('#kb-detail-panel-discussion')).toBeVisible();
    await page.locator('#kb-comment-body').fill('Draft comment');
    await page.locator('.rf-header__back').click();
    await expect(page.locator('.kb-discard-dialog[open]')).toBeVisible();
    await page.locator('.kb-discard-dialog .rf-btn--secondary').click();
    await expect(page.locator('.kb-details[open]')).toBeVisible();
    await page.locator('.kb-details[open]>header button').click();
    await expect(page.locator('.kb-discard-dialog[open]')).toContainText(copy.unsaved);
    await page.locator('.kb-discard-dialog .rf-btn--secondary').click();
    await expect(page.locator('.kb-details[open]')).toBeVisible();
    await page.locator('.kb-details[open]>header button').click();
    await page.locator('.kb-discard-dialog .rf-btn--danger').click();
    await expect(page.locator('.kb-details[open]')).toHaveCount(0);
    expect(nativePrompts).toBe(0);
});

test('card work uses two clean columns and notifications toggle from the header gear',async({page})=>{
    const {copy}=await fixture(page,'en');
    await page.goto('/admin/posts/post/kanban');
    await page.locator('[data-kanban-card="1"] .kb-card-title').click();
    await page.locator('#kb-detail-tab-work').click();
    await expect(page.locator('.kb-work-column')).toHaveCount(2);
    await expect(page.locator('.kb-work-column').first()).toContainText(copy.assignee);
    await expect(page.locator('.kb-work-column').last()).toContainText(copy.dependencies);
    await page.locator('.kb-details[open]>header button').click();
    await page.locator('.kb-notification-trigger').click();
    await expect(page.locator('.kb-notifications')).toBeVisible();
    await page.locator('.kb-notification-settings-trigger').click();
    await expect(page.locator('.kb-notification-preferences form')).toBeVisible();
    await page.locator('.kb-notification-settings-trigger').click();
    await expect(page.locator('.kb-notifications')).toBeVisible();
});

test('clearing a saved grouping survives a full board reload',async({page})=>{
    await fixture(page,'en');
    await page.goto('/admin/posts/post/kanban');
    await page.locator('.kb-group-trigger').click();
    await page.locator('.kb-group-choices input[value=author]').check();
    await expect(page.locator('.kb-lane-title')).toContainText('Board author');
    await page.locator('.kb-group-trigger').click();
    await page.locator('.kb-group-choices input[value=none]').check();
    await expect(page.locator('.kb-lane-title')).toHaveCount(0);
    await page.reload();
    await expect(page.locator('.kb-group-trigger')).not.toHaveClass(/is-active/);
    await expect(page.locator('.kb-lane-title')).toHaveCount(0);
});

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

test('reorder placeholder stays at the start while dragging across its gap', async({page})=>{
    await fixture(page);
    await page.goto('/admin/posts/post/kanban');
    const source=page.locator('[data-kanban-column="draft"] [data-kanban-card="2"]');
    const first=page.locator('[data-kanban-column="draft"] [data-kanban-card="1"]');
    const body=page.locator('[data-kanban-column="draft"] .kb-column-body');
    const point=await first.evaluate(element=>{const rect=element.getBoundingClientRect();return {clientX:rect.left+20,clientY:rect.top+4};});
    const transfer=await page.evaluateHandle(()=>new DataTransfer());
    await source.dispatchEvent('dragstart',{dataTransfer:transfer});
    await first.dispatchEvent('dragover',{...point,dataTransfer:transfer});
    for(let index=0;index<6;index++){
        await body.dispatchEvent('dragover',{...point,dataTransfer:transfer});
        await expect(page.locator('[data-kanban-column="draft"] .kb-placeholder + [data-kanban-card="1"]')).toHaveCount(1);
    }
    await source.dispatchEvent('dragend',{dataTransfer:transfer});
    await expect(page.locator('[data-kanban-column="draft"] .kb-card-checkbox:checked')).toHaveCount(0);
});

for(const locale of ['en','fa'])test(`new card details can be hidden and saved in ${locale}`,async({page})=>{
    const controls=await fixture(page,locale);controls.seedCardWork();
    await page.goto('/admin/posts/post/kanban');
    const card=page.locator('[data-kanban-card="1"]');
    await expect(card.locator('.kb-card-assignee')).toHaveCount(1);
    await expect(card.locator('.kb-card-signals')).toContainText('2026/11/20');
    await expect(card.locator('.kb-card-work-signals')).toContainText('2026/11/18');
    const documentId=await page.evaluate(()=>performance.timeOrigin);
    await page.getByRole('button',{name:controls.copy.settings,exact:true}).click();
    for(const key of ['assignee','scheduled','deadline','dependencies'])await page.getByRole('checkbox',{name:controls.copy[`field_${key}`]}).uncheck();
    await page.locator('.kb-settings[open] button[type=submit]').click();
    await expect(card.locator('.kb-card-assignee,.kb-card-work-signals')).toHaveCount(0);
    await expect(card.locator('.kb-card-signals')).not.toContainText('2026/11/20');
    expect(await page.evaluate(()=>performance.timeOrigin)).toBe(documentId);
    await page.getByRole('button',{name:controls.copy.settings,exact:true}).click();
    for(const key of ['assignee','scheduled','deadline','dependencies'])await expect(page.getByRole('checkbox',{name:controls.copy[`field_${key}`]})).not.toBeChecked();
});

for (const locale of ['en','fa']) for (const mobile of [false,true]) {
    test(`managed boards ${locale} ${mobile?'mobile':'desktop'} save filters in the same document`,async({page})=>{
        const errors=[];page.on('pageerror',error=>errors.push(error.message));
        let boards=[],nextId=1;
        await page.route('**/admin/posts/post/kanban/boards**',async route=>{
            const request=route.request();
            if(request.isNavigationRequest())return route.continue();
            if(new URL(request.url()).pathname.endsWith('/history'))return route.fulfill({json:{ok:true,data:{entries:[],page:1,pages:1}}});
            if(request.method()==='GET')return route.fulfill({json:{ok:true,data:{items:boards,total:boards.length,filtered:boards.length}}});
            const body=request.postData()||'';
            const field=name=>body.match(new RegExp('name="'+name+'"\\r\\n\\r\\n([^\\r]+)'))?.[1];
            const board={id:nextId++,name:field('name'),version:1,states:JSON.parse(field('states')),roles:JSON.parse(field('roles')),users:JSON.parse(field('users')),filters:JSON.parse(field('filters')),url:'/unused-board'};
            boards.push(board);
            await new Promise(resolve=>setTimeout(resolve,250));
            await route.fulfill({json:{ok:true,message:'Saved',data:{board}}});
        });
        if(mobile)await page.setViewportSize({width:390,height:844});
        await page.goto(`/fixture/boards?locale=${locale}`);
        if(locale==='fa')await page.evaluate(()=>{document.documentElement.lang='fa';document.documentElement.dir='rtl';});
        await expect(page.locator('[data-board-manage]')).toHaveCount(0);
        await expect(page.locator('.crud-title-back')).toBeVisible();
        await expect(page.locator('[data-crud-toolbar] [data-board-default]')).toBeVisible();
        await page.locator('[data-board-create]').click();
        await expect(page).toHaveURL(/kanban\/boards\/create/);
        await page.evaluate(()=>{window.documentMarker='directory-stays';});
        await page.locator('#board-name').fill('Editorial team');
        await page.locator('[data-board-tab="1"]').click();
        await page.locator('[data-column-action="add"][data-column-id="draft"]').click();
        await page.locator('[data-column-action="add"][data-column-id="review"]').click();
        await page.locator('[data-board-tab="0"]').click();
        await page.locator('#board-roles').selectOption(['1'],{force:true});
        await page.locator('[data-board-tab="2"]').click();
        await page.locator('[data-board-add-filter]').click();
        await page.locator('[data-board-filters] [data-field="ids"]').selectOption(['7'],{force:true});
        if(locale==='fa'&&mobile)await page.screenshot({path:'test-results/kanban-board-manager-fa-mobile.png',fullPage:true});
        const submit=page.locator('[data-board-form] button[value="save"]');await submit.click();
        await expect(submit).toBeDisabled();await expect(submit).toBeEnabled();
        expect(boards[0].filters).toEqual([{driver:'author',config:{ids:['7']}}]);
        expect(boards[0].states).toEqual(['draft','review']);
        expect(await page.evaluate(()=>window.documentMarker)).toBe('directory-stays');
        await expect(page).toHaveURL(/boards\/1\/edit/);
        await page.locator('[data-crud-action="cancel"]').click();
        await expect(page.locator('[data-board-rows]')).toContainText('Editorial team');
        expect(errors).toEqual([]);
    });
}

test('custom workspace preferences submit the current board version without navigation',async({page})=>{
    const controls=await fixture(page);controls.customBoard(4);
    await page.goto('/admin/posts/post/kanban');
    await page.evaluate(()=>window.documentMarker='custom-workspace');
    await page.locator('.rf-settings-trigger').click();
    await page.locator('#kb-page-size').selectOption('10',{force:true});
    await page.locator('[form="kanban-settings-form"][type="submit"]').click();
    await expect(page.locator('.kb-board-settings[open]')).toHaveCount(0);
    await expect(page.locator('[data-kanban-column="draft"] .kb-card')).toHaveCount(10);
    expect(await page.evaluate(()=>window.documentMarker)).toBe('custom-workspace');
});

test('board management retains selection across pages and submits versioned bulk deletion',async({page})=>{
    const errors=[];page.on('pageerror',error=>errors.push(error.message));
    let boards=Array.from({length:25},(_,i)=>({id:i+1,name:`Board ${i+1}`,version:1,states:['draft'],roles:[],users:[],filters:[],updated_at:'2026/11/10',url:'/unused'}));
    let deleted=[];
    await page.route('**/admin/posts/post/kanban/boards**',async route=>{
        if(route.request().method()==='GET'){
            const url=new URL(route.request().url());const start=Number(url.searchParams.get('start')||0);const length=Number(url.searchParams.get('length')||20);
            return route.fulfill({json:{ok:true,data:{items:boards.slice(start,start+length),total:boards.length,filtered:boards.length,stats:[boards.length,boards.length,0,boards.length]}}});
        }
        const body=route.request().postData()||'';deleted=JSON.parse(body.match(/name="items"\r\n\r\n([^\r]+)/)[1]);
        boards=boards.filter(board=>!deleted.some(item=>item.id===board.id));await route.fulfill({json:{ok:true,message:'Deleted'}});
    });
    await page.goto('/fixture/boards');await expect(page.locator('[data-board-manage]')).toHaveCount(0);
    await page.locator('[data-board-select="1"]').check();
    await page.locator('#board-table_next').click();
    await page.locator('[data-board-select="21"]').check();
    await expect(page.locator('[data-board-selected]')).toContainText('(2)');
    await page.locator('#board-table_previous').click();await expect(page.locator('[data-board-select="1"]')).toBeChecked();
    await page.locator('[data-board-list] .crud-dropdown').first().locator('summary').click();
    await page.locator('[data-board-bulk]').click();await expect(page.locator('[data-crud-dialog][open]')).toBeVisible();
    await page.locator('[data-dialog-confirm]').click();
    await expect.poll(()=>deleted.length).toBe(2);expect(deleted).toEqual([{id:1,version:1},{id:21,version:1}]);expect(errors).toEqual([]);
});

test('board form shows field errors and confirms unsaved cancellation',async({page})=>{
    const errors=[];page.on('pageerror',error=>errors.push(error.message));
    await page.route('**/admin/posts/post/kanban/boards**',async route=>{
        if(route.request().isNavigationRequest())return route.continue();
        if(route.request().method()==='GET')return route.fulfill({json:{ok:true,data:{items:[],total:0,filtered:0,stats:[0,0,0,0]}}});
        await route.fulfill({status:422,json:{ok:false,message:'Check board name',errors:{name:['Required name']}}});
    });
    await page.goto('/fixture/boards');await page.locator('[data-board-create]').click();
    await page.locator('#board-name').fill('Unsaved');await page.locator('[data-board-tab="1"]').click();await page.locator('[data-column-action="add"][data-column-id="draft"]').click();
    await page.locator('[data-board-form] button[value="save"]').click();
    await expect(page.locator('#board-name')).toHaveAttribute('aria-invalid','true');
    await page.locator('[data-crud-action="cancel"]').click();await expect(page.locator('[data-crud-dialog][open]')).toBeVisible();
    await page.locator('[data-dialog-cancel]').click();await expect(page.locator('[data-board-form]')).toBeVisible();
    await page.locator('[data-crud-action="cancel"]').click();await page.locator('[data-dialog-confirm]').click();await expect(page.locator('[data-board-list]')).toBeVisible();
    expect(errors).toEqual([]);
});

test('board columns move between lists and icon ordering persists with filter cards',async({page})=>{
    const errors=[];page.on('pageerror',error=>errors.push(error.message));let saved;
    await page.route('**/admin/posts/post/kanban/boards**',async route=>{
        if(route.request().isNavigationRequest())return route.continue();
        if(route.request().method()==='GET')return route.fulfill({json:{ok:true,data:{entries:[],page:1,pages:1}}});
        const body=route.request().postData()||'';const field=name=>body.match(new RegExp('name="'+name+'"\\r\\n\\r\\n([^\\r]+)'))?.[1];
        saved={id:1,name:field('name'),version:1,states:JSON.parse(field('states')),filters:JSON.parse(field('filters')),roles:[],users:[]};
        await route.fulfill({json:{ok:true,message:'Saved',data:{board:saved}}});
    });
    await page.goto('/admin/posts/post/kanban/boards/create');
    await expect(page.locator('[data-board-tab]')).toHaveCount(3);
    await expect(page.locator('.crud-history-disclosure')).toBeHidden();
    await expect(page.locator('#board-roles')).toBeVisible();
    expect(await page.locator('.board-name-field').evaluate(element=>getComputedStyle(element).gridColumnStart)).toBe('1');
    expect(await page.locator('.board-name-field').evaluate(element=>getComputedStyle(element).gridColumnEnd)).toBe('-1');
    await page.locator('#board-name').fill('Focused board');await page.locator('[data-board-tab="1"]').click();
    await expect(page.locator('[data-board-state-available]')).toBeVisible();await expect(page.locator('select#board-states')).toHaveCount(0);
    await page.locator('[data-column-action="add"][data-column-id="draft"]').click();await page.locator('[data-column-action="add"][data-column-id="review"]').click();
    await page.locator('[data-column-action="up"][data-column-id="review"]').click();
    await expect(page.locator('[data-selected-column]').first()).toHaveAttribute('data-selected-column','review');
    await page.locator('[data-column-action="remove"][data-column-id="draft"]').click();
    await expect(page.locator('[data-column-action="add"][data-column-id="draft"]')).toBeFocused();
    await page.locator('[data-column-action="add"][data-column-id="draft"]').click();
    await page.screenshot({path:'test-results/kanban-column-picker-desktop.png',fullPage:true});
    await page.locator('[data-board-tab="2"]').click();await expect(page.locator('.board-filters-empty')).toBeVisible();
    await page.locator('[data-board-add-filter]').click();await page.locator('#board-filter-0-driver').selectOption('taxonomy',{force:true});
    await page.locator('[data-field="ids"]').selectOption(['4'],{force:true});
    await expect(page.locator('.board-filter-values .select2-selection__choice')).toHaveCount(1);
    expect(await page.locator('.board-filter-values .select2-selection--multiple').evaluate(element=>element.scrollWidth<=element.clientWidth)).toBe(true);
    await page.screenshot({path:'test-results/kanban-board-filter-form-desktop.png',fullPage:true});
    await page.locator('[data-board-form] button[value="save"]').click();
    await expect.poll(()=>saved?.states).toEqual(['review','draft']);
    await expect(page.locator('.crud-history-disclosure')).toBeVisible();
    await expect(page.locator('.crud-history-disclosure')).not.toHaveAttribute('open');
    await page.screenshot({path:'test-results/kanban-board-history-closed.png',fullPage:true});
    await page.locator('.crud-history-disclosure__trigger').click();
    await expect(page.locator('.crud-history-disclosure')).toHaveAttribute('open');
    expect(saved.filters).toEqual([{driver:'taxonomy',config:{collection:'categories',ids:['4']}}]);expect(errors).toEqual([]);
});

test('board details place name above audience fields on mobile',async({page})=>{
    await page.setViewportSize({width:390,height:844});
    await page.goto('/admin/posts/post/kanban/boards/create?locale=fa');
    const name=await page.locator('#board-name').boundingBox();
    const roles=await page.locator('#board-roles + .select2-container').boundingBox();
    const users=await page.locator('#board-users + .select2-container').boundingBox();
    expect(name.y+name.height).toBeLessThan(roles.y);
    expect(roles.y+roles.height).toBeLessThan(users.y);
    await page.screenshot({path:'test-results/kanban-board-details-fa-mobile.png',fullPage:true});
});

test('board directory has no subtitle and offers a detailed help dialog',async({page})=>{
    await page.route('**/kanban/boards/data**',route=>route.fulfill({json:{ok:true,data:{items:[],total:0,filtered:0}}}));
    await page.goto('/fixture/boards?locale=fa');
    await expect(page.locator('[data-board-list] > header p')).toHaveCount(0);
    await page.locator('[data-board-list] > header .crud-dropdown > summary').click();await page.locator('[data-board-help]').click();
    await expect(page.locator('[data-crud-dialog][open] .crud-help-section')).toHaveCount(5);
    await expect(page.locator('[data-crud-dialog]')).toContainText('وضعیت واقعی محتوا');
});

test('post toolbar preserves the requested order and translates every action live',async({page})=>{
    const source=fs.readFileSync('resources/js/posts/toolbar.js','utf8')
        .replace("import {iconMarkup} from '../common/icons';",'const iconMarkup=name=>`<svg data-icon="${name}" aria-hidden="true"></svg>`;')
        .replace('export function mountPostListToolbar','function mountPostListToolbar');
    await page.setContent('<div data-crud-toolbar></div>');
    await page.addStyleTag({content:fs.readFileSync('resources/css/crud.css','utf8')+'\n'+fs.readFileSync('resources/css/crud-fields.css','utf8')});
    await page.addScriptTag({content:source+'\nwindow.mountPostListToolbar=mountPostListToolbar;'});
    await page.evaluate(()=>{
        window.documentMarker='live-toolbar';
        window.toolbarConfig={type:'post',createUrl:'/create',defaultKanbanUrl:'/kanban/default',kanbanUrl:'/kanban',workflowUrl:'/workflow',operations:['unlock','help','delete'],
            labels:{create:'افزودن نوشته',actions:'عملیات',delete:'حذف',help:'راهنما',unlock:'آزادسازی قفل ویرایش'},defaultKanbanLabel:'برد کانبان',kanbanLabel:'مدیریت بردهای کانبان',workflowLabel:'تنظیمات گردش کار'};
        window.mountPostListToolbar(document.querySelector('[data-crud-toolbar]'),()=>window.toolbarConfig);
    });
    const order=()=>page.locator('.crud-dropdown__menu').evaluate(menu=>[...menu.children].map(control=>control.tagName==='HR'?'separator':control.textContent));
    expect(await page.locator('[data-crud-toolbar]').evaluate(root=>[...root.children].map(control=>control.tagName))).toEqual(['A','A','DETAILS']);
    await expect(page.locator('[data-crud-toolbar] > [data-post-default-kanban-link]')).toHaveText('برد کانبان');
    expect(await order()).toEqual(['حذف','separator','مدیریت بردهای کانبان','تنظیمات گردش کار','separator','راهنما','آزادسازی قفل ویرایش']);
    await expect(page.locator('[data-post-workflow-link] svg')).toHaveAttribute('data-icon','settings');
    expect(await page.locator('[data-post-action=help]').evaluate(control=>getComputedStyle(control).borderBlockStartWidth)).toBe('0px');
    await expect(page.locator('[data-post-action=delete]')).toHaveAttribute('hidden','');
    await page.evaluate(()=>{
        Object.assign(window.toolbarConfig,{labels:{create:'Add post',actions:'Actions',delete:'Delete',help:'Help',unlock:'Release edit locks'},defaultKanbanLabel:'Kanban board',kanbanLabel:'Manage Kanban boards',workflowLabel:'Workflow settings'});
        window.dispatchEvent(new CustomEvent('cms:presentation-refreshed'));
    });
    expect(await order()).toEqual(['Delete','separator','Manage Kanban boards','Workflow settings','separator','Help','Release edit locks']);
    await expect(page.locator('[data-crud-action="create"]')).toHaveText('Add post');
    await expect(page.locator('[data-post-default-kanban-link]')).toHaveText('Kanban board');
    await expect(page.locator('.post-list-operations > summary')).toHaveText('Actions');
    expect(await page.evaluate(()=>window.documentMarker)).toBe('live-toolbar');
    await page.evaluate(()=>{window.toolbarConfig={...window.toolbarConfig,kanbanUrl:null,workflowUrl:null,operations:['delete']};window.dispatchEvent(new CustomEvent('cms:presentation-refreshed'));});
    expect(await order()).toEqual(['Delete']);
    await expect(page.locator('.crud-dropdown__menu hr')).toHaveCount(0);
});

test('editor metadata does not trap an unchanged post but content edits remain protected',async({page})=>{
    const normalize=fs.readFileSync('resources/js/posts/form-snapshot.js','utf8').replace('export function editorContentSnapshot','function editorContentSnapshot');
    const guard=fs.readFileSync('resources/js/common/unsaved.js','utf8').replace('export function guardUnsaved','function guardUnsaved');
    await page.setContent('<form><input name="title" value="Original"><input name="editor" type="hidden"></form>');
    await page.addScriptTag({content:normalize+'\n'+guard+'\nwindow.editorContentSnapshot=editorContentSnapshot;window.guardUnsaved=guardUnsaved;'});
    await page.evaluate(()=>{
        const form=document.querySelector('form');form.elements.editor.value=JSON.stringify({time:1,version:'old',blocks:[{id:'old-id',type:'paragraph',data:{text:'Original'}}]});
        window.guard=window.guardUnsaved(form,async()=>false,{normalizeValue:(name,value)=>name==='editor'?window.editorContentSnapshot(value):value});
        form.elements.editor.value=JSON.stringify({version:'new',time:200,blocks:[{type:'paragraph',id:'generated-id',data:{text:'Original'}}]});
    });
    expect(await page.evaluate(()=>window.guard.dirty())).toBe(false);
    await page.evaluate(()=>{const input=document.querySelector('[name=editor]');const value=JSON.parse(input.value);value.blocks[0].data.text='Changed';input.value=JSON.stringify(value);});
    expect(await page.evaluate(()=>window.guard.dirty())).toBe(true);
});

test('board directory translates column headers live and keeps Open outside the row menu',async({page})=>{
    await page.route('**/kanban/boards/data**',route=>route.fulfill({json:{ok:true,data:{items:[{id:1,name:'Editorial',version:1,updated_at:'2026/11/10',url:'/board/1',edit_url:'/board/1/edit'}],total:1,filtered:1}}}));
    await page.goto('/fixture/boards?locale=fa');
    await expect(page.locator('.crud-row-actions > a')).toHaveAttribute('href','/board/1');
    await expect(page.locator('.crud-row-actions .crud-dropdown__menu a')).toHaveAttribute('href','/board/1/edit');
    await expect(page.locator('[data-board-action=history]')).toHaveCount(0);
    const english=copyFor('en');
    await page.evaluate(copy=>{const registry=document.getElementById('jobic-page-data');const data=JSON.parse(registry.textContent);data.kanbanBoards.copy=copy;registry.textContent=JSON.stringify(data);document.documentElement.lang='en';document.documentElement.dir='ltr';window.dispatchEvent(new CustomEvent('cms:presentation-refreshed'));},english);
    await expect(page.locator('[data-board-column-label]')).toHaveText(['Board name','Last change','Actions']);
    await expect(page.locator('.crud-row-actions > a')).toHaveAttribute('aria-label','Open board');
});

test('board directory drawer applies field filters and resets them without losing selection',async({page})=>{
    const boards=[{id:1,name:'Open board',version:1,url:'/board/1'},{id:2,name:'Restricted board',version:1,url:'/board/2'}];let lastQuery='';
    await page.route('**/kanban/boards/data**',async route=>{
        const url=new URL(route.request().url());lastQuery=url.search;
        const items=url.searchParams.get('audience')==='restricted'?[boards[1]]:boards;
        await new Promise(resolve=>setTimeout(resolve,100));
        await route.fulfill({json:{ok:true,data:{items,total:2,filtered:items.length,stats:[2,1,1,2]}}});
    });
    await page.goto('/fixture/boards');await page.evaluate(()=>window.documentMarker='board-filters');
    await expect(page.locator('[data-board-rows] tr')).toHaveCount(2);
    await page.locator('[data-board-open-filters]').click();await expect(page.locator('[data-board-filter-dialog][open]')).toBeVisible();
    await page.locator('#board-filter-audience').selectOption('restricted',{force:true});
    await page.locator('#board-filter-has-filters').selectOption('1',{force:true});
    await page.locator('#board-filter-states').selectOption(['review'],{force:true});
    await page.locator('#board-filter-roles').selectOption(['1'],{force:true});
    await page.locator('[data-board-filter-form] [type=submit]').click();
    await expect(page.locator('[data-board-rows] tr')).toHaveCount(1);
    await expect(page.locator('[data-board-filter-count]')).toHaveText('4');
    expect(new URLSearchParams(lastQuery).getAll('states[]')).toEqual(['review']);
    expect(new URLSearchParams(lastQuery).getAll('roles[]')).toEqual(['1']);
    await page.locator('[data-board-select="2"]').check();
    await page.locator('[data-board-open-filters]').click();await page.locator('[data-board-filter-form] [type=reset]').click();
    await expect(page.locator('[data-board-rows] tr')).toHaveCount(2);await expect(page.locator('[data-board-filter-count]')).toBeHidden();
    await expect(page.locator('[data-board-select="2"]')).toBeChecked();
    expect(new URLSearchParams(lastQuery).has('audience')).toBe(false);expect(await page.evaluate(()=>window.documentMarker)).toBe('board-filters');
});

for(const locale of ['en','fa'])for(const mobile of [false,true])test(`workspace tools ${locale} ${mobile?'mobile':'desktop'}: search, overview, avatars, grouping, capacities and bulk outcomes`,async({page})=>{
    await page.setViewportSize(mobile?{width:390,height:844}:{width:1440,height:900});
    const {copy}=await fixture(page,locale);const errors=[];page.on('pageerror',error=>errors.push(error.message));
    await page.goto('/admin/posts/post/kanban');await expect(page.locator('.kb-author-avatar')).toHaveCount(5);
    await page.locator('.kb-search input').fill(locale==='fa'?'نمونه 12':'Article 12');await expect(page.locator('.kb-card')).toHaveCount(1);
    await page.getByRole('button',{name:copy.clear_view,exact:true}).click();await expect(page.locator('.kb-card')).toHaveCount(5);
    await page.locator('.kb-group-trigger').click();await page.locator('.kb-group-choices input[value=author]').check();await expect(page.locator('.kb-lane-title')).toContainText('Board author');
    await page.locator('.rf-settings-trigger').click();await page.locator('#kb-density').selectOption('compact',{force:true});await page.locator('#kb-tab-capacity').click();await page.locator('#kb-limit-review').fill('2');
    await page.locator('[form="kanban-settings-form"][type="submit"]').click();await expect(page.locator('.kb-board-settings[open]')).toHaveCount(0);await expect(page.locator('.kb-page')).toHaveClass(/is-compact/);
    await expect(page.locator('[data-kanban-column="review"] .kb-count')).toContainText(locale==='fa'?'۲':'2');
    await page.locator('[data-kanban-card="1"] input[type=checkbox]').check();await page.locator('[data-kanban-card="2"] input[type=checkbox]').check();
    await page.getByRole('button',{name:copy.selection_actions,exact:true}).click();await page.getByRole('button',{name:copy.bulk_move,exact:true}).click();await expect(page.locator('.kb-bulk-row')).toHaveCount(2);
    await page.locator('.kb-bulk-destination select').selectOption('review',{force:true});await page.locator('.kb-bulk button[type=submit]').click();await expect(page.locator('.kb-bulk-results li')).toHaveCount(2);
    await expect(page.locator('.kb-bulk-results li.is-error')).toContainText(copy.locked);
    expect(errors).toEqual([]);
});

test('Reverb invalidations refresh idle boards and open dialogs automatically',async({page})=>{
    const controls=await fixture(page);controls.live(true);let socket;
    await page.route('**/fixture-broadcast-auth',route=>route.fulfill({json:{auth:'fixture-key:fixture-signature'}}));
    await page.routeWebSocket('ws://127.0.0.1:5185/**',ws=>{socket=ws;ws.send(JSON.stringify({event:'pusher:connection_established',data:JSON.stringify({socket_id:'123.456',activity_timeout:30})}));ws.onMessage(message=>{const event=JSON.parse(message);if(event.event==='pusher:subscribe')ws.send(JSON.stringify({event:'pusher_internal:subscription_succeeded',channel:'private-jobic.kanban.post',data:'{}'}));});});
    await page.goto('/admin/posts/post/kanban');await expect(page.locator('.kb-live-status')).toHaveClass(/is-connected/);
    controls.seedTarget(1);socket.send(JSON.stringify({event:'kanban.invalidated',channel:'private-jobic.kanban.post',data:'[]'}));
    await expect(page.locator('[data-kanban-column="review"] .kb-card')).toHaveCount(1);
    await page.locator('.rf-settings-trigger').click();await page.locator('#kb-page-size').selectOption('10',{force:true});controls.seedTarget(2);socket.send(JSON.stringify({event:'kanban.invalidated',channel:'private-jobic.kanban.post',data:'[]'}));
    await expect(page.locator('.kb-refresh-pending')).toHaveCount(0);
    await expect(page.locator('[data-kanban-column="review"] .kb-card')).toHaveCount(2);
    await expect(page.locator('#kb-page-size')).toHaveValue('10');
    await page.locator('.rf-settings-dialog[open] .rf-mapping__close').click();
});

for(const locale of ['en','fa'])for(const width of [320,390,768,1440])test(`one Kanban header and accessible settings ${locale} ${width}`,async({page})=>{
    const {copy}=await fixture(page,locale);await page.setViewportSize({width,height:900});
    await page.goto('/admin/posts/post/kanban');
    await expect(page.locator('.rf-header')).toHaveCount(1);
    for(const selector of ['.kb-tools','.kb-summary','.rf-settings-trigger','.kb-group-trigger'])await expect(page.locator('.rf-header '+selector)).toBeVisible();
    await expect(page.locator('.kb-selection-bar,.kb-update-notice')).toHaveCount(0);
    expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
    await page.locator('.rf-settings-trigger').click();await expect(page.locator('#kb-panel-personal')).toBeVisible();
    if(locale==='fa'&&width===390)await page.screenshot({path:'test-results/kanban-settings-personal-fa-390.png'});
    await page.locator('#kb-tab-personal').focus();await page.keyboard.press('ArrowRight');await expect(page.locator('#kb-panel-capacity')).toBeVisible();
    await expect(page.locator('#kb-tab-capacity')).toBeFocused();
    const submit=page.locator('[form="kanban-settings-form"][type=submit]');await expect(submit).toBeInViewport();
    const panel=page.locator('.kb-board-settings .rf-settings-dialog__body');await panel.evaluate(element=>element.scrollTop=element.scrollHeight);
    await expect(page.locator('#kb-limit-private')).toBeInViewport();await expect(submit).toBeInViewport();await expect(page.locator('.kb-settings-tabs')).toBeInViewport();
    await page.screenshot({path:`test-results/kanban-settings-${locale}-${width}.png`});
});

test('live indicator waits for private subscription and explains authorization errors with retry',async({page})=>{
    const controls=await fixture(page);controls.live(true);let denied=true;
    await page.route('**/fixture-broadcast-auth',async route=>{await new Promise(resolve=>setTimeout(resolve,350));await route.fulfill(denied?{status:403,json:{message:'Forbidden'}}:{json:{auth:'fixture-key:signature'}});});
    await page.routeWebSocket('ws://127.0.0.1:5185/**',ws=>{ws.send(JSON.stringify({event:'pusher:connection_established',data:JSON.stringify({socket_id:'123.456',activity_timeout:30})}));ws.onMessage(message=>{if(JSON.parse(message).event==='pusher:subscribe')ws.send(JSON.stringify({event:'pusher_internal:subscription_succeeded',channel:'private-jobic.kanban.post',data:'{}'}));});});
    await page.goto('/admin/posts/post/kanban');const indicator=page.locator('.kb-live-status');
    await expect(indicator).not.toHaveClass(/is-connected/);await expect(indicator).toHaveClass(/is-error/);await indicator.focus();
    await expect(page.locator('#kb-live-tooltip')).toBeVisible();await expect(page.locator('#kb-live-tooltip')).toContainText(controls.copy.live_authorization);
    denied=false;await indicator.click();await expect(indicator).toHaveClass(/is-connected/);await expect(indicator).toHaveAttribute('aria-label',controls.copy.live_connected);
});

for(const locale of ['en','fa'])test(`Kanban compact dialogs, quick filter tooltips and column collapse ${locale}`,async({page})=>{
    const {copy}=await fixture(page,locale);await page.setViewportSize({width:390,height:844});
    await page.goto('/admin/posts/post/kanban');
    await expect(page.locator('.kb-summary button')).toHaveCount(3);
    for(const [index,key] of ['total','scheduled','stale'].entries())await expect(page.locator('.kb-summary button').nth(index)).toHaveAttribute('data-tooltip',copy[`summary_${key}`]);
    for(const selector of ['.rf-header__back','.kb-tool-button[aria-label="'+copy.filters+'"]','.kb-group-trigger','.rf-settings-trigger'])await expect(page.locator('.kb-header '+selector)).toHaveAttribute('data-tooltip',/.+/);
    await page.locator('.kb-group-trigger').hover();expect(await page.locator('.kb-group-trigger').evaluate(element=>getComputedStyle(element,'::after').visibility)).toBe('visible');
    const draft=page.locator('[data-kanban-column="draft"]');
    await draft.locator('.kb-collapse-button').click();await expect(draft).toHaveClass(/is-collapsed/);await expect(draft.locator('.kb-column-body')).toBeHidden();
    await expect(draft.locator('.kb-collapse-button svg path')).toHaveAttribute('d',/M10 12H4/);
    await draft.locator('.kb-collapse-button').click();await expect(draft.locator('.kb-card')).toHaveCount(5);
    await expect(draft.locator('.kb-collapse-button svg path')).toHaveAttribute('d',/M12 5v14/);
    await draft.locator('[data-kanban-card="1"] .kb-card-move').click();await expect(draft.locator('[data-kanban-card="1"] .kb-move-head')).toBeVisible();await expect(draft.locator('[data-kanban-card="1"] .kb-move-menu')).not.toContainText(copy.cancel);await draft.locator('[data-kanban-card="1"] .kb-move-close').click();
    await page.locator('.kb-group-trigger').click();await expect(page.locator('.kb-group-choices .kb-choice')).toHaveCount(2);await page.locator('.kb-group-choices input[value=author]').check();
    await page.locator('.kb-tool-button[aria-label="'+copy.filters+'"]').click();await page.locator('.kb-add-filter').click();await expect(page.locator('.kb-view-filter')).toHaveCount(1);
    await expect(page.locator('.kb-filter-value .select2-container')).toBeVisible();
    await page.locator('.kb-filter-value .select2-selection--multiple').click();await page.locator('.kb-board-filters .select2-results__option').getByText('Board author').click();
    await expect(page.locator('.kb-filter-value .select2-selection__choice')).toHaveCount(1);
    expect(await page.locator('.kb-filter-value .select2-selection--multiple').evaluate(element=>element.scrollWidth<=element.clientWidth)).toBe(true);
    if(locale==='fa')await page.screenshot({path:'test-results/kanban-filters-fa-390.png'});
    await expect(page.locator('.kb-board-filters .rf-settings-dialog__foot')).not.toContainText(copy.cancel);
});

for(const locale of ['en','fa'])for(const mobile of [false,true])test(`Kanban member avatars and paged dialog ${locale} ${mobile?'mobile':'desktop'}`,async({page})=>{
    await page.setViewportSize(mobile?{width:390,height:844}:{width:1440,height:900});
    const controls=await fixture(page,locale);const {copy}=controls;controls.memberCount(23);
    await page.goto('/admin/posts/post/kanban');
    await expect(page.locator('.kb-member-avatar')).toHaveCount(4);
    await expect(page.locator('.kb-member-more')).toBeVisible();
    await page.locator('.kb-member-avatar').first().hover();
    expect(await page.locator('.kb-member-avatar').first().evaluate(element=>getComputedStyle(element,'::after').visibility)).toBe('visible');
    await page.locator('.kb-member-more').click();
    await expect(page.locator('.kb-members-dialog[open]')).toBeVisible();
    await expect(page.locator('.kb-members-row')).toHaveCount(20);
    await expect(page.locator('.kb-members-row').first()).toContainText('Editorial');
    await page.locator('.kb-members-load-more').click();
    await expect(page.locator('.kb-members-row')).toHaveCount(23);
    await page.locator('.kb-members-search input').fill('member 21');
    await expect(page.locator('.kb-members-row')).toHaveCount(1);
    await expect(page.locator('.kb-members-row')).toContainText('Board member 21');
    await page.locator('.kb-members-search input').fill('');
    await page.locator('.kb-members-role-menu summary').click();
    await page.locator('.kb-members-role-options button').filter({hasText:'Review'}).click();
    await expect(page.locator('.kb-members-row')).toHaveCount(11);
    await page.locator('.kb-members-sort button').filter({hasText:copy.member_sort_cards}).click();
    await expect(page.locator('.kb-members-row').first().locator('.kb-members-card-count strong')).toHaveText(new Intl.NumberFormat(locale).format(2));
    await page.locator('.kb-members-dialog-head button').click();
    await expect(page.locator('.kb-member-more')).toBeFocused();
    expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
    if(locale==='fa'&&mobile)await page.screenshot({path:'test-results/kanban-members-fa-mobile.png'});
});

test('member card action filters assignments and can be cleared without navigation',async({page})=>{
    await fixture(page,'en');
    await page.goto('/admin/posts/post/kanban');
    const documentId=await page.evaluate(()=>performance.timeOrigin);
    await page.locator('.kb-member-more').click();
    const assigned=page.waitForRequest(request=>request.url().includes('assignee_id=1'));
    await page.locator('.kb-members-row .kb-members-card-count').first().click();
    await assigned;
    await expect(page.locator('.kb-assignee-filter')).toBeVisible();
    await page.locator('.kb-assignee-filter').click();
    await expect(page.locator('.kb-assignee-filter')).toHaveCount(0);
    expect(await page.evaluate(()=>performance.timeOrigin)).toBe(documentId);
});

for(const locale of ['en','fa'])for(const mobile of [false,true])test(`Kanban planning tools ${locale} ${mobile?'mobile':'desktop'}`,async({page})=>{
    await page.setViewportSize(mobile?{width:390,height:844}:{width:1440,height:900});
    const {copy}=await fixture(page,locale);
    await page.goto('/admin/posts/post/kanban');
    const documentId=await page.evaluate(()=>performance.timeOrigin);
    await page.getByRole('button',{name:copy.my_work}).click();
    await expect(page.getByRole('button',{name:copy.my_work})).toHaveAttribute('aria-pressed','true');
    await page.getByRole('button',{name:copy.calendar}).click();
    await expect(page.locator('.kb-calendar-day')).toHaveCount(1);
    await expect(page.locator('.kb-calendar-day button')).toContainText('Article 1');
    await expect(page.locator('.kb-calendar-day.is-today')).toHaveCount(1);
    await page.locator('.kb-calendar-view-switch button').last().click();
    await expect(page.locator('.kb-calendar-table tbody tr')).toHaveCount(1);
    await expect(page.locator('.kb-calendar-table')).toContainText('Article 1');
    await page.locator('.kb-calendar-view-switch button').first().click();
    if(locale==='en'&&!mobile)await page.screenshot({path:'test-results/kanban-calendar-redesign.png'});
    await page.locator('.kb-calendar-head-month button').last().click();
    await expect(page.locator('.kb-calendar-head-month strong')).toContainText('12');
    await page.locator('.kb-calendar-dialog .rf-mapping__close').click();
    await page.getByRole('button',{name:copy.insights}).click();
    await expect(page.locator('.kb-insights-list')).toContainText('Draft');
    if(locale==='en'&&!mobile)await page.screenshot({path:'test-results/kanban-insights-redesign.png'});
    await page.locator('.kb-feature-dialog .rf-mapping__close').last().click();
    await page.locator('.kb-card-title').first().click();
    await page.locator('.kb-work-panel summary').click();
    if(locale==='en'&&!mobile)await page.screenshot({path:'test-results/kanban-card-work-redesign.png'});
    await page.locator('#kb-assignee-search').fill('Board member 1');
    await page.locator('.kb-work-options button').filter({hasText:'Board member 1'}).click();
    await page.locator('#kb-work-due').click();
    await page.locator('#kb-work-date-input').fill('2026/11/20 12:00');
    await page.getByRole('button',{name:copy.apply_deadline}).click();
    const dependencyTitle=locale==='fa'?'محتوای نمونه 2':'Article 2';
    await page.locator('#kb-dependency-search').fill(dependencyTitle);
    await page.locator('.kb-dependency-results button').filter({hasText:dependencyTitle}).click();
    await page.locator('.kb-work-panel button[type=submit]').click();
    await expect(page.locator('.kb-work-panel')).toContainText('Board member 1');
    if(mobile)expect(await page.evaluate(()=>document.documentElement.scrollWidth<=innerWidth)).toBe(true);
    expect(await page.evaluate(()=>performance.timeOrigin)).toBe(documentId);
});

for(const locale of ['en','fa'])test(`editorial deadline opens the shared ${locale} calendar inside card details`,async({page})=>{
    const {copy}=await fixture(page,locale,true);
    await page.goto('/admin/posts/post/kanban');
    await page.locator('.kb-card-title').first().click();
    await page.locator('.kb-work-panel summary').click();
    await page.getByRole('button',{name:copy.choose_deadline}).click();
    await expect(page.locator('.kb-work-date-dialog[open]')).toBeVisible();
    await expect(page.locator('.kb-work-date-picker #ui-datepicker-div')).toBeVisible();
    await expect(page.locator('.kb-work-date-picker .ui-datepicker-month')).toBeVisible();
    await expect(page.locator('.kb-work-date-picker .ui-datepicker-year')).toBeVisible();
    const month=page.locator('.kb-work-date-picker .ui-datepicker-month');
    const nextMonth=await month.evaluate(select=>select.options[select.selectedIndex===0?1:0]?.value);
    await month.selectOption(nextMonth);
    await expect(page.locator('.kb-work-date-picker .ui-datepicker-month')).toHaveValue(nextMonth);
    await page.locator('#kb-work-date-input').fill(locale==='fa'?'۱۴۰۵/۰۱/۰۱ ۱۲:۳۴':'2026/03/21 12:34');
    await page.getByRole('button',{name:copy.apply_deadline}).click();
    await expect(page.locator('.kb-work-date-dialog')).not.toBeVisible();
    await expect(page.locator('#kb-work-due')).toContainText(locale==='fa'?'۱۴۰۵/۰۱/۰۱':'2026/03/21');
});

test('card selection appears on hover and stays visible after selection',async({page})=>{
    await fixture(page,'en');
    await page.goto('/admin/posts/post/kanban');
    const card=page.locator('.kb-card').first();
    const checkbox=card.locator('.kb-card-checkbox');
    await expect(checkbox).toHaveCSS('opacity','0');
    await card.hover();
    await expect(checkbox).toHaveCSS('opacity','1');
    await checkbox.check();
    await page.locator('.kb-header').hover();
    await expect(checkbox).toHaveCSS('opacity','1');
    await expect(card).toHaveClass(/is-selected/);
});

test('Kanban planning controls translate in the current document',async({page})=>{
    await fixture(page,'en');
    await page.goto('/admin/posts/post/kanban');
    const persian=copyFor('fa');
    await page.evaluate(copy=>{const element=document.getElementById('jobic-page-data');const data=JSON.parse(element.textContent);data.kanban.copy=copy;element.textContent=JSON.stringify(data);document.documentElement.lang='fa';document.documentElement.dir='rtl';window.dispatchEvent(new CustomEvent('cms:presentation-refreshed',{detail:{locale:'fa'}}));},persian);
    await expect(page.getByRole('button',{name:persian.my_work})).toBeVisible();
    await expect(page.locator('.kb-save-view,.kb-saved-view-list')).toHaveCount(0);
    await expect(page.getByRole('button',{name:persian.calendar})).toBeVisible();
    await expect(page.getByRole('button',{name:persian.insights})).toBeVisible();
});
