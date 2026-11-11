<script setup>
import {computed, nextTick, onBeforeUnmount, onMounted, ref} from 'vue';
import {pageData, loadingButton} from '../common/page';
import {sendSettings} from '../admin/settings';
import {announce} from '../common/toast';
import {enhanceSelects} from '../common/selects';
import {connectKanban} from './realtime';
import {iconMarkup} from '../common/icons';
import WorkflowPageHeader from '../workflow/components/WorkflowPageHeader.vue';
import WorkflowSettingsDialog from '../workflow/components/WorkflowSettingsDialog.vue';
import WorkflowToast from '../workflow/components/WorkflowToast.vue';
import '../../css/kanban.css';

const config = pageData().kanban;
const copy = config.copy;
const columns = ref([]);
const collapsedColumns = ref(new Set());
function toggleColumn(column){const next=new Set(collapsedColumns.value);const key=columnKey(column);next.has(key)?next.delete(key):next.add(key);collapsedColumns.value=next;}
function isCollapsed(column){return collapsedColumns.value.has(columnKey(column));}
const definition = ref(null);
const initialLoading = ref(true);
const boardError = ref('');
const busy = ref(false);
const pageSize = ref(config.pageSize);
const selectedSize = ref(config.pageSize);
const settingsError = ref('');
const settingsErrors=ref({});
const settingsTab=ref('personal');
function settingsKeydown(event){if(!config.editable||!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;event.preventDefault();settingsTab.value=event.key==='Home'?'personal':event.key==='End'?'capacity':settingsTab.value==='personal'?'capacity':'personal';nextTick(()=>document.querySelector(`#kb-tab-${settingsTab.value}`)?.focus());}
const groupDialog=ref(null);
const selectionOpen=ref(false);
const liveReason=ref('connecting');
const selectionMenu=ref(null);
function retryLive(){disconnectLive();disconnectLive=connectKanban(config.realtime,invalidated,(state,reason)=>{liveStatus.value=state;liveReason.value=reason||state;});}
const liveTooltip=computed(()=>copy[`live_${liveReason.value}`]||copy.live_connecting);
function openSelection(){const menu=selectionMenu.value;menu?.togglePopover();if(menu?.matches(':popover-open')){const rect=document.querySelector('.kb-selection-action>button').getBoundingClientRect();menu.style.top=`${rect.bottom+8}px`;menu.style.left=`${Math.max(12,Math.min(innerWidth-242,rect.right-230))}px`;}}
function bulkFromMenu(){selectionMenu.value?.hidePopover();openBulk();}
let bulkGeneration=0;
const settingsDialog = ref(null);
const detailDialog = ref(null);
const scheduleDialog = ref(null);
const detail = ref(null);
const detailError = ref('');
const editorFrame = ref(null);
const editorLocale = ref(config.locales[0]?.code);
const frameLoading = ref(false);
const drag = ref(null);
const dropAt = ref(null);
const pendingDrop = ref(null);
const dueLocal = ref('');
const dueError = ref('');
const menu = ref(null);
let lastFocus = null;
let lastCardId = null;
let readGeneration = 0;
let detailGeneration = 0;
let dragGeneration = 0;
let previewPromise = null;
const columnReads = new WeakMap();
let dropping = false;
const liveMessage = ref('');
const search=ref('');
const appliedFilters=ref([]);
const filterDraft=ref([]);
const filtersDialog=ref(null);
const groupBy=ref(config.workspace?.group_by||'none');
const density=ref(config.workspace?.density||'full');
const cardFields=ref(config.workspace?.card_fields||['image','terms','author','updated','age']);
const summary=ref({total:0,scheduled:0,stale:0});
const summaryFilter=ref('');
const options=ref(config.options||{version:0,stale_days:7,limits:[]});
const staleDays=ref(options.value.stale_days);
const limitDraft=ref([]);
const nextLaneOffset=ref(null);
const laneLoading=ref(false);
const selected=ref(new Map());
const bulkDialog=ref(null);
const bulkCards=ref([]);
const bulkTarget=ref('');
const bulkChoices=ref({});
const bulkDue=ref('');
const bulkResults=ref([]);
const bulkError=ref('');
const bulkLoading=ref(false);
const liveStatus=ref('disabled');
const livePending=ref(false);
const avatarErrors=ref(new Set());
const imageErrors=ref(new Set());
let disconnectLive=()=>{};
let liveTimer;
let searchTimer;
let liveRevision=0;
const laneRows=computed(()=>{
    const map=new Map();for(const column of columns.value){const key=column.lane||'all';if(!map.has(key))map.set(key,{id:key,name:column.lane_name||'',columns:[]});map.get(key).columns.push(column);}return [...map.values()];
});
const bulkTargets=computed(()=>{
    if(!bulkCards.value.length)return [];
    return columns.value.filter((column,index,all)=>all.findIndex(row=>row.status===column.status)===index&&bulkCards.value.every(card=>card.destinations.some(target=>target.state===column.status&&target.status!=='blocked')));
});
const bulkNeedsDate=computed(()=>bulkCards.value.some(card=>card.destinations.some(target=>target.transition===bulkChoices.value[card.card.id]&&target.requires_due)));
function fields(key){return cardFields.value.includes(key);}
function number(value){return new Intl.NumberFormat(document.documentElement.lang).format(value||0);}
function columnKey(column){return `${column.lane||'all'}:${column.status}`;}
function sameLane(card,column){if(groupBy.value==='none')return true;const source=columns.value.find(row=>row.items.some(item=>item.id===card.id));return !source||source.lane===column.lane;}
function targetColumn(state,card){return columns.value.find(column=>column.status===state&&sameLane(card,column));}
function toggleSelected(card,event){const next=new Map(selected.value);if(event.target.checked){if(next.size>=50){event.target.checked=false;announce(copy.selection_limit,true);return;}next.set(card.id,card);}else next.delete(card.id);selected.value=next;}
function syncLimits(){
    limitDraft.value=columns.value.filter((column,index,all)=>all.findIndex(row=>row.status===column.status)===index).map(column=>{const limit=(options.value.limits||[]).find(row=>row.state===column.status);return {state:column.status,name:column.name,limit:limit?.limit||0,mode:limit?.mode||'warn'};});
    staleDays.value=options.value.stale_days||7;
}
function filterOptions(filter,field){return field.dependsOn?(config.filterCatalog?.[filter.driver]?.fields.find(row=>row.key===field.key)?.optionsBy?.[filter.config[field.dependsOn]]||[]):field.options||[];}
function syncSelects(root){if(!root)return;if(window.jQuery?.fn.select2)root.querySelectorAll('select.select2-hidden-accessible').forEach(select=>window.jQuery(select).off('.cms').select2('destroy'));enhanceSelects(root);}
function resetFilter(filter){const definition=config.filterCatalog[filter.driver];filter.config={};for(const field of definition?.fields||[])filter.config[field.key]=field.multiple?[]:(field.options?.[0]?.id||'');nextTick(()=>syncSelects(document.querySelector('#kb-filter-form')));}
function addFilter(){const driver=Object.keys(config.filterCatalog||{})[0];if(!driver)return;const filter={driver,config:{}};resetFilter(filter);filterDraft.value.push(filter);nextTick(()=>enhanceSelects(document.querySelector('#kb-filter-form')));}
function filterChanged(filter,field){for(const dependent of config.filterCatalog[filter.driver].fields.filter(row=>row.dependsOn===field.key))filter.config[dependent.key]=[];nextTick(()=>{const area=document.querySelector('#kb-filter-form');syncSelects(area);});}
function applyFilters(){appliedFilters.value=JSON.parse(JSON.stringify(filterDraft.value));filtersDialog.value.close();selected.value=new Map();reload();}
function clearFilters(){search.value='';appliedFilters.value=[];filterDraft.value=[];summaryFilter.value='';selected.value=new Map();reload();}
function searchChanged(){clearTimeout(searchTimer);searchTimer=setTimeout(()=>{selected.value=new Map();reload();},300);}
function changeGrouping(){groupDialog.value?.close();selected.value=new Map();reload();}
function chipFilter(value){summaryFilter.value=summaryFilter.value===value?'':value;selected.value=new Map();reload();}
function liveUnsafe(){return busy.value||drag.value||initialLoading.value||!!document.querySelector('.kb-page dialog[open]')||selected.value.size>0||columns.value.some(column=>column.items.length>pageSize.value);}
function invalidated(){liveRevision++;livePending.value=true;clearTimeout(liveTimer);liveTimer=setTimeout(()=>{if(!liveUnsafe())reload(true);},700);}
function flushLive(){if(livePending.value&&!liveUnsafe())reload(true);}
async function moreLanes(){
    if(laneLoading.value||nextLaneOffset.value===null)return;laneLoading.value=true;
    try{const url=new URL(config.dataUrl,location.origin);url.searchParams.set('lane_offset',nextLaneOffset.value);url.searchParams.set('definition_id',definition.value);const generation=readGeneration;const data=await get(url);if(generation!==readGeneration)return;columns.value.push(...data.columns.filter(column=>!columns.value.some(old=>columnKey(old)===columnKey(column))).map(column=>({...column,loading:false,error:''})));nextLaneOffset.value=data.next_lane_offset;}
    catch(error){announce(error.message,true);}finally{laneLoading.value=false;}
}
async function openBulk(){
    const generation=++bulkGeneration;
    bulkResults.value=[];bulkError.value='';bulkCards.value=[];bulkTarget.value='';bulkChoices.value={};bulkDue.value='';bulkLoading.value=true;bulkDialog.value.showModal();
    try{for(const card of selected.value.values()){const data=await get(config.detailUrl.replace('__ID__',card.id));if(generation!==bulkGeneration||!bulkDialog.value.open)return;bulkCards.value.push(data);}}
    catch(error){if(generation===bulkGeneration){bulkError.value=error.message;bulkCards.value=[];}}finally{if(generation===bulkGeneration){bulkLoading.value=false;nextTick(()=>enhanceSelects(bulkDialog.value));}}
}
function bulkTargetChanged(){const choices={};for(const item of bulkCards.value){const candidates=item.destinations.filter(target=>target.state===bulkTarget.value&&target.status!=='blocked');choices[item.card.id]=candidates.length===1?candidates[0].transition:'';}bulkChoices.value=choices;nextTick(()=>syncSelects(bulkDialog.value));}
async function executeBulk(event){
    if(busy.value)return;bulkError.value='';
    if(!bulkTarget.value||bulkCards.value.some(item=>!bulkChoices.value[item.card.id])||(bulkNeedsDate.value&&!bulkDue.value.trim())){bulkError.value=copy.bulk_required;return;}
    busy.value=true;const restore=loadingButton(event.submitter);
    try{
        const items=bulkCards.value.map(item=>{const destination=item.destinations.find(target=>target.transition===bulkChoices.value[item.card.id]);return {post_id:item.card.id,definition_id:item.definition_id,version:item.card.version,revision_id:item.card.revision_id,state:destination.state,transition:destination.transition,column_version:columns.value.find(column=>column.status===destination.state)?.version||0,source_column_version:columns.value.find(column=>column.status===item.card.state)?.version||0,...(destination.requires_due?{due_local:bulkDue.value}:{})};});
        const data=new FormData();data.set('operation','bulk');data.set('items',JSON.stringify(items));if(config.boardVersion)data.set('board_version',config.boardVersion);
        const result=await sendSettings(config.commandUrl,data);bulkResults.value=result.data.results;
        const remaining=new Map(selected.value);for(const item of bulkResults.value)if(item.ok)remaining.delete(item.id);selected.value=remaining;announce(result.message);await reload(true);
    }catch(error){bulkError.value=error.message;announce(error.message,true);}finally{busy.value=false;restore();}
}

const destinations = computed(() => drag.value?.destinations ?? []);
const editorUrl = computed(() => {
    if (!detail.value?.edit_url) return null;
    const url = new URL(detail.value.edit_url, location.origin);
    if (editorLocale.value) url.searchParams.set('locale', editorLocale.value);
    return url.href;
});

async function get(url, fresh = false) {
    url = new URL(url, location.origin);
    if (search.value.trim()) url.searchParams.set('search',search.value.trim());
    if (appliedFilters.value.length) url.searchParams.set('filters',JSON.stringify(appliedFilters.value));
    if (summaryFilter.value) url.searchParams.set('summary',summaryFilter.value);
    url.searchParams.set('group_by',groupBy.value);
    if (config.boardVersion && !fresh) url.searchParams.set('board_version',config.boardVersion);
    const response = await fetch(url, {headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'});
    const result = await response.json();
    if (!response.ok || !result.ok) throw Object.assign(new Error(result.message || copy.failed), {status: response.status});
    return result.data;
}
async function reload(silent = false) {
    const scroll=new Map([...document.querySelectorAll('[data-kanban-column]')].map(element=>[element.dataset.columnKey,element.querySelector('.kb-column-body')?.scrollTop||0]));
    const generation = ++readGeneration;
    const notificationRevision=liveRevision;
    if(!silent)initialLoading.value = true; boardError.value = '';
    try {
        const data = await get(config.dataUrl, true);
        if (generation !== readGeneration) return;
        if (data.board_version) { config.boardVersion=data.board_version; config.boardName=data.board_name; }
        summary.value=data.summary||summary.value;options.value=data.options||options.value;nextLaneOffset.value=data.next_lane_offset??null;livePending.value=notificationRevision!==liveRevision;
        definition.value = data.definition_id;
        columns.value = data.columns.map(column => ({...column, loading: false, error: ''}));
        syncLimits();await nextTick();if(silent)document.querySelectorAll('[data-kanban-column]').forEach(element=>{const body=element.querySelector('.kb-column-body');if(body)body.scrollTop=scroll.get(element.dataset.columnKey)||0;});
    } catch (error) { if (generation === readGeneration) boardError.value = error.message; }
    finally { if (generation === readGeneration) initialLoading.value = false; }
}
function more(column) {
    if (columnReads.has(column)) return columnReads.get(column);
    if (!column.cursor || busy.value) return Promise.resolve(false);
    const pending = loadMore(column);
    columnReads.set(column, pending);
    pending.finally(() => columnReads.delete(column));
    return pending;
}
async function loadMore(column) {
    const generation = readGeneration;
    column.loading = true; column.error = '';
    try {
        const url = new URL(config.dataUrl, location.origin);
        url.searchParams.set('state', column.status); url.searchParams.set('cursor', column.cursor);
        url.searchParams.set('definition_id', definition.value);
        if(column.lane)url.searchParams.set('lane',column.lane);if(column.lane_offset)url.searchParams.set('lane_offset',column.lane_offset);
        const data = await get(url);
        if (generation !== readGeneration) return false;
        const incoming = data.columns[0];
        const seen = new Set(column.items.map(item => item.id));
        column.items.push(...incoming.items.filter(item => !seen.has(item.id)));
        column.cursor = incoming.cursor; column.count = incoming.count; column.version = incoming.version;
        return true;
    } catch (error) {
        column.error = error.message;
        if (error.status === 409) { announce(copy.stale, true); await reload(); }
        return false;
    } finally { column.loading = false; }
}
function scrolled(event, column) {
    const element = event.currentTarget;
    if (element.scrollHeight - element.scrollTop - element.clientHeight < 80) more(column);
}
function wheel(event, column) {
    const element = event.currentTarget;
    if (event.deltaY > 0 && element.scrollHeight - element.scrollTop - element.clientHeight < 80) more(column);
}
async function settingsSave(event) {
    if (busy.value) return;
    const restore = loadingButton(event.submitter);
    busy.value = true; settingsError.value = '';settingsErrors.value={};
    try {
        const data = new FormData();data.set('page_size',selectedSize.value);data.set('density',density.value);data.set('group_by',groupBy.value);data.set('card_fields',JSON.stringify(cardFields.value)); data.set('operation', 'settings');
        if (config.boardVersion) data.set('board_version',config.boardVersion);
        const result = await sendSettings(config.commandUrl, data);
        pageSize.value=result.data.page_size;selectedSize.value=result.data.page_size;
        if(config.editable){
            const limits=limitDraft.value.map(({state,limit,mode})=>({state,limit:Number(limit),mode}));
            const changed=JSON.stringify(limits.filter(row=>row.limit))!==JSON.stringify((options.value.limits||[]).filter(row=>Number(row.limit)))||Number(staleDays.value)!==Number(options.value.stale_days||7);
            if(changed){const shared=new FormData();shared.set('operation','limits');shared.set('limits',JSON.stringify(limits));shared.set('stale_days',staleDays.value);shared.set('options_version',options.value.version);if(config.boardVersion)shared.set('board_version',config.boardVersion);await sendSettings(config.commandUrl,shared);}
        }
        settingsDialog.value.close(); announce(result.message); await reload();
    } catch (error) { settingsErrors.value=error.errors||{};if(Object.keys(settingsErrors.value).some(key=>key.startsWith('limits')||key==='stale_days'))settingsTab.value='capacity';else if(settingsErrors.value.page_size)settingsTab.value='personal';settingsError.value = Object.values(settingsErrors.value)[0]?.[0] || error.message; await nextTick(); document.querySelector('#kanban-settings-form [aria-invalid="true"]')?.focus(); if(error.status===409)await reload(); }
    finally { busy.value = false; restore(); }
}
async function openCard(card, event) {
    if (busy.value || drag.value) return;
    lastFocus = event?.currentTarget ?? document.activeElement;
    lastCardId = card.id;
    const generation = ++detailGeneration;
    detail.value = null; detailError.value = ''; frameLoading.value = false;
    detailDialog.value.showModal();
    try {
        const value = await get(config.detailUrl.replace('__ID__', card.id));
        if (generation !== detailGeneration) return;
        detail.value = value; editorLocale.value = value.locale ?? document.documentElement.lang;
        frameLoading.value = Boolean(value.edit_url);
        await nextTick(); enhanceSelects(detailDialog.value);
    } catch (error) { if (generation === detailGeneration) detailError.value = error.message; }
}
function restoreFocus() {
    const replacement = lastCardId ? document.querySelector(`[data-kanban-card="${lastCardId}"] .kb-card-title`) : null;
    (lastFocus?.isConnected ? lastFocus : replacement ?? document.querySelector('.rf-header__back'))?.focus();
}
async function canLeaveEditor() {
    try {
        const editor = editorFrame.value?.contentWindow?.kanbanEditor;
        return editor ? await editor.canClose() : !frameLoading.value;
    } catch { return true; }
}
async function closeDetails() {
    if (busy.value || !(await canLeaveEditor())) return;
    ++detailGeneration; detailDialog.value.close(); detail.value = null;
    await nextTick(); restoreFocus();
}
async function localeChange(event) {
    const selected = event.target.value;
    if (!(await canLeaveEditor())) { event.target.value = editorLocale.value; return; }
    editorLocale.value = selected;
    if (detail.value?.edit_url) frameLoading.value = true;
    else if (detail.value) {
        const url = new URL(config.detailUrl.replace('__ID__', detail.value.card.id), location.origin); url.searchParams.set('locale', selected);
        try { detail.value = await get(url); } catch(error) { announce(error.message, true); }
    }
}
async function changed() {
    if (!detailDialog.value.open) return;
    detailDialog.value.close(); detail.value = null; ++detailGeneration;
    await reload(); restoreFocus();
}
async function saved() {
    const id = detail.value?.card.id;
    const generation = detailGeneration;
    await reload();
    if (!id || !detailDialog.value.open || generation !== detailGeneration) return;
    try {
        const url = new URL(config.detailUrl.replace('__ID__', id), location.origin);
        if (editorLocale.value) url.searchParams.set('locale', editorLocale.value);
        const value = await get(url);
        if (generation === detailGeneration && detailDialog.value.open) detail.value = value;
    } catch (error) { if (generation === detailGeneration) detailError.value = error.message; }
}
async function detailMove(target) {
    if (!detail.value || !(await canLeaveEditor())) return;
    const card = {...detail.value.card};
    detailDialog.value.close(); detail.value=null; ++detailGeneration;
    await chooseMove(card, targetColumn(target.state,card), target);
}
function eligible(column) {
    if (!drag.value) return null;
    if(!sameLane(drag.value.card,column))return {status:'blocked',checks:[{message:copy.same_lane}]};
    if (column.status === drag.value.card.state) return {status: 'ready'};
    const target=destinations.value.find(target=>target.state===column.status)??null;
    if(target&&column.capacity?.mode==='enforce'&&column.capacity.limit>0&&column.capacity_count!==null&&column.capacity_count>=column.capacity.limit)return {...target,status:'blocked',checks:[{message:copy.capacity_full}]};
    return target;
}
async function startDrag(card, event) {
    if (!config.editable || busy.value) { event?.preventDefault(); return; }
    menu.value = null;
    const generation = ++dragGeneration;
    drag.value = {card, destinations: [], loading: true};
    if (event?.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move'; event.dataTransfer.setData('text/plain', String(card.id));
    }
    previewPromise = get(config.detailUrl.replace('__ID__', card.id)).then(data => {
        if (generation !== dragGeneration || drag.value?.card.id !== card.id) return;
        drag.value = {...drag.value, card: data.card, destinations: data.destinations, loading: false, definition_id: data.definition_id};
        liveMessage.value = copy.choose_destination;
    }).catch(error => { announce(error.message, true); clearDrag(); });
    await previewPromise;
}
function clearDrag() { drag.value = null; dropAt.value = null; ++dragGeneration; }
function over(event, column, card = null) {
    if (!drag.value || busy.value) return;
    const target = eligible(column);
    if (!drag.value.loading && (!target || target.status === 'blocked')) { if (event.dataTransfer) event.dataTransfer.dropEffect = 'none'; return; }
    event.preventDefault();
    let before = card?.id ?? null;
    if (card) {
        const rect = event.currentTarget.getBoundingClientRect();
        if (event.clientY > rect.top + rect.height / 2) {
            const index = column.items.findIndex(row => row.id === card.id);
            before = column.items[index + 1]?.id ?? null;
        }
    }
    dropAt.value = {state: column.status, before, key:columnKey(column)};
    if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
}
async function dropped(event, column) {
    event.preventDefault(); event.stopPropagation();
    if (!drag.value || busy.value || dropping) return;
    dropping = true;
    const generation = dragGeneration;
    try {
        await previewPromise;
        if (generation !== dragGeneration || !drag.value || drag.value.loading) return;
        const active = drag.value;
        const destination = eligible(column);
        if (!destination || destination.status === 'blocked') { clearDrag(); return; }
        let before = dropAt.value?.state === column.status ? dropAt.value.before : null;
        if (before == null && column.cursor) {
            const length = column.items.length;
            const loaded = await more(column);
            if (!loaded || generation !== dragGeneration) { clearDrag(); return; }
            before = column.items[length]?.id ?? null;
        }
        if (before === active.card.id && active.card.state === column.status) { clearDrag(); return; }
        const operation = active.card.state === column.status ? 'reorder' : 'move';
        const payload = {operation, post_id: active.card.id, definition_id: active.definition_id ?? definition.value,
            version: active.card.version, revision_id: active.card.revision_id, state: column.status,
            column_version: column.version, source_column_version: columns.value.find(c => c.status === active.card.state)?.version ?? 0,
            ...(operation === 'move' ? {transition: destination.transition} : {}), ...(before && before !== active.card.id ? {before_id: before} : {})};
        clearDrag();
        if (destination.requires_due && operation === 'move') {
            pendingDrop.value = payload; dueLocal.value = ''; dueError.value = '';
            scheduleDialog.value.showModal(); return;
        }
        await execute(payload);
    } finally { dropping = false; }
}
async function execute(payload, submitter = null) {
    if (busy.value) return;
    busy.value = true;
    const restore = submitter ? loadingButton(submitter) : () => {};
    try {
        const data = new FormData();
        if (config.boardVersion) data.set('board_version',config.boardVersion);
        Object.entries(payload).forEach(([key, value]) => { if (value != null) data.set(key, value); });
        const result = await sendSettings(config.commandUrl, data);
        announce(result.message); liveMessage.value = result.message;
        if (scheduleDialog.value.open) scheduleDialog.value.close();
        pendingDrop.value = null; await reload();
    } catch (error) {
        dueError.value = error.errors?.due_local?.[0] || error.errors?.due_at?.[0] || error.message;
        announce(dueError.value, true);
        if ([403, 409, 423].includes(error.status)) await reload();
    } finally { busy.value = false; restore(); }
}
async function scheduleSave(event) {
    if (!dueLocal.value.trim()) { dueError.value = copy.due_error; return; }
    await execute({...pendingDrop.value, due_local: dueLocal.value}, event.submitter);
}
async function chooseMove(card, column, target = null, before = null) {
    await startDrag(card);
    if (!drag.value) return;
    dropAt.value = {state: column.status, before, key:columnKey(column)};
    // A named transition also permits a self-transition from the accessible move menu.
    if (target && target.state === card.state) {
        const payload = {operation: 'move', post_id: card.id, definition_id: definition.value, version: drag.value.card.version,
            revision_id: drag.value.card.revision_id, state: target.state, transition: target.transition,
            column_version: column.version, source_column_version: column.version};
        clearDrag();
        if (target.requires_due) { pendingDrop.value=payload; dueLocal.value=''; dueError.value=''; scheduleDialog.value.showModal(); return; }
        await execute(payload); return;
    }
    await dropped({preventDefault() {}, stopPropagation() {}}, column);
}
async function shiftCard(card, column, direction) {
    let index = column.items.findIndex(item => item.id === card.id);
    if (direction > 0 && index + 2 >= column.items.length && column.cursor) await more(column);
    index = column.items.findIndex(item => item.id === card.id);
    const before = direction < 0 ? column.items[index - 1]?.id : column.items[index + 2]?.id;
    await chooseMove(card, column, null, before); menu.value = null;
}
async function openMove(card, event) {
    lastFocus = event.currentTarget;
    await startDrag(card);
    if (drag.value) menu.value = card.id;
}
function endDrag() { if (!dropping && !menu.value && !pendingDrop.value && !busy.value) clearDrag(); flushLive(); }
function keydown(event) {
    if (event.key === 'Escape' && drag.value) { clearDrag(); menu.value = null; }
}
onMounted(() => { reload();disconnectLive=connectKanban(config.realtime,invalidated,(state,reason)=>{liveStatus.value=state;liveReason.value=reason||state;}); window.addEventListener('jobic:kanban:changed', changed); window.addEventListener('jobic:kanban:saved', saved); window.addEventListener('keydown', keydown); });
onBeforeUnmount(() => { disconnectLive();clearTimeout(liveTimer);clearTimeout(searchTimer);readGeneration++; detailGeneration++; window.removeEventListener('jobic:kanban:changed', changed); window.removeEventListener('jobic:kanban:saved', saved); window.removeEventListener('keydown', keydown); });
</script>

<template>
    <main class="kb-page rf-page" :class="{'is-compact':density==='compact','is-grouped':groupBy!=='none','has-selection':selected.size>0}" :aria-busy="busy" data-kanban-board>
        <WorkflowPageHeader class="kb-header" :title="`${copy.title} · ${config.boardName || config.typeLabel}`" :back-url="config.backUrl" :back-label="copy.back"
            :settings-label="copy.settings" :busy="busy" use-tooltips @settings="settingsDialog.showModal()">
            <template #before-settings>
                <div class="kb-summary" role="group" :aria-label="copy.summary">
                    <button v-for="metric in [{key:'',field:'total',icon:'kanban'},{key:'scheduled',field:'scheduled',icon:'calendar'},{key:'stale',field:'stale',icon:'clock'}]" :key="metric.field" type="button" :class="{'is-active':summaryFilter===metric.key}" :aria-pressed="summaryFilter===metric.key" :aria-label="copy[`summary_${metric.field}`]+' '+number(summary[metric.field])" :data-tooltip="copy[`summary_${metric.field}`]" @click="chipFilter(metric.key)"><span v-html="iconMarkup(metric.icon,16)"/><strong>{{ number(summary[metric.field]) }}</strong></button>
                </div>
                <div class="kb-tools">
                    <label class="kb-search" :data-tooltip="copy.search_cards"><span v-html="iconMarkup('search',16)"/><span class="kb-screen-reader">{{ copy.search_cards }}</span><input v-model="search" type="search" :placeholder="copy.search_cards" maxlength="160" @input="searchChanged"/></label>
                    <button type="button" class="rf-btn rf-btn--compact kb-tool-button" :aria-label="copy.filters" :data-tooltip="copy.filters" @click="filterDraft=JSON.parse(JSON.stringify(appliedFilters));filtersDialog.showModal()"><span v-html="iconMarkup('filter',16)"/><b v-if="appliedFilters.length">{{ number(appliedFilters.length) }}</b></button>
                    <button type="button" class="rf-btn rf-btn--compact kb-tool-button kb-group-trigger" :class="{'is-active':groupBy!=='none'}" :disabled="busy" :aria-label="copy.group_by" :data-tooltip="copy.group_by+': '+(config.groupChoices?.[groupBy]||copy.group_none)" @click="groupDialog.showModal()"><span v-html="iconMarkup('list-tree',16)"/></button>
                    <button v-if="search||appliedFilters.length||summaryFilter" type="button" class="rf-btn rf-btn--compact kb-tool-button" :aria-label="copy.clear_view" :data-tooltip="copy.clear_view" @click="clearFilters"><span v-html="iconMarkup('x',16)"/></button>
                </div>
                <div v-if="selected.size" class="kb-selection-action">
                    <button type="button" class="rf-btn rf-btn--compact rf-btn--primary" :disabled="busy" :aria-label="copy.selection_actions" :data-tooltip="copy.selected_cards.replace(':count',number(selected.size))" :aria-expanded="selectionOpen" @click="openSelection"><span v-html="iconMarkup('check-circle',16)"/><span class="kb-selection-label">{{ copy.selection_actions }}</span><span>{{ number(selected.size) }}</span><span v-html="iconMarkup('chevron-down',14)"/></button>
                    <div ref="selectionMenu" popover="auto" @toggle="selectionOpen=$event.newState==='open'" class="kb-selection-menu"><strong>{{ copy.selected_cards.replace(':count',number(selected.size)) }}</strong><button type="button" :disabled="busy" @click="bulkFromMenu"><span v-html="iconMarkup('arrows-left-right',16)"/>{{ copy.bulk_move }}</button><button type="button" @click="selectionMenu.hidePopover();selected=new Map();flushLive()"><span v-html="iconMarkup('x',16)"/>{{ copy.clear_selection }}</button></div>
                </div>
                <button v-if="livePending" type="button" class="rf-btn rf-btn--compact kb-tool-button kb-refresh-pending" :disabled="busy||!!drag" :aria-label="copy.refresh_board" :data-tooltip="copy.live_updates" @click="reload(true)"><span v-html="iconMarkup('refresh',17)"/><i/></button>
                <div v-if="config.realtime?.enabled" class="kb-live-control">
                    <button type="button" class="kb-live-status" :class="'is-'+liveStatus" :aria-label="liveTooltip" aria-describedby="kb-live-tooltip" @click="liveStatus!=='connected'&&retryLive()"><i/><span class="kb-screen-reader">{{ liveTooltip }}</span></button>
                    <span id="kb-live-tooltip" class="kb-live-tooltip" role="tooltip"><strong>{{ liveTooltip }}</strong><small v-if="liveStatus!=='connected'">{{ copy.live_retry_hint }}</small></span>
                </div>
            </template>
        </WorkflowPageHeader>
        <p class="kb-screen-reader" aria-live="polite">{{ liveMessage }}</p>

        <div v-if="boardError" class="kb-empty" role="alert">{{ boardError }}<button type="button" @click="reload()">{{ copy.retry }}</button></div>
        <div v-else-if="initialLoading" class="kb-loading" role="status">{{ copy.loading }}</div>
        <div v-else-if="!definition" class="kb-empty">{{ copy.no_workflow }}</div>
        <div v-else class="kb-board-scroll" tabindex="-1" @dragend="endDrag">
          <section v-for="lane in laneRows" :key="lane.id" class="kb-lane">
            <h2 v-if="groupBy!=='none'" class="kb-lane-title">{{ lane.name }}<span v-if="groupBy.startsWith('taxonomy:')">{{ copy.group_first_term }}</span></h2>
            <div class="kb-columns" :class="{'is-moving':drag}">
            <section v-for="column in lane.columns" :key="columnKey(column)" class="kb-column" :data-kanban-column="column.status" :data-column-key="columnKey(column)"
                :class="{'is-eligible': eligible(column)?.status === 'ready', 'is-unknown': eligible(column)?.status === 'unknown', 'is-needs-input': eligible(column)?.requires_due && eligible(column)?.status === 'ready', 'is-blocked': drag && (!eligible(column) || eligible(column)?.status === 'blocked'), 'is-over': dropAt?.key === columnKey(column), 'is-collapsed':isCollapsed(column)}"
                @dragover="over($event, column)" @drop="dropped($event, column)">
                <header class="kb-column-header"><span class="kb-state-dot"/><h2>{{ column.name }}</h2><span v-if="!column.restricted" class="kb-count" :class="{'is-at-capacity':column.capacity?.limit>0&&column.capacity_count>=column.capacity.limit}">{{ number(column.count) }}<span v-if="column.capacity?.limit>0" :title="copy.capacity_total.replace(':count',number(column.capacity_count))"> / {{ number(column.capacity.limit) }}</span></span><span v-else aria-hidden="true">◈</span><button type="button" class="kb-collapse-button" :aria-label="isCollapsed(column)?copy.expand_column.replace(':name',column.name):copy.collapse_column.replace(':name',column.name)" :aria-expanded="!isCollapsed(column)" :data-tooltip="isCollapsed(column)?copy.expand:copy.collapse" @click="toggleColumn(column)"><span v-html="iconMarkup(isCollapsed(column)?'expand-out':'collapse-in',18)"/></button></header>
                <div v-show="!isCollapsed(column)" class="kb-column-body" tabindex="0" :aria-label="column.name" @scroll.passive="scrolled($event, column)" @wheel.passive="wheel($event, column)">
                    <p v-if="column.restricted" class="kb-restricted">{{ copy.restricted }}</p>
                    <p v-else-if="!column.items.length" class="kb-column-empty">{{ copy.empty }}</p>
                    <template v-for="(card, index) in column.items" :key="card.id">
                        <div v-if="dropAt?.key === columnKey(column) && dropAt.before === card.id" class="kb-placeholder"/>
                        <article class="kb-card" :data-kanban-card="card.id" :draggable="config.editable && !busy"
                            :class="{'is-dragging': drag?.card.id === card.id}" @dragstart="startDrag(card, $event)" @dragover.stop="over($event, column, card)">
                            <img v-if="fields('image')&&card.image_url&&!imageErrors.has(card.image_url)" class="kb-card-image" :src="card.image_url" alt="" loading="lazy" @error="imageErrors.add(card.image_url)"/>
                            <div class="kb-card-top"><input v-if="config.editable" type="checkbox" class="kb-card-checkbox" :checked="selected.has(card.id)" :aria-label="copy.select_card.replace(':title',card.title)" @click.stop @change="toggleSelected(card,$event)"/><span class="kb-card-kind"><span v-html="iconMarkup('file-text',17)"/>{{ config.typeLabel }}</span><span class="kb-card-id">#{{ card.id }}</span><span v-if="config.editable" class="kb-card-grip" :title="copy.drag_hint" v-html="iconMarkup('grip-vertical',18)"/></div>
                            <button type="button" class="kb-card-title" @click="openCard(card, $event)">{{ card.title }}</button>
                            <div v-if="fields('author')" class="kb-card-meta"><img v-if="card.avatar_url&&!avatarErrors.has(card.avatar_url)" class="kb-author-avatar" :src="card.avatar_url" alt="" loading="lazy" @error="avatarErrors.add(card.avatar_url)"/><span v-else class="kb-author-mark" aria-hidden="true">{{ Array.from(card.author || '').slice(0,1).join('') }}</span><span class="kb-card-author" :title="card.author">{{ card.author }}</span></div>
                            <div v-if="fields('terms')&&card.terms?.length" class="kb-card-terms"><span v-for="term in card.terms.slice(0,3)" :key="`${term.collection}:${term.id}`">{{ term.name }}</span><span v-if="card.terms.length>3">+{{ number(card.terms.length-3) }}</span></div>
                            <div class="kb-card-signals"><span v-if="fields('age')&&card.age_days!==undefined" :class="{'is-stale':card.age_days>=options.stale_days}" :title="card.entered_at"><i v-html="iconMarkup('clock',13)"/>{{ copy.age_days.replace(':count',number(card.age_days)) }}</span><span v-if="card.scheduled_at" :title="copy.summary_scheduled"><i v-html="iconMarkup('calendar',13)"/>{{ card.scheduled_at }}</span></div>
                            <footer><time v-if="fields('updated')"><span v-html="iconMarkup('clock',14)"/>{{ card.updated_at }}</time><button v-if="config.editable" type="button" class="kb-card-move" :aria-label="copy.move" :title="copy.move_hint" :disabled="busy" @click="openMove(card, $event)"><span v-html="iconMarkup('arrows-left-right',18)"/><span>{{ copy.move }}</span></button></footer>
                            <div v-if="menu === card.id" class="kb-move-menu" :aria-label="copy.choose_destination">
                                <div class="kb-move-head"><span v-html="iconMarkup('arrows-left-right',16)"/><strong>{{ copy.choose_destination }}</strong><button type="button" class="kb-move-close" :aria-label="copy.close" :title="copy.close" @click="menu=null; clearDrag()"><span v-html="iconMarkup('x',15)"/></button></div>
                                <div v-if="index > 0 || index < column.items.length-1" class="kb-move-order"><button v-if="index > 0" type="button" @click="shiftCard(card, column, -1)"><span v-html="iconMarkup('arrow-right',15)"/>{{ copy.move_up }}</button><button v-if="index < column.items.length-1" type="button" @click="shiftCard(card, column, 1)"><span v-html="iconMarkup('arrow-left',15)"/>{{ copy.move_down }}</button></div>
                                <p v-if="!destinations.length" class="kb-move-empty">{{ drag?.loading?copy.preview_loading:copy.bulk_no_target }}</p>
                                <div v-else class="kb-move-destinations"><button v-for="target in destinations" :key="target.transition" type="button" :disabled="target.status === 'blocked'" :title="target.checks.map(c => c.message).filter(Boolean).join(' · ')" @click="chooseMove(card, targetColumn(target.state,card), target); menu=null"><span class="kb-move-destination-icon" v-html="iconMarkup('arrow-right',15)"/><span>{{ target.label }}</span><small v-if="target.status==='unknown'">{{ copy.move_check_on_run }}</small><small v-else-if="target.requires_due">{{ copy.needs_due }}</small></button></div>
                            </div>
                        </article>
                    </template>
                    <div v-if="dropAt?.key === columnKey(column) && !dropAt.before" class="kb-placeholder"/>
                    <p v-if="drag?.loading" class="kb-hint">{{ copy.preview_loading }}</p>
                    <p v-else-if="eligible(column)?.status === 'unknown'" class="kb-hint is-warning">{{ copy.unknown }}</p>
                    <p v-if="eligible(column)?.requires_due" class="kb-hint">{{ copy.needs_due }}</p>
                    <p v-if="eligible(column)?.status === 'blocked'" class="kb-hint">{{ eligible(column).checks?.map(c => c.message).filter(Boolean).join(' · ') || copy.blocked }}</p>
                    <p v-if="column.error" class="kb-error" role="alert">{{ column.error }}</p>
                    <button v-if="column.cursor" type="button" class="kb-more" :disabled="column.loading || busy" @click="more(column)">{{ column.loading ? copy.loading : (column.error ? copy.retry : copy.more) }}</button>
                </div>
            </section>
            </div>
          </section>
          <button v-if="nextLaneOffset!==null" type="button" class="kb-more-lanes rf-btn rf-btn--secondary" :disabled="laneLoading" @click="moreLanes">{{ laneLoading?copy.loading:copy.more_groups }}</button>
        </div>
        <WorkflowSettingsDialog ref="settingsDialog" class="kb-settings kb-board-settings" :title="copy.settings" :intro="copy.settings_intro" :close-label="copy.close" :busy="busy" @close="flushLive">
            <form id="kanban-settings-form" novalidate @submit.prevent="settingsSave">
                <div class="kb-settings-tabs role-form-tabs" role="tablist" @keydown="settingsKeydown" :aria-label="copy.settings">
                    <button id="kb-tab-personal" type="button" role="tab" :tabindex="settingsTab==='personal'?0:-1" :aria-selected="settingsTab==='personal'" aria-controls="kb-panel-personal" @click="settingsTab='personal'"> <span v-html="iconMarkup('panel',16)"/>{{ copy.personal_display }}</button>
                    <button v-if="config.editable" id="kb-tab-capacity" type="button" role="tab" :tabindex="settingsTab==='capacity'?0:-1" :aria-selected="settingsTab==='capacity'" aria-controls="kb-panel-capacity" @click="settingsTab='capacity'"><span v-html="iconMarkup('kanban',16)"/>{{ copy.column_limits }}</button>
                </div>
                <section id="kb-panel-personal" v-show="settingsTab==='personal'" role="tabpanel" aria-labelledby="kb-tab-personal" class="kb-settings-panel">
                    <p class="kb-settings-note">{{ copy.personal_settings_note }}</p>
                    <div class="kb-preferences-grid">
                        <div class="kb-preference"><label for="kb-page-size">{{ copy.page_size }}</label><select id="kb-page-size" name="page_size" v-model="selectedSize" :disabled="busy" :aria-invalid="!!settingsErrors.page_size" :aria-describedby="settingsErrors.page_size ? 'kb-settings-error' : 'kb-size-hint'"><option v-for="size in [5,10,20,50]" :key="size" :value="size">{{ number(size) }}</option></select><small id="kb-size-hint">{{ copy.page_size_hint }}</small></div>
                        <div class="kb-preference"><label for="kb-density">{{ copy.card_density }}</label><select id="kb-density" v-model="density" :disabled="busy"><option value="full">{{ copy.density_full }}</option><option value="compact">{{ copy.density_compact }}</option></select><small>{{ copy.group_save_hint }}</small></div>
                    </div>
                    <fieldset class="kb-card-fields"><legend>{{ copy.card_fields }}</legend><label v-for="field in [{key:'image',icon:'file-image'},{key:'terms',icon:'bookmark'},{key:'author',icon:'user'},{key:'updated',icon:'calendar'},{key:'age',icon:'clock'}]" :key="field.key" :class="{'is-selected':cardFields.includes(field.key)}"><span class="kb-field-icon" v-html="iconMarkup(field.icon,18)"/><span>{{ copy[`field_${field.key}`] }}</span><input v-model="cardFields" :disabled="busy" type="checkbox" :value="field.key"/></label></fieldset>
                </section>
                <section v-if="config.editable" id="kb-panel-capacity" v-show="settingsTab==='capacity'" role="tabpanel" aria-labelledby="kb-tab-capacity" class="kb-settings-panel">
                    <p class="kb-settings-note"><span v-html="iconMarkup('users',16)"/>{{ copy.shared_settings }}</p>
                    <p class="kb-hint">{{ copy.limits_hint }}</p>
                    <div class="kb-stale-setting"><label for="kb-stale-days">{{ copy.stale_days }}</label><input id="kb-stale-days" :disabled="busy" :aria-invalid="!!settingsErrors.stale_days" aria-describedby="kb-settings-error" v-model.number="staleDays" type="number" min="1" max="3650"/></div>
                    <div class="kb-limits-list"><div v-for="(limit,index) in limitDraft" :key="limit.state" class="kb-limit-row"><h3><i/>{{ limit.name }}</h3><div class="kb-limit-controls"><label :for="`kb-limit-${limit.state}`">{{ copy.capacity_number }}<input :id="`kb-limit-${limit.state}`" :disabled="busy" :aria-invalid="!!settingsErrors[`limits.${index}.limit`]" aria-describedby="kb-settings-error" v-model.number="limit.limit" type="number" min="0" max="100000"/></label><label>{{ copy.limit_mode }}<select v-model="limit.mode" :disabled="busy" :aria-label="copy.limit_mode+' '+limit.name"><option value="warn">{{ copy.limit_warn }}</option><option value="enforce">{{ copy.limit_enforce }}</option></select></label></div></div></div>
                </section>
                <p v-if="settingsError" id="kb-settings-error" class="kb-error" role="alert">{{ settingsError }}</p>
            </form>
            <template #footer><button type="submit" form="kanban-settings-form" class="rf-btn rf-btn--primary" :disabled="busy">{{ copy.save }}</button></template>
        </WorkflowSettingsDialog>
        <WorkflowSettingsDialog ref="groupDialog" class="kb-group-dialog" :title="copy.group_by" :intro="copy.group_save_hint" :close-label="copy.close" @close="flushLive">
            <div class="kb-group-choices" role="radiogroup" :aria-label="copy.group_by"><label v-for="(label,key) in config.groupChoices||{none:copy.group_none,author:copy.author}" :key="key" class="kb-choice" :class="{'is-selected':groupBy===key}"><input v-model="groupBy" type="radio" name="kb-group" :value="key" :disabled="busy" @change="changeGrouping"/><span class="kb-choice-icon" v-html="iconMarkup(key==='none'?'kanban':key==='author'?'user':'bookmark',18)"/><span class="kb-choice-copy"><strong>{{ label }}</strong><small>{{ key==='none'?copy.group_none_hint:key==='author'?copy.group_author_hint:copy.group_taxonomy_hint }}</small></span><span class="kb-choice-check" v-html="iconMarkup('check',16)"/></label></div>
        </WorkflowSettingsDialog>
        <WorkflowSettingsDialog ref="filtersDialog" class="kb-board-filters" :title="copy.card_filters" :intro="copy.filters_hint" :close-label="copy.close" @close="flushLive">
            <form id="kb-filter-form" @submit.prevent="applyFilters">
                <div v-if="!filterDraft.length" class="kb-filter-empty"><span v-html="iconMarkup('filter',22)"/><strong>{{ copy.no_filters_title }}</strong><p>{{ copy.no_filters_hint }}</p></div>
                <section v-for="(filter,index) in filterDraft" :key="index" class="kb-view-filter">
                    <header><span class="kb-filter-index">{{ number(index+1) }}</span><strong>{{ copy.filter_number.replace(':number',number(index+1)) }}</strong><button type="button" class="kb-filter-remove" :aria-label="copy.remove" :title="copy.remove" @click="filterDraft.splice(index,1)"><span v-html="iconMarkup('trash-2',16)"/></button></header>
                    <div class="kb-filter-fields"><div class="kb-filter-type"><strong>{{ copy.filter_type }}</strong><div class="kb-filter-driver-options"><label v-for="(driver,key) in config.filterCatalog" :key="key" :class="{'is-selected':filter.driver===key}"><input v-model="filter.driver" type="radio" :name="`kb-filter-driver-${index}`" :value="key" @change="resetFilter(filter)"/><span v-html="iconMarkup(key==='author'?'user':'bookmark',16)"/>{{ driver.label }}</label></div></div>
                    <label v-for="field in config.filterCatalog?.[filter.driver]?.fields||[]" :key="field.key" class="kb-filter-value"><span>{{ field.label }}</span><select v-model="filter.config[field.key]" :class="{'cms-select--multiple':field.multiple}" :multiple="!!field.multiple" :data-placeholder="copy.choose_filter_values.replace(':field',field.label)" :aria-label="field.label" @change="filterChanged(filter,field)"><option v-for="option in filterOptions(filter,field)" :key="option.id" :value="option.id">{{ option.name }}</option></select><small v-if="field.multiple && !(filter.config[field.key]||[]).length" class="kb-filter-placeholder">{{ copy.choose_filter_values.replace(':field',field.label) }}</small></label></div>
                    <p class="kb-filter-help">{{ filter.driver==='author'?copy.author_filter_hint:copy.taxonomy_filter_hint }}</p>
                </section>
                <button type="button" class="kb-add-filter" @click="addFilter"><span v-html="iconMarkup('plus',16)"/>{{ copy.add_filter }}</button>
            </form>
            <template #footer><button type="submit" form="kb-filter-form" class="rf-btn rf-btn--primary">{{ copy.apply_filters }}</button></template>
        </WorkflowSettingsDialog>
        <dialog ref="bulkDialog" class="kb-dialog kb-bulk" @cancel="busy&&$event.preventDefault()" @close="bulkGeneration++;flushLive()">
            <form @submit.prevent="executeBulk"><header class="kb-bulk-head"><span class="kb-bulk-head-icon" v-html="iconMarkup('arrows-left-right',20)"/><div><h2>{{ copy.bulk_move }}</h2><p>{{ copy.selected_cards.replace(':count',number(selected.size)) }}</p></div><button type="button" :disabled="busy" :aria-label="copy.close" @click="bulkDialog.close()"><span v-html="iconMarkup('x',18)"/></button></header><div class="kb-bulk-body">
                <p v-if="bulkLoading" role="status">{{ copy.loading }}</p><p v-if="bulkError" class="kb-error" role="alert">{{ bulkError }}</p>
                <template v-if="!bulkLoading&&!bulkResults.length"><div class="kb-bulk-destination"><strong>{{ copy.choose_destination }}</strong><p>{{ copy.bulk_destination_hint }}</p><select v-model="bulkTarget" :disabled="busy" :aria-label="copy.choose_destination" @change="bulkTargetChanged"><option value="">{{ copy.choose_destination }}</option><option v-for="target in bulkTargets" :key="target.status" :value="target.status">{{ target.name }}</option></select></div><p v-if="bulkCards.length&&!bulkTargets.length" class="kb-hint">{{ copy.bulk_no_target }}</p>
                    <div v-if="bulkCards.length" class="kb-bulk-list"><div v-for="item in bulkCards" :key="item.card.id" class="kb-bulk-row"><div class="kb-bulk-card-label"><span v-html="iconMarkup('file-text',17)"/><div><strong>{{ item.card.title }}</strong><small>#{{ item.card.id }}</small></div></div><label v-if="bulkTarget"><span>{{ copy.choose_transition }}</span><select v-model="bulkChoices[item.card.id]" :disabled="busy" :aria-label="copy.move+' '+item.card.title"><option value="">{{ copy.choose_transition }}</option><option v-for="target in item.destinations.filter(row=>row.state===bulkTarget&&row.status!=='blocked')" :key="target.transition" :value="target.transition">{{ target.label }}{{ target.status==='unknown'?' ⚠':'' }}</option></select></label></div></div>
                    <label v-if="bulkNeedsDate" class="kb-bulk-due">{{ copy.due_label }}<input v-model="bulkDue" dir="ltr" placeholder="YYYY/MM/DD HH:mm"/><small>{{ copy.due_hint }}</small></label>
                </template>
                <ul v-else-if="bulkResults.length" class="kb-bulk-results"><li v-for="result in bulkResults" :key="result.id" :class="{'is-error':!result.ok}"><strong>#{{ result.id }} · {{ bulkCards.find(item=>item.card.id===result.id)?.card.title }}</strong><span>{{ result.message }}</span></li></ul>
            </div><footer><button v-if="!bulkResults.length" type="submit" class="rf-btn rf-btn--primary" :disabled="busy||bulkLoading||!bulkTargets.length">{{ copy.bulk_execute }}</button></footer></form>
        </dialog>
        <dialog ref="detailDialog" class="kb-dialog kb-details" @cancel.prevent="closeDetails">
            <header><div><span class="kb-eyebrow">{{ copy.details }} <span v-if="detail">#{{ detail.card.id }}</span></span><h2>{{ detail?.card.title }}</h2></div><button type="button" :disabled="busy" :aria-label="copy.close" @click="closeDetails">×</button></header>
            <p v-if="detailError" class="kb-error" role="alert">{{ detailError }}</p>
            <p v-else-if="!detail" class="kb-loading">{{ copy.details_loading }}</p>
            <template v-else>
                <div class="kb-detail-toolbar"><label for="kb-editor-locale">{{ copy.locale }}</label><select id="kb-editor-locale" :value="editorLocale" @change="localeChange"><option v-for="locale in config.locales" :key="locale.code" :value="locale.code">{{ locale.name }}</option></select></div>
                <div v-if="detail.edit_url" class="kb-frame-wrap"><p v-if="frameLoading" class="kb-loading">{{ copy.loading }}</p><iframe ref="editorFrame" :src="editorUrl" :title="copy.edit" @load="frameLoading=false"/></div>
                <div v-else class="kb-readonly"><p>{{ detail.excerpt }}</p><div class="editor-document" v-html="detail.content_html"/><dl><dt>{{ copy.author }}</dt><dd>{{ detail.card.author }}</dd><dt>{{ copy.updated }}</dt><dd>{{ detail.card.updated_at }}</dd></dl><p>{{ copy.read_only }}</p></div>
                <div v-if="config.editable && detail.destinations.length" class="kb-detail-transitions"><button v-for="target in detail.destinations" :key="target.transition" type="button" :disabled="busy || target.status === 'blocked'" :title="target.checks.map(c => c.message).filter(Boolean).join(' · ')" @click="detailMove(target)">{{ target.label }}</button></div>
            </template>
        </dialog>
        <dialog ref="scheduleDialog" class="kb-dialog kb-settings" @cancel="busy && $event.preventDefault()">
            <form @submit.prevent="scheduleSave"><header><h2>{{ copy.needs_due }}</h2><button type="button" :disabled="busy" :aria-label="copy.close" @click="scheduleDialog.close(); pendingDrop=null">×</button></header>
                <label for="kb-due">{{ copy.due_label }}</label><input id="kb-due" v-model="dueLocal" autocomplete="off" placeholder="YYYY/MM/DD HH:mm" dir="ltr" :disabled="busy" :aria-invalid="!!dueError" aria-describedby="kb-due-hint kb-due-error"/>
                <p id="kb-due-hint" class="kb-hint">{{ copy.due_hint }}</p><p v-if="dueError" id="kb-due-error" class="kb-error" role="alert">{{ dueError }}</p><footer><button type="submit" class="kb-primary" :disabled="busy">{{ copy.continue }}</button></footer>
            </form>
        </dialog>
        <WorkflowToast :close-label="copy.close" />
    </main>
</template>

