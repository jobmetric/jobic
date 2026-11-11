import {pageData, loadingButton} from '../common/page';
import {sendSettings} from '../admin/settings';
import {announce} from '../common/toast';
import {enhanceSelects} from '../common/selects';
import {bindHistory} from '../taxonomies/history';
import {guardUnsaved} from '../common/unsaved';
import {showDialog, icon} from '../languages/shared';
import '../../css/roles.css';
import '../../css/kanban-boards.css';

const config=pageData().kanbanBoardForm;
const copy=config.copy;
const root=document.querySelector('[data-board-form]');
const form=root;
const find=selector=>root.querySelector(selector);
let historyPanel=null;
let pending=false, editing=null, filters=[], states=[];
const confirm=description=>showDialog({title:copy.boards,description,confirm:copy.confirm});
const guard=guardUnsaved(form,()=>confirm(copy.unsaved));
const node=(tag,text,className)=>{const element=document.createElement(tag);if(text!=null)element.textContent=text;if(className)element.className=className;return element;};

// Keep the Roles form's Cancel + split Save markup and dimensions.
const toolbar=find('[data-crud-toolbar]');
const cancel=node('a',copy.cancel,'vuero-button crud-cancel');cancel.href=config.indexUrl || config.baseUrl;cancel.dataset.crudAction='cancel';
const group=node('div',null,'crud-split-save');
const save=node('button',copy.save,'vuero-button is-primary');save.type='submit';save.name='intent';save.value='save';save.dataset.crudAction='save';
const dropdown=node('details',null,'crud-dropdown');const toggle=node('summary',null,'vuero-button');toggle.setAttribute('aria-label',copy.save_options);toggle.innerHTML=icon('chevron-down');
const menu=node('div',null,'crud-dropdown__menu');
for(const [key,label] of [['new',copy.save_new],['close',copy.save_close]]){const control=node('button',label);control.type='submit';control.name='intent';control.value=key;control.dataset.crudAction=key;menu.append(control);}
dropdown.append(toggle,menu);group.append(save,dropdown);toolbar.append(cancel,group);
function activateTab(index){
    root.querySelectorAll('[data-board-tab]').forEach(tab=>tab.setAttribute('aria-selected',String(Number(tab.dataset.boardTab)===index)));
    root.querySelectorAll('[data-board-panel]').forEach(panel=>panel.hidden=Number(panel.dataset.boardPanel)!==index);
    enhanceSelects(root);
}
root.querySelectorAll('[data-board-tab]').forEach(tab=>{
    tab.onclick=()=>activateTab(Number(tab.dataset.boardTab));
    tab.onkeydown=event=>{let direction=document.documentElement.dir==='rtl'?-1:1;const tabs=[...root.querySelectorAll('[data-board-tab]')];let index=tabs.indexOf(tab);if(event.key==='ArrowRight')index+=direction;else if(event.key==='ArrowLeft')index-=direction;else if(event.key==='Home')index=0;else if(event.key==='End')index=tabs.length-1;else return;event.preventDefault();index=(index+tabs.length)%tabs.length;activateTab(index);tabs[index].focus();};
});
function options(select,items,values=[]){
    const $=window.jQuery;
    if ($?.fn.select2 && $(select).hasClass('select2-hidden-accessible')) $(select).select2('destroy');
    select.replaceChildren(...items.map(item=>{const option=new Option(item.name,String(item.id));option.selected=values.map(String).includes(String(item.id));return option;}));
    if(select.parentElement)enhanceSelects(select.parentElement);
}
function button(text,action){const control=node('button',text,'vuero-button');control.type='button';control.onclick=action;return control;}
function clearErrors(){form.querySelectorAll('[data-error-for]').forEach(el=>el.textContent='');form.querySelectorAll('[aria-invalid]').forEach(el=>el.removeAttribute('aria-invalid'));}
function iconButton(name,label,action){
    const control=button('',action);control.className='crud-icon-button board-control';control.innerHTML=icon(name);control.setAttribute('aria-label',label);control.title=label;return control;
}
function focusColumn(id,action){
    const target=[...root.querySelectorAll('[data-column-id]')].find(control=>control.dataset.columnId===id&&control.dataset.columnAction===action&&!control.disabled);
    (target||find('[data-board-state-order]')).focus();
}
function renderStates(){
    const available=find('[data-board-state-available]');const area=find('[data-board-state-order]');
    available.replaceChildren();area.replaceChildren();
    const remaining=config.states.filter(state=>!states.includes(state.id));
    find('[data-board-available-count]').textContent=new Intl.NumberFormat(document.documentElement.lang).format(remaining.length);
    find('[data-board-selected-count]').textContent=new Intl.NumberFormat(document.documentElement.lang).format(states.length);
    remaining.forEach(state=>{
        const row=button('',()=>{states.push(state.id);renderStates();focusColumn(state.id,'remove');});row.className='board-column-option';row.dataset.columnId=state.id;row.dataset.columnAction='add';row.setAttribute('aria-label',`${copy.add_column}: ${state.name}`);
        const label=node('span',state.name);const mark=node('span',null,'board-column-add');mark.innerHTML=icon('plus');row.append(label,mark);available.append(row);
    });
    if(!remaining.length)available.append(node('p',copy.all_columns_selected,'board-picker-empty'));
    states.forEach((id,index)=>{
        const name=config.states.find(state=>state.id===id)?.name || id;
        const row=node('div',null,'board-state-row');row.dataset.selectedColumn=id;
        row.append(node('span',new Intl.NumberFormat(document.documentElement.lang).format(index+1),'board-column-number'),node('span',name,'board-column-name'));
        const controls=node('div',null,'board-column-controls');
        for(const [action,label,offset] of [['up',copy.move_up,-1],['down',copy.move_down,1]]){
            const control=iconButton('chevron-down',`${label}: ${name}`,()=>{const next=index+offset;[states[next],states[index]]=[states[index],states[next]];renderStates();focusColumn(id,action);});
            control.classList.toggle('is-up',offset===-1);control.dataset.columnId=id;control.dataset.columnAction=action;control.disabled=index+offset<0||index+offset>=states.length;controls.append(control);
        }
        const remove=iconButton('x',`${copy.remove}: ${name}`,()=>{states.splice(index,1);renderStates();focusColumn(id,'add');});remove.classList.add('is-remove');remove.dataset.columnId=id;remove.dataset.columnAction='remove';controls.append(remove);row.append(controls);area.append(row);
    });
    if(!states.length)area.append(node('p',copy.no_columns_selected,'board-picker-empty'));
    syncDirty();
}
function syncDirty(){form.querySelector('[name="board_definition"]')?.remove();const hidden=node('input');hidden.type='hidden';hidden.name='board_definition';hidden.value=JSON.stringify({states,filters});form.append(hidden);}
function renderFilters(){
    const area=find('[data-board-filters]');
    if(window.jQuery?.fn.select2)area.querySelectorAll('select.select2-hidden-accessible').forEach(select=>window.jQuery(select).off('.cms').select2('destroy'));
    area.replaceChildren();
    if(!filters.length){
        const empty=node('div',null,'board-filters-empty');const mark=node('span',null,'board-filter-symbol');mark.innerHTML=icon('filter');empty.append(mark,node('h3',copy.no_filters_title),node('p',copy.no_filters_hint));area.append(empty);
    }
    filters.forEach((filter,index)=>{
        const definition=config.drivers[filter.driver];
        const card=node('section',null,'board-filter-card');card.dataset.filterIndex=index;
        const header=node('header',null,'board-filter-head');const mark=node('span',null,'board-filter-symbol');mark.innerHTML=icon('filter');
        const heading=node('div');heading.append(node('small',copy.filter_number.replace(':number',new Intl.NumberFormat(document.documentElement.lang).format(index+1))),node('h3',definition?.label||copy.invalid_filter));
        const remove=iconButton('trash-2',`${copy.remove}: ${definition?.label||copy.filters}`,()=>{filters.splice(index,1);renderFilters();find('[data-board-add-filter]').focus();});remove.classList.add('is-remove');header.append(mark,heading,remove);card.append(header);
        const fields=node('div',null,'crud-form-fields board-filter-fields');
        const driverLabel=node('label',copy.filter_type,'crud-field');const driver=node('select');driver.id=`board-filter-${index}-driver`;driverLabel.htmlFor=driver.id;driver.setAttribute('aria-label',copy.filter_type);
        const drivers=Object.entries(config.drivers).map(([id,value])=>({id,name:value.label}));if(!definition)drivers.unshift({id:filter.driver,name:copy.invalid_filter});options(driver,drivers,[filter.driver]);
        driver.onchange=()=>{filter.driver=driver.value;filter.config={};renderFilters();};driverLabel.append(driver);fields.append(driverLabel);
        for(const field of definition?.fields||[]){
            const label=node('label',field.label,'crud-field');const select=node('select');select.id=`board-filter-${index}-${field.key}`;label.htmlFor=select.id;select.multiple=Boolean(field.multiple);select.dataset.field=field.key;
            if(field.multiple)select.classList.add('cms-select--multiple');
            options(select,field.dependsOn?(field.optionsBy?.[filter.config[field.dependsOn]]||[]):field.options,field.multiple?(filter.config[field.key]||[]):[filter.config[field.key]]);
            if(!field.multiple&&!filter.config[field.key])filter.config[field.key]=select.value;
            select.onchange=()=>{filter.config[field.key]=field.multiple?[...select.selectedOptions].map(option=>option.value):select.value;const dependents=definition.fields.filter(item=>item.dependsOn===field.key);dependents.forEach(item=>filter.config[item.key]=[]);if(dependents.length)renderFilters();else syncDirty();};
            label.append(select);if(field.multiple)label.classList.add('board-filter-values');fields.append(label);
        }
        card.append(fields);
        const hint=definition?.description || copy[`${filter.driver}_filter_hint`] || copy.filters_hint;card.append(node('p',hint,'board-filter-hint'));area.append(card);
    });enhanceSelects(area);syncDirty();
}
function initialize(){
    const board=config.board;
    editing=board?structuredClone(board):{id:null,name:'',roles:[],users:[],states:[],filters:[],version:0};
    clearErrors();
    find('[data-board-form-title]').textContent=board?.id?copy.edit:copy.create_board;
    form.elements.name.value=editing.name;
    options(form.querySelector('[name="roles[]"]'),config.roles,editing.roles);
    options(form.querySelector('[name="users[]"]'),config.users,editing.users);
    states=[...editing.states];filters=structuredClone(editing.filters);renderStates();renderFilters();
    guard.saved(guard.snapshot());form.elements.name.focus();
}
form.addEventListener('submit',async event=>{
    event.preventDefault();if(pending)return;pending=true;clearErrors();const restores=[...form.querySelectorAll('[type=submit]')].map(loadingButton);form.dataset.saving='1';
    const intent=event.submitter?.value || 'save';
    const submitted=guard.snapshot();
    const submitters=[...form.querySelectorAll('[type=submit]')];submitters.forEach(control=>control.disabled=true);
    try{
        const data=new FormData();data.set('name',form.elements.name.value);data.set('version',editing.version);
        for(const key of ['roles','users'])data.set(key,JSON.stringify([...form.querySelector(`[name="${key}[]"]`).selectedOptions].map(option=>Number(option.value))));
        data.set('states',JSON.stringify(states));data.set('filters',JSON.stringify(filters));
        if(editing.id)data.set('_method','PUT');
        const result=await sendSettings(editing.id?`${config.baseUrl}/boards/${editing.id}`:config.saveUrl,data);
        editing=result.data.board;guard.saved(submitted);announce(result.message);
        form.action=result.data.save_url || `${config.baseUrl}/boards/${editing.id}`;
        history.replaceState({},'',result.data.edit_url || `${config.baseUrl}/boards/${editing.id}/edit`);
        const panel=document.querySelector('[data-record-history]');panel.hidden=false;
        if(!historyPanel)historyPanel=bindHistory(form,()=>({...pageData().languages,...pageData().kanbanBoardForm.copy}));else historyPanel.refresh();
        find('[data-board-form-title]').textContent=copy.edit;
        if(intent==='new'||intent==='close'){guard.leave();location.assign(intent==='new'?config.createUrl:(config.indexUrl || config.baseUrl));}
    }catch(error){
        for(const [key,messages] of Object.entries(error.errors||{})){
            const target=form.querySelector(`[data-error-for="${key.split('.')[0]}"]`);if(target)target.textContent=messages.join(' ');
            const base=key.split('.')[0];const input=form.querySelector(`[name="${base}"],[name="${base}[]"]`) || (base==='filters' ? form.querySelectorAll('.board-filter-card')[Number(key.split('.')[1]||0)]?.querySelector('select') : base==='states' ? find('[data-board-state-order]') : null);if(input){input.setAttribute('aria-invalid','true');input.setAttribute('aria-describedby',`error-${key.split('.')[0]}`);}
        }
        const first=form.querySelector('[aria-invalid="true"]');if(first){const panel=first.closest('[data-board-panel]');if(panel)activateTab(Number(panel.dataset.boardPanel));first.focus();}announce(error.message,true);
    }finally{pending=false;delete form.dataset.saving;restores.forEach(restore=>restore());submitters.forEach(control=>control.disabled=false);}
});
find('[data-board-add-filter]').onclick=()=>{const key=Object.keys(config.drivers)[0];if(key){filters.push({driver:key,config:{}});renderFilters();}};


initialize();
enhanceSelects(form);

if(config.board?.id)historyPanel=bindHistory(form,()=>({...pageData().languages,...pageData().kanbanBoardForm.copy}));
