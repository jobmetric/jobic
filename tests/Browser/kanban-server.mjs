// Isolated UI fixtures: actual compiled workspaces and public assets, never application data.
import http from 'node:http';
import fs from 'node:fs';
import path from 'node:path';
const root=process.cwd();
const publicRoot=path.join(root,'public');
const manifest=JSON.parse(fs.readFileSync(path.join(publicRoot,'vuero/build/manifest.json'),'utf8'));
const flatCopy=file=>Object.fromEntries([...fs.readFileSync(path.join(root,file),'utf8').matchAll(/'([^']+)'\s*=>\s*'((?:\\.|[^'])*)'/g)].map(m=>[m[1],m[2].replaceAll("\\'","'")]));
const copy=flatCopy('lang/en/kanban.php');
const base={ui:{loading:'Loading…'},selects:{no_results:'No results',searching:'Searching…'}};
const data={...base,kanban:{copy,pageSize:5,editable:true,typeLabel:'Posts',dataUrl:'/admin/posts/post/kanban/data',commandUrl:'/admin/posts/post/kanban/command',detailUrl:'/admin/posts/post/kanban/__ID__',backUrl:'/admin/posts/post',locales:[{code:'en',name:'English'},{code:'fa',name:'فارسی'}]}};
const workflowData={...base,workflow:{copy:{...flatCopy('lang/en/workflow_canvas.php'),...flatCopy('lang/en/workflow.php')},initial:{graph:{states:[{id:'draft',name:'Draft',status:'draft',content_status:'editable',is_start:true,is_terminal:false}],transitions:[]},drivers:[],version:0,enabled:true,state:'draft',definition_id:1},viewRoles:[],simulationRoles:[],simulationLocales:[],typeLabel:'Posts',editable:true,previewOnly:false,url:'/fixture-workflow-command',backUrl:'/admin/posts/post',statusChoices:{editable:'Editable'},appearance:{theme:'light'}}};
function shell(entryKey,body,registry) {
 const css=new Set([manifest['resources/css/vuero/app.css'].file]);const visited=new Set();
 function collect(key){if(visited.has(key))return;visited.add(key);const item=manifest[key];for(const file of item?.css||[])css.add(file);for(const dependency of item?.imports||[])collect(dependency);}
 collect(entryKey);
 return `<!doctype html><html lang="en" dir="ltr" data-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="isolated-browser-fixture">${[...css].map(file=>`<link rel="stylesheet" href="/vuero/build/${file}">`).join('')}<link rel="stylesheet" href="/assets/vendor/domi/plugins/select2/dist/css/select2.min.css"><script src="/assets/vendor/domi/plugins/jquery/jquery.min.js"></script><script src="/assets/vendor/domi/plugins/select2/dist/js/select2.full.min.js"></script></head><body class="cms-page ${(entryKey.endsWith('/boards.js')||entryKey.endsWith('/board-form.js'))?'cms-page--admin cms-page--default':entryKey.includes('/kanban/')?'cms-page--kanban':'cms-page--workflow'}">${body}<script type="application/json" id="jobic-page-data">${JSON.stringify(registry)}</script><script type="module" src="/vuero/build/${manifest[entryKey].file}"></script></body></html>`;
}
function directoryFixture(copy=flatCopy('lang/en/kanban.php'),form=false){
 let html=fs.readFileSync(path.join(root,form?'resources/views/posts/kanban-board-form.blade.php':'resources/views/posts/kanban-boards.blade.php'),'utf8');
 html=html.slice(html.indexOf(form?'<form':'<section'),html.indexOf('@endsection'));
 html=html.replace(/@foreach\(\['states','roles','users'\][\s\S]*?@endforeach\s*<\/x-form.select><\/x-form.field>\s*@endif\s*@endforeach/,['states','roles','users'].map(field=>`<label class="crud-field">${copy[field]}<select name="${field}[]" id="board-filter-${field}" multiple>${(directoryData.kanbanBoards[field]||[]).map(option=>`<option value="${option.id}">${option.name}</option>`).join('')}</select></label>`).join(''));
 html=html.replace(/@foreach\(\['details_tab'[^\n]*@endforeach/,['details_tab','columns_tab','filters_tab'].map((key,index)=>`<button type="button" role="tab" data-board-tab="${index}" aria-selected="${index===0}">${copy[key]}</button>`).join(''));
 html=html.replaceAll(/@foreach\(\$choices[^<]*<option[\s\S]*?<\/option>@endforeach/g,'');
 html=html.replaceAll(/@if\([^\n]*?\) @method\('PUT'\) @endif/g,'');
 html=html.replaceAll(/@if\(app\([^\n]*?\)\)<a/g,'<a').replaceAll('@endif','').replaceAll('@csrf','');
 html=html.replaceAll(/action="{{[^}]*}}"/g,'action="/admin/posts/post/kanban/boards"');
 html=html.replaceAll(/{{ __\('kanban.'\.\(\$record[^}]*}}/g,copy.create_board);
 html=html.replaceAll(/:value="[^"]*"/g,'');

 html=html.replaceAll(/@foreach\(\['total_boards'[^\n]*@endforeach/g,`<article><span>${copy.total_boards}</span><strong data-board-stat>—</strong></article>`);
 html=html.replaceAll(/@cmsHook\([^\n]+/g,'');
 html=html.replaceAll(/<x-vuero.icon[^>]*\/>/g,'');
 html=html.replaceAll(/<x-form.field([^>]*) :label="__\('kanban.([^']+)'\)"[^>]*>/g,(_,attributes,key)=>`<label class="crud-field${attributes.includes('board-name-field')?' board-name-field':''}">${copy[key]||key}`);
 html=html.replaceAll('</x-form.field>','</label>');
 html=html.replaceAll(/<x-form.input([^>]*)\/>/g,'<input $1>');
 html=html.replaceAll(/<x-form.select([^>]*)\/>/g,'<select $1></select>').replaceAll(/<x-form.select([^>]*)>/g,'<select $1>').replaceAll('</x-form.select>','</select>');
 html=html.replaceAll(/{{ __\('kanban.([^']+)'\) }}/g,(_,key)=>copy[key]||key);
 html=html.replaceAll(/{{ route\('admin.posts.kanban.boards.create'[^}]*}}/g,'/admin/posts/post/kanban/boards/create');
 html=html.replaceAll(/{{ route\('admin.posts.kanban.default'[^}]*}}/g,'/admin/posts/post/kanban/default');
 html=html.replaceAll(/{{ route\([^}]*}}/g,'/admin/posts/post');
 html=html.replaceAll(/@include\('common.crud.empty'[^\n]*/g,'<p data-table-empty hidden>No boards</p>');
 let dialog=fs.readFileSync(path.join(root,'resources/views/common/crud/dialog.blade.php'),'utf8');
 dialog=dialog.replaceAll(/<x-vuero.icon[^>]*\/>/g,'').replaceAll(/{{ __\('languages.([^']+)'\) }}/g,(_,key)=>key);
 html=html.replace("@include('common.crud.dialog')",dialog);
 return html;
}
const directoryData={...base,languages:flatCopy('lang/en/languages.php'),kanbanBoards:{copy,manage:true,baseUrl:'/admin/posts/post/kanban',dataUrl:'/admin/posts/post/kanban/boards/data',saveUrl:'/admin/posts/post/kanban/boards',createUrl:'/admin/posts/post/kanban/boards/create',defaultUrl:'/admin/posts/post/kanban/default',backUrl:'/admin/posts/post',states:[{id:'draft',name:'Draft'},{id:'review',name:'Review'}],roles:[{id:1,name:'Editorial'}],users:[{id:7,name:'Editor'}],drivers:{author:{label:copy.authors,fields:[{key:'ids',label:copy.authors,multiple:true,options:[{id:7,name:'Editor'}]}]},taxonomy:{label:copy.taxonomy,fields:[{key:'collection',label:copy.taxonomy,options:[{id:'categories',name:'Categories'}]},{key:'ids',label:copy.terms,multiple:true,options:[{id:4,name:'News'}]}]}}}};
const directoryHtml=shell('resources/js/kanban/boards.js',directoryFixture(),directoryData);
const boardHtml=shell('resources/js/kanban/index.js','<div data-kanban-root></div>',data);
const workflowHtml=shell('resources/js/workflow/index.js','<div data-workflow-designer></div>',workflowData);
http.createServer((request,response)=>{
 const url=new URL(request.url,'http://127.0.0.1:5184');
 if(url.pathname==='/fixture/boards'||url.pathname==='/admin/posts/post/kanban/boards/create'||/\/kanban\/boards\/\d+\/edit$/.test(url.pathname)){
   response.setHeader('Content-Type','text/html; charset=utf-8');
   const locale=url.searchParams.get('locale')==='fa'?'fa':'en';
   const localized={...directoryData,languages:flatCopy(`lang/${locale}/languages.php`),kanbanBoards:{...directoryData.kanbanBoards,copy:flatCopy(`lang/${locale}/kanban.php`)}};
   const labels=localized.kanbanBoards.copy;
   localized.kanbanBoards.drivers=structuredClone(directoryData.kanbanBoards.drivers);
   localized.kanbanBoards.drivers.author.label=labels.authors;localized.kanbanBoards.drivers.author.fields[0].label=labels.authors;
   localized.kanbanBoards.drivers.taxonomy.label=labels.taxonomy;localized.kanbanBoards.drivers.taxonomy.fields[0].label=labels.taxonomy;localized.kanbanBoards.drivers.taxonomy.fields[1].label=labels.terms;
   const form=url.pathname!=='/fixture/boards';
   if(form)localized.kanbanBoardForm={...localized.kanbanBoards,indexUrl:`/fixture/boards?locale=${locale}`,board:{id:null,name:'',version:0,roles:[],users:[],states:[],filters:[]}};
   let html=shell(form?'resources/js/kanban/board-form.js':'resources/js/kanban/boards.js',directoryFixture(labels,form),localized);
   html=html.replaceAll('href="/admin/posts/post/kanban/boards/create"',`href="/admin/posts/post/kanban/boards/create?locale=${locale}"`);
   html=html.replace('</head>','<link rel="stylesheet" href="/assets/vendor/domi/plugins/datatables/datatables.bundle.min.css"><script src="/assets/vendor/domi/plugins/datatables/datatables.bundle.min.js"></script></head>').replace('lang="en" dir="ltr"',`lang="${locale}" dir="${locale==='fa'?'rtl':'ltr'}"`);
   response.end(html);return;
 }
 if(url.pathname==='/admin/posts/post/kanban'||url.pathname==='/admin/posts/post/workflow'){
   response.setHeader('Content-Type','text/html; charset=utf-8');response.end(url.pathname.endsWith('/kanban')?boardHtml:workflowHtml);return;
 }
 const file=path.resolve(publicRoot,'.'+decodeURIComponent(url.pathname));
 if(!file.startsWith(publicRoot+path.sep)||!fs.existsSync(file)||!fs.statSync(file).isFile()){response.writeHead(404);response.end();return;}
 const mime={'.js':'text/javascript','.css':'text/css','.svg':'image/svg+xml','.woff2':'font/woff2','.woff':'font/woff','.png':'image/png'}[path.extname(file)]||'application/octet-stream';
 response.setHeader('Content-Type',mime);fs.createReadStream(file).pipe(response);
}).listen(5184,'127.0.0.1');
