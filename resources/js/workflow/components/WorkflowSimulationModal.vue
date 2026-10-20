<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import WorkflowSimulationSelect from './WorkflowSimulationSelect.vue';
import WorkflowSimulationReport from './WorkflowSimulationReport.vue';
import { inspectionEntry, transitionEntry } from '../utils/workflowSimulationReport.js';

const props = defineProps({
    open: { type: Boolean, default: false },
    copy: { type: Object, required: true },
    states: { type: Array, required: true },
    roles: { type: Array, default: () => [] },
    locales: { type: Array, default: () => [] },
    statusChoices: { type: Object, default: () => ({}) },
    run: { type: Function, required: true },
});
const emit = defineEmits(['close']);
const scenario = reactive({ state_id: '', locale: '', role_id: null, title: '', excerpt: '', body: '', approved: false });
const result = ref(null);
const history = ref([]);
const running = ref(false);
const runError = ref(false);
const reportOpen = ref(false);
const reportEntries = ref([]);
const awaitingNextStage = ref(false);
const session = ref(0);
const reportTrigger = ref(null);
const initialState = computed(() => props.states.find((state) => state.is_start)?.id ?? props.states[0]?.id ?? '');
const stateOptions = computed(() => props.states.map((state) => ({value: state.id, label: state.translations?.[scenario.locale]?.name ?? state.name})));
const localeOptions = computed(() => props.locales.map((locale) => ({value: locale.code, label: locale.name})));
const roleOptions = computed(() => [{value: null, label: props.copy.simulation_current_user}, ...props.roles.map((role) => ({value: role.id, label: role.name}))]);
const readyCount = computed(() => result.value?.transitions.filter((transition) => transition.status === 'ready').length ?? 0);
function statusLabel(status) { return props.statusChoices[status] ?? status; }
function primaryIssue(transition) { return transition.checks.find((check) => check.status === 'blocked') ?? transition.checks.find((check) => check.status === 'unknown'); }
function stageName(id) { const state = props.states.find((item) => item.id === id); return state?.translations?.[scenario.locale]?.name ?? state?.name ?? id; }
function record(entry) { reportEntries.value.push({sequence: reportEntries.value.length + 1, ...entry}); }

watch(() => props.open, (open) => {
    if (!open) return;
    session.value += 1;
    scenario.state_id = initialState.value;
    scenario.locale = props.locales[0]?.code ?? '';
    scenario.role_id = null;
    scenario.title = '';
    scenario.excerpt = '';
    scenario.body = '';
    scenario.approved = false;
    result.value = null;
    runError.value = false;
    history.value = [];
    reportEntries.value = [];
    reportOpen.value = false;
    awaitingNextStage.value = false;
    running.value = false;
});
watch(reportOpen, async (open) => {
    if (!open && props.open) { await nextTick(); reportTrigger.value?.focus(); }
});

function invalidate() { result.value = null; runError.value = false; }
function selectStage(value) {
    if (value === scenario.state_id) return;
    if (reportEntries.value.length) record({kind: 'jump', from: stageName(scenario.state_id), to: stageName(value)});
    scenario.state_id = value;
    history.value = [];
    awaitingNextStage.value = true;
    invalidate();
}

async function test() {
    if (running.value || !scenario.state_id || !scenario.locale) return;
    running.value = true;
    result.value = null;
    runError.value = false;
    const snapshot = { ...scenario };
    const activeSession = session.value;
    try {
        const tested = await props.run(snapshot);
        if (activeSession !== session.value || !props.open) return;
        if (tested) {
            record(inspectionEntry({sequence: reportEntries.value.length + 1, step: history.value.length + 1,
                scenario: snapshot, result: tested,
                locale: props.locales.find((locale) => locale.code === snapshot.locale)?.name ?? snapshot.locale}));
        } else {
            record({kind: 'error', state: stageName(snapshot.state_id), role: roleOptions.value.find((role) => role.value === snapshot.role_id)?.label ?? props.copy.simulation_current_user});
        }
        if (JSON.stringify(snapshot) === JSON.stringify(scenario)) {
            result.value = tested;
            runError.value = !tested;
            if (tested) awaitingNextStage.value = false;
        }
    } catch (_) {
        if (activeSession !== session.value || !props.open) return;
        record({kind: 'error', state: stageName(snapshot.state_id), role: roleOptions.value.find((role) => role.value === snapshot.role_id)?.label ?? props.copy.simulation_current_user});
        runError.value = true;
    } finally {
        if (activeSession === session.value) running.value = false;
    }
}

function continueTo(transition) {
    if (transition.status !== 'ready' || !result.value) return;
    record(transitionEntry({sequence: reportEntries.value.length + 1, step: history.value.length + 1,
        current: result.value, transition, contentStatus: statusLabel(transition.content_status)}));
    history.value.push({ label: transition.label, to: transition.to_name });
    scenario.state_id = transition.to;
    awaitingNextStage.value = true;
    invalidate();
}

function restart() {
    if (reportEntries.value.length) record({kind: 'restart'});
    scenario.state_id = initialState.value;
    history.value = [];
    result.value = null;
    runError.value = false;
    awaitingNextStage.value = false;
}

function onKeydown(event) {
    if (props.open && event.key === 'Escape') {
        if (reportOpen.value) reportOpen.value = false;
        else emit('close');
    }
}
onMounted(() => window.addEventListener('keydown', onKeydown));
onBeforeUnmount(() => window.removeEventListener('keydown', onKeydown));
</script>

<template>
    <div v-if="open" class="rf-sim-backdrop" @click.self="emit('close')">
        <section class="rf-sim" role="dialog" aria-modal="true" :aria-label="copy.simulation_title" :inert="reportOpen">
            <header class="rf-sim__header">
                <span class="rf-sim__icon"><i class="ki-filled ki-flash" aria-hidden="true" /></span>
                <div class="rf-sim__heading">
                    <h2>{{ copy.simulation_title }}</h2>
                    <p>{{ copy.simulation_intro }}</p>
                </div>
                <div class="rf-sim__header-actions"><button ref="reportTrigger" type="button" class="rf-sim__report-trigger" @click="reportOpen = true"><i class="ki-filled ki-history" aria-hidden="true" />{{ copy.simulation_report }}<span>{{ reportEntries.length }}</span></button><button type="button" class="rf-sim__close" :aria-label="copy.cancel" @click="emit('close')"><i class="ki-filled ki-cross" aria-hidden="true" /></button></div>
            </header>
            <div class="rf-sim__body">
                <form class="rf-sim__form" @submit.prevent="test">
                    <div class="rf-sim__guide"><span>1</span><div><h3>{{ copy.simulation_step_context }}</h3><p>{{ copy.simulation_step_context_hint }}</p></div></div>
                    <div class="rf-sim__fields-grid">
                        <div class="rf-sim__field"><span>{{ copy.simulation_state }}</span><WorkflowSimulationSelect :model-value="scenario.state_id" :options="stateOptions" :label="copy.simulation_state" :disabled="running" @update:model-value="selectStage" /></div>
                        <div class="rf-sim__field"><span>{{ copy.simulation_locale }}</span><WorkflowSimulationSelect v-model="scenario.locale" :options="localeOptions" :label="copy.simulation_locale" :disabled="running" @update:model-value="invalidate" /></div>
                    </div>
                    <div class="rf-sim__field"><span>{{ copy.simulation_role }}</span><WorkflowSimulationSelect v-model="scenario.role_id" :options="roleOptions" :label="copy.simulation_role" :disabled="running" @update:model-value="invalidate" /></div>
                    <p class="rf-sim__hint">{{ copy.simulation_role_step_hint }}</p>
                    <div class="rf-sim__guide rf-sim__guide--second"><span>2</span><div><h3>{{ copy.simulation_step_sample }}</h3><p>{{ copy.simulation_step_sample_hint }}</p></div></div>
                    <label class="rf-sim__field"><span>{{ copy.simulation_title_field }}</span><input v-model="scenario.title" maxlength="300" :disabled="running" @input="invalidate" /></label>
                    <label class="rf-sim__field"><span>{{ copy.simulation_excerpt }}</span><textarea v-model="scenario.excerpt" rows="2" maxlength="3000" :disabled="running" @input="invalidate" /></label>
                    <label class="rf-sim__field"><span>{{ copy.simulation_body }}</span><textarea v-model="scenario.body" rows="3" maxlength="20000" :disabled="running" @input="invalidate" /></label>
                    <label class="rf-sim__approved"><input v-model="scenario.approved" type="checkbox" :disabled="running" @change="invalidate" /><span>{{ copy.simulation_approved }}</span></label>
                    <button type="submit" class="rf-btn rf-btn--primary rf-sim__run" :disabled="running || !scenario.state_id || !scenario.locale"><i class="ki-filled ki-flash" aria-hidden="true" />{{ running ? copy.simulation_running : copy.simulation_run }}</button>
                </form>
                <div class="rf-sim__results" aria-live="polite">
                    <div class="rf-sim__results-head"><div class="rf-sim__guide"><span>3</span><div><h3>{{ copy.simulation_results }}</h3><p>{{ copy.simulation_results_hint }}</p></div></div><button v-if="history.length" type="button" class="rf-sim__restart" @click="restart"><i class="ki-filled ki-arrow-right-left" aria-hidden="true" />{{ copy.simulation_restart }}</button></div>
                    <p v-if="runError" class="rf-sim__empty rf-sim__empty--error" role="alert">{{ copy.simulation_failed }}</p>
                    <div v-else-if="!result" class="rf-sim__empty"><span class="rf-sim__empty-icon"><i class="ki-filled ki-route" aria-hidden="true" /></span><strong>{{ awaitingNextStage ? copy.simulation_next_stage_title : copy.simulation_empty_title }}</strong><span>{{ awaitingNextStage ? copy.simulation_next_stage_hint.replace(':stage', stageName(scenario.state_id)) : copy.simulation_empty }}</span></div>
                    <template v-else>
                        <div class="rf-sim__overview"><div class="rf-sim__overview-stage"><span class="rf-sim__overview-icon"><i class="ki-filled ki-element-11" aria-hidden="true" /></span><div><small>{{ copy.simulation_state }}</small><strong>{{ result.state.name }}</strong><small>{{ result.role }}</small></div></div><span class="rf-sim__overview-count"><strong>{{ readyCount }} / {{ result.transitions.length }}</strong><small>{{ copy.simulation_ready }}</small></span></div>
                        <p class="rf-sim__summary">{{ copy.simulation_result_summary.replace(':ready', readyCount).replace(':total', result.transitions.length) }}</p>
                        <div v-if="history.length" class="rf-sim__history"><span>{{ copy.simulation_path }}</span><div><span v-for="(step, index) in history" :key="index" class="rf-sim__history-step">{{ step.label }}: {{ step.to }}</span></div></div>
                        <p v-if="!result.transitions.length" class="rf-sim__empty rf-sim__empty--terminal">{{ copy.simulation_no_transitions }}</p>
                        <div v-else class="rf-sim__routes">
                            <article v-for="(transition, index) in result.transitions" :key="transition.id" class="rf-sim__transition" :class="`is-${transition.status}`">
                                <header class="rf-sim__transition-head"><span class="rf-sim__transition-number">{{ index + 1 }}</span><div class="rf-sim__transition-route"><strong>{{ result.state.name }} <i class="ki-filled ki-arrow-right" aria-hidden="true" /> {{ transition.to_name }}</strong><small>{{ transition.label }} · {{ copy.simulation_status_after }}: {{ statusLabel(transition.content_status) }}</small></div><span class="rf-sim__state-badge" :class="`is-${transition.status}`"><i :class="`ki-filled ki-${transition.status === 'ready' ? 'check-circle' : transition.status === 'blocked' ? 'cross-circle' : 'question-2'}`" aria-hidden="true" />{{ copy[`simulation_${transition.status}`] }}</span></header>
                                <p v-if="primaryIssue(transition)" class="rf-sim__issue" :class="`is-${transition.status}`"><i :class="`ki-filled ki-${transition.status === 'blocked' ? 'cross-circle' : 'question-2'}`" aria-hidden="true" /><span><strong>{{ primaryIssue(transition).title }}:</strong> {{ primaryIssue(transition).message }}</span></p>
                                <footer class="rf-sim__transition-foot"><details v-if="transition.checks.length" class="rf-sim__details"><summary>{{ copy.simulation_checks_title }} <span>{{ transition.checks.length }}</span><i class="ki-filled ki-down" aria-hidden="true" /></summary><ul class="rf-sim__checks"><li v-for="(check, checkIndex) in transition.checks" :key="checkIndex" :class="`is-${check.status}`"><span class="rf-sim__check-icon"><i :class="`ki-filled ki-${check.status === 'passed' ? 'check-circle' : check.status === 'blocked' ? 'cross-circle' : check.status === 'unknown' ? 'question-2' : 'information-2'}`" aria-hidden="true" /></span><div><strong>{{ check.title }}</strong><small>{{ check.message }}</small></div><em>{{ copy[`simulation_${check.status}`] }}</em></li></ul></details><span v-else class="rf-sim__no-tasks">{{ copy.simulation_no_tasks }}</span><button v-if="transition.status === 'ready'" type="button" class="rf-sim__next" :disabled="running" @click="continueTo(transition)">{{ transition.label }}<i class="ki-filled ki-arrow-right" aria-hidden="true" /></button></footer>
                            </article>
                        </div>
                    </template>
                </div>
            </div>
            <footer class="rf-sim__footer"><i class="ki-filled ki-information-2" aria-hidden="true" />{{ copy.simulation_disclaimer }}</footer>
        </section>
        <WorkflowSimulationReport :open="reportOpen" :entries="reportEntries" :copy="copy" @close="reportOpen = false" />
    </div>
</template>

<style scoped>
.rf-sim-backdrop { position:fixed; z-index:1100; inset:0; display:grid; place-items:center; padding:16px; background:rgb(15 23 42 / 62%); backdrop-filter:blur(4px); }
.rf-sim { display:flex; flex-direction:column; width:min(1180px,100%); max-height:min(92dvh,940px); overflow:hidden; border:1px solid var(--rf-border); border-radius:18px; background:var(--rf-panel); color:var(--rf-text); box-shadow:0 25px 70px rgb(0 0 0 / 25%); }
.rf-sim__header { display:flex; align-items:flex-start; gap:12px; padding:18px 22px; border-bottom:1px solid var(--rf-border); }
.rf-sim__icon { display:grid; place-items:center; flex:0 0 36px; height:36px; border-radius:10px; background:color-mix(in srgb,#2eae98 13%,var(--rf-panel)); color:#2eae98; }
.rf-sim__heading { flex:1; min-width:0; }.rf-sim__heading h2 { margin:0; font-size:17px; }.rf-sim__heading p { margin:5px 0 0; color:var(--rf-muted); font-size:12px; line-height:1.7; }
.rf-sim__header-actions { display:flex; align-items:center; gap:8px; flex:0 0 auto; }.rf-sim__report-trigger { display:inline-flex; align-items:center; gap:7px; min-height:34px; padding:5px 9px; border:1px solid var(--rf-border); border-radius:9px; background:var(--rf-panel); color:var(--rf-text); font-size:11px; font-weight:700; cursor:pointer; }.rf-sim__report-trigger:hover { border-color:#2eae98; color:#168d78; }.rf-sim__report-trigger > span { display:grid; place-items:center; min-width:19px; height:19px; padding-inline:4px; border-radius:999px; background:color-mix(in srgb,#2eae98 11%,var(--rf-panel)); color:#168d78; font-size:10px; }
.rf-sim__close { display:grid; place-items:center; width:34px; height:34px; border:1px solid var(--rf-border); border-radius:9px; background:var(--rf-panel); color:var(--rf-muted); cursor:pointer; }
.rf-sim__body { display:grid; grid-template-columns:minmax(330px,390px) minmax(0,1fr); min-height:0; overflow:hidden; }
.rf-sim__form,.rf-sim__results { min-height:0; overflow:auto; padding:20px 22px; }.rf-sim__form { border-inline-end:1px solid var(--rf-border); background:var(--rf-canvas); }
.rf-sim__guide { display:flex; align-items:flex-start; gap:10px; margin-bottom:16px; }.rf-sim__guide > span { display:grid; place-items:center; flex:0 0 25px; height:25px; border-radius:8px; background:color-mix(in srgb,#2eae98 13%,var(--rf-panel)); color:#168d78; font-size:11px; font-weight:800; }.rf-sim__guide h3 { margin:1px 0 3px; font-size:13px; }.rf-sim__guide p { margin:0; color:var(--rf-muted); font-size:11px; line-height:1.6; }.rf-sim__guide--second { margin-top:20px; padding-top:18px; border-top:1px solid var(--rf-border); }
.rf-sim__fields-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:10px; }
.rf-sim__field { display:flex; flex-direction:column; gap:6px; margin-bottom:12px; font-size:11px; font-weight:700; }.rf-sim__field input,.rf-sim__field select,.rf-sim__field textarea { width:100%; min-width:0; padding:9px 10px; border:1px solid var(--rf-border); border-radius:9px; outline:none; background:var(--rf-panel); color:var(--rf-text); font:inherit; font-size:12px; }.rf-sim__field textarea { resize:vertical; }.rf-sim__field :is(input,select,textarea):focus { border-color:#2eae98; box-shadow:0 0 0 3px rgb(46 174 152 / 12%); }
.rf-sim__hint { margin:-5px 0 14px; color:var(--rf-muted); font-size:10px; line-height:1.6; }.rf-sim__approved { display:flex; align-items:center; gap:8px; margin:3px 0 16px; font-size:11px; cursor:pointer; }.rf-sim__approved input { accent-color:#2eae98; }.rf-sim__run { width:100%; }
.rf-sim__results-head { display:flex; align-items:center; justify-content:space-between; gap:12px; margin-bottom:18px; padding-bottom:16px; border-bottom:1px solid var(--rf-border); }.rf-sim__results-head .rf-sim__guide { margin-bottom:0; }
.rf-sim__restart { display:inline-flex; align-items:center; gap:6px; flex:0 0 auto; padding:7px 9px; border:1px solid var(--rf-border); border-radius:8px; background:var(--rf-panel); color:var(--rf-text); font-size:11px; font-weight:700; cursor:pointer; }.rf-sim__restart:hover { border-color:#2eae98; color:#168d78; }
.rf-sim__empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; min-height:250px; padding:26px; border:1px dashed var(--rf-border); border-radius:14px; background:var(--rf-canvas); color:var(--rf-muted); font-size:12px; line-height:1.7; text-align:center; }.rf-sim__empty-icon { display:grid; place-items:center; width:54px; height:54px; margin-bottom:4px; border:1px solid color-mix(in srgb,#2eae98 20%,var(--rf-border)); border-radius:17px; background:color-mix(in srgb,#2eae98 9%,var(--rf-panel)); color:#2eae98; }.rf-sim__empty-icon i { font-size:23px; }.rf-sim__empty strong { color:var(--rf-text); font-size:14px; }.rf-sim__empty--error { min-height:130px; color:#c54b4b; border-color:#c54b4b; }.rf-sim__empty--terminal { min-height:120px; }
.rf-sim__overview { display:flex; align-items:center; justify-content:space-between; gap:14px; padding:14px 16px; border:1px solid color-mix(in srgb,#2eae98 25%,var(--rf-border)); border-radius:13px; background:color-mix(in srgb,#2eae98 6%,var(--rf-panel)); }.rf-sim__overview-stage { display:flex; align-items:center; gap:10px; min-width:0; }.rf-sim__overview-icon { display:grid; place-items:center; flex:0 0 35px; width:35px; height:35px; border-radius:10px; background:var(--rf-panel); color:#2eae98; }.rf-sim__overview-stage div,.rf-sim__overview-count { display:flex; flex-direction:column; gap:3px; }.rf-sim__overview-stage small,.rf-sim__overview-count small { color:var(--rf-muted); font-size:10px; }.rf-sim__overview-stage strong { font-size:13px; }.rf-sim__overview-count { align-items:center; flex:0 0 auto; padding-inline-start:14px; border-inline-start:1px solid var(--rf-border); }.rf-sim__overview-count strong { color:#168d78; font-size:15px; }
.rf-sim__summary { margin:11px 0 18px; color:var(--rf-muted); font-size:11px; line-height:1.6; }.rf-sim__history { margin:0 0 18px; padding:11px 13px; border:1px solid var(--rf-border); border-radius:10px; color:var(--rf-muted); font-size:10px; }.rf-sim__history > div { display:flex; flex-wrap:wrap; gap:6px; margin-top:7px; }.rf-sim__history-step { padding:5px 8px; border-radius:7px; background:var(--rf-canvas); color:var(--rf-text); font-weight:700; }
.rf-sim__routes { display:flex; flex-direction:column; gap:9px; }.rf-sim__transition { overflow:hidden; border:1px solid var(--rf-border); border-radius:11px; background:var(--rf-panel); }.rf-sim__transition.is-ready { border-color:color-mix(in srgb,#2eae98 35%,var(--rf-border)); }.rf-sim__transition.is-blocked { border-color:color-mix(in srgb,#d95d65 30%,var(--rf-border)); }
.rf-sim__transition-head { display:flex; align-items:center; gap:9px; padding:10px 12px; }.rf-sim__transition-number { display:grid; place-items:center; flex:0 0 23px; height:23px; border-radius:7px; background:var(--rf-canvas); color:var(--rf-muted); font-size:10px; font-weight:800; }.rf-sim__transition-route { display:flex; flex:1; flex-direction:column; gap:3px; min-width:0; }.rf-sim__transition-route strong { overflow:hidden; font-size:12px; text-overflow:ellipsis; white-space:nowrap; }.rf-sim__transition-route strong i { margin-inline:4px; color:#2eae98; font-size:11px; }[dir=rtl] .rf-sim__transition-route strong i { transform:scaleX(-1); }.rf-sim__transition-route small { color:var(--rf-muted); font-size:10px; }.rf-sim__state-badge { display:inline-flex; align-items:center; gap:5px; flex:0 0 auto; padding:5px 8px; border-radius:999px; background:var(--rf-canvas); color:var(--rf-muted); font-size:10px; font-weight:700; }.rf-sim__state-badge.is-ready { background:color-mix(in srgb,#2eae98 12%,var(--rf-panel)); color:#168d78; }.rf-sim__state-badge.is-blocked { background:color-mix(in srgb,#d95d65 12%,var(--rf-panel)); color:#bf4651; }.rf-sim__state-badge.is-unknown { background:color-mix(in srgb,#d29a40 13%,var(--rf-panel)); color:#a77829; }
.rf-sim__issue { display:flex; align-items:flex-start; gap:7px; margin:0 12px 9px; padding:7px 9px; border-radius:7px; background:color-mix(in srgb,#d29a40 8%,var(--rf-panel)); color:var(--rf-text); font-size:10px; line-height:1.5; }.rf-sim__issue i { flex:0 0 auto; margin-top:1px; color:#a77829; }.rf-sim__issue.is-blocked { background:color-mix(in srgb,#d95d65 8%,var(--rf-panel)); }.rf-sim__issue.is-blocked i { color:#bf4651; }
.rf-sim__transition-foot { display:grid; grid-template-columns:minmax(0,1fr) auto; align-items:center; gap:8px; min-height:39px; padding:6px 12px; border-top:1px solid var(--rf-border); background:var(--rf-canvas); }.rf-sim__details { min-width:0; }.rf-sim__details[open] { grid-column:1 / -1; }.rf-sim__details summary { display:inline-flex; align-items:center; gap:5px; color:var(--rf-muted); font-size:10px; font-weight:700; cursor:pointer; list-style:none; }.rf-sim__details summary::-webkit-details-marker { display:none; }.rf-sim__details summary span { display:grid; place-items:center; min-width:17px; height:17px; padding-inline:4px; border-radius:50%; background:var(--rf-panel); color:var(--rf-text); font-size:9px; }.rf-sim__details[open] summary i { transform:rotate(180deg); }
.rf-sim__checks { display:flex; flex-direction:column; margin:9px 0 3px; padding:0; border:1px solid var(--rf-border); border-radius:8px; background:var(--rf-panel); list-style:none; }.rf-sim__checks li { display:flex; align-items:flex-start; gap:8px; padding:8px 10px; }.rf-sim__checks li + li { border-top:1px solid var(--rf-border); }.rf-sim__check-icon { display:grid; place-items:center; flex:0 0 21px; width:21px; height:21px; border-radius:6px; background:var(--rf-canvas); color:var(--rf-muted); }.rf-sim__checks li.is-passed .rf-sim__check-icon { background:color-mix(in srgb,#2eae98 12%,var(--rf-panel)); color:#168d78; }.rf-sim__checks li.is-blocked .rf-sim__check-icon { background:color-mix(in srgb,#d95d65 12%,var(--rf-panel)); color:#bf4651; }.rf-sim__checks li.is-unknown .rf-sim__check-icon { background:color-mix(in srgb,#d29a40 13%,var(--rf-panel)); color:#a77829; }.rf-sim__checks li > div { display:flex; flex:1; flex-direction:column; gap:2px; min-width:0; }.rf-sim__checks li strong { font-size:10px; }.rf-sim__checks li small { color:var(--rf-muted); font-size:10px; line-height:1.5; }.rf-sim__checks li em { flex:0 0 auto; color:var(--rf-muted); font-size:10px; font-style:normal; }.rf-sim__checks li.is-blocked em { color:#bf4651; }.rf-sim__no-tasks { color:var(--rf-muted); font-size:10px; }
.rf-sim__next { display:inline-flex; align-items:center; justify-self:end; gap:6px; max-width:240px; padding:6px 10px; border:1px solid #2eae98; border-radius:7px; background:#2eae98; color:#fff; font-size:11px; font-weight:700; cursor:pointer; }.rf-sim__details[open] + .rf-sim__next { grid-column:2; }.rf-sim__next:hover { background:#168d78; }.rf-sim__next:disabled { opacity:.5; cursor:not-allowed; }
.rf-sim__footer { display:flex; gap:8px; padding:12px 22px; border-top:1px solid var(--rf-border); color:var(--rf-muted); font-size:10px; line-height:1.6; }.rf-sim__footer i { color:#2eae98; }
@media(max-width:850px) { .rf-sim__body { display:block; overflow:auto; }.rf-sim__form,.rf-sim__results { overflow:visible; }.rf-sim__form { border-inline-end:0; border-bottom:1px solid var(--rf-border); } }
@media(max-width:600px) { .rf-sim__header { flex-wrap:wrap; }.rf-sim__header-actions { margin-inline-start:auto; }.rf-sim__heading { flex-basis:calc(100% - 48px); }.rf-sim__fields-grid { grid-template-columns:1fr; } }
</style>
