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
 return `<!doctype html><html lang="en" dir="ltr" data-theme="light"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="csrf-token" content="isolated-browser-fixture">${[...css].map(file=>`<link rel="stylesheet" href="/vuero/build/${file}">`).join('')}<link rel="stylesheet" href="/assets/vendor/domi/plugins/select2/dist/css/select2.min.css"><script src="/assets/vendor/domi/plugins/jquery/jquery.min.js"></script><script src="/assets/vendor/domi/plugins/select2/dist/js/select2.full.min.js"></script></head><body class="cms-page ${entryKey.includes('/kanban/')?'cms-page--kanban':'cms-page--workflow'}">${body}<script type="application/json" id="jobic-page-data">${JSON.stringify(registry)}</script><script type="module" src="/vuero/build/${manifest[entryKey].file}"></script></body></html>`;
}
const boardHtml=shell('resources/js/kanban/index.js','<div data-kanban-root></div>',data);
const workflowHtml=shell('resources/js/workflow/index.js','<div data-workflow-designer></div>',workflowData);
http.createServer((request,response)=>{
 const url=new URL(request.url,'http://127.0.0.1:5184');
 if(url.pathname==='/admin/posts/post/kanban'||url.pathname==='/admin/posts/post/workflow'){
   response.setHeader('Content-Type','text/html; charset=utf-8');response.end(url.pathname.endsWith('/kanban')?boardHtml:workflowHtml);return;
 }
 const file=path.resolve(publicRoot,'.'+decodeURIComponent(url.pathname));
 if(!file.startsWith(publicRoot+path.sep)||!fs.existsSync(file)||!fs.statSync(file).isFile()){response.writeHead(404);response.end();return;}
 const mime={'.js':'text/javascript','.css':'text/css','.svg':'image/svg+xml','.woff2':'font/woff2','.woff':'font/woff','.png':'image/png'}[path.extname(file)]||'application/octet-stream';
 response.setHeader('Content-Type',mime);fs.createReadStream(file).pipe(response);
}).listen(5184,'127.0.0.1');
