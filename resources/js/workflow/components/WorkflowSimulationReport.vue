<script setup>
import { computed, nextTick, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    entries: { type: Array, required: true },
    copy: { type: Object, required: true },
});
const emit = defineEmits(['close']);
const closeButton = ref(null);
const inspections = computed(() => props.entries.filter((entry) => entry.kind === 'inspection'));
const moves = computed(() => props.entries.filter((entry) => entry.kind === 'transition'));
watch(() => props.open, async (open) => {
    if (open) { await nextTick(); closeButton.value?.focus(); }
});

function count(entry, status) {
    return entry.transitions.filter((transition) => transition.status === status).length;
}
</script>

<template>
    <div v-if="open" class="rf-sim-report-backdrop" @click.self="emit('close')">
        <section class="rf-sim-report" role="dialog" aria-modal="true" :aria-label="copy.simulation_report">
            <header class="rf-sim-report__head">
                <span class="rf-sim-report__icon"><i class="ki-filled ki-history" aria-hidden="true" /></span>
                <div><h2>{{ copy.simulation_report }}</h2><p>{{ copy.simulation_report_intro }}</p></div>
                <button ref="closeButton" type="button" class="rf-sim-report__close" :aria-label="copy.cancel" @click="emit('close')"><i class="ki-filled ki-cross" aria-hidden="true" /></button>
            </header>
            <div class="rf-sim-report__body">
                <div class="rf-sim-report__summary">
                    <div><strong>{{ inspections.length }}</strong><span>{{ copy.simulation_report_checks }}</span></div>
                    <div><strong>{{ moves.length }}</strong><span>{{ copy.simulation_report_moves }}</span></div>
                    <div><strong>{{ new Set(inspections.map((entry) => entry.role)).size }}</strong><span>{{ copy.simulation_report_roles }}</span></div>
                </div>
                <div v-if="!entries.length" class="rf-sim-report__empty"><i class="ki-filled ki-route" aria-hidden="true" /><strong>{{ copy.simulation_report_empty_title }}</strong><p>{{ copy.simulation_report_empty }}</p></div>
                <ol v-else class="rf-sim-report__timeline">
                    <li v-for="entry in entries" :key="entry.sequence" class="rf-sim-report__item" :class="`is-${entry.kind}`">
                        <span class="rf-sim-report__marker"><i :class="`ki-filled ki-${entry.kind === 'inspection' ? 'check-circle' : entry.kind === 'transition' ? 'arrow-right-left' : entry.kind === 'error' ? 'cross-circle' : 'route'}`" aria-hidden="true" /></span>
                        <article v-if="entry.kind === 'inspection'" class="rf-sim-report__card">
                            <div class="rf-sim-report__card-head"><div><small>{{ copy.simulation_report_step }} {{ entry.step }} · {{ copy.simulation_report_inspection }}</small><h3>{{ entry.state }}</h3></div><span>{{ entry.role }}</span></div>
                            <p class="rf-sim-report__context">{{ entry.locale }} · {{ entry.approved ? copy.simulation_report_approved : copy.simulation_report_unapproved }}<template v-if="entry.title"> · {{ entry.title }}</template></p>
                            <div class="rf-sim-report__counts"><span class="is-ready">{{ count(entry, 'ready') }} {{ copy.simulation_ready }}</span><span class="is-blocked">{{ count(entry, 'blocked') }} {{ copy.simulation_blocked }}</span><span class="is-unknown">{{ count(entry, 'unknown') }} {{ copy.simulation_unknown }}</span></div>
                            <p v-if="!entry.transitions.length" class="rf-sim-report__none">{{ copy.simulation_no_transitions }}</p>
                            <div v-for="transition in entry.transitions" :key="transition.id" class="rf-sim-report__transition"><div><strong>{{ transition.label }}</strong><small>{{ entry.state }} → {{ transition.to }}</small></div><span :class="`is-${transition.status}`">{{ copy[`simulation_${transition.status}`] }}</span><ul v-if="transition.checks.length"><li v-for="(check, index) in transition.checks" :key="index"><strong>{{ check.title }}</strong><span :class="`is-${check.status}`">{{ copy[`simulation_${check.status}`] }}</span><small>{{ check.message }}</small></li></ul></div>
                        </article>
                        <div v-else-if="entry.kind === 'transition'" class="rf-sim-report__event"><strong>{{ entry.label }}: {{ entry.from }} → {{ entry.to }}</strong><span>{{ entry.role }} · {{ copy.simulation_status_after }}: {{ entry.contentStatus }}</span></div>
                        <div v-else class="rf-sim-report__event"><strong>{{ entry.kind === 'restart' ? copy.simulation_report_restart : entry.kind === 'jump' ? copy.simulation_report_jump : copy.simulation_report_error }}</strong><span v-if="entry.kind === 'jump'">{{ entry.from }} → {{ entry.to }}</span><span v-else-if="entry.kind === 'error'">{{ entry.state }} · {{ entry.role }}</span></div>
                    </li>
                </ol>
            </div>
            <footer class="rf-sim-report__foot"><i class="ki-filled ki-information-2" aria-hidden="true" />{{ copy.simulation_report_note }}</footer>
        </section>
    </div>
</template>

<style scoped>
.rf-sim-report-backdrop { position:fixed; z-index:1250; inset:0; display:grid; place-items:center; padding:16px; background:rgb(15 23 42 / 55%); backdrop-filter:blur(3px); }
.rf-sim-report { display:flex; flex-direction:column; width:min(780px,100%); max-height:min(88dvh,850px); overflow:hidden; border:1px solid var(--rf-border); border-radius:17px; background:var(--rf-panel); color:var(--rf-text); box-shadow:0 26px 70px rgb(0 0 0 / 26%); }
.rf-sim-report__head { display:flex; align-items:flex-start; gap:12px; padding:18px 21px; border-bottom:1px solid var(--rf-border); }.rf-sim-report__head > div { flex:1; min-width:0; }.rf-sim-report__head h2 { margin:0; font-size:17px; }.rf-sim-report__head p { margin:5px 0 0; color:var(--rf-muted); font-size:11px; line-height:1.7; }.rf-sim-report__icon { display:grid; place-items:center; flex:0 0 35px; height:35px; border-radius:10px; background:color-mix(in srgb,#2eae98 12%,var(--rf-panel)); color:#168d78; }.rf-sim-report__close { display:grid; place-items:center; width:33px; height:33px; border:1px solid var(--rf-border); border-radius:9px; background:var(--rf-panel); color:var(--rf-muted); cursor:pointer; }
.rf-sim-report__body { min-height:0; overflow:auto; padding:18px 22px; }.rf-sim-report__summary { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:9px; margin-bottom:19px; }.rf-sim-report__summary div { display:flex; align-items:center; gap:9px; padding:10px 12px; border:1px solid var(--rf-border); border-radius:10px; background:var(--rf-canvas); }.rf-sim-report__summary strong { color:#168d78; font-size:15px; }.rf-sim-report__summary span { color:var(--rf-muted); font-size:10px; }
.rf-sim-report__empty { display:flex; flex-direction:column; align-items:center; gap:8px; padding:38px 15px; border:1px dashed var(--rf-border); border-radius:12px; color:var(--rf-muted); text-align:center; }.rf-sim-report__empty i { color:#2eae98; font-size:22px; }.rf-sim-report__empty strong { color:var(--rf-text); font-size:13px; }.rf-sim-report__empty p { margin:0; font-size:11px; }
.rf-sim-report__timeline { margin:0; padding:0; padding-inline-start:13px; border-inline-start:1px solid var(--rf-border); list-style:none; }.rf-sim-report__item { position:relative; padding:0; padding-inline-start:17px; padding-bottom:13px; }.rf-sim-report__marker { position:absolute; inset-inline-start:-24px; top:13px; display:grid; place-items:center; width:22px; height:22px; border:1px solid var(--rf-border); border-radius:50%; background:var(--rf-panel); color:#2eae98; }.rf-sim-report__item.is-error .rf-sim-report__marker { color:#c54b4b; }
.rf-sim-report__card { overflow:hidden; border:1px solid var(--rf-border); border-radius:11px; background:var(--rf-panel); }.rf-sim-report__card-head { display:flex; align-items:center; justify-content:space-between; gap:8px; padding:12px 13px 7px; }.rf-sim-report__card-head small { color:var(--rf-muted); font-size:10px; }.rf-sim-report__card-head h3 { margin:3px 0 0; font-size:13px; }.rf-sim-report__card-head > span { max-width:45%; overflow:hidden; padding:5px 8px; border-radius:7px; background:color-mix(in srgb,#2eae98 10%,var(--rf-panel)); color:#168d78; font-size:10px; font-weight:700; text-overflow:ellipsis; white-space:nowrap; }.rf-sim-report__context { margin:0; padding:0 13px 10px; color:var(--rf-muted); font-size:10px; }.rf-sim-report__counts { display:flex; flex-wrap:wrap; gap:5px; padding:0 13px 11px; }.rf-sim-report__counts span { padding:4px 7px; border-radius:6px; background:var(--rf-canvas); font-size:10px; }.rf-sim-report__counts .is-ready { color:#168d78; }.rf-sim-report__counts .is-blocked { color:#bf4651; }.rf-sim-report__counts .is-unknown { color:#a77829; }
.rf-sim-report__transition { padding:9px 13px; border-top:1px solid var(--rf-border); }.rf-sim-report__transition > div { display:flex; flex-direction:column; gap:3px; }.rf-sim-report__transition > div strong { font-size:11px; }.rf-sim-report__transition > div small { color:var(--rf-muted); font-size:10px; }.rf-sim-report__transition > span { display:inline-block; margin-top:6px; font-size:10px; font-weight:700; }.rf-sim-report__transition > span.is-ready,.rf-sim-report__transition li .is-passed { color:#168d78; }.rf-sim-report__transition > span.is-blocked,.rf-sim-report__transition li .is-blocked { color:#bf4651; }.rf-sim-report__transition > span.is-unknown,.rf-sim-report__transition li .is-unknown { color:#a77829; }.rf-sim-report__transition ul { margin:8px 0 0; padding:0; list-style:none; }.rf-sim-report__transition li { display:flex; align-items:baseline; flex-wrap:wrap; gap:4px 8px; padding:6px 0; border-top:1px solid var(--rf-border); font-size:10px; }.rf-sim-report__transition li strong { font-weight:700; }.rf-sim-report__transition li span { font-weight:700; }.rf-sim-report__transition li small { flex-basis:100%; color:var(--rf-muted); line-height:1.5; }.rf-sim-report__none { margin:0; padding:0 13px 12px; color:var(--rf-muted); font-size:10px; }
.rf-sim-report__event { display:flex; flex-direction:column; gap:4px; padding:10px 12px; border:1px solid var(--rf-border); border-radius:9px; background:var(--rf-canvas); }.rf-sim-report__event strong { font-size:11px; }.rf-sim-report__event span { color:var(--rf-muted); font-size:10px; }.rf-sim-report__foot { display:flex; gap:7px; padding:11px 20px; border-top:1px solid var(--rf-border); color:var(--rf-muted); font-size:10px; line-height:1.6; }.rf-sim-report__foot i { color:#2eae98; }
@media(max-width:600px) { .rf-sim-report__body { padding:14px; }.rf-sim-report__summary { gap:5px; }.rf-sim-report__summary div { flex-direction:column; align-items:flex-start; gap:2px; padding:8px; }.rf-sim-report__timeline { margin-inline-start:7px; } }
</style>
