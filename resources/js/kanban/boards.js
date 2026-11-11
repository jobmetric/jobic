import {pageData, loadingButton} from '../common/page';
import {sendSettings} from '../admin/settings';
import {announce} from '../common/toast';
import {enhanceSelects} from '../common/selects';
import {showDialog, escape, icon} from '../languages/shared';
import '../../css/crud.css';
import '../../css/crud-fields.css';
import '../../css/kanban-boards.css';

const config=pageData().kanbanBoards;
const copy=new Proxy({}, {get:(_,key)=>pageData().kanbanBoards.copy[key]});
const root=document.querySelector('[data-board-directory]');
const find=selector=>root.querySelector(selector);
const management=config.manage;
let generation=0, pending=false, table, rows=[];
let appliedFilters=new URLSearchParams();
let restoreFilter=null;
const filterDialog=find('[data-board-filter-dialog]') || document.querySelector('[data-board-filter-dialog]');
const filterForm=document.querySelector('[data-board-filter-form]');
const selected=new Map();
const confirm=description=>showDialog({title:copy.boards,description,confirm:copy.confirm || copy.delete});
const node=(tag,text,className)=>{const element=document.createElement(tag);if(text!=null)element.textContent=text;if(className)element.className=className;return element;};
function load(){table.ajax.reload(null,false);}
function selection(){
    root.querySelectorAll('[data-board-select]').forEach(control=>{control.checked=selected.has(Number(control.dataset.boardSelect));});
    const all=find('[data-board-select-all]');const count=rows.filter(row=>selected.has(row.id)).length;
    if(all){all.checked=rows.length>0&&count===rows.length;all.indeterminate=count>0&&count<rows.length;}
    const review=find('[data-board-selected]');review.hidden=!selected.size;review.textContent=`${copy.selected} (${selected.size})`;
    find('[data-board-bulk]').hidden=!management||!selected.size;
}
async function read(request,callback){
    const current=++generation;
    const loading=find('[data-board-loading]');loading.hidden=false;find('[data-board-error]').hidden=true;find('[data-board-retry]').hidden=true;
    try {
        const url=new URL(config.dataUrl,location.origin);
        url.searchParams.set('start',request.start);url.searchParams.set('length',request.length);url.searchParams.set('search',find('[data-board-search]').value);
        appliedFilters.forEach((value,key)=>url.searchParams.append(key,value));
        url.searchParams.set('sort',request.order[0]?.column===2?'updated_at':'name');url.searchParams.set('direction',request.order[0]?.dir||'asc');
        const response=await fetch(url,{headers:{Accept:'application/json'},credentials:'same-origin'});const result=await response.json();
        if(!response.ok||!result.ok)throw new Error(result.message||copy.failed);
        if(current!==generation)return;
        root.querySelectorAll('[data-board-stat]').forEach((control,index)=>{control.textContent=new Intl.NumberFormat(document.documentElement.lang).format(result.data.stats?.[index] ?? result.data.total);});
        rows=result.data.items;rows.forEach(row=>{if(selected.has(row.id))selected.set(row.id,{id:row.id,name:row.name,version:row.version});});
        callback({draw:request.draw,data:rows,recordsTotal:result.data.total,recordsFiltered:result.data.filtered});
        find('[data-table-empty]').hidden=rows.length>0;
    }catch(error){if(current===generation){rows=[];callback({draw:request.draw,data:[],recordsTotal:0,recordsFiltered:0});const errorNode=find('[data-board-error]');errorNode.textContent=error.message;errorNode.hidden=false;find('[data-board-retry]').hidden=false;}}
    finally{if(current===generation){loading.hidden=true;selection();if(restoreFilter){restoreFilter();restoreFilter=null;filterForm.querySelector('[type=submit]').textContent=copy.apply_filters;}}}
}
async function remove(board,submitter){
    if(pending||!await confirm(copy.confirm_delete))return;
    pending=true;const restore=loadingButton(submitter);
    try{const data=new FormData();data.set('_method','DELETE');data.set('version',board.version);const result=await sendSettings(`${config.baseUrl}/boards/${board.id}`,data);announce(result.message);await load();}
    catch(error){announce(error.message,true);}finally{pending=false;restore();}
}
let searchTimer;find('[data-board-search]').oninput=()=>{clearTimeout(searchTimer);searchTimer=setTimeout(()=>table.page('first').draw(),250);};
find('[data-board-retry]').onclick=load;
find('[data-board-open-filters]').onclick=()=>{filterDialog.showModal();enhanceSelects(filterDialog);};
document.querySelector('[data-board-close-filters]').onclick=()=>filterDialog.close();
function filterCount(){
    const count=new Set([...appliedFilters.keys()].map(key=>key.replace('[]',''))).size;
    const badge=find('[data-board-filter-count]');badge.hidden=count===0;badge.textContent=new Intl.NumberFormat(document.documentElement.lang).format(count);
}
filterForm.onsubmit=event=>{
    event.preventDefault();if(restoreFilter)return;
    appliedFilters=new URLSearchParams();
    new FormData(filterForm).forEach((value,key)=>{if(value!=='')appliedFilters.append(key,value);});
    filterCount();restoreFilter=loadingButton(event.submitter);filterDialog.close();table.page('first').draw();
};
filterForm.onreset=()=>{
    if(restoreFilter)return;
    appliedFilters=new URLSearchParams();filterCount();
    requestAnimationFrame(()=>{if(window.jQuery?.fn.select2)filterForm.querySelectorAll('select').forEach(select=>window.jQuery(select).trigger('change.select2'));});
    table.page('first').draw();
};
find('[data-board-help]').onclick=()=>{
    const decision=showDialog({title:copy.help,description:copy.help_intro});const body=document.querySelector('[data-dialog-body]');
    for(const [index,key] of ['open','access','columns','filters','save'].entries()){
        const section=node('details',null,'crud-help-section');const heading=node('summary',copy[`help_${key}_title`]);section.append(heading,node('p',copy[`help_${key}_body`]));section.open=index===0;body.append(section);
    }
    return decision;
};
find('[data-board-selected]').onclick=()=>showDialog({title:copy.selected,description:copy.boards,items:[...selected.values()],remove:id=>{selected.delete(id);selection();}});
find('[data-board-select-all]').onchange=event=>{rows.forEach(row=>{if(event.target.checked)selected.set(row.id,{id:row.id,name:row.name,version:row.version});else selected.delete(row.id);});selection();};
find('[data-board-rows]').addEventListener('change',event=>{const control=event.target.closest('[data-board-select]');if(!control)return;const id=Number(control.dataset.boardSelect);const row=rows.find(row=>row.id===id);if(control.checked)selected.set(id,{id,name:row.name,version:row.version});else selected.delete(id);selection();});
find('[data-board-rows]').addEventListener('click',event=>{const control=event.target.closest('[data-board-action]');if(!control||pending)return;const row=rows.find(row=>row.id===Number(control.dataset.id));if(!row)return;
    if(control.dataset.boardAction==='delete')remove(row,control);
});
find('[data-board-bulk]').onclick=async event=>{
    const submitter=event.currentTarget;
    if(pending||!selected.size||!await showDialog({title:copy.delete,description:copy.confirm_delete,items:[...selected.values()],confirm:copy.delete}))return;
    pending=true;const restore=loadingButton(submitter);
    try{const data=new FormData();data.set('items',JSON.stringify([...selected.values()].map(({id,version})=>({id,version}))));const result=await sendSettings(`${config.baseUrl}/boards/bulk`,data);selected.clear();announce(result.message);load();}
    catch(error){announce(error.message,true);}finally{pending=false;restore();}
};
const $=window.jQuery;
table=$('#board-table').DataTable({serverSide:true,autoWidth:false,processing:false,searching:false,order:[[1,'asc']],pageLength:20,lengthMenu:[20,50,100],dom:'rt<"crud-table-footer"lip>',
    language:{emptyTable:'',zeroRecords:'',info:'',infoEmpty:'',lengthMenu:`${copy.page_size_label} _MENU_`,paginate:{previous:copy.previous,next:copy.next}},
    ajax:read,columns:[
        {data:'id',visible:management,orderable:false,className:'board-column-selection',width:'36px',render:id=>`<input type="checkbox" data-board-select="${Number(id)}" aria-label="${escape(copy.selected)}">`},
        {data:'name',className:'board-column-name',width:'65%',render:$.fn.dataTable.render.text()},
        {data:'updated_at',className:'board-column-updated',width:'20%',defaultContent:'—',render:$.fn.dataTable.render.text()},
        {data:'id',orderable:false,className:'board-column-actions',width:'112px',render:(id,kind,row)=>{
            const open=row.url?`<a class="crud-icon-button" href="${escape(row.url)}" aria-label="${escape(copy.view)}" data-tooltip="${escape(copy.view)}">${icon('kanban')}</a>`:'';
            if(!management)return `<div class="crud-row-actions">${open}</div>`;
            return `<div class="crud-row-actions">${open}<details class="crud-dropdown"><summary class="crud-icon-button" aria-label="${escape(copy.actions)}">${icon('more-horizontal')}</summary><div class="crud-dropdown__menu"><a href="${escape(row.edit_url || `${config.baseUrl}/boards/${id}/edit`)}">${icon('edit')}${escape(copy.edit)}</a><button type="button" data-board-action="delete" data-id="${Number(id)}">${icon('trash')}${escape(copy.delete)}</button></div></details></div>`;
        }},
    ],drawCallback(){const controls=document.querySelector('#board-table_wrapper .crud-table-footer');if(controls)find('[data-board-footer]').append(controls);enhanceSelects(find('[data-board-footer]'));selection();}
});
enhanceSelects(root);

window.addEventListener('cms:presentation-refreshed',()=>{
    filterCount();
    const headings=[copy.select_all,copy.name,copy.updated_at,copy.actions];
    table.columns().every(function(index){const header=this.header();if(index===0)header.querySelector('input')?.setAttribute('aria-label',copy.select_all);else{const label=header.querySelector('[data-board-column-label]');if(label)label.textContent=headings[index];}});
    const language=table.settings()[0].oLanguage;
    language.sLengthMenu=`${copy.page_size_label} _MENU_`;
    Object.assign(language.oPaginate,{sPrevious:copy.previous,sNext:copy.next});
    const label=document.querySelector('#board-table_length label');
    if(label){let first=true;[...label.childNodes].filter(node=>node.nodeType===Node.TEXT_NODE).forEach(node=>{node.textContent=first?`${copy.page_size_label} `:'';first=false;});}
    table.ajax.reload(null,false);
});
