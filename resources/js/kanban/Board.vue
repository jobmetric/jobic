<script setup>
import {computed, nextTick, onBeforeUnmount, onMounted, reactive, ref} from 'vue';
import {pageData, loadingButton} from '../common/page';
import {sendSettings} from '../admin/settings';
import {announce} from '../common/toast';
import {enhanceSelects} from '../common/selects';
import {connectKanban} from './realtime';
import {iconMarkup} from '../common/icons';
import $ from '../vuero/admin/jquery';
import WorkflowPageHeader from '../workflow/components/WorkflowPageHeader.vue';
import WorkflowSettingsDialog from '../workflow/components/WorkflowSettingsDialog.vue';
import WorkflowToast from '../workflow/components/WorkflowToast.vue';
import '../../css/kanban.css';
import '../../css/posts.css';

const config = pageData().kanban;
const copy = reactive(config.copy);
const activeLocale=ref(document.documentElement.lang);
function refreshPresentation(){const next=pageData().kanban;if(next?.copy)Object.assign(copy,next.copy);if(next?.typeLabel)config.typeLabel=next.typeLabel;if(next?.calendar)config.calendar=next.calendar;activeLocale.value=document.documentElement.lang;if(document.querySelector('.kb-calendar-dialog[open]'))loadCalendar();if(document.querySelector('.kb-insights-dialog[open]'))openInsights();if(workDateDialog.value?.open)nextTick(initWorkDatePicker);}
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
const discardDialog=ref(null);
let resolveDiscard=null;
let boardLeaving=false;
function confirmDiscard(){
    return new Promise(resolve=>{resolveDiscard=resolve;discardDialog.value?.showModal();});
}
function finishDiscard(accepted){
    const resolve=resolveDiscard;resolveDiscard=null;discardDialog.value?.close();resolve?.(accepted);
}
function hasUnsavedCardChanges(){
    if(!detailDialog.value?.open)return false;
    const workChanged=workDraft.value&&JSON.stringify(workDraft.value)!==workOriginal.value;
    let editorChanged=false;
    try{editorChanged=Boolean(editorFrame.value?.contentWindow?.kanbanEditor?.isDirty());}catch{}
    return Boolean(commentDraft.value.trim()||workChanged||editorChanged);
}
function beforeBoardUnload(event){
    if(boardLeaving||!hasUnsavedCardChanges())return;
    event.preventDefault();event.returnValue='';
}
async function boardBack(event){
    if(event.defaultPrevented||event.button!==0||event.ctrlKey||event.metaKey||event.shiftKey||event.altKey)return;
    if(!hasUnsavedCardChanges())return;
    event.preventDefault();
    const destination=event.currentTarget.href;
    if(await canLeaveEditor()){boardLeaving=true;location.assign(destination);}
}
const scheduleDialog = ref(null);
const detail = ref(null);
const detailTab = ref('discussion');
const detailWorkspaceOpen=ref(true);
function selectDetailTab(tab){detailTab.value=tab;detailWorkspaceOpen.value=true;}
function detailTabKeydown(event){
    if(!['ArrowLeft','ArrowRight','Home','End'].includes(event.key))return;
    event.preventDefault();
    const tabs=['discussion','work'];
    const direction=document.documentElement.dir==='rtl'?-1:1;
    const index=event.key==='Home'?0:event.key==='End'?1:(tabs.indexOf(detailTab.value)+(event.key==='ArrowRight'?direction:-direction)+tabs.length)%tabs.length;
    selectDetailTab(tabs[index]);
    nextTick(()=>detailDialog.value?.querySelector(`[data-detail-tab="${detailTab.value}"]`)?.focus());
}
const detailError = ref('');
const commentDraft=ref('');
const commentBusy=ref(false);
const olderCommentsBusy=ref(false);
const commentError=ref('');
const commentCandidates=ref([]);
const reviewerSearch=ref('');
const reviewerCandidates=ref([]);
const chosenReviewers=ref([]);
const reviewBusy=ref(false);
const reviewError=ref('');
const notificationDialog=ref(null);
const notificationSettingsOpen=ref(false);
const notificationItems=ref([]);
const notificationUnread=ref(0);
const notificationLoading=ref(false);
const notificationError=ref('');
const notificationBusy=ref(false);
const notificationPreferences=ref({mention:true,assignment:true,deadline:true,review_requested:true,review_decided:true});
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
let detailRefreshGeneration = 0;
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
const savedGroupBy=ref(groupBy.value);
const groupBusy=ref(false);
const density=ref(config.workspace?.density||'full');
const cardFieldChoices=[{key:'image',icon:'file-image'},{key:'terms',icon:'bookmark'},{key:'author',icon:'user'},{key:'assignee',icon:'user-check'},{key:'updated',icon:'calendar'},{key:'age',icon:'clock'},{key:'scheduled',icon:'calendar'},{key:'deadline',icon:'bell'},{key:'dependencies',icon:'link'}];
const cardFields=ref(config.workspace?.card_fields||cardFieldChoices.map(field=>field.key));
const summary=ref({total:0,scheduled:0,stale:0});
const summaryFilter=ref('');
const options=ref(config.options||{version:0,stale_days:7,limits:[]});
const staleDays=ref(options.value.stale_days);
const colorMode=ref(options.value.color_mode||'signals');
function cardTone(card){if(options.value.color_mode==='none')return '';if(options.value.color_mode==='priority')return card.priority==='urgent'?'urgent':card.priority==='high'?'high':'';return card.due_state==='overdue'?'overdue':card.review_status==='pending'?'review':card.priority==='urgent'?'urgent':card.priority==='high'?'high':'';}
const limitDraft=ref([]);
const settingsOptionsBaseline=ref(null);
function openSettings(){syncLimits();settingsOptionsBaseline.value=JSON.parse(JSON.stringify(options.value));settingsDialog.value?.showModal();}
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
const memberAvatarErrors=ref(new Set());
const members=ref([]);
const memberTotal=ref(0);
const headerMembers=ref([]);
const headerMemberTotal=ref(0);
const memberNext=ref(null);
const memberSearch=ref('');
const memberRole=ref(0);
const memberSort=ref('name');
const memberRoleOptions=ref([]);
const mine=ref(false);
const assigneeFilter=ref(null);
const calendarDialog=ref(null);
const calendarData=ref(null);
const calendarView=ref('calendar');
const calendarOffset=ref(0);
const calendarLoading=ref(false);
const calendarError=ref('');
const calendarLeading=computed(()=>calendarData.value?.days?.length?((calendarData.value.days[0].weekday-(activeLocale.value==='fa'?6:1)+7)%7):0);
const calendarWeekdays=computed(()=>activeLocale.value==='fa'?[copy.weekday_sat,copy.weekday_sun,copy.weekday_mon,copy.weekday_tue,copy.weekday_wed,copy.weekday_thu,copy.weekday_fri]:[copy.weekday_mon,copy.weekday_tue,copy.weekday_wed,copy.weekday_thu,copy.weekday_fri,copy.weekday_sat,copy.weekday_sun]);
const calendarEventsByDate=computed(()=>{const dates=new Map();for(const event of calendarData.value?.events||[]){const items=dates.get(event.date)||[];items.push(event);dates.set(event.date,items);}return dates;});
const calendarAgendaDays=computed(()=>(calendarData.value?.days||[]).filter(day=>calendarEventsByDate.value.has(day.date)));
const calendarRows=computed(()=>{const labels=new Map((calendarData.value?.days||[]).map(day=>[day.date,day.label]));return (calendarData.value?.events||[]).map((event,index)=>({...event,label:labels.get(event.date)||event.date,index})).sort((a,b)=>a.date.localeCompare(b.date)||a.index-b.index);});
const insightsDialog=ref(null);
const insightsData=ref(null);
const insightsTotal=computed(()=>insightsData.value?.states?.reduce((total,state)=>total+Number(state.count||0),0)||0);
const insightsStale=computed(()=>insightsData.value?.states?.reduce((total,state)=>total+Number(state.stale||0),0)||0);
const insightsAverage=computed(()=>insightsTotal.value?Math.round((insightsData.value?.states||[]).reduce((total,state)=>total+Number(state.count||0)*Number(state.average_days||0),0)/insightsTotal.value):0);
const bottleneckState=computed(()=>[...(insightsData.value?.states||[])].filter(state=>state.count>0).sort((a,b)=>Number(b.average_days)-Number(a.average_days)||Number(b.stale)-Number(a.stale))[0]?.status||null);
const insightColors=['#2eae98','#6685e5','#e6a94c','#a973d8','#de6e86'];
const insightsLoading=ref(false);
const insightsError=ref('');
const workDraft=ref(null);
const workOriginal=ref('');
const workBusy=ref(false);
const workError=ref('');
const workDateDialog=ref(null);
const workDateInput=ref(null);
const workDateTrigger=ref(null);
const workDateValue=ref('');
let pickerCalendar=null;
const assigneeSearch=ref('');
const assigneeOptions=ref([]);
const dependencySearch=ref('');
const dependencyOptions=ref([]);
const dependencyLoading=ref(false);
const dependencyError=ref('');
let dependencyGeneration=0;
let assigneeTimer;
let dependencyTimer;
const memberError=ref('');
const membersLoading=ref(false);
const membersDialog=ref(null);
const memberRoleMenu=ref(null);
let memberGeneration=0;
let memberSearchTimer;
let memberFocus=null;
async function loadMembers(reset=false){
    if(!config.membersUrl||(!reset&&membersLoading.value))return;
    if(reset){memberGeneration++;members.value=[];memberNext.value=null;memberTotal.value=0;}
    const generation=memberGeneration;
    membersLoading.value=true;memberError.value='';
    try{
        const url=new URL(config.membersUrl,location.origin);
        if(config.boardVersion)url.searchParams.set('board_version',config.boardVersion);
        if(memberNext.value)url.searchParams.set('member_cursor',memberNext.value);
        if(memberSearch.value.trim())url.searchParams.set('member_search',memberSearch.value.trim());
        if(memberRole.value)url.searchParams.set('member_role',memberRole.value);
        url.searchParams.set('member_sort',memberSort.value);
        const response=await fetch(url,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});
        const result=await response.json();
        if(!response.ok||!result.ok)throw new Error(result.message||copy.failed);
        if(generation!==memberGeneration)return;
        const seen=new Set(members.value.map(member=>member.id));
        members.value.push(...result.data.items.filter(member=>!seen.has(member.id)));
        memberTotal.value=result.data.total;memberNext.value=result.data.next_cursor;
        if(result.data.role_options)memberRoleOptions.value=result.data.role_options;
        if(!memberSearch.value.trim()&&!url.searchParams.has('member_cursor')){headerMembers.value=result.data.items.slice(0,4);headerMemberTotal.value=result.data.total;}
    }catch(error){if(generation===memberGeneration)memberError.value=error.message;}
    finally{if(generation===memberGeneration)membersLoading.value=false;}
}
function openMembers(event){memberFocus=event.currentTarget;memberSearch.value='';memberRole.value=0;memberSort.value='name';membersDialog.value.showModal();loadMembers(true);nextTick(()=>membersDialog.value?.querySelector('input')?.focus());}
function searchMembers(){clearTimeout(memberSearchTimer);memberSearchTimer=setTimeout(()=>loadMembers(true),300);}
function closeMembers(){membersDialog.value?.close();}
function membersClosed(){memberGeneration++;membersLoading.value=false;memberFocus?.focus();flushLive();}
function chooseMemberRole(id){memberRole.value=id;if(memberRoleMenu.value)memberRoleMenu.value.open=false;loadMembers(true);}
function toggleMine(){mine.value=!mine.value;assigneeFilter.value=null;selected.value=new Map();reload();}
function filterMember(member){assigneeFilter.value={id:member.id,name:member.name};mine.value=false;closeMembers();selected.value=new Map();reload();}
async function loadCalendar(){calendarLoading.value=true;calendarError.value='';try{const url=new URL(config.calendarUrl,location.origin);url.searchParams.set('calendar_offset',calendarOffset.value);if(config.boardVersion)url.searchParams.set('board_version',config.boardVersion);calendarData.value=await get(url);}catch(error){calendarError.value=error.message;}finally{calendarLoading.value=false;}}
function openCalendar(){calendarOffset.value=0;calendarDialog.value.showModal();loadCalendar();}
function calendarStep(delta){calendarOffset.value=Math.max(-12,Math.min(12,calendarOffset.value+delta));loadCalendar();}
async function openInsights(){insightsDialog.value.showModal();insightsLoading.value=true;insightsError.value='';try{insightsData.value=await get(config.insightsUrl);}catch(error){insightsError.value=error.message;}finally{insightsLoading.value=false;}}
async function searchAssignees(){const url=new URL(config.membersUrl,location.origin);if(config.boardVersion)url.searchParams.set('board_version',config.boardVersion);if(assigneeSearch.value.trim())url.searchParams.set('member_search',assigneeSearch.value.trim());try{const response=await fetch(url,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});const result=await response.json();if(response.ok&&result.ok)assigneeOptions.value=result.data.items;}catch{assigneeOptions.value=[];}}
function assigneeChanged(){clearTimeout(assigneeTimer);assigneeTimer=setTimeout(searchAssignees,250);}
function initWorkDatePicker(){
    const input=workDateInput.value;
    if(!input||typeof $.fn?.datetimepicker!=='function')return;
    const jalali=['jalali','persian'].includes(config.calendar);
    if(pickerCalendar!==config.calendar){
        if(input.classList.contains('hasDatepicker'))$(input).datetimepicker('destroy');
        $(input).datetimepicker({dateFormat:jalali?'yy/mm/dd':'yy-mm-dd',timeFormat:'HH:mm',separator:' ',showButtonPanel:false,changeMonth:true,changeYear:true,controlType:'select',oneLine:false,showTimezone:false,showTime:true,onSelect:function(dateText,timepicker){
            if(jalali){const instance=$.datepicker?._getInst(this);if(instance){const pad=value=>String(value).padStart(2,'0');this.value=`${instance.selectedYear}/${pad(instance.selectedMonth+1)}/${pad(instance.selectedDay)} ${timepicker?.formattedTime||String(dateText).match(/([0-2]?\d:[0-5]\d)/)?.[1]||'00:00'}`;}}
            workDateValue.value=this.value;
        }});
        pickerCalendar=config.calendar;
    }
    const picker=document.querySelector('#ui-datepicker-div')||$.datepicker?.dpDiv?.[0];
    const host=workDateDialog.value?.querySelector('.kb-work-date-picker');
    if(picker&&host&&picker.parentNode!==host)host.append(picker);
    $(input).datetimepicker('show');
}
function openWorkDate(){
    workDateValue.value=workDraft.value?.due_local||'';
    workDateDialog.value?.showModal();
    nextTick(()=>{if(workDateInput.value){workDateInput.value.value=workDateValue.value;initWorkDatePicker();workDateInput.value.focus();}});
}
function closeWorkDate(){
    workDateDialog.value?.close();
    nextTick(()=>workDateTrigger.value?.focus());
}
function applyWorkDate(){if(workDraft.value)workDraft.value.due_local=workDateInput.value?.value.trim()||'';closeWorkDate();}
async function searchDependencies(){
    const query=dependencySearch.value.trim();const generation=++dependencyGeneration;
    if(query.length<2){dependencyOptions.value=[];dependencyLoading.value=false;dependencyError.value='';return;}
    dependencyLoading.value=true;dependencyError.value='';
    try{const url=new URL(config.dataUrl,location.origin);url.searchParams.set('search',query);url.searchParams.set('group_by','none');if(config.boardVersion)url.searchParams.set('board_version',config.boardVersion);const response=await fetch(url,{headers:{Accept:'application/json','X-Requested-With':'XMLHttpRequest'},credentials:'same-origin'});const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.message||copy.failed);if(generation===dependencyGeneration){const seen=new Set();dependencyOptions.value=(result.data.columns||[]).flatMap(column=>(column.items||[]).map(card=>({...card,column_name:column.name}))).filter(card=>{if(card.id===detail.value?.card.id||seen.has(card.id)||workDraft.value?.dependencies.some(item=>item.id===card.id))return false;seen.add(card.id);return true;});}}
    catch(error){if(generation===dependencyGeneration){dependencyOptions.value=[];dependencyError.value=error.message;}}
    finally{if(generation===dependencyGeneration)dependencyLoading.value=false;}
}
function dependencyChanged(){clearTimeout(dependencyTimer);dependencyTimer=setTimeout(searchDependencies,250);}
function addDependency(card){if(workDraft.value.dependencies.length<20&&!workDraft.value.dependencies.some(item=>item.id===card.id))workDraft.value.dependencies.push({id:card.id,title:card.title});dependencySearch.value='';dependencyOptions.value=[];dependencyGeneration++;}
async function saveWork(event){if(!detail.value||!workDraft.value||workBusy.value)return;const restore=loadingButton(event.submitter);workBusy.value=true;workError.value='';try{const data=new FormData();data.set('operation','card_work');data.set('post_id',detail.value.card.id);data.set('meta_version',workDraft.value.version);data.set('assignee_id',workDraft.value.assignee_id||'');data.set('work_due_local',workDraft.value.due_local||'');data.set('dependency_ids',JSON.stringify(workDraft.value.dependencies.map(item=>item.id)));data.set('priority',workDraft.value.priority||'normal');if(config.boardVersion)data.set('board_version',config.boardVersion);const result=await sendSettings(config.commandUrl,data);detail.value.work=result.data.work;workDraft.value.version=result.data.work.version;workOriginal.value=JSON.stringify(workDraft.value);announce(result.message);await reload(true,true);}catch(error){workError.value=Object.values(error.errors||{})[0]?.[0]||error.message;if(error.status===409){await saved();if(workDraft.value&&detail.value?.work)workDraft.value.version=detail.value.work.version;}}finally{workBusy.value=false;restore();}}
const imageErrors=ref(new Set());
let disconnectLive=()=>{};
let liveTimer;
let liveFallbackTimer;
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
    staleDays.value=options.value.stale_days||7;colorMode.value=options.value.color_mode||'signals';
}
function filterOptions(filter,field){return field.dependsOn?(config.filterCatalog?.[filter.driver]?.fields.find(row=>row.key===field.key)?.optionsBy?.[filter.config[field.dependsOn]]||[]):field.options||[];}
function syncSelects(root){if(!root)return;if(window.jQuery?.fn.select2)root.querySelectorAll('select.select2-hidden-accessible').forEach(select=>window.jQuery(select).off('.cms').select2('destroy'));enhanceSelects(root);}
function resetFilter(filter){const definition=config.filterCatalog[filter.driver];filter.config={};for(const field of definition?.fields||[])filter.config[field.key]=field.multiple?[]:(field.options?.[0]?.id||'');nextTick(()=>syncSelects(document.querySelector('#kb-filter-form')));}
function addFilter(){const driver=Object.keys(config.filterCatalog||{})[0];if(!driver)return;const filter={driver,config:{}};resetFilter(filter);filterDraft.value.push(filter);nextTick(()=>enhanceSelects(document.querySelector('#kb-filter-form')));}
function filterChanged(filter,field){for(const dependent of config.filterCatalog[filter.driver].fields.filter(row=>row.dependsOn===field.key))filter.config[dependent.key]=[];nextTick(()=>{const area=document.querySelector('#kb-filter-form');syncSelects(area);});}
function applyFilters(){appliedFilters.value=JSON.parse(JSON.stringify(filterDraft.value));filtersDialog.value.close();selected.value=new Map();reload();}
function clearFilters(){search.value='';appliedFilters.value=[];filterDraft.value=[];summaryFilter.value='';mine.value=false;assigneeFilter.value=null;selected.value=new Map();reload();}
function searchChanged(){clearTimeout(searchTimer);searchTimer=setTimeout(()=>{selected.value=new Map();reload();},300);}
async function changeGrouping(){
    if(groupBusy.value)return;
    const next=groupBy.value;
    if(next===savedGroupBy.value){groupDialog.value?.close();return;}
    groupBusy.value=true;busy.value=true;
    try{
        const data=new FormData();data.set('operation','settings');data.set('page_size',pageSize.value);data.set('group_by',next);
        if(config.boardVersion)data.set('board_version',config.boardVersion);
        const result=await sendSettings(config.commandUrl,data);
        savedGroupBy.value=next;groupDialog.value?.close();selected.value=new Map();announce(result.message);await reload();
    }catch(error){groupBy.value=savedGroupBy.value;announce(error.message,true);if(error.status===409)await reload();}
    finally{groupBusy.value=false;busy.value=false;flushLive();}
}
function chipFilter(value){summaryFilter.value=summaryFilter.value===value?'':value;selected.value=new Map();reload();}
function liveUnsafe(){return busy.value||!!drag.value||dropping||initialLoading.value;}
let liveRefreshing=false;
function scheduleLive(delay=500){clearTimeout(liveTimer);liveTimer=setTimeout(refreshLive,delay);}
function invalidated(){liveRevision++;livePending.value=true;scheduleLive(700);}
function flushLive(){if(livePending.value)scheduleLive(100);}
async function refreshLive(){
    if(!livePending.value||liveRefreshing)return;
    if(liveUnsafe()){scheduleLive(400);return;}
    liveRefreshing=true;
    try{
        await loadNotifications();
        const refreshed=await reload(true,true);
        if(refreshed&&detailDialog.value?.open&&detail.value)await refreshDetail();
        if(livePending.value)scheduleLive(refreshed?350:3000);
    }finally{liveRefreshing=false;}
}
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
    if (mine.value) url.searchParams.set('mine','1');
    else if (assigneeFilter.value) url.searchParams.set('assignee_id',assigneeFilter.value.id);
    url.searchParams.set('group_by',groupBy.value);
    if (config.boardVersion && !fresh) url.searchParams.set('board_version',config.boardVersion);
    const response = await fetch(url, {headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'});
    const result = await response.json();
    if (!response.ok || !result.ok) throw Object.assign(new Error(result.message || copy.failed), {status: response.status});
    return result.data;
}
async function reload(silent = false, preservePages = false) {
    const scroll=new Map([...document.querySelectorAll('[data-kanban-column]')].map(element=>[element.dataset.columnKey,element.querySelector('.kb-column-body')?.scrollTop||0]));
    const loaded=preservePages?new Map(columns.value.map(column=>[columnKey(column),column.items.length])):null;
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
        if(loaded){
            let complete=true;
            await Promise.all(columns.value.map(async column=>{
                const previous=loaded.get(columnKey(column))||0;
                while(generation===readGeneration&&column.cursor&&column.items.length<previous){
                    if(!await more(column)){complete=false;break;}
                }
            }));
            if(generation!==readGeneration)return false;
            if(!complete){livePending.value=true;return false;}
        }
        if(!document.querySelector('.kb-board-settings[open]'))syncLimits();
        await nextTick();if(silent)document.querySelectorAll('[data-kanban-column]').forEach(element=>{const body=element.querySelector('.kb-column-body');if(body)body.scrollTop=scroll.get(element.dataset.columnKey)||0;});
        return true;
    } catch (error) { if (generation === readGeneration) boardError.value = error.message; return false; }
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
        savedGroupBy.value=groupBy.value;
        pageSize.value=result.data.page_size;selectedSize.value=result.data.page_size;
        if(config.editable){
            const limits=limitDraft.value.map(({state,limit,mode})=>({state,limit:Number(limit),mode}));
            const baseline=settingsOptionsBaseline.value||options.value;
            const changed=JSON.stringify(limits.filter(row=>row.limit))!==JSON.stringify((baseline.limits||[]).filter(row=>Number(row.limit)))||Number(staleDays.value)!==Number(baseline.stale_days||7)||colorMode.value!==(baseline.color_mode||'signals');
            if(changed){const shared=new FormData();shared.set('operation','limits');shared.set('limits',JSON.stringify(limits));shared.set('stale_days',staleDays.value);shared.set('color_mode',colorMode.value);shared.set('options_version',baseline.version);if(config.boardVersion)shared.set('board_version',config.boardVersion);await sendSettings(config.commandUrl,shared);}
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
    detailTab.value='discussion';detailWorkspaceOpen.value=true;
    detailDialog.value.showModal();
    try {
        const value = await get(config.detailUrl.replace('__ID__', card.id));
        if (generation !== detailGeneration) return;
        detail.value = value; editorLocale.value = value.locale ?? document.documentElement.lang;
        commentDraft.value='';commentError.value='';commentCandidates.value=[];reviewerSearch.value='';reviewerCandidates.value=[];chosenReviewers.value=[];reviewError.value='';
        workDraft.value={version:value.work?.version||0,assignee_id:value.work?.assignee?.id||'',due_local:value.work?.due_at?.replace(/[،,]\s*/,' ')||'',priority:value.work?.priority||'normal',dependencies:[...(value.work?.dependencies||[])]};
        workOriginal.value=JSON.stringify(workDraft.value);
        assigneeSearch.value='';dependencySearch.value='';dependencyOptions.value=[];dependencyGeneration++;dependencyLoading.value=false;dependencyError.value='';searchAssignees();
        frameLoading.value = Boolean(value.edit_url);
        await nextTick(); enhanceSelects(detailDialog.value);
    } catch (error) { if (generation === detailGeneration) detailError.value = error.message; }
}
function restoreFocus() {
    const replacement = lastCardId ? document.querySelector(`[data-kanban-card="${lastCardId}"] .kb-card-title`) : null;
    (lastFocus?.isConnected ? lastFocus : replacement ?? document.querySelector('.rf-header__back'))?.focus();
}
async function canLeaveEditor() {
    const dirty=hasUnsavedCardChanges();
    if(dirty&&!await confirmDiscard())return false;
    try {
        const editor = editorFrame.value?.contentWindow?.kanbanEditor;
        const allowed=editor ? await editor.canClose({confirmed:dirty}) : true;
        if(allowed&&dirty){commentDraft.value='';if(workOriginal.value)workDraft.value=JSON.parse(workOriginal.value);}
        return allowed;
    } catch { return true; }
}
function commentText(body){return String(body||'').replace(/@\[([^\]]+)\]\(user:\d+\)/g,'@$1');}
async function commentChanged(){
    const match=commentDraft.value.match(/(?:^|\s)@([^@\s]{0,40})$/u);
    if(!match){commentCandidates.value=[];return;}
    try{const url=new URL(config.membersUrl,location.origin);url.searchParams.set('member_search',match[1]);if(config.boardVersion)url.searchParams.set('board_version',config.boardVersion);const data=await get(url);if(commentDraft.value.match(/(?:^|\s)@([^@\s]{0,40})$/u)?.[1]===match[1])commentCandidates.value=data.items||[];}catch{commentCandidates.value=[];}
}
function insertMention(member){commentDraft.value=commentDraft.value.replace(/@[^@\s]*$/u,`@[${member.name.replace(/[\[\]]/g,'')}](user:${member.id}) `);commentCandidates.value=[];nextTick(()=>document.querySelector('#kb-comment-body')?.focus());}
async function saveComment(event){
    if(commentBusy.value||!detail.value)return;
    const restore=loadingButton(event.submitter);commentBusy.value=true;commentError.value='';
    try{const data=new FormData();data.set('operation','comment');data.set('post_id',detail.value.card.id);data.set('comment_body',commentDraft.value.trim());if(config.boardVersion)data.set('board_version',config.boardVersion);const result=await sendSettings(config.commandUrl,data);detail.value.discussion=result.data.discussion;commentDraft.value='';commentCandidates.value=[];announce(result.message);}
    catch(error){commentError.value=error.errors?.comment_body?.[0]||error.message;announce(commentError.value,true);}finally{commentBusy.value=false;restore();}
}
async function loadOlderComments(){
    const cursor=detail.value?.discussion?.cursor;
    if(!cursor||olderCommentsBusy.value)return;
    const postId=detail.value.card.id;
    olderCommentsBusy.value=true;commentError.value='';
    try{const url=new URL(config.detailUrl.replace('__ID__',postId),location.origin);url.searchParams.set('comment_cursor',cursor);if(config.boardVersion)url.searchParams.set('board_version',config.boardVersion);const data=await get(url);if(detail.value?.card.id!==postId)return;const seen=new Set(detail.value.discussion.items.map(item=>item.id));detail.value.discussion.items.push(...data.discussion.items.filter(item=>!seen.has(item.id)));detail.value.discussion.cursor=data.discussion.cursor;}
    catch(error){commentError.value=error.message;}finally{olderCommentsBusy.value=false;}
}
async function searchReviewers(){
    const url=new URL(config.membersUrl,location.origin);if(config.boardVersion)url.searchParams.set('board_version',config.boardVersion);url.searchParams.set('member_search',reviewerSearch.value.trim());
    try{const data=await get(url);reviewerCandidates.value=(data.items||[]).filter(member=>member.can_review&&!chosenReviewers.value.some(chosen=>chosen.id===member.id));}catch{reviewerCandidates.value=[];}
}
async function reviewCommand(operation,decision=''){
    if(reviewBusy.value||!detail.value)return;
    reviewBusy.value=true;reviewError.value='';
    try{const data=new FormData();data.set('operation',operation);data.set('post_id',detail.value.card.id);data.set('version',detail.value.card.version);data.set('revision_id',detail.value.card.revision_id);if(config.boardVersion)data.set('board_version',config.boardVersion);if(operation==='request_review')data.set('reviewer_ids',JSON.stringify(chosenReviewers.value.map(user=>user.id)));if(decision)data.set('decision',decision);const result=await sendSettings(config.commandUrl,data);announce(result.message);detail.value=await get(config.detailUrl.replace('__ID__',detail.value.card.id));chosenReviewers.value=[];reviewerSearch.value='';reviewerCandidates.value=[];await reload(true,true);}
    catch(error){reviewError.value=Object.values(error.errors||{})[0]?.[0]||error.message;announce(reviewError.value,true);}finally{reviewBusy.value=false;}
}
async function loadNotifications(open=false){
    if(open){notificationSettingsOpen.value=false;notificationDialog.value?.showModal();}notificationLoading.value=true;notificationError.value='';
    try{const url=new URL(config.notificationsUrl,location.origin);const data=await get(url);notificationItems.value=data.items||[];notificationUnread.value=data.unread||0;if(!notificationSettingsOpen.value)notificationPreferences.value=data.preferences||notificationPreferences.value;}
    catch(error){notificationError.value=error.message;}finally{notificationLoading.value=false;}
}
async function readNotification(item){
    if(notificationBusy.value)return;notificationBusy.value=true;
    const data=new FormData();data.set('operation','notification_read');data.set('notification_id',item.id);if(config.boardVersion)data.set('board_version',config.boardVersion);
    try{await sendSettings(config.commandUrl,data);if(!item.read)notificationUnread.value=Math.max(0,notificationUnread.value-1);item.read=true;notificationDialog.value?.close();await openCard({id:item.post_id});}catch(error){notificationError.value=error.message;}finally{notificationBusy.value=false;}
}
async function saveNotificationPreferences(event){
    if(notificationBusy.value)return;notificationBusy.value=true;notificationError.value='';const restore=loadingButton(event.submitter);
    try{const data=new FormData();data.set('operation','notification_preferences');data.set('notification_preferences',JSON.stringify(notificationPreferences.value));if(config.boardVersion)data.set('board_version',config.boardVersion);const result=await sendSettings(config.commandUrl,data);notificationPreferences.value=result.data.preferences;announce(result.message);}
    catch(error){notificationError.value=Object.values(error.errors||{})[0]?.[0]||error.message;announce(notificationError.value,true);}finally{notificationBusy.value=false;restore();}
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
    await reload(true,true);
    await refreshDetail();
}
async function refreshDetail() {
    const id = detail.value?.card.id;
    const generation = detailGeneration;
    const refreshGeneration=++detailRefreshGeneration;
    if (!id || !detailDialog.value.open || generation !== detailGeneration) return;
    try {
        const url = new URL(config.detailUrl.replace('__ID__', id), location.origin);
        if (editorLocale.value) url.searchParams.set('locale', editorLocale.value);
        const value = await get(url);
        if (generation === detailGeneration && refreshGeneration===detailRefreshGeneration && detailDialog.value.open) {const pendingWork=workDraft.value&&JSON.stringify(workDraft.value)!==workOriginal.value?JSON.parse(JSON.stringify(workDraft.value)):null;detail.value = value;workDraft.value={version:value.work?.version||0,assignee_id:value.work?.assignee?.id||'',due_local:value.work?.due_at?.replace(/[،,]\s*/,' ')||'',priority:value.work?.priority||'normal',dependencies:[...(value.work?.dependencies||[])]};workOriginal.value=JSON.stringify(workDraft.value);if(pendingWork)workDraft.value=pendingWork;}
    } catch (error) { if (generation === detailGeneration && refreshGeneration===detailRefreshGeneration) detailError.value = error.message; }
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
function over(event, column) {
    if (!drag.value || busy.value) return;
    const target = eligible(column);
    if (!drag.value.loading && (!target || target.status === 'blocked')) { if (event.dataTransfer) event.dataTransfer.dropEffect = 'none'; return; }
    event.preventDefault();
    const body=event.target.closest('.kb-column-body');
    const cards=body ? [...body.querySelectorAll('.kb-card')].filter(element=>Number(element.dataset.kanbanCard)!==drag.value.card.id) : [];
    const before=cards.find(element=>event.clientY<element.getBoundingClientRect().top+element.getBoundingClientRect().height/2)?.dataset.kanbanCard;
    const nextBefore=before?Number(before):null;
    const key=columnKey(column);
    if(dropAt.value?.key!==key||dropAt.value?.before!==nextBefore)dropAt.value={state:column.status,before:nextBefore,key};
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
onMounted(() => { reload();loadMembers(true);loadNotifications();disconnectLive=connectKanban(config.realtime,invalidated,(state,reason)=>{liveStatus.value=state;liveReason.value=reason||state;});liveFallbackTimer=setInterval(()=>{if(liveStatus.value!=='connected')invalidated();},20000); window.addEventListener('jobic:kanban:changed', changed); window.addEventListener('jobic:kanban:saved', saved); window.addEventListener('cms:presentation-refreshed',refreshPresentation); window.addEventListener('keydown', keydown);window.addEventListener('beforeunload',beforeBoardUnload); });
onBeforeUnmount(() => { disconnectLive();clearTimeout(liveTimer);clearInterval(liveFallbackTimer);clearTimeout(searchTimer);clearTimeout(memberSearchTimer);clearTimeout(assigneeTimer);clearTimeout(dependencyTimer);memberGeneration++;readGeneration++; detailGeneration++; window.removeEventListener('jobic:kanban:changed', changed); window.removeEventListener('jobic:kanban:saved', saved); window.removeEventListener('cms:presentation-refreshed',refreshPresentation); window.removeEventListener('keydown', keydown);window.removeEventListener('beforeunload',beforeBoardUnload); });
</script>

<template>
    <main class="kb-page rf-page" :class="{'is-compact':density==='compact','is-grouped':groupBy!=='none','has-selection':selected.size>0}" :aria-busy="busy" data-kanban-board>
        <WorkflowPageHeader class="kb-header" :title="`${copy.title} · ${config.boardName || config.typeLabel}`" :back-url="config.backUrl" :back-label="copy.back" @back="boardBack"
            :settings-label="copy.settings" :busy="busy" use-tooltips @settings="openSettings">
            <template #before-settings>
                <div class="kb-summary" role="group" :aria-label="copy.summary">
                    <button v-for="metric in [{key:'',field:'total',icon:'kanban'},{key:'scheduled',field:'scheduled',icon:'calendar'},{key:'stale',field:'stale',icon:'clock'}]" :key="metric.field" type="button" :class="{'is-active':summaryFilter===metric.key}" :aria-pressed="summaryFilter===metric.key" :aria-label="copy[`summary_${metric.field}`]+' '+number(summary[metric.field])" :data-tooltip="copy[`summary_${metric.field}`]" @click="chipFilter(metric.key)"><span v-html="iconMarkup(metric.icon,16)"/><strong>{{ number(summary[metric.field]) }}</strong></button>
                </div>
                <div v-if="headerMemberTotal" class="kb-members" role="group" :aria-label="copy.members">
                    <button v-for="member in headerMembers" :key="member.id" type="button" class="kb-member-avatar" :aria-label="member.name" :data-tooltip="member.name" @click="openMembers"><img v-if="member.avatar_url&&!memberAvatarErrors.has(member.avatar_url)" :src="member.avatar_url" alt="" @error="memberAvatarErrors.add(member.avatar_url)"/><span v-else>{{ Array.from(member.name||'').slice(0,1).join('') }}</span></button>
                    <button v-if="headerMemberTotal>4" type="button" class="kb-member-more" :aria-label="copy.members_show_all.replace(':count',number(headerMemberTotal))" :data-tooltip="copy.members_show_all.replace(':count',number(headerMemberTotal))" @click="openMembers"><span v-html="iconMarkup('plus',16)"/></button>
                </div>
                <div class="kb-tools">
                    <button v-if="assigneeFilter" type="button" class="kb-assignee-filter" :title="copy.clear_view" @click="assigneeFilter=null;reload()">{{ copy.assignee }}: {{ assigneeFilter.name }} <span aria-hidden="true">×</span></button>
                    <label class="kb-search" :data-tooltip="copy.search_cards"><span v-html="iconMarkup('search',16)"/><span class="kb-screen-reader">{{ copy.search_cards }}</span><input v-model="search" type="search" :placeholder="copy.search_cards" maxlength="160" @input="searchChanged"/></label>
                    <button type="button" class="rf-btn rf-btn--compact kb-tool-button" :aria-label="copy.filters" :data-tooltip="copy.filters" @click="filterDraft=JSON.parse(JSON.stringify(appliedFilters));filtersDialog.showModal()"><span v-html="iconMarkup('filter',16)"/><b v-if="appliedFilters.length">{{ number(appliedFilters.length) }}</b></button>
                    <button type="button" class="rf-btn rf-btn--compact kb-tool-button kb-group-trigger" :class="{'is-active':groupBy!=='none'}" :disabled="busy" :aria-label="copy.group_by" :data-tooltip="copy.group_by+': '+(config.groupChoices?.[groupBy]||copy.group_none)" @click="groupDialog.showModal()"><span v-html="iconMarkup('list-tree',16)"/></button>
                    <button type="button" class="rf-btn rf-btn--compact kb-tool-button" :class="{'is-active':mine}" :aria-pressed="mine" :aria-label="copy.my_work" :data-tooltip="copy.my_work" @click="toggleMine"><span v-html="iconMarkup('user-check',16)"/></button>
                    <button type="button" class="rf-btn rf-btn--compact kb-tool-button" :aria-label="copy.calendar" :data-tooltip="copy.calendar" @click="openCalendar"><span v-html="iconMarkup('calendar',16)"/></button>
                    <button type="button" class="rf-btn rf-btn--compact kb-tool-button" :aria-label="copy.insights" :data-tooltip="copy.insights" @click="openInsights"><span v-html="iconMarkup('chart-bar',16)"/></button>
                    <button type="button" class="rf-btn rf-btn--compact kb-tool-button kb-notification-trigger" :aria-label="copy.notifications" :data-tooltip="copy.notifications" @click="loadNotifications(true)"><span v-html="iconMarkup('bell',16)"/><b v-if="notificationUnread">{{ number(notificationUnread) }}</b></button>
                    <button v-if="search||appliedFilters.length||summaryFilter||mine||assigneeFilter" type="button" class="rf-btn rf-btn--compact kb-tool-button" :aria-label="copy.clear_view" :data-tooltip="copy.clear_view" @click="clearFilters"><span v-html="iconMarkup('x',16)"/></button>
                </div>
                <div v-if="selected.size" class="kb-selection-action">
                    <button type="button" class="rf-btn rf-btn--compact rf-btn--primary" :disabled="busy" :aria-label="copy.selection_actions" :data-tooltip="copy.selected_cards.replace(':count',number(selected.size))" :aria-expanded="selectionOpen" @click="openSelection"><span v-html="iconMarkup('check-circle',16)"/><span class="kb-selection-label">{{ copy.selection_actions }}</span><span>{{ number(selected.size) }}</span><span v-html="iconMarkup('chevron-down',14)"/></button>
                    <div ref="selectionMenu" popover="auto" @toggle="selectionOpen=$event.newState==='open'" class="kb-selection-menu"><strong>{{ copy.selected_cards.replace(':count',number(selected.size)) }}</strong><button type="button" :disabled="busy" @click="bulkFromMenu"><span v-html="iconMarkup('arrows-left-right',16)"/>{{ copy.bulk_move }}</button><button type="button" @click="selectionMenu.hidePopover();selected=new Map();flushLive()"><span v-html="iconMarkup('x',16)"/>{{ copy.clear_selection }}</button></div>
                </div>
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
                            :class="{'is-dragging': drag?.card.id === card.id,'is-selected':selected.has(card.id),[`is-tone-${cardTone(card)}`]:!!cardTone(card)}" @dragstart="startDrag(card, $event)" @dragover.stop="over($event, column)">
                            <img v-if="fields('image')&&card.image_url&&!imageErrors.has(card.image_url)" class="kb-card-image" :src="card.image_url" alt="" loading="lazy" @error="imageErrors.add(card.image_url)"/>
                            <input v-if="config.editable" type="checkbox" class="kb-card-checkbox" :checked="selected.has(card.id)" :aria-label="copy.select_card.replace(':title',card.title)" @click.stop @change="toggleSelected(card,$event)"/><div class="kb-card-top"><span v-if="card.priority==='urgent'||card.priority==='high'" class="kb-priority-chip" :title="copy.priority"><span v-html="iconMarkup('flag',13)"/>{{ copy[`priority_${card.priority}`] }}</span><span class="kb-card-kind"><span v-html="iconMarkup('file-text',17)"/>{{ config.typeLabel }}</span><span class="kb-card-id">#{{ card.id }}</span><span v-if="config.editable" class="kb-card-grip" :title="copy.drag_hint" v-html="iconMarkup('grip-vertical',18)"/></div>
                            <button type="button" class="kb-card-title" @click="openCard(card, $event)">{{ card.title }}</button>
                            <div v-if="fields('author')||fields('assignee')&&card.assignee" class="kb-card-people"><div v-if="fields('author')" class="kb-card-meta"><img v-if="card.avatar_url&&!avatarErrors.has(card.avatar_url)" class="kb-author-avatar" :src="card.avatar_url" alt="" loading="lazy" @error="avatarErrors.add(card.avatar_url)"/><span v-else class="kb-author-mark" aria-hidden="true">{{ Array.from(card.author || '').slice(0,1).join('') }}</span><span class="kb-card-author" :title="card.author">{{ card.author }}</span></div><div v-if="fields('assignee')&&card.assignee" class="kb-card-assignee" :title="copy.assignee+': '+card.assignee.name"><img v-if="card.assignee.avatar_url" :src="card.assignee.avatar_url" alt="" loading="lazy"/><span v-else class="kb-assignee-initial" aria-hidden="true">{{ Array.from(card.assignee.name||'').slice(0,1).join('') }}</span><span class="kb-card-assignee-name">{{ card.assignee.name }}</span></div></div>
                            <div v-if="fields('terms')&&card.terms?.length" class="kb-card-terms"><span v-for="term in card.terms.slice(0,3)" :key="`${term.collection}:${term.id}`">{{ term.name }}</span><span v-if="card.terms.length>3">+{{ number(card.terms.length-3) }}</span></div>
                            <div v-if="fields('age')&&card.age_days!==undefined||fields('scheduled')&&card.scheduled_at" class="kb-card-signals"><span v-if="fields('age')&&card.age_days!==undefined" :class="{'is-stale':card.age_days>=options.stale_days}" :title="card.entered_at"><i v-html="iconMarkup('clock',13)"/>{{ copy.age_days.replace(':count',number(card.age_days)) }}</span><span v-if="fields('scheduled')&&card.scheduled_at" :title="copy.summary_scheduled"><i v-html="iconMarkup('calendar',13)"/>{{ card.scheduled_at }}</span></div>
                            <div v-if="fields('deadline')&&card.due_at||fields('dependencies')&&card.dependency_count" class="kb-card-work-signals"><span v-if="fields('deadline')&&card.due_at" :class="card.due_state?`is-${card.due_state}`:''" :title="card.due_state?copy[`due_${card.due_state}`]:copy.deadline"><i v-html="iconMarkup(card.due_state==='overdue'?'bell':'calendar',13)"/>{{ copy.deadline }}: {{ card.due_at }}<strong v-if="card.due_state">{{ copy[`due_${card.due_state}`] }}</strong></span><span v-if="fields('dependencies')&&card.dependency_count"><i v-html="iconMarkup('link',13)"/>{{ copy.dependencies }}: {{ number(card.dependency_count) }}</span></div>
                            <footer v-if="fields('updated')||config.editable"><time v-if="fields('updated')"><span v-html="iconMarkup('clock',14)"/>{{ card.updated_at }}</time><button v-if="config.editable" type="button" class="kb-card-move" :aria-label="copy.move" :title="copy.move_hint" :disabled="busy" @click="openMove(card, $event)"><span v-html="iconMarkup('arrows-left-right',18)"/><span>{{ copy.move }}</span></button></footer>
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
        <WorkflowSettingsDialog ref="calendarDialog" class="kb-feature-dialog kb-calendar-dialog" icon="calendar" :title="copy.calendar" :close-label="copy.close">
            <template #header-actions>
                <div class="kb-calendar-head-tools">
                    <div class="kb-calendar-head-month"><button type="button" :disabled="calendarOffset<=-12||calendarLoading" :aria-label="copy.previous_month" @click="calendarStep(-1)"><span v-html="iconMarkup('arrow-left',16)"/></button><strong aria-live="polite">{{ calendarData?.month||'—' }}</strong><button type="button" :disabled="calendarOffset>=12||calendarLoading" :aria-label="copy.next_month" @click="calendarStep(1)"><span v-html="iconMarkup('arrow-right',16)"/></button></div>
                    <div class="kb-calendar-view-switch" role="group" :aria-label="copy.calendar_view"><button type="button" :class="{'is-active':calendarView==='calendar'}" :aria-pressed="calendarView==='calendar'" :title="copy.calendar_view" @click="calendarView='calendar'"><span v-html="iconMarkup('calendar',16)"/><span>{{ copy.calendar_view }}</span></button><button type="button" :class="{'is-active':calendarView==='table'}" :aria-pressed="calendarView==='table'" :title="copy.table_view" @click="calendarView='table'"><span v-html="iconMarkup('list-tree',16)"/><span>{{ copy.table_view }}</span></button></div>
                </div>
            </template>
            <div class="kb-feature-body kb-publication-calendar">
                <p v-if="calendarLoading" class="kb-calendar-loading" role="status">{{ copy.loading }}</p><p v-if="calendarError" class="kb-error" role="alert">{{ calendarError }}</p>
                <template v-if="calendarData&&!calendarLoading">
                    <template v-if="calendarView==='calendar'">
                        <div class="kb-calendar-grid" :aria-label="copy.calendar">
                            <span v-for="label in calendarWeekdays" :key="label" class="kb-calendar-weekday">{{ label }}</span>
                            <span v-for="blank in calendarLeading" :key="`blank-${blank}`" class="kb-calendar-blank"/>
                            <div v-for="day in calendarData.days" :key="day.date" class="kb-calendar-day" :class="{'has-events':calendarEventsByDate.has(day.date),'is-today':calendarData.today===day.date}"><strong :title="day.label">{{ day.label.split('/').pop() }}</strong><div class="kb-calendar-day-events"><button v-for="(event,eventIndex) in calendarEventsByDate.get(day.date)||[]" :key="`${event.id}:${event.kind}:${day.date}:${eventIndex}`" type="button" :class="`is-${event.kind}`" :title="(event.kind==='publish'?copy.publish_date:copy.deadline)+': '+event.title" @click="openCard({id:event.id},$event)"><span v-html="iconMarkup(event.kind==='publish'?'cloud-upload':'clock',13)"/><span>{{ event.title }}</span></button></div></div>
                        </div>
                        <div class="kb-calendar-agenda"><div v-for="day in calendarAgendaDays" :key="day.date"><strong><span v-html="iconMarkup('calendar',16)"/>{{ day.label }}</strong><button v-for="(event,eventIndex) in calendarEventsByDate.get(day.date)||[]" :key="`${event.id}:${event.kind}:${day.date}:${eventIndex}`" type="button" :class="`is-${event.kind}`" @click="openCard({id:event.id},$event)"><span v-html="iconMarkup(event.kind==='publish'?'cloud-upload':'clock',13)"/>{{ event.kind==='publish'?copy.publish_date:copy.deadline }} · {{ event.title }}</button></div><p v-if="!calendarData.events.length">{{ copy.no_calendar_events }}</p></div>
                    </template>
                    <div v-else class="kb-calendar-table-wrap"><table class="kb-calendar-table"><thead><tr><th scope="col">{{ copy.calendar_date }}</th><th scope="col">{{ copy.calendar_kind }}</th><th scope="col">{{ copy.calendar_content }}</th><th scope="col">{{ copy.calendar_state }}</th></tr></thead><tbody><tr v-for="event in calendarRows" :key="`${event.id}:${event.kind}:${event.date}:${event.index}`"><td :data-label="copy.calendar_date">{{ event.label }}</td><td :data-label="copy.calendar_kind"><span class="kb-calendar-kind" :class="`is-${event.kind}`"><i v-html="iconMarkup(event.kind==='publish'?'cloud-upload':'clock',14)"/>{{ event.kind==='publish'?copy.publish_date:copy.deadline }}</span></td><td :data-label="copy.calendar_content"><button type="button" class="kb-calendar-table-link" @click="openCard({id:event.id},$event)">{{ event.title }}</button></td><td :data-label="copy.calendar_state">{{ event.state }}</td></tr></tbody></table><p v-if="!calendarRows.length" class="kb-calendar-table-empty">{{ copy.no_calendar_events }}</p></div>
                    <p v-if="calendarData.truncated" class="kb-hint">{{ copy.calendar_truncated }}</p>
                </template>
            </div>
        </WorkflowSettingsDialog>
        <WorkflowSettingsDialog ref="insightsDialog" class="kb-feature-dialog kb-insights-dialog" icon="chart-bar" :title="copy.insights" :close-label="copy.close"><div class="kb-feature-body"><p v-if="insightsLoading" role="status">{{ copy.loading }}</p><p v-if="insightsError" class="kb-error" role="alert">{{ insightsError }}</p><div v-if="insightsData&&!insightsLoading" class="kb-insights-list"><div class="kb-insights-hero"><div class="kb-insights-hero-copy"><span class="kb-insights-hero-icon" v-html="iconMarkup('chart-bar',24)"/><div><small>{{ copy.cards }}</small><strong>{{ number(insightsTotal) }}</strong></div></div><div class="kb-insights-strip" :aria-label="copy.insights"><span v-for="(state,index) in insightsData.states" :key="state.status" :style="{width:`${insightsTotal?state.count/insightsTotal*100:0}%`,background:insightColors[index%insightColors.length]}" :title="state.name+': '+number(state.count)"/></div><div class="kb-insights-legend"><span v-for="(state,index) in insightsData.states" :key="state.status"><i :style="{background:insightColors[index%insightColors.length]}"/>{{ state.name }} <b>{{ number(state.count) }}</b></span></div></div><div class="kb-insights-overview"><div><span v-html="iconMarkup('clock',18)"/><small>{{ copy.average_days }}</small><strong>{{ number(insightsAverage) }}</strong></div><div><span v-html="iconMarkup('bell',18)"/><small>{{ copy.stale_cards }}</small><strong>{{ number(insightsStale) }}</strong></div></div><div class="kb-insights-state-list"><div v-for="(state,index) in insightsData.states" :key="state.status" class="kb-insight-state" :style="{'--state-tone':insightColors[index%insightColors.length]}"><span class="kb-insight-index">{{ number(index+1) }}</span><div class="kb-insight-main"><div><strong>{{ state.name }} <small v-if="state.status===bottleneckState" class="kb-bottleneck-tag">{{ copy.bottleneck }}</small></strong><span>{{ copy.cards }} · {{ number(state.count) }}</span></div><div class="kb-insight-track"><span :style="{width:`${insightsTotal?Math.min(100,state.count/insightsTotal*100):0}%`}"/></div></div><div class="kb-insight-facts"><span :title="copy.average_days"><i v-html="iconMarkup('clock',14)"/>{{ number(state.average_days) }}</span><span :title="copy.stale_cards"><i v-html="iconMarkup('bell',14)"/>{{ number(state.stale) }}</span><span :title="copy.oldest_days"><i v-html="iconMarkup('clock',14)"/>{{ number(state.oldest_days) }}</span><span :title="copy.flow_30d">{{ number(state.arrived_30d) }} ↑ · {{ number(state.departed_30d) }} ↓</span></div></div></div></div></div></WorkflowSettingsDialog>
        <dialog ref="workDateDialog" class="kb-dialog kb-work-date-dialog" :aria-label="copy.deadline" @cancel.prevent="closeWorkDate">
            <header><span class="kb-work-date-title-icon" v-html="iconMarkup('calendar',20)"/><h2>{{ copy.deadline }}</h2><button type="button" :aria-label="copy.close" @click="closeWorkDate"><span v-html="iconMarkup('x',18)"/></button></header>
            <div class="kb-work-date-body"><label for="kb-work-date-input">{{ copy.choose_deadline }}</label><input id="kb-work-date-input" ref="workDateInput" v-model="workDateValue" type="text" autocomplete="off" dir="ltr" :placeholder="copy.due_placeholder"/><div class="kb-work-date-picker"/><small>{{ copy.due_hint }}</small></div>
            <footer @click.capture="applyWorkDate"><button type="button" class="rf-btn rf-btn--primary">{{ copy.apply_deadline }}</button></footer>
        </dialog>
        <dialog ref="membersDialog" class="kb-dialog kb-members-dialog" :aria-label="copy.members" @close="membersClosed">
            <header class="kb-members-dialog-head"><span class="kb-members-dialog-icon" v-html="iconMarkup('users',20)"/><div><h2>{{ copy.members }}</h2><p>{{ copy.members_count.replace(':count',number(headerMemberTotal)) }}</p></div><button type="button" class="kb-members-close" :aria-label="copy.close" @click="closeMembers"><span v-html="iconMarkup('x',18)"/></button></header>
            <div class="kb-members-dialog-body"><div class="kb-members-toolbar"><label class="kb-members-search"><span v-html="iconMarkup('search',18)"/><span class="kb-screen-reader">{{ copy.members_search }}</span><input v-model="memberSearch" type="search" maxlength="80" :placeholder="copy.members_search" @input="searchMembers"/></label><details ref="memberRoleMenu" class="kb-members-role-menu"><summary :aria-label="copy.member_filter_role"><span v-html="iconMarkup('filter',16)"/><span>{{ memberRoleOptions.find(role=>role.id===memberRole)?.name||copy.member_all_roles }}</span><span v-html="iconMarkup('chevron-down',14)"/></summary><div class="kb-members-role-options" role="group" :aria-label="copy.member_filter_role"><button type="button" :class="{'is-active':memberRole===0}" :aria-pressed="memberRole===0" @click="chooseMemberRole(0)">{{ copy.member_all_roles }}</button><button v-for="role in memberRoleOptions" :key="role.id" type="button" :class="{'is-active':memberRole===role.id}" :aria-pressed="memberRole===role.id" @click="chooseMemberRole(role.id)">{{ role.name }}</button></div></details></div>
                <div class="kb-members-list-head"><span>{{ copy.members_count.replace(':count',number(memberTotal)) }}</span><div class="kb-members-sort" role="group" :aria-label="copy.member_sort"><button type="button" :class="{'is-active':memberSort==='name'}" :aria-pressed="memberSort==='name'" @click="memberSort='name';loadMembers(true)">{{ copy.member_sort_name }}</button><button type="button" :class="{'is-active':memberSort==='cards'}" :aria-pressed="memberSort==='cards'" @click="memberSort='cards';loadMembers(true)">{{ copy.member_sort_cards }}</button></div></div>
                <p v-if="memberError" class="kb-error" role="alert">{{ memberError }} <button type="button" @click="loadMembers(true)">{{ copy.retry }}</button></p>
                <div v-if="members.length" class="kb-members-list"><div v-for="member in members" :key="member.id" class="kb-members-row"><span class="kb-members-row-avatar"><img v-if="member.avatar_url&&!memberAvatarErrors.has(member.avatar_url)" :src="member.avatar_url" alt="" loading="lazy" @error="memberAvatarErrors.add(member.avatar_url)"/><span v-else>{{ Array.from(member.name||'').slice(0,1).join('') }}</span></span><div class="kb-members-row-info"><div class="kb-members-row-top"><strong :title="member.name">{{ member.name }}</strong><span class="kb-members-row-meta">{{ copy.member_id.replace(':id',number(member.id)) }}</span></div><div class="kb-members-row-tags"><span v-if="member.primary_role" class="kb-members-primary-role">{{ member.primary_role }}</span><span v-for="role in member.roles" :key="role" class="kb-members-role" :title="role">{{ role }}</span><span class="kb-members-access" :class="`is-${member.access}`">{{ copy[`member_access_${member.access}`] }}</span></div></div><button type="button" class="kb-members-card-count" :title="copy.member_show_cards" :aria-label="copy.member_show_cards+' '+member.name" @click="filterMember(member)"><strong>{{ number(member.card_count) }}</strong><small>{{ copy.member_cards }}</small></button></div></div>
                <p v-else-if="!membersLoading&&!memberError" class="kb-members-empty">{{ copy.members_empty }}</p>
                <p v-if="membersLoading" class="kb-members-loading" role="status">{{ copy.loading }}</p>
                <button v-if="memberNext&&!membersLoading" type="button" class="kb-members-load-more rf-btn rf-btn--secondary" @click="loadMembers()">{{ copy.more }}</button>
            </div>
        </dialog>
        <WorkflowSettingsDialog ref="settingsDialog" class="kb-settings kb-board-settings" :title="copy.settings" :intro="copy.settings_intro" :close-label="copy.close" :busy="busy" @close="syncLimits();flushLive()">
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
                    <fieldset class="kb-card-fields"><legend>{{ copy.card_fields }}</legend><label v-for="field in cardFieldChoices" :key="field.key" :class="{'is-selected':cardFields.includes(field.key)}"><span class="kb-field-icon" v-html="iconMarkup(field.icon,18)"/><span>{{ copy[`field_${field.key}`] }}</span><input v-model="cardFields" :disabled="busy" type="checkbox" :value="field.key"/></label></fieldset>
                </section>
                <section v-if="config.editable" id="kb-panel-capacity" v-show="settingsTab==='capacity'" role="tabpanel" aria-labelledby="kb-tab-capacity" class="kb-settings-panel">
                    <p class="kb-settings-note"><span v-html="iconMarkup('users',16)"/>{{ copy.shared_settings }}</p>
                    <p class="kb-hint">{{ copy.limits_hint }}</p>
                    <div class="kb-stale-setting"><label for="kb-color-mode">{{ copy.color_mode }}</label><select id="kb-color-mode" v-model="colorMode" :disabled="busy"><option value="none">{{ copy.color_none }}</option><option value="signals">{{ copy.color_signals }}</option><option value="priority">{{ copy.color_priority }}</option></select></div><div class="kb-stale-setting"><label for="kb-stale-days">{{ copy.stale_days }}</label><input id="kb-stale-days" :disabled="busy" :aria-invalid="!!settingsErrors.stale_days" aria-describedby="kb-settings-error" v-model.number="staleDays" type="number" min="1" max="3650"/></div>
                    <div class="kb-limits-list"><div v-for="(limit,index) in limitDraft" :key="limit.state" class="kb-limit-row"><h3><i/>{{ limit.name }}</h3><div class="kb-limit-controls"><label :for="`kb-limit-${limit.state}`">{{ copy.capacity_number }}<input :id="`kb-limit-${limit.state}`" :disabled="busy" :aria-invalid="!!settingsErrors[`limits.${index}.limit`]" aria-describedby="kb-settings-error" v-model.number="limit.limit" type="number" min="0" max="100000"/></label><label>{{ copy.limit_mode }}<select v-model="limit.mode" :disabled="busy" :aria-label="copy.limit_mode+' '+limit.name"><option value="warn">{{ copy.limit_warn }}</option><option value="enforce">{{ copy.limit_enforce }}</option></select></label></div></div></div>
                </section>
                <p v-if="settingsError" id="kb-settings-error" class="kb-error" role="alert">{{ settingsError }}</p>
            </form>
            <template #footer><button type="submit" form="kanban-settings-form" class="rf-btn rf-btn--primary" :disabled="busy">{{ copy.save }}</button></template>
        </WorkflowSettingsDialog>
        <WorkflowSettingsDialog ref="groupDialog" class="kb-group-dialog" :title="copy.group_by" :intro="copy.group_save_hint" :close-label="copy.close" :busy="groupBusy" @close="flushLive">
            <div class="kb-group-choices" role="radiogroup" :aria-label="copy.group_by"><label v-for="(label,key) in config.groupChoices||{none:copy.group_none,author:copy.author}" :key="key" class="kb-choice" :class="{'is-selected':groupBy===key}"><input v-model="groupBy" type="radio" name="kb-group" :value="key" :disabled="busy" @change="changeGrouping"/><span class="kb-choice-icon" v-html="iconMarkup(key==='none'?'kanban':key==='author'?'user':'bookmark',18)"/><span class="kb-choice-copy"><strong>{{ label }}</strong><small>{{ key==='none'?copy.group_none_hint:key==='author'?copy.group_author_hint:copy.group_taxonomy_hint }}</small></span><span class="kb-choice-check" v-html="iconMarkup('check',16)"/></label></div><p v-if="groupBusy" class="kb-group-saving" role="status">{{ copy.loading }}</p>
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
                <div class="kb-detail-workspace" :class="{'is-collapsed':!detailWorkspaceOpen}">
                    <div class="kb-detail-tabs-bar">
                    <div class="kb-detail-tabs" role="tablist" :aria-label="copy.card_work+' / '+copy.discussion" @keydown="detailTabKeydown">
                        <button type="button" role="tab" data-detail-tab="discussion" id="kb-detail-tab-discussion" aria-controls="kb-detail-panel-discussion" :aria-selected="detailTab==='discussion'" :tabindex="detailTab==='discussion'?0:-1" @click="selectDetailTab('discussion')"><span v-html="iconMarkup('message',17)"/>{{ copy.discussion }} <small>{{ number(detail.discussion?.items?.length||0) }}</small></button>
                        <button type="button" role="tab" data-detail-tab="work" id="kb-detail-tab-work" aria-controls="kb-detail-panel-work" :aria-selected="detailTab==='work'" :tabindex="detailTab==='work'?0:-1" @click="selectDetailTab('work')"><span v-html="iconMarkup('user-check',17)"/>{{ copy.card_work }}</button>
                    </div>
                    <button type="button" class="kb-detail-collapse" :aria-label="detailWorkspaceOpen?copy.collapse_detail_panel:copy.expand_detail_panel" :title="detailWorkspaceOpen?copy.collapse_detail_panel:copy.expand_detail_panel" :aria-expanded="detailWorkspaceOpen" @click="detailWorkspaceOpen=!detailWorkspaceOpen"><span v-html="iconMarkup('chevron-down',17)"/></button>
                    </div>
                    <div id="kb-detail-panel-discussion" class="kb-detail-panel" role="tabpanel" aria-labelledby="kb-detail-tab-discussion" v-show="detailWorkspaceOpen&&detailTab==='discussion'" tabindex="0">
                <section class="kb-discussion" :aria-label="copy.discussion"><header><span v-html="iconMarkup('message',18)"/><h3>{{ copy.discussion }}</h3><small>{{ number(detail.discussion?.items?.length||0) }}</small></header><div v-if="detail.discussion?.items?.length" class="kb-discussion-list"><article v-for="comment in detail.discussion.items" :key="comment.id" class="kb-comment"><span class="kb-comment-avatar"><img v-if="comment.avatar_url" :src="comment.avatar_url" alt=""/><span v-else>{{ Array.from(comment.author||'').slice(0,1).join('') }}</span></span><div><div class="kb-comment-meta"><strong>{{ comment.author }}</strong><time>{{ comment.created_at }}</time></div><p>{{ commentText(comment.body) }}</p></div></article></div><button v-if="detail.discussion?.cursor" type="button" class="rf-btn rf-btn--secondary kb-comment-more" :disabled="olderCommentsBusy" @click="loadOlderComments">{{ olderCommentsBusy?copy.loading:copy.more }}</button><p v-if="!detail.discussion?.items?.length" class="kb-discussion-empty">{{ copy.no_comments }}</p><form @submit.prevent="saveComment"><label for="kb-comment-body">{{ copy.add_comment }}</label><textarea id="kb-comment-body" v-model="commentDraft" :disabled="commentBusy" :aria-invalid="!!commentError" maxlength="2000" rows="3" :placeholder="copy.comment_hint" @input="commentChanged"/><div v-if="commentCandidates.length" class="kb-mention-options"><button v-for="member in commentCandidates" :key="member.id" type="button" @click="insertMention(member)">{{ member.name }}</button></div><p v-if="commentError" class="kb-error" role="alert">{{ commentError }}</p><button type="submit" class="rf-btn rf-btn--primary" :disabled="commentBusy||!commentDraft.trim()">{{ commentBusy?copy.loading:copy.send_comment }}</button></form></section>
                    </div>
                    <div id="kb-detail-panel-work" class="kb-detail-panel" role="tabpanel" aria-labelledby="kb-detail-tab-work" v-show="detailWorkspaceOpen&&detailTab==='work'" tabindex="0">
                <section v-if="workDraft" class="kb-work-panel"><div class="kb-work-panel-head"><span v-html="iconMarkup('user-check',18)"/><strong>{{ copy.card_work }}</strong></div><form v-if="config.editable" @submit.prevent="saveWork"><div class="kb-work-layout"><div class="kb-work-column"><div class="kb-work-field"><label for="kb-assignee-search"><span v-html="iconMarkup('user-check',17)"/>{{ copy.assignee }}</label><div v-if="workDraft.assignee_id" class="kb-work-selected"><span>{{ assigneeOptions.find(person=>person.id===Number(workDraft.assignee_id))?.name||detail.work?.assignee?.name||`#${workDraft.assignee_id}` }}</span><button type="button" :aria-label="copy.unassigned" @click="workDraft.assignee_id='';assigneeSearch=''">×</button></div><input id="kb-assignee-search" v-model="assigneeSearch" type="search" :placeholder="copy.search_members" @input="assigneeChanged"/><div class="kb-work-options"><button type="button" :class="{'is-active':!workDraft.assignee_id}" @click="workDraft.assignee_id=''">{{ copy.unassigned }}</button><button v-for="person in assigneeOptions" :key="person.id" type="button" :class="{'is-active':workDraft.assignee_id===person.id}" @click="workDraft.assignee_id=person.id;assigneeSearch=person.name"><img v-if="person.avatar_url" :src="person.avatar_url" alt=""/>{{ person.name }}</button></div></div><div class="kb-work-field"><label for="kb-work-priority"><span v-html="iconMarkup('flag',17)"/>{{ copy.priority }}</label><select id="kb-work-priority" v-model="workDraft.priority" :disabled="workBusy"><option v-for="level in ['low','normal','high','urgent']" :key="level" :value="level">{{ copy[`priority_${level}`] }}</option></select></div></div><div class="kb-work-column"><div class="kb-work-field"><label for="kb-work-due"><span v-html="iconMarkup('calendar',17)"/>{{ copy.deadline }}</label><button id="kb-work-due" ref="workDateTrigger" type="button" class="kb-work-date-trigger" :aria-label="copy.choose_deadline" @click="openWorkDate"><span v-html="iconMarkup('calendar',17)"/><span :class="{'is-placeholder':!workDraft.due_local}">{{ workDraft.due_local||copy.choose_deadline }}</span><span v-html="iconMarkup('chevron-down',15)"/></button><small>{{ copy.due_hint }}</small></div><div class="kb-work-field"><label for="kb-dependency-search"><span v-html="iconMarkup('link',17)"/>{{ copy.dependencies }}</label><div v-if="workDraft.dependencies.length" class="kb-work-dependencies"><span v-for="dependency in workDraft.dependencies" :key="dependency.id">{{ dependency.title||`#${dependency.id}` }}<button type="button" :aria-label="copy.remove_dependency" @click="workDraft.dependencies=workDraft.dependencies.filter(item=>item.id!==dependency.id)">×</button></span></div><input id="kb-dependency-search" v-model="dependencySearch" type="search" :placeholder="copy.search_dependencies" @input="dependencyChanged"/><p v-if="dependencyLoading" class="kb-dependency-status" role="status">{{ copy.loading }}</p><p v-else-if="dependencyError" class="kb-error" role="alert">{{ dependencyError }}</p><div v-else-if="dependencyOptions.length" class="kb-dependency-results" role="group" :aria-label="copy.search_dependencies"><button v-for="candidate in dependencyOptions" :key="candidate.id" type="button" @click="addDependency(candidate)"><span class="kb-dependency-icon" v-html="iconMarkup('file-text',18)"/><span class="kb-dependency-label"><strong>{{ candidate.title }}</strong><small>#{{ number(candidate.id) }} · {{ candidate.column_name }}</small></span><span class="kb-dependency-add" v-html="iconMarkup('plus',16)"/></button></div><p v-else-if="dependencySearch.trim().length>=2" class="kb-dependency-status">{{ copy.no_dependency_results }}</p></div></div></div><p v-if="workError" class="kb-error" role="alert">{{ workError }}</p><button type="submit" class="rf-btn rf-btn--primary" :disabled="workBusy">{{ workBusy?copy.loading:copy.save_card_work }}</button></form><div v-else class="kb-work-readonly"><p>{{ copy.assignee }}: {{ detail.work.assignee?.name||copy.unassigned }}</p><p>{{ copy.priority }}: {{ copy[`priority_${detail.work.priority||'normal'}`] }}</p><p>{{ copy.deadline }}: {{ detail.work.due_at||'—' }}</p><p>{{ copy.dependencies }}: {{ detail.work.dependencies.map(item=>item.title||`#${item.id}`).join('، ')||'—' }}</p></div></section>
                <section v-if="detail.review?.enabled" class="kb-review" :aria-label="copy.review_title"><header><span v-html="iconMarkup('shield',18)"/><h3>{{ copy.review_title }}</h3><small>{{ copy[`review_${detail.review.status}`] }}</small></header><div v-if="detail.review.reviewers.length" class="kb-reviewers"><span v-for="reviewer in detail.review.reviewers" :key="reviewer.id">{{ reviewer.name }} · {{ copy[`review_${reviewer.status}`] }}</span></div><template v-if="config.editable&&detail.review.status!=='approved'&&detail.review.status!=='pending'"><label for="kb-reviewer-search">{{ copy.choose_reviewers }}</label><input id="kb-reviewer-search" v-model="reviewerSearch" type="search" :placeholder="copy.search_members" @input="searchReviewers" @focus="searchReviewers"/><div v-if="reviewerCandidates.length" class="kb-review-options"><button v-for="reviewer in reviewerCandidates" :key="reviewer.id" type="button" @click="chosenReviewers.push(reviewer);reviewerCandidates=reviewerCandidates.filter(item=>item.id!==reviewer.id)">{{ reviewer.name }}</button></div><div v-if="chosenReviewers.length" class="kb-reviewers"><span v-for="reviewer in chosenReviewers" :key="reviewer.id">{{ reviewer.name }}<button type="button" :aria-label="copy.remove" @click="chosenReviewers=chosenReviewers.filter(item=>item.id!==reviewer.id)">×</button></span></div><button type="button" class="rf-btn rf-btn--primary" :disabled="reviewBusy||!chosenReviewers.length" @click="reviewCommand('request_review')">{{ reviewBusy?copy.loading:copy.request_review }}</button></template><div v-if="config.editable&&detail.review.can_decide" class="kb-review-actions"><button type="button" class="rf-btn rf-btn--primary" :disabled="reviewBusy" @click="reviewCommand('review_decide','approve')">{{ reviewBusy?copy.loading:copy.approve_review }}</button><button type="button" class="rf-btn rf-btn--secondary" :disabled="reviewBusy" @click="reviewCommand('review_decide','reject')">{{ reviewBusy?copy.loading:copy.reject_review }}</button></div><p v-if="reviewError" class="kb-error" role="alert">{{ reviewError }}</p></section>
                    </div>
                </div>
                <div v-if="config.editable && detail.destinations.length" class="kb-detail-transitions"><button v-for="target in detail.destinations" :key="target.transition" type="button" :disabled="busy || target.status === 'blocked'" :title="target.checks.map(c => c.message).filter(Boolean).join(' · ')" @click="detailMove(target)">{{ target.label }}</button></div>
            </template>
        </dialog>
        <WorkflowSettingsDialog ref="notificationDialog" class="kb-notification-dialog" :title="copy.notifications" :close-label="copy.close" @close="notificationSettingsOpen=false">
            <template #header-actions><button type="button" class="kb-notification-settings-trigger" :class="{'is-active':notificationSettingsOpen}" :aria-label="copy.notification_preferences" :title="copy.notification_preferences" :aria-pressed="notificationSettingsOpen" :aria-expanded="notificationSettingsOpen" :disabled="notificationLoading||notificationBusy" @click="notificationSettingsOpen=!notificationSettingsOpen"><span v-html="iconMarkup('settings',18)"/></button></template>
            <div v-if="!notificationSettingsOpen" class="kb-notifications"><p v-if="notificationLoading" role="status">{{ copy.loading }}</p><p v-if="notificationError" class="kb-error" role="alert">{{ notificationError }}</p><p v-if="!notificationLoading&&!notificationItems.length">{{ copy.no_notifications }}</p><button v-for="item in notificationItems" :key="item.id" type="button" :disabled="notificationBusy" :class="{'is-unread':!item.read}" @click="readNotification(item)"><span v-html="iconMarkup(item.kind==='mention'?'message':item.kind==='deadline'?'calendar':'bell',17)"/><span><strong>{{ copy[`notification_${item.kind}`] }}</strong><small>{{ item.created_at }}</small></span><i v-if="!item.read"/></button></div>
            <div v-else class="kb-notification-preferences"><header><span v-html="iconMarkup('settings',18)"/><div><strong>{{ copy.notification_preferences }}</strong><small>{{ copy.notification_preferences_hint }}</small></div></header><p v-if="notificationError" class="kb-error" role="alert">{{ notificationError }}</p><form @submit.prevent="saveNotificationPreferences"><label v-for="kind in ['mention','assignment','deadline','review_requested','review_decided']" :key="kind"><span>{{ copy[`notification_${kind}`] }}</span><input v-model="notificationPreferences[kind]" type="checkbox" :disabled="notificationBusy"/></label><button type="submit" class="rf-btn rf-btn--primary" :disabled="notificationBusy">{{ notificationBusy?copy.loading:copy.save }}</button></form></div>
        </WorkflowSettingsDialog>
        <WorkflowSettingsDialog ref="discardDialog" class="kb-discard-dialog" icon="info" :title="copy.unsaved_title" :close-label="copy.close" @close="finishDiscard(false)"><p class="kb-discard-message">{{ copy.unsaved }}</p><template #footer><button type="button" class="rf-btn rf-btn--secondary" @click="finishDiscard(false)">{{ copy.keep_editing }}</button><button type="button" class="rf-btn rf-btn--danger" @click="finishDiscard(true)">{{ copy.discard_changes }}</button></template></WorkflowSettingsDialog>
        <dialog ref="scheduleDialog" class="kb-dialog kb-settings" @cancel="busy && $event.preventDefault()">
            <form @submit.prevent="scheduleSave"><header><h2>{{ copy.needs_due }}</h2><button type="button" :disabled="busy" :aria-label="copy.close" @click="scheduleDialog.close(); pendingDrop=null">×</button></header>
                <label for="kb-due">{{ copy.due_label }}</label><input id="kb-due" v-model="dueLocal" autocomplete="off" placeholder="YYYY/MM/DD HH:mm" dir="ltr" :disabled="busy" :aria-invalid="!!dueError" aria-describedby="kb-due-hint kb-due-error"/>
                <p id="kb-due-hint" class="kb-hint">{{ copy.due_hint }}</p><p v-if="dueError" id="kb-due-error" class="kb-error" role="alert">{{ dueError }}</p><footer><button type="submit" class="kb-primary" :disabled="busy">{{ copy.continue }}</button></footer>
            </form>
        </dialog>
        <WorkflowToast :close-label="copy.close" />
    </main>
</template>

