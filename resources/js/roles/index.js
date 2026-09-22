import { toolbar, copy, escape, icon, showDialog } from './shared';
import { loadingButton, pageData } from '../common/page';
import { sendSettings } from '../admin/settings';
import { announce } from '../common/toast';
import { enhanceSelects } from '../common/selects';

const $ = window.jQuery; const routes = pageData().roleRoutes; const canManage = Boolean(pageData().roleAccess?.manage); const selected = new Map(); const card = document.querySelector('[data-table-card]'); const empty = document.querySelector('[data-table-empty]'); const footer = document.querySelector('[data-table-footer]'); const filterDialog = document.querySelector('[data-filter-dialog]'); const filterForm = document.querySelector('[data-filter-form]'); const selectAll = document.querySelector('[data-select-all]'); const selectionButton = document.querySelector('[data-show-selected]'); let pending = false, loading = false, rows = [], controller, timer, restoreFilter;
selectAll.hidden = !canManage;
toolbar('list');

function lock() { const busy = pending || loading; card.setAttribute('aria-busy', String(busy)); document.querySelector('[data-table-loading]').hidden = !busy; document.querySelector('.crud-table-scroll').inert = busy; document.querySelectorAll('[data-list-controls]').forEach(node => { node.inert = busy; }); filterForm.inert = busy; }
function selection() { selectionButton.hidden = !selected.size; selectionButton.innerHTML = `${icon('check')}${escape(copy().selected.replace(':count', selected.size))}`; const checks = [...document.querySelectorAll('[data-row-id]')]; checks.forEach(node => { node.checked = selected.has(Number(node.dataset.rowId)); }); selectAll.checked = checks.length > 0 && checks.every(node => node.checked); selectAll.indeterminate = checks.some(node => node.checked) && !selectAll.checked; }
function select(id) { if (selected.size >= 100 && !selected.has(id)) return announce(copy().selection_limit, true); const row = rows.find(item => item.id === id); if (row) selected.set(id, {id, name: row.name}); }
function rolePath(value, fallback) { return String(value || fallback || '').split(' > ').map(part => `<span>${escape(part)}</span>`).join(`<i class="role-path-separator">${icon('chevron-right')}</i>`); }

$.fn.dataTable.ext.errMode = 'none';
const table = $('#role-table').DataTable({
    serverSide:true, processing:false, searching:false, order:[[4,'asc']], pageLength:10, lengthMenu:[10,25,50,100], dom:'rt<"crud-table-footer"lip>',
    language:{...pageData().language?.datatable, emptyTable:'', zeroRecords:'', info:'', infoEmpty:'', lengthMenu:`${copy().length} _MENU_`, paginate:{previous:copy().previous,next:copy().next}},
    ajax: async (request, callback) => {
        controller?.abort(); const current = new AbortController(); controller = current; loading = true; lock(); document.querySelector('[data-table-error]').hidden = true; empty.hidden = true;
        const params = new URLSearchParams({draw:request.draw,start:request.start,length:request.length,'search[value]':document.querySelector('[data-search]').value,'order[0][column]':request.order[0]?.column || 4,'order[0][dir]':request.order[0]?.dir || 'asc'}); new FormData(filterForm).forEach((value,key) => { if (value) params.set(key,value); });
        try { const response = await fetch(`${routes.data}?${params}`, {headers:{Accept:'application/json'},signal:current.signal}); if (!response.ok) throw new Error(); const result = await response.json(); if (!result.ok) throw new Error(); rows = result.data.data; callback(result.data); document.querySelectorAll('[data-role-stat]').forEach((node,index) => { node.textContent = new Intl.NumberFormat(document.documentElement.lang).format(result.data.stats[index]); }); empty.hidden = result.data.recordsFiltered !== 0; card.append(empty); }
        catch (error) { if (error.name === 'AbortError') return; rows=[]; callback({draw:request.draw,data:[],recordsTotal:0,recordsFiltered:0}); document.querySelector('[data-table-error]').hidden=false; announce(copy().failed,true); }
        finally { if (controller === current) { loading=false; lock(); selection(); enhanceSelects(footer); restoreFilter?.(); restoreFilter=undefined; } }
    },
    columns:[
        {data:'id',orderable:false,render:id=>canManage?`<input type="checkbox" data-row-id="${Number(id)}" aria-label="${escape(copy().select_row)}">`:''},
        {data:'path_label',render:(value,type,row)=>`<span class="role-path">${rolePath(value,row.name)}</span>`},
        {data:'is_default',className:'role-boolean',render:value=>value?`<span class="role-check" title="${escape(copy().yes)}">${icon('check')}</span>`:'<span aria-hidden="true">—</span>'},
        {data:'is_super',className:'role-boolean',render:value=>value?`<span class="role-check" title="${escape(copy().yes)}">${icon('check')}</span>`:'<span aria-hidden="true">—</span>'},
        {data:'ordering'},
        {data:'id',orderable:false,render:id=>canManage?`<div class="crud-row-actions"><a class="crud-icon-button" href="${routes.index}/${Number(id)}/edit" aria-label="${escape(copy().edit)}">${icon('edit')}</a><details class="crud-dropdown"><summary class="crud-icon-button" aria-label="${escape(copy().more)}">${icon('more-horizontal')}</summary><div class="crud-dropdown__menu"><a href="${routes.index}/${Number(id)}">${icon('eye')}${escape(copy().view)}</a><button type="button" data-delete="${Number(id)}">${icon('trash')}${escape(copy().delete)}</button></div></details></div>`:`<div class="crud-row-actions"><a class="crud-icon-button" href="${routes.index}/${Number(id)}" aria-label="${escape(copy().view)}">${icon('eye')}</a></div>`},
    ],
    drawCallback(){ const info=this.api().page.info(); const controls=document.querySelector('#role-table_wrapper .crud-table-footer'); if(controls) footer.append(controls); document.querySelector('#role-table_info').textContent=copy().page.replace(':page',info.pages?info.page+1:0).replace(':pages',info.pages); selection(); }
});

document.querySelector('#role-table').addEventListener('change', event => { if (!event.target.matches('[data-row-id]') || loading || pending) return; const id=Number(event.target.dataset.rowId); event.target.checked?select(id):selected.delete(id); selection(); });
selectAll.onchange=()=>{ document.querySelectorAll('[data-row-id]').forEach(node=>{const id=Number(node.dataset.rowId);selectAll.checked?select(id):selected.delete(id);});selection(); };
document.querySelector('[data-search]').oninput=()=>{clearTimeout(timer);timer=setTimeout(()=>table.ajax.reload(),300);};
document.querySelector('[data-open-filters]').onclick=()=>{filterDialog.showModal();enhanceSelects(filterDialog);}; document.querySelector('[data-close-filters]').onclick=()=>filterDialog.close();
filterForm.onsubmit=event=>{event.preventDefault();if(loading||pending)return;restoreFilter=loadingButton(filterForm.querySelector('[type=submit]'));filterDialog.close();table.ajax.reload();}; filterForm.onreset=()=>setTimeout(()=>{filterDialog.close();table.ajax.reload();},0); document.querySelector('[data-retry]').onclick=()=>table.ajax.reload();
selectionButton.onclick=()=>showDialog({title:copy().selected.replace(':count',selected.size),description:copy().selection_hint,items:[...selected.values()],remove:id=>{selected.delete(id);selection();}});

async function remove(ids){ if(pending||loading)return;if(!ids.length)return announce(copy().selection_required,true);const items=ids.map(id=>selected.get(id)||rows.find(row=>row.id===id)).filter(Boolean);const confirmed=await showDialog({title:copy().batch_title.replace(':operation',copy().delete),description:copy().batch_description.replace(':count',ids.length),items,confirm:copy().confirm_operation});if(!confirmed)return;pending=true;lock();try{const data=new FormData();ids.forEach(id=>data.append('ids[]',id));data.set('operation','delete');const result=await sendSettings(routes.bulk,data);ids.forEach(id=>selected.delete(id));announce(result.message);table.ajax.reload();}catch(error){announce(error.status?error.message:copy().failed,true);}finally{pending=false;lock();selection();}}
document.querySelector('[data-crud-toolbar]').onclick=event=>{const action=event.target.closest('[data-crud-action]')?.dataset.crudAction;if(action==='help')showDialog({title:copy().help_title,description:copy().help_intro,items:copy().help_items,accordion:true});else if(action==='delete')remove([...selected.keys()]);};
document.querySelector('#role-table').onclick=event=>{const button=event.target.closest('[data-delete]');if(button)remove([Number(button.dataset.delete)]);};
window.addEventListener('cms:presentation-refreshed', () => {
    toolbar('list');
    document.querySelectorAll('[data-role-column]').forEach(header => {
        const key = header.dataset.roleColumn;
        header.textContent = key === 'actions' ? copy().actions : pageData().roleFields?.[key] || key;
    });
    selectAll.setAttribute('aria-label', copy().select_all);
    const language = table.settings()[0].oLanguage;
    language.sLengthMenu = `${copy().length} _MENU_`;
    language.oPaginate.sPrevious = copy().previous;
    language.oPaginate.sNext = copy().next;
    table.draw(false);
    selection();
});
