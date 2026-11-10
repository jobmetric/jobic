<script setup>
import { computed, onBeforeUnmount, onMounted, provide, ref } from 'vue';
import ReservationFlowEditor from './components/reservations/ReservationFlowEditor.vue';
import ReservationFlowAddStateModal from './components/reservations/flow/ReservationFlowAddStateModal.vue';
import ReservationFlowAddTransitionModal from './components/reservations/flow/ReservationFlowAddTransitionModal.vue';
import ReservationFlowConfirmModal from './components/reservations/flow/ReservationFlowConfirmModal.vue';
import WorkflowSimulationModal from './components/WorkflowSimulationModal.vue';
import { buildReservationFlowDiagram } from './utils/reservationFlowDiagramLayout.js';
import { mergeTransitionChange } from './utils/reservationFlowDraft.js';
import { useReservationFlowConfirm } from './composables/useReservationFlowConfirm.js';
import { RESERVATION_FLOW_CONFIRM_KEY } from './composables/reservationFlowConfirmKey.js';
import { pageData } from '../common/page.js';
import { announce } from '../common/toast.js';
import WorkflowPageHeader from './components/WorkflowPageHeader.vue';
import WorkflowSettingsDialog from './components/WorkflowSettingsDialog.vue';
import WorkflowToast from './components/WorkflowToast.vue';

const options = pageData().workflow;
const copy = options.copy;
const initial = options.initial;
const drivers = new Map(initial.drivers.flatMap((group) => group.tasks ?? []).map((driver) => [driver.class, driver]));
function presentTask(task) {
    const driver = drivers.get(task.driver);
    return {...task, id: task.id ?? crypto.randomUUID(), type: driver?.type ?? 'action',
        title: driver?.title ?? task.driver, description: driver?.description ?? '',
        driver_key: task.driver?.split('\\').at(-1)};
}
const states = ref(structuredClone(initial.graph.states));
const startPosition = ref(initial.graph.start_position ?? null);
const startConnection = ref(initial.graph.start_connection ?? {to_side: 'top'});
const transitions = ref(structuredClone(initial.graph.transitions).map((edge) => ({
    ...mergeTransitionChange(edge, {}), tasks: (edge.tasks ?? []).map(presentTask),
})));
const baseline = ref('');
const baselineEnabled = ref(Boolean(initial.enabled));
const version = ref(initial.version);
const definitionId = ref(initial.definition_id);
const definitionState = ref(initial.state);
const enabled = ref(initial.enabled);
const settingsOpen = ref(false);
const simulationOpen = ref(false);
const busy = ref(false);
const error = ref('');
const stateModal = ref(false);
const transitionModal = ref(false);
const transitionPreset = ref({from: '', to: '', anchors: {}});
const review = ref(null);
const mapping = ref({});
const confirm = useReservationFlowConfirm();
provide(RESERVATION_FLOW_CONFIRM_KEY, confirm.confirm);

function startEdgeFor(currentStates, connection = startConnection.value) {
    const first = currentStates.find((state) => state.is_start);

    return first ? [{id: '__start__', from: null, to: first.id, slug: '__start__', label: copy.start,
        is_start_edge: true, anchors: {from_side: 'bottom', to_side: connection.to_side}, tasks: []}] : [];
}

function freezeTransitionAnchors(rows, currentStates, position = startPosition.value, existingRows = []) {
    rows = rows.map((edge) => mergeTransitionChange(edge, {}));
    if (!rows.some((edge) => !edge.anchors?.from_side || !edge.anchors?.to_side)) return rows;

    const diagram = buildReservationFlowDiagram(currentStates, [...startEdgeFor(currentStates), ...existingRows, ...rows],
        {startPosition: position});

    return rows.map((edge) => {
        if (edge.anchors?.from_side && edge.anchors?.to_side) return edge;
        const rendered = diagram.edges.find((item) => String(item.id) === String(edge.id));
        if (!rendered) return edge;

        return {...edge, anchors: {from_side: edge.anchors?.from_side ?? rendered.start.side,
            to_side: edge.anchors?.to_side ?? rendered.end.side}};
    });
}

// Older drafts without explicit ports are fixed at their initial layout too.
transitions.value = freezeTransitionAnchors(transitions.value, states.value);

const graph = computed(() => ({
    ...(startPosition.value ? {start_position: startPosition.value} : {}),
    start_connection: startConnection.value,
    states: states.value.map((state) => ({
        ...state,
        id: String(state.id), status: String(state.status),
        content_status: state.content_status ?? (options.statusChoices?.[state.status] ? state.status : 'editable'),
        is_start: Boolean(state.is_start), is_terminal: Boolean(state.is_terminal),
        allows_approval: Boolean(state.allows_approval),
        view_roles: (state.view_roles ?? []).map(Number),
    })),
    transitions: transitions.value.map((edge) => ({
        ...edge,
        id: String(edge.id), from: String(edge.from), to: String(edge.to),
        tasks: (edge.tasks ?? []).map((task) => ({
            driver: task.driver, config: task.config ?? {}, status: task.status ?? true,
        })),
    })),
}));
baseline.value = JSON.stringify(graph.value);
const dirty = computed(() => JSON.stringify(graph.value) !== baseline.value || enabled.value !== baselineEnabled.value);
const start = computed(() => states.value.find((state) => state.is_start));
const visualTransitions = computed(() => [...startEdgeFor(states.value), ...transitions.value]);
const availableDrivers = computed(() => initial.drivers);
const mappingTotals = computed(() => (review.value?.mapping ?? []).reduce((totals, row) => ({
    items: totals.items + Number(row.count ?? 0),
    live: totals.live + Number(row.live_count ?? 0),
    scheduled: totals.scheduled + Number(row.scheduled_count ?? 0),
}), {items: 0, live: 0, scheduled: 0}));
const statusChoices = computed(() => Object.entries(options.statusChoices ?? {})
    .map(([value, label]) => ({value, label})));
const previewStage = computed(() => states.value.find((state) => state.status === options.currentStatus)?.name
    ?? options.currentStatus ?? '');

function newId(prefix) {
    return `${prefix}${crypto.randomUUID()}`;
}
function stageName(status) {
    return states.value.find((state) => state.status === status)?.name ?? status;
}
function updateState(change) {
    states.value = states.value.map((state) => state.id === change.stateId ? {...state, ...change} : state);
    review.value = null;
}
function updateTransition(change) {
    transitions.value = transitions.value.map((edge) => edge.id === change.transitionId
        ? {...mergeTransitionChange(edge, change), name: change.label ?? edge.name} : edge);
    review.value = null;
}
function reconnectTransition(change) {
    if (change.transitionId === '__start__') {
        if (change.to && change.to !== start.value?.id) {
            if (!states.value.some((state) => state.id === change.to && !state.is_terminal)) return;
            states.value = states.value.map((state) => ({...state, is_start: state.id === change.to}));
        }
        startConnection.value = {to_side: change.anchors?.to_side ?? startConnection.value.to_side};
        review.value = null;
        return;
    }
    updateTransition(change);
}
function handleAddState(value) {
    const status = value.status.trim();
    if (!/^[a-z][a-z0-9_-]*$/.test(status) || states.value.some((state) => state.status === status)) {
        error.value = copy.reservation_flow_validation_state_exists;
        return;
    }
    states.value.push({id: newId('state-'), name: value.name, status, is_start: false,
        content_status: value.content_status, is_terminal: value.is_terminal, allows_approval: false});
    error.value = '';
    stateModal.value = false;
}
function handleAddTransition(value) {
    if (value.from === 'start' || value.from == null) {
        error.value = copy.reservation_flow_add_transition_connection_required;
        return;
    }
    if (transitions.value.some((edge) => edge.slug === value.slug)) {
        error.value = copy.reservation_flow_validation_transition_exists;
        return;
    }
    const candidate = {id: newId('edge-'), from: String(value.from), to: String(value.to),
        label: value.name, name: value.name, slug: value.slug, tasks: [],
        ...(value.anchors?.from_side || value.anchors?.to_side ? {anchors: value.anchors} : {})};
    const [anchored] = freezeTransitionAnchors([candidate], states.value, startPosition.value, transitions.value);
    transitions.value.push(mergeTransitionChange(anchored, {}));
    error.value = '';
    transitionModal.value = false;
}
function onAddTransition(preset = {}) {
    transitionPreset.value = {from: preset.from != null ? String(preset.from) : '',
        to: preset.to != null ? String(preset.to) : '', anchors: preset.anchors ?? {}};
    transitionModal.value = true;
}
function modifyTask({transitionId, ...task}) {
    if (!transitions.value.some((edge) => edge.id === transitionId)) {
        error.value = copy.reservation_flow_start_edge_hint;
        announce(error.value, 'error');
        return;
    }
    transitions.value = transitions.value.map((edge) => edge.id === transitionId
        ? {...edge, tasks: [...(edge.tasks ?? []), {...task, id: newId('task-')}]} : edge);
    review.value = null;
}
function updateTask({transitionId, taskId, ...changes}) {
    transitions.value = transitions.value.map((edge) => edge.id === transitionId
        ? {...edge, tasks: edge.tasks.map((task) => task.id === taskId ? {...task, ...changes} : task)} : edge);
    review.value = null;
}
function removeTask({transitionId, taskId}) {
    transitions.value = transitions.value.map((edge) => edge.id === transitionId
        ? {...edge, tasks: edge.tasks.filter((task) => task.id !== taskId)} : edge);
    review.value = null;
}
async function command(data) {
    busy.value = true;
    error.value = '';
    try {
        const response = await fetch(options.url, {
            method: 'POST', credentials: 'same-origin',
            headers: {Accept: 'application/json', 'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content},
            body: JSON.stringify(data),
        });
        const result = await response.json();
        if (!response.ok || !result.ok) {
            throw Object.assign(new Error(result.message || copy.save_failed), {errors: result.errors ?? {}});
        }
        return result.data;
    } catch (exception) {
        error.value = Object.values(exception.errors ?? {})[0]?.[0] || exception.message;
        announce(error.value, 'error');
        return null;
    } finally {
        busy.value = false;
    }
}
async function runSimulation(scenario) {
    return command({operation: 'simulate', graph: JSON.parse(JSON.stringify(graph.value)), scenario});
}
async function saveDraft() {
    // graph contains Vue proxies; serialize the same plain JSON sent to the server.
    const submittedGraph = JSON.parse(JSON.stringify(graph.value));
    const submittedEnabled = enabled.value;
    const result = await command({operation: 'draft', graph: submittedGraph, enabled: submittedEnabled, version: version.value});
    if (!result) return;
    const submittedStates = submittedGraph.states.map((state) => state.id).sort();
    const savedStates = (result.graph?.states ?? []).map((state) => String(state.id)).sort();
    const transitionSnapshot = (edge) => ({
        id: String(edge.id), from: String(edge.from), to: String(edge.to), slug: edge.slug, label: edge.label,
        roles: (edge.roles ?? []).map(Number),
        anchors: { ...(edge.anchors?.from_side ? {from_side: edge.anchors.from_side} : {}),
            ...(edge.anchors?.to_side ? {to_side: edge.anchors.to_side} : {}),
            ...(Number.isFinite(Number(edge.anchors?.route_offset_x)) ? {route_offset_x: Number(edge.anchors.route_offset_x)} : {}),
            ...(Number.isFinite(Number(edge.anchors?.route_offset_y)) ? {route_offset_y: Number(edge.anchors.route_offset_y)} : {}) },
        tasks: (edge.tasks ?? []).map((task) => ({
            driver: task.driver, config: Array.isArray(task.config) && task.config.length === 0 ? {} : (task.config ?? {}),
            status: task.status ?? true,
        })),
    });
    const submittedTransitions = submittedGraph.transitions.map(transitionSnapshot).sort((a, b) => a.id.localeCompare(b.id));
    const savedTransitions = (result.graph?.transitions ?? []).map(transitionSnapshot).sort((a, b) => a.id.localeCompare(b.id));
    version.value = result.version;
    definitionId.value = result.definition_id;
    definitionState.value = result.state;
    if (JSON.stringify(submittedStates) !== JSON.stringify(savedStates)
        || JSON.stringify(submittedTransitions) !== JSON.stringify(savedTransitions)) {
        error.value = copy.reservation_flow_incomplete_save;
        announce(error.value, 'error');
        return;
    }
    baseline.value = JSON.stringify(submittedGraph);
    baselineEnabled.value = submittedEnabled;
    review.value = null;
    announce(copy.saved);
}
async function preview() {
    if (dirty.value) { error.value = copy.reservation_flow_no_changes_to_update; return; }
    const result = await command({operation: 'preview', definition_id: definitionId.value});
    if (!result) return;
    review.value = result;
    mapping.value = Object.fromEntries(result.mapping.map((row) => [row.from, row.to ?? '']));
}
async function activate() {
    if (!review.value || Object.values(mapping.value).some((value) => !value)) return;
    const result = await command({operation: 'activate', definition_id: definitionId.value,
        fingerprint: review.value.fingerprint, mapping: mapping.value, confirmed: 1});
    if (!result) return;
    version.value = result.version;
    definitionState.value = result.state;
    review.value = null;
    announce(copy.saved);
}
function confirmLeave(event) {
    if (!dirty.value) return;
    event.preventDefault();
    event.returnValue = '';
}
window.addEventListener('beforeunload', confirmLeave);
function preventBrowserContextMenu(event) { event.preventDefault(); }
onMounted(() => document.addEventListener('contextmenu', preventBrowserContextMenu, true));
onBeforeUnmount(() => {
    window.removeEventListener('beforeunload', confirmLeave);
    document.removeEventListener('contextmenu', preventBrowserContextMenu, true);
});
</script>

<template>
    <section class="rf-page rf-shell" :class="{'rf-page--preview': options.previewOnly}" @contextmenu.prevent>
        <WorkflowPageHeader :title="`${options.previewOnly ? copy.preview_diagram_title : copy.reservation_flow_page_title} · ${options.typeLabel}`"
            :back-url="options.previewOnly ? null : options.backUrl" :back-label="copy.back"
            :settings-label="options.previewOnly ? '' : copy.advanced_settings" :busy="busy" @settings="settingsOpen = true">
            <template v-if="!options.previewOnly" #center>
                <button type="button" class="rf-btn rf-btn--secondary rf-btn--compact" :disabled="!options.editable || busy" @click="stateModal = true"><i class="ki-filled ki-plus" aria-hidden="true" />{{ copy.reservation_flow_add_state }}</button>
                <button type="button" class="rf-btn rf-btn--secondary rf-btn--compact" :disabled="!options.editable || busy" @click="onAddTransition()"><i class="ki-filled ki-arrow-right-left" aria-hidden="true" />{{ copy.reservation_flow_add_transition }}</button>
            </template>
            <template v-if="!options.previewOnly" #before-settings>
                <button type="button" class="rf-btn rf-btn--secondary rf-btn--compact" :disabled="busy" @click="simulationOpen = true"><i class="ki-filled ki-flash" aria-hidden="true" />{{ copy.simulation_open }}</button>
            </template>
            <template v-if="!options.previewOnly" #actions>
                <button type="button" class="rf-btn rf-btn--primary rf-btn--compact" :disabled="!options.editable || busy || !dirty" @click="saveDraft">{{ busy ? copy.saving : copy.save_draft }}</button>
                <button v-if="definitionState === 'draft'" type="button" class="rf-btn rf-btn--secondary rf-btn--compact" :disabled="!options.editable || busy || dirty" @click="preview">{{ copy.review }}</button>
            </template>
        </WorkflowPageHeader>
        <p v-if="error" class="rf-error" role="alert">{{ error }}</p>
        <ReservationFlowEditor :copy="copy" :states="states" :transitions="visualTransitions"
            :start-position="startPosition"
            :current-status="options.currentStatus || ''"
            :available-statuses="statusChoices"
            :available-task-drivers="availableDrivers" :available-roles="options.viewRoles || []" :editable="options.editable" :saving="busy"
            :hide-inspector="Boolean(options.previewOnly)" :hide-mini-map="Boolean(options.previewOnly)"
            @add-state="stateModal = true" @add-transition="onAddTransition"
            @update-state="updateState" @delete-state="({stateId}) => {states = states.filter((state) => state.id !== stateId); transitions = transitions.filter((edge) => edge.from !== stateId && edge.to !== stateId)}"
            @save-graph="saveDraft"
            @duplicate-state="({stateId}) => {const source = states.find((state) => state.id === stateId); if (source) states.push({...source, id: newId('state-'), name: source.name + ' 2', status: source.status + '_2', is_start: false})}"
            @move-state="({stateId, position}) => updateState({stateId, position})"
            @move-start="({position}) => {startPosition = position; review = null}"
            @update-transition="updateTransition" @delete-transition="({transitionId}) => {transitions = transitions.filter((edge) => edge.id !== transitionId)}"
            @reconnect-transition="reconnectTransition" @add-task="modifyTask" @update-task="updateTask" @delete-task="removeTask"
            @state-menu-edit="({stateId}) => {}" @state-menu-add-transition="({stateId}) => onAddTransition({from: stateId})" />
        <WorkflowSettingsDialog :open="settingsOpen" :title="copy.advanced_settings" :intro="copy.advanced_settings_intro"
            :close-label="copy.cancel" :busy="busy" @close="settingsOpen = false">
            <p>{{ copy.advanced_settings_explanation }}</p><div class="rf-settings-dialog__option"><div><strong>{{ copy.enabled }}</strong><small>{{ copy.enabled_hint }}</small></div><label class="rf-settings-switch"><input v-model="enabled" type="checkbox" role="switch" :disabled="!options.editable || busy" :aria-label="copy.enabled" /><span /></label></div><p class="rf-settings-dialog__note"><i class="ki-filled ki-information-2" aria-hidden="true" />{{ copy.advanced_settings_activation }}</p>
            <template #footer><button type="button" class="rf-btn rf-btn--primary" @click="settingsOpen = false">{{ copy.advanced_settings_done }}</button></template>
        </WorkflowSettingsDialog>
        <div v-if="review" class="rf-mapping" role="dialog" aria-modal="true" :aria-label="copy.mapping_title" @click.self="review = null">
            <div class="rf-mapping__panel">
                <header class="rf-mapping__header">
                    <span class="rf-mapping__icon"><i class="ki-filled ki-arrow-right-left" aria-hidden="true" /></span>
                    <div><h2>{{ copy.mapping_title }}</h2><p>{{ copy.mapping_hint }}</p></div>
                    <button type="button" class="rf-mapping__close" :aria-label="copy.cancel" @click="review = null"><i class="ki-filled ki-cross" aria-hidden="true" /></button>
                </header>
                <div class="rf-mapping__body">
                    <div class="rf-mapping__summary">
                        <div><strong>{{ mappingTotals.items }}</strong><span>{{ copy.count }}</span></div>
                        <div><strong>{{ mappingTotals.live }}</strong><span>{{ copy.live_count }}</span></div>
                        <div><strong>{{ mappingTotals.scheduled }}</strong><span>{{ copy.scheduled_count }}</span></div>
                    </div>
                    <div class="rf-mapping__rows">
                        <div v-for="row in review.mapping" :key="row.from" class="rf-mapping__row">
                            <div class="rf-mapping__source"><small>{{ copy.from }}</small><strong>{{ stageName(row.from) }}</strong>
                                <span>{{ row.count }} {{ copy.count }} · {{ row.live_count }} {{ copy.live_count }} · {{ row.scheduled_count }} {{ copy.scheduled_count }}</span></div>
                            <i class="ki-filled ki-arrow-right rf-mapping__arrow" aria-hidden="true" />
                            <label class="rf-mapping__target"><small>{{ copy.to }}</small><select v-model="mapping[row.from]" :aria-label="`${copy.to}: ${stageName(row.from)}`"><option value="">{{ copy.choose }}</option>
                                <option v-for="target in review.targets" :key="target" :value="target">{{ stageName(target) }}</option></select></label>
                        </div>
                        <p v-if="!review.mapping.length" class="rf-mapping__empty">{{ copy.mapping_empty }}</p>
                    </div>
                </div>
                <footer class="rf-mapping__actions">
                    <button type="button" class="rf-btn rf-btn--primary" :disabled="busy || Object.values(mapping).some((value) => !value)" @click="activate">{{ busy ? copy.saving : copy.activate }}</button></footer>
            </div>
        </div>
        <ReservationFlowAddStateModal :copy="copy" :open="stateModal" :saving="busy" :available-statuses="statusChoices" @close="stateModal = false" @submit="handleAddState" />
        <ReservationFlowAddTransitionModal :copy="copy" :open="transitionModal" :states="states" :transitions="visualTransitions"
            :preset-from="transitionPreset.from" :preset-to="transitionPreset.to"
            :preset-anchors="transitionPreset.anchors" :saving="busy"
            @close="transitionModal = false" @submit="handleAddTransition" />
        <ReservationFlowConfirmModal :open="confirm.isOpen.value" :copy="copy" :title="confirm.current.value.title"
            :message="confirm.current.value.message" :warning="confirm.current.value.warning"
            :confirm-label="confirm.current.value.confirmLabel" :cancel-label="confirm.current.value.cancelLabel"
            :variant="confirm.current.value.variant" :focus="confirm.current.value.focus"
            @confirm="confirm.handleConfirm" @cancel="confirm.handleCancel" />
        <WorkflowSimulationModal :open="simulationOpen" :copy="copy" :states="states"
            :roles="options.simulationRoles" :locales="options.simulationLocales" :status-choices="options.statusChoices" :run="runSimulation"
            @close="simulationOpen = false" />
        <WorkflowToast :close-label="copy.cancel" />
    </section>
</template>

<style>
body.workflow-icons-active i.ki-filled::before,body.workflow-icons-active i.ki-filled::after { content:none !important; }
body.workflow-icons-active i.ki-filled { display:inline-flex; align-items:center; justify-content:center; line-height:1; }
body.workflow-icons-active i.ki-filled .ui-icon { display:block; }
.cms-page--workflow { margin:0; overflow:hidden; }
[data-workflow-designer], .rf-page { width:100%; height:100dvh; min-height:0; }
.rf-shell { border:0; border-radius:0; overflow:hidden; }
.rf-page > .rf-editor { flex:1; min-height:0; }
.rf-page--preview .rf-header { min-height:56px; }
.rf-preview-stage { display:inline-flex; align-items:center; gap:7px; padding:6px 10px; border:1px solid var(--rf-border); border-radius:8px; color:#29ab91; background:var(--rf-panel); font-size:12px; font-weight:700; }
.rf-toggle { display:flex; align-items:center; gap:8px; font-size:13px; color:var(--rf-text); cursor:help; }.rf-toggle i { color:var(--rf-muted); }
.rf-mapping__actions { justify-content:flex-end; }
.rf-error { color:#d23f4f; padding:10px 20px; }
.rf-mapping { position:fixed; z-index:1000; inset:0; background:rgb(15 23 42 / 65%); display:grid; place-items:center; padding:20px; backdrop-filter:blur(5px); }
.rf-mapping__panel { --rf-map-surface:#fff; --rf-map-soft:#f7f9fb; --rf-map-border:#e4e8ee; --rf-map-text:#1f2d48; --rf-map-muted:#72809a; display:flex; flex-direction:column; width:min(860px,100%); max-height:min(88vh,850px); overflow:hidden; background:var(--rf-map-surface); color:var(--rf-map-text); border:1px solid var(--rf-map-border); border-radius:20px; box-shadow:0 26px 80px rgb(0 0 0 / 25%); }
.rf-mapping__header { display:flex; align-items:flex-start; gap:14px; padding:24px 26px; border-bottom:1px solid var(--rf-map-border); }
.rf-mapping__icon { display:grid; place-items:center; flex:0 0 44px; height:44px; border-radius:13px; color:#279f89; background:#e7f7f2; font-size:20px; }
.rf-mapping__header h2 { margin:0; font-size:20px; font-weight:750; color:var(--rf-map-text); }
.rf-mapping__header p { margin:6px 0 0; font-size:12px; line-height:1.7; color:var(--rf-map-muted); }
.rf-mapping__close { display:grid; place-items:center; width:34px; height:34px; margin-inline-start:auto; border:1px solid var(--rf-map-border); border-radius:9px; background:var(--rf-map-surface); color:var(--rf-map-muted); cursor:pointer; }
.rf-mapping__body { overflow:auto; padding:20px 26px 24px; }
.rf-mapping__summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; margin-bottom:20px; }
.rf-mapping__summary div { display:flex; flex-direction:column; gap:5px; padding:15px 17px; border:1px solid var(--rf-map-border); border-radius:12px; background:var(--rf-map-soft); }
.rf-mapping__summary strong { font-size:20px; line-height:1.2; color:var(--rf-map-text); }.rf-mapping__summary span { font-size:11px; color:var(--rf-map-muted); }
.rf-mapping__rows { display:grid; gap:10px; }.rf-mapping__row { display:grid; grid-template-columns:minmax(0,1fr) 34px minmax(0,1fr); align-items:center; gap:12px; padding:14px 16px; border:1px solid var(--rf-map-border); border-radius:13px; background:var(--rf-map-surface); }
.rf-mapping__source { display:flex; flex-direction:column; gap:4px; min-width:0; }.rf-mapping__source strong { overflow:hidden; text-overflow:ellipsis; font-size:14px; }.rf-mapping__source span { font-size:11px; color:var(--rf-map-muted); line-height:1.5; }
.rf-mapping__source small,.rf-mapping__target small { display:block; margin-bottom:5px; font-size:11px; color:var(--rf-map-muted); }
.rf-mapping__arrow { display:grid; place-items:center; width:32px; height:32px; border-radius:50%; background:#e7f7f2; color:#27a58e; }
[dir=rtl] .rf-mapping__arrow { transform:scaleX(-1); }
.rf-mapping__target { min-width:0; }.rf-mapping__target select { display:block; width:100%; min-height:40px; padding:7px 10px; border:1px solid var(--rf-map-border); border-radius:9px; background:var(--rf-map-soft); color:var(--rf-map-text); outline:none; }
.rf-mapping__target select:focus { border-color:#2eae98; box-shadow:0 0 0 3px rgb(46 174 152 / 14%); }
.rf-mapping__empty { margin:0; padding:24px; text-align:center; border:1px dashed var(--rf-map-border); border-radius:12px; color:var(--rf-map-muted); font-size:13px; }
.rf-mapping__actions { display:flex; justify-content:space-between; align-items:center; gap:12px; padding:16px 26px; border-top:1px solid var(--rf-map-border); }
.rf-mapping__actions .rf-btn--secondary { color:var(--rf-map-text); background:var(--rf-map-surface); border-color:var(--rf-map-border); }
html[data-theme=dark] .rf-mapping__panel { --rf-map-surface:var(--surface); --rf-map-soft:var(--surface-soft); --rf-map-border:var(--border); --rf-map-text:var(--text); --rf-map-muted:var(--text-soft); color-scheme:dark; }
html[data-theme=dark] .rf-mapping__icon,html[data-theme=dark] .rf-mapping__arrow { background:var(--primary-soft); color:var(--primary); }
/* The designer is outside the admin shell, so its canvas and inspector must carry their own theme. */
html[data-theme=dark] .rf-page .rf-editor__workspace { border-color:var(--rf-border); }
html[data-theme=dark] .rf-page .rf-editor__canvas-wrap { background-color:var(--rf-canvas); background-image:radial-gradient(circle,rgb(255 255 255 / 9%) 1px,transparent 1px); }
html[data-theme=dark] .rf-page .rf-editor__edge { stroke:var(--text-soft); }
html[data-theme=dark] .rf-page .rf-editor__arrow-head { fill:var(--text-soft); }
html[data-theme=dark] .rf-page .rf-editor__edge-label { background:var(--surface-raised); color:var(--text); border-color:var(--border-strong); }
html[data-theme=dark] .rf-page .rf-editor__node { background:color-mix(in srgb,var(--rf-node-border) 7%,var(--surface)); border-color:color-mix(in srgb,var(--rf-node-border) 42%,var(--border-strong)); box-shadow:0 5px 18px rgb(0 0 0 / 13%); }
html[data-theme=dark] .rf-page .rf-editor__node.is-terminal { background:color-mix(in srgb,#2eae98 14%,var(--surface)); border-color:#2eae98; box-shadow:0 4px 14px rgb(0 0 0 / 13%); }
html[data-theme=dark] .rf-page .rf-editor__node.is-terminal.is-current-reservation { border-color:#F59E0B; }
html[data-theme=dark] .rf-page .rf-editor__node-title { color:var(--text); }
html[data-theme=dark] .rf-page .rf-editor__node-menu { color:var(--text-soft); }
html[data-theme=dark] .rf-page .rf-editor__node-menu:hover { background:var(--surface-soft); }
html[data-theme=dark] .rf-page .rf-editor__port { background:var(--surface); }
html[data-theme=dark] .rf-page .rf-editor__inspector { background:var(--surface); border-color:var(--rf-border); }
html[data-theme=dark] .rf-page .rf-inspector-head,
html[data-theme=dark] .rf-page .rf-inspector-tabs,
html[data-theme=dark] .rf-page .rf-inspector-panel,
html[data-theme=dark] .rf-page .rf-inspector-foot { background:var(--surface); border-color:var(--rf-border); }
html[data-theme=dark] .rf-page .rf-inspector-head__title,
html[data-theme=dark] .rf-page .rf-section-head__title,
html[data-theme=dark] .rf-page .rf-inspector-readonly h3,
html[data-theme=dark] .rf-page .rf-inspector-form h3 { color:var(--rf-text); }
html[data-theme=dark] .rf-page .rf-inspector-head__meta,
html[data-theme=dark] .rf-page .rf-field__label,
html[data-theme=dark] .rf-page .rf-field__hint,
html[data-theme=dark] .rf-page .rf-inspector-tabs__btn { color:var(--rf-muted); }
html[data-theme=dark] .rf-page .rf-inspector-tabs__btn.is-active { color:var(--primary); background:var(--primary-soft); }
html[data-theme=dark] .rf-page .rf-inspector-panel input,
html[data-theme=dark] .rf-page .rf-inspector-panel select { background:var(--surface-soft); color:var(--rf-text); border-color:var(--rf-border); }
html[data-theme=dark] .rf-page .rf-inspector-panel button:not(.rf-btn--primary):not(.rf-btn--danger),
html[data-theme=dark] .rf-page .rf-inspector-foot .rf-btn--secondary { background:var(--surface-soft); color:var(--rf-text); border-color:var(--rf-border); }
html[data-theme=dark] .rf-page .rf-canvas-toolbar,
html[data-theme=dark] .rf-page .rf-zoom,
html[data-theme=dark] .rf-page .rf-minimap { background:var(--surface); color:var(--rf-text); border-color:var(--rf-border); }
html[data-theme=dark] .rf-page .rf-canvas-toolbar button,
html[data-theme=dark] .rf-page .rf-zoom button { background:var(--surface); color:var(--rf-text); }
html[data-theme=dark] .rf-page .rf-canvas-toolbar button:hover,
html[data-theme=dark] .rf-page .rf-zoom button:hover { background:var(--surface-soft); }
html[data-theme=dark] .rf-page .rf-canvas-toolbar button.is-active { background:var(--primary-soft); color:var(--primary); }
html[data-theme=dark] .rf-page .rf-zoom__label { color:var(--text-soft); }
html[data-theme=dark] .rf-page .rf-inspector-empty__icon { background:var(--primary-soft); color:var(--primary); }
html[data-theme=dark] .rf-page .rf-inspector-empty__hints { color:var(--text-soft); }
html[data-theme=dark] .rf-page .rf-inspector-empty h3 { color:var(--rf-text); }
html[data-theme=dark] .rf-page .rf-inspector-empty p { color:var(--rf-muted); }
html[data-theme=light] .rf-page .rf-inspector-tabs__btn.is-active { color:#17957f; }
html[data-theme=light] .rf-page .rf-inspector-foot .rf-btn--primary { background:#2eae98; }
.rf-page .rf-editor__inspector .rf-inspector-head { border-color:var(--rf-border); }
.rf-page .rf-editor__inspector .rf-inspector-head__title { color:var(--rf-text); font-size:16px; line-height:1.45; }
.rf-page .rf-editor__inspector .rf-inspector-head__meta { color:var(--rf-muted); line-height:1.5; }
.rf-page .rf-editor__inspector .rf-inspector-head__close { border:1px solid var(--rf-border); border-radius:9px; color:var(--rf-muted); }
.rf-page .rf-editor__inspector .rf-inspector-head__close:hover { background:var(--rf-canvas); color:var(--rf-text); }
.rf-page .rf-editor__inspector .rf-inspector-tabs__btn { color:var(--rf-muted); transition:background .15s,color .15s; }
.rf-page .rf-editor__inspector .rf-inspector-tabs__btn:hover { background:var(--rf-canvas); color:var(--rf-text); }
.rf-page .rf-editor__inspector .rf-inspector-tabs__btn.is-active { background:#e8f7f3; color:#148b76; }
.rf-page .rf-editor__inspector .rf-inspector-panel { border-color:var(--rf-border); }
.rf-page .rf-editor__inspector .rf-field__label { color:var(--rf-text); font-weight:650; font-size:12px; }
.rf-page .rf-editor__inspector .rf-field__hint { color:var(--rf-muted); line-height:1.65; }
.rf-page .rf-editor__inspector .rf-field__input { min-height:42px; border-radius:9px; border-color:var(--rf-border); color:var(--rf-text); background:var(--rf-panel); }
.rf-page .rf-editor__inspector .rf-inspector-input { display:block; width:100%; padding:9px 12px; border:1px solid var(--rf-border); border-radius:9px; background:var(--rf-panel); color:var(--rf-text); font:inherit; line-height:1.5; outline:none; transition:border-color .15s,box-shadow .15s; }
.rf-page .rf-editor__inspector .rf-inspector-input:disabled { opacity:.65; cursor:not-allowed; background:var(--rf-canvas); }
.rf-page .rf-editor__inspector .rf-field__input:focus { border-color:#2eae98; box-shadow:0 0 0 3px rgb(46 174 152 / 13%); }
.rf-page .rf-editor__inspector .rf-transition-row { border-color:var(--rf-border); background:var(--rf-panel); }
.rf-page .rf-editor__inspector .rf-transition-row:hover { border-color:#2eae98; background:var(--rf-canvas); }
.rf-page .rf-editor__inspector .rf-transition-row__label,.rf-page .rf-editor__inspector .rf-connection-list__row dd { color:var(--rf-text); }
.rf-page .rf-editor__inspector .rf-transition-row__route,.rf-page .rf-editor__inspector .rf-connection-list__row dt { color:var(--rf-muted); }
.rf-page .rf-editor__inspector .rf-count-badge { background:var(--rf-canvas); color:var(--rf-text); }
.rf-page .rf-editor__inspector .rf-section-head__add { border-color:var(--rf-border); background:var(--rf-panel); color:#2eae98; }
.rf-page .rf-editor__inspector .rf-inspector-foot .rf-btn--primary { background:#2eae98; border-color:#2eae98; color:#fff; }
html[data-theme=dark] .rf-page .rf-editor__inspector .rf-inspector-tabs__btn.is-active { background:var(--primary-soft); color:var(--primary); }
html[data-theme=dark] .rf-page .rf-editor__inspector .rf-field__input,
html[data-theme=dark] .rf-page .rf-editor__inspector .rf-transition-row,
html[data-theme=dark] .rf-page .rf-editor__inspector .rf-section-head__add { background:var(--surface-soft); }
html[data-theme=dark] .rf-flow-modal {
    --rf-modal-bg:var(--surface);
    --rf-modal-surface:var(--surface-soft);
    --rf-modal-text:var(--text);
    --rf-modal-secondary:var(--text-soft);
    --rf-modal-muted:var(--muted);
    --rf-modal-border:var(--border);
    --rf-modal-border-strong:var(--border-strong);
    --rf-modal-indigo-soft:var(--primary-soft);
    --rf-modal-indigo:var(--primary);
}
html[data-theme=dark] .rf-flow-modal__footer,
html[data-theme=dark] .rf-flow-modal__preview-pane,
html[data-theme=dark] .rf-flow-advanced { background:var(--surface-soft); border-color:var(--border); }
html[data-theme=dark] .rf-flow-modal .rf-flow-field__input,
html[data-theme=dark] .rf-flow-modal .rf-flow-picker__card,
html[data-theme=dark] .rf-flow-modal .rf-flow-btn--secondary,
html[data-theme=dark] .rf-flow-modal .rf-flow-btn--ghost,
html[data-theme=dark] .rf-flow-modal .rf-flow-route-preview,
html[data-theme=dark] .rf-flow-modal .rf-flow-route-preview__node { background:var(--surface); border-color:var(--border-strong); color:var(--text); }
html[data-theme=dark] .rf-flow-modal .rf-flow-field__label,
html[data-theme=dark] .rf-flow-modal .rf-flow-picker__name,
html[data-theme=dark] .rf-flow-modal .rf-flow-route-preview__label,
html[data-theme=dark] .rf-flow-modal .rf-flow-advanced summary { color:var(--text); }
html[data-theme=dark] .rf-flow-modal .rf-flow-steps__item { background:var(--surface-soft); color:var(--text-soft); }
html[data-theme=dark] .rf-flow-modal .rf-flow-steps__item.is-active { background:var(--primary-soft); color:var(--primary); }
html[data-theme=dark] .rf-flow-modal .rf-flow-picker__card.is-selected { background:var(--primary-soft); border-color:var(--primary); }
@media(max-width:900px) { .rf-header__left,.rf-header__right,.rf-header__center { flex-wrap:wrap; } .cms-page--workflow { overflow:auto; } .rf-mapping__row { grid-template-columns:1fr; } .rf-mapping__arrow { transform:rotate(90deg); } [dir=rtl] .rf-mapping__arrow { transform:rotate(90deg); } }
@media(max-width:540px) { .rf-mapping__summary { grid-template-columns:1fr 1fr 1fr; } .rf-mapping__summary div { padding:10px; } .rf-mapping__header,.rf-mapping__body,.rf-mapping__actions { padding-inline:16px; } .rf-mapping__actions { flex-wrap:wrap; } }
</style>
