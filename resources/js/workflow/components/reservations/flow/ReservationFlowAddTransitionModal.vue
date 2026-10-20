<script setup>
import { computed, nextTick, ref, watch } from 'vue';

import ReservationFlowModalShell from './ReservationFlowModalShell.vue';
import ReservationFlowIdentityBadge from './ReservationFlowIdentityBadge.vue';
import { slugifyLabel } from '../../../utils/reservationFlowDraft.js';
import { reservationFlowNodeBadgeLabel, reservationFlowNodeTheme } from '../../../utils/reservationFlowNodeTheme.js';

const props = defineProps({
    copy: { type: Object, required: true },
    open: { type: Boolean, default: false },
    states: { type: Array, default: () => [] },
    transitions: { type: Array, default: () => [] },
    presetFrom: { type: String, default: '' },
    presetTo: { type: String, default: '' },
    presetAnchors: { type: Object, default: () => ({}) },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const step = ref(1);
const from = ref('');
const to = ref('');
const drawnFrom = ref('');
const drawnTo = ref('');
const label = ref('');
const generatedSlug = ref('');
const error = ref('');
const formPaneRef = ref(null);
const toPickerRef = ref(null);

const hasStartTransition = computed(() => props.transitions.some((transition) => (
    transition.is_start_edge || transition.from == null || transition.from === 'start'
)));

const sourceOptions = computed(() => [
    ...(!hasStartTransition.value ? [{ value: 'start', label: props.copy.reservation_flow_from_any, kind: 'start' }] : []),
    ...props.states.map((state) => ({
        value: String(state.id),
        label: state.name,
        kind: 'state',
        state,
    })),
]);

const destinationOptions = computed(() => props.states
    .map((state) => ({
        value: String(state.id),
        label: state.name,
        state,
    })));

const fromState = computed(() => (
    from.value === 'start'
        ? null
        : props.states.find((row) => String(row.id) === from.value) ?? null
));

const toState = computed(() => props.states.find((row) => String(row.id) === to.value) ?? null);

const fromLabel = computed(() => (
    from.value === 'start'
        ? props.copy.reservation_flow_from_any
        : fromState.value?.name ?? ''
));

const toLabel = computed(() => toState.value?.name ?? '');
const resolvedSlug = computed(() => {
    // Leave room for a collision suffix within the workflow key's 80-character limit.
    const base = slugifyLabel(label.value).slice(0, 71) || generatedSlug.value;
    const taken = props.transitions.some((transition) => transition.slug === base);

    return taken ? `${base}_${generatedSlug.value.slice(-8)}` : base;
});

const stepDescription = computed(() => (
    step.value === 1
        ? props.copy.reservation_flow_add_transition_step_connection
        : props.copy.reservation_flow_add_transition_step_action
));

watch(() => props.open, async (isOpen) => {
    if (!isOpen) {
        return;
    }

    const nextFrom = sourceOptions.value.some((option) => option.value === props.presetFrom) ? props.presetFrom : '';
    const nextTo = destinationOptions.value.some((option) => option.value === props.presetTo) ? props.presetTo : '';

    step.value = nextFrom && nextTo ? 2 : 1;
    from.value = nextFrom;
    to.value = nextTo;
    drawnFrom.value = nextFrom;
    drawnTo.value = nextTo;
    label.value = '';
    generatedSlug.value = `transition_${crypto.randomUUID().replaceAll('-', '').slice(0, 12)}`;
    error.value = '';

    await nextTick();
    if (formPaneRef.value) {
        formPaneRef.value.scrollTop = 0;
    }
});

function nodeTheme(state, kind = 'state') {
    if (kind === 'start') {
        return reservationFlowNodeTheme({ kind: 'start' });
    }

    return reservationFlowNodeTheme(state ?? { kind: 'state', status: 'open' });
}

function nodeBadge(state, kind = 'state') {
    if (kind === 'start') {
        return reservationFlowNodeBadgeLabel({ kind: 'start' }, props.copy);
    }

    return reservationFlowNodeBadgeLabel(state, props.copy);
}

function selectFrom(value) {
    from.value = value;
    error.value = '';
    scrollToDestinationPicker();
}

function selectTo(value) {
    to.value = value;
    error.value = '';
}

async function scrollToDestinationPicker() {
    await nextTick();

    window.requestAnimationFrame(() => {
        const container = formPaneRef.value;
        const target = toPickerRef.value;

        if (!container || !target) {
            return;
        }

        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const behavior = prefersReducedMotion ? 'auto' : 'smooth';

        target.scrollIntoView({
            behavior,
            block: 'start',
            inline: 'nearest',
        });

        const containerRect = container.getBoundingClientRect();
        const targetRect = target.getBoundingClientRect();
        const top = container.scrollTop + targetRect.top - containerRect.top - 8;

        container.scrollTo({
            top: Math.max(top, 0),
            behavior,
        });

        const firstDestinationButton = target.querySelector('button:not([disabled])');
        firstDestinationButton?.focus?.({ preventScroll: true });
    });
}

function nextStep() {
    if (!from.value || !to.value) {
        error.value = props.copy.reservation_flow_add_transition_connection_required;
        return;
    }

    error.value = '';
    step.value = 2;
}

function submit() {
    if (!from.value || !to.value) {
        error.value = props.copy.reservation_flow_add_transition_connection_required;
        step.value = 1;
        return;
    }

    if (!label.value.trim()) {
        error.value = props.copy.reservation_flow_add_transition_label_required;
        return;
    }

    const actionLabel = label.value.trim();
    const actionSlug = resolvedSlug.value;

    const drawnAnchors = from.value === drawnFrom.value && to.value === drawnTo.value
        ? props.presetAnchors : {};
    const anchors = from.value === to.value
        ? {from_side: drawnAnchors.from_side ?? 'right', to_side: drawnAnchors.from_side ?? 'right'}
        : drawnAnchors.from_side && drawnAnchors.to_side
            ? {from_side: drawnAnchors.from_side, to_side: drawnAnchors.to_side} : null;

    emit('submit', {
        from: from.value,
        to: to.value,
        name: actionLabel,
        slug: actionSlug,
        // Reflexive transitions keep both ends on the selected exit side.
        ...(anchors ? {anchors} : {}),
    });
}

function setSubmitError(message) {
    error.value = message || props.copy.reservation_flow_save_error;
}

function onPrimaryAction() {
    if (step.value === 1) {
        nextStep();
        return;
    }

    submit();
}

defineExpose({
    setSubmitError,
});
</script>

<template>
    <ReservationFlowModalShell
        :open="open"
        :title="copy.reservation_flow_add_transition_modal_title"
        :description="copy.reservation_flow_add_transition_modal_desc"
        icon="ki-arrow-right-left"
        size="xl"
        :saving="saving"
        :cancel-label="copy.cancel"
        :aria-label="copy.reservation_flow_add_transition_modal_title"
        @close="emit('close')"
    >
        <form class="rf-flow-modal__body" @submit.prevent="onPrimaryAction">
            <div class="rf-flow-modal__split">
                <div ref="formPaneRef" class="rf-flow-modal__form-pane">
                    <template v-if="step === 1">
                        <section class="rf-flow-picker">
                            <h3 class="rf-flow-picker__title">{{ copy.reservation_flow_transition_from }}</h3>
                            <p class="rf-flow-picker__hint">{{ copy.reservation_flow_add_transition_from_help }}</p>
                            <div class="rf-flow-picker__list">
                                <button
                                    v-for="option in sourceOptions"
                                    :key="option.value"
                                    type="button"
                                    class="rf-flow-picker__card"
                                    :class="{ 'is-selected': from === option.value }"
                                    :aria-pressed="from === option.value"
                                    @click="selectFrom(option.value)"
                                >
                                    <span
                                        class="rf-flow-picker__icon"
                                        :style="{
                                            background: nodeTheme(option.state, option.kind).iconBg,
                                            color: nodeTheme(option.state, option.kind).iconColor,
                                        }"
                                    >
                                        <i :class="`ki-filled ${nodeTheme(option.state, option.kind).icon}`" aria-hidden="true" />
                                    </span>
                                    <span class="rf-flow-picker__body">
                                        <span class="rf-flow-picker__name">{{ option.label }}</span>
                                        <span v-if="nodeBadge(option.state, option.kind)" class="rf-flow-picker__meta">{{ nodeBadge(option.state, option.kind) }}</span>
                                    </span>
                                </button>
                            </div>
                        </section>

                        <section ref="toPickerRef" class="rf-flow-picker">
                            <h3 class="rf-flow-picker__title">{{ copy.reservation_flow_transition_to }}</h3>
                            <p class="rf-flow-picker__hint">{{ copy.reservation_flow_add_transition_to_help }}</p>
                            <p v-if="from && from === to" class="rf-flow-picker__hint">{{ copy.reservation_flow_add_transition_same_state }}</p>
                            <div class="rf-flow-picker__list">
                                <button
                                    v-for="option in destinationOptions"
                                    :key="option.value"
                                    type="button"
                                    class="rf-flow-picker__card"
                                    :class="{ 'is-selected': to === option.value }"
                                    :aria-pressed="to === option.value"
                                    @click="selectTo(option.value)"
                                >
                                    <span
                                        class="rf-flow-picker__icon"
                                        :style="{
                                            background: nodeTheme(option.state).iconBg,
                                            color: nodeTheme(option.state).iconColor,
                                        }"
                                    >
                                        <i :class="`ki-filled ${nodeTheme(option.state).icon}`" aria-hidden="true" />
                                    </span>
                                    <span class="rf-flow-picker__body">
                                        <span class="rf-flow-picker__name">{{ option.label }}</span>
                                        <span v-if="nodeBadge(option.state)" class="rf-flow-picker__meta">{{ nodeBadge(option.state) }}</span>
                                    </span>
                                </button>
                            </div>
                        </section>
                    </template>

                    <template v-else>
                        <ReservationFlowIdentityBadge
                            :label="copy.reservation_flow_transition_button_key"
                            :value="resolvedSlug"
                            :hint="copy.transition_key_help"
                        />
                        <label class="rf-flow-field">
                            <span class="rf-flow-field__label">{{ copy.reservation_flow_transition_button_label }}</span>
                            <input v-model="label" class="rf-flow-field__input" autofocus />
                            <span class="rf-flow-field__hint">{{ copy.reservation_flow_add_transition_label_help }}</span>
                        </label>

                    </template>

                    <p v-if="error" class="rf-flow-field__error" role="alert">{{ error }}</p>
                </div>

                <aside class="rf-flow-modal__preview-pane">
                    <template v-if="step === 1">
                        <h3 class="rf-flow-modal__preview-heading">{{ copy.reservation_flow_add_transition_preview_heading }}</h3>
                        <p class="rf-flow-modal__preview-desc">{{ copy.reservation_flow_add_transition_step_connection }}</p>

                        <div class="rf-flow-route-preview">
                            <div class="rf-flow-route-preview__node is-source">
                                <span class="rf-flow-route-preview__label">{{ fromLabel || '…' }}</span>
                            </div>
                            <div class="rf-flow-route-preview__arrow" aria-hidden="true">
                                <i class="ki-filled ki-arrow-right" />
                            </div>
                            <div class="rf-flow-route-preview__node is-target">
                                <span class="rf-flow-route-preview__label">{{ toLabel || '…' }}</span>
                            </div>
                        </div>

                        <div class="rf-flow-modal__callout">
                            <i class="ki-filled ki-information-2" aria-hidden="true" />
                            <p>{{ copy.reservation_flow_add_transition_to_help }}</p>
                        </div>
                    </template>

                    <template v-else>
                        <h3 class="rf-flow-modal__preview-heading">{{ copy.reservation_flow_add_transition_action_preview_heading }}</h3>
                        <p class="rf-flow-modal__preview-desc">{{ copy.reservation_flow_add_transition_label_help }}</p>

                        <div class="rf-flow-route-preview rf-flow-route-preview--action">
                            <div class="rf-flow-route-preview__node is-source">
                                <span class="rf-flow-route-preview__label">{{ fromLabel }}</span>
                            </div>
                            <span class="rf-flow-action-chip">{{ label || '…' }}</span>
                            <div class="rf-flow-route-preview__node is-target">
                                <span class="rf-flow-route-preview__label">{{ toLabel }}</span>
                            </div>
                        </div>

                    </template>
                </aside>
            </div>
        </form>

        <template #footer>
            <div class="rf-flow-steps" role="list" :aria-label="stepDescription">
                <span class="rf-flow-steps__item" :class="{ 'is-active': step === 1, 'is-done': step > 1 }" role="listitem">
                    <span class="rf-flow-steps__index">1</span>
                    {{ copy.reservation_flow_add_transition_step_connection }}
                </span>
                <span class="rf-flow-steps__divider" aria-hidden="true" />
                <span class="rf-flow-steps__item" :class="{ 'is-active': step === 2 }" role="listitem">
                    <span class="rf-flow-steps__index">2</span>
                    {{ copy.reservation_flow_add_transition_step_action }}
                </span>
            </div>
            <div class="rf-flow-modal__footer-actions">
                <button
                    v-if="step === 2"
                    type="button"
                    class="rf-flow-btn rf-flow-btn--ghost"
                    :disabled="saving"
                    @click="step = 1"
                >
                    <i class="ki-filled ki-left" aria-hidden="true" />
                    {{ copy.reservation_flow_back_step }}
                </button>
                <button type="button" class="rf-flow-btn rf-flow-btn--primary" :disabled="saving" @click="onPrimaryAction">
                    <i :class="`ki-filled ${step === 1 ? 'ki-right' : 'ki-plus'}`" aria-hidden="true" />
                    {{
                        step === 1
                            ? copy.reservation_flow_next_step
                            : (saving ? copy.saving : copy.reservation_flow_add_transition)
                    }}
                </button>
            </div>
        </template>
    </ReservationFlowModalShell>
</template>

<style scoped>
.rf-flow-steps {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.5rem;
    justify-content: center;
    margin-inline: 0;
}

.rf-flow-steps__item {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.35rem 0.65rem;
    border-radius: 9999px;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748B;
    background: #F1F5F9;
}

.rf-flow-steps__item.is-active {
    color: #4F46E5;
    background: #F0EEFF;
}

.rf-flow-steps__item.is-done {
    color: #334155;
}

.rf-flow-steps__index {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.25rem;
    height: 1.25rem;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.85);
    font-size: 0.6875rem;
    font-weight: 700;
}

.rf-flow-steps__divider {
    width: 1.5rem;
    height: 1px;
    background: #CBD5E1;
}

[dir='rtl'] .rf-flow-steps__divider {
    transform: scaleX(-1);
}

.rf-flow-modal__body {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.rf-flow-modal__split {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 300px;
    min-height: 0;
    height: 100%;
}

.rf-flow-modal__form-pane {
    min-height: 0;
    padding: 22px 28px;
    overflow-y: auto;
}

.rf-flow-modal__preview-pane {
    min-height: 0;
    padding: 22px 20px;
    overflow-y: auto;
    border-inline-start: 1px solid #E2E8F0;
    background: linear-gradient(180deg, #FAFBFF 0%, #F8FAFC 100%);
}

.rf-flow-modal__preview-heading {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94A3B8;
}

.rf-flow-modal__preview-desc {
    margin: 0.4rem 0 1rem;
    font-size: 0.8125rem;
    color: #64748B;
    line-height: 1.5;
}

.rf-flow-modal__preview-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid #E2E8F0;
}

.rf-flow-modal__preview-section h4 {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #94A3B8;
}

.rf-flow-modal__preview-section p {
    margin: 0.4rem 0 0;
    font-size: 0.8125rem;
    color: #334155;
}

.rf-flow-modal__callout {
    display: flex;
    gap: 0.55rem;
    margin-top: 1rem;
    padding: 0.8rem 0.9rem;
    border-radius: 10px;
    border: 1px solid #DBEAFE;
    background: #EFF6FF;
    color: #1E40AF;
    font-size: 0.8125rem;
    line-height: 1.5;
}

.rf-flow-modal__footer-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rf-flow-picker + .rf-flow-picker {
    margin-top: 1.25rem;
}

.rf-flow-picker__title {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: #64748B;
}

.rf-flow-picker__hint {
    margin: 0.3rem 0 0.65rem;
    font-size: 0.75rem;
    color: #94A3B8;
    line-height: 1.45;
}

.rf-flow-picker__list {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

.rf-flow-picker__card {
    display: grid;
    grid-template-columns: auto 1fr;
    align-items: center;
    gap: 0.65rem;
    width: 100%;
    padding: 0.7rem 0.8rem;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    background: #FFFFFF;
    text-align: start;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.rf-flow-picker__card:hover {
    border-color: #CBD5E1;
    background: #FCFDFF;
}

.rf-flow-picker__card.is-selected {
    border-color: #716BFF;
    background: #F0EEFF;
    box-shadow: 0 0 0 3px rgba(113, 107, 255, 0.12);
}

.rf-flow-picker__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    font-size: 0.95rem;
}

.rf-flow-picker__body {
    min-width: 0;
}

.rf-flow-picker__name {
    display: block;
    font-size: 0.875rem;
    font-weight: 700;
    color: #172033;
}

.rf-flow-picker__meta {
    display: block;
    margin-top: 0.1rem;
    font-size: 0.6875rem;
    font-weight: 600;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: #94A3B8;
}

.rf-flow-field {
    display: block;
    margin-bottom: 1rem;
}

.rf-flow-field__label {
    display: block;
    margin-bottom: 0.4rem;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    color: #64748B;
}

.rf-flow-field__input {
    width: 100%;
    height: 2.65rem;
    padding: 0 0.85rem;
    border: 1px solid #CBD5E1;
    border-radius: 11px;
    background: #FFFFFF;
    font-size: 0.875rem;
    color: #0F172A;
}

.rf-flow-field__input:focus {
    outline: none;
    border-color: #146EF5;
    box-shadow: 0 0 0 3px rgba(20, 110, 245, 0.14);
}

.rf-flow-field__hint {
    display: block;
    margin-top: 0.35rem;
    font-size: 0.75rem;
    color: #94A3B8;
    line-height: 1.45;
}

.rf-flow-field__error {
    margin: 0.5rem 0 0;
    font-size: 0.8125rem;
    color: #DC2626;
}

@media (max-width: 700px) {
    .rf-flow-steps { order: 3; width: 100%; }
}

.rf-flow-route-preview {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    flex-wrap: wrap;
    padding: 0.85rem;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    background: #FFFFFF;
}

.rf-flow-route-preview--action {
    flex-direction: column;
    align-items: stretch;
    gap: 0.65rem;
}

.rf-flow-route-preview__node {
    flex: 1;
    min-width: 0;
    padding: 0.55rem 0.7rem;
    border-radius: 10px;
    border: 1px solid #CBD5E1;
    background: #F8FAFC;
}

.rf-flow-route-preview__node.is-source {
    border-color: #93C5FD;
    background: #EFF6FF;
}

.rf-flow-route-preview__node.is-target {
    border-color: #C4B5FD;
    background: #F5F3FF;
}

.rf-flow-route-preview__label {
    display: block;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #172033;
    word-break: break-word;
}

.rf-flow-route-preview__arrow {
    color: #716BFF;
    font-size: 0.9rem;
}

[dir='rtl'] .rf-flow-route-preview__arrow .ki-arrow-right {
    transform: scaleX(-1);
}

.rf-flow-action-chip {
    align-self: center;
    padding: 0.35rem 0.85rem;
    border-radius: 9999px;
    border: 1px solid #4F5EF7;
    background: #FFFFFF;
    box-shadow: 0 2px 7px rgba(15, 23, 42, 0.05);
    font-size: 0.75rem;
    font-weight: 700;
    color: #312E81;
}

.rf-flow-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    height: 2.4rem;
    padding: 0 1rem;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
}

.rf-flow-btn--primary {
    background: #146EF5;
    color: #FFFFFF;
}

.rf-flow-btn--secondary {
    background: #FFFFFF;
    border-color: #CBD5E1;
    color: #334155;
}

.rf-flow-btn--ghost {
    background: transparent;
    color: #334155;
}

.rf-flow-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.rf-flow-modal__preview-pane { border-color:var(--rf-modal-border); background:var(--rf-modal-surface); color:var(--rf-modal-text); }
.rf-flow-modal__preview-heading,
.rf-flow-modal__preview-section h4,
.rf-flow-field__hint,
.rf-flow-picker__hint { color:var(--rf-modal-muted); }
.rf-flow-modal__preview-desc,
.rf-flow-field__label,
.rf-flow-picker__title { color:var(--rf-modal-secondary); }
.rf-flow-modal__preview-section { border-color:var(--rf-modal-border); }
.rf-flow-modal__preview-section p,
.rf-flow-picker__name,
.rf-flow-route-preview__label { color:var(--rf-modal-text); }
.rf-flow-modal__callout { border-color:color-mix(in srgb,var(--rf-modal-primary) 28%,var(--rf-modal-border)); background:color-mix(in srgb,var(--rf-modal-primary) 9%,var(--rf-modal-bg)); color:var(--rf-modal-text); }
.rf-flow-picker__card,
.rf-flow-route-preview,
.rf-flow-action-chip { border-color:var(--rf-modal-border); background:var(--rf-modal-bg); color:var(--rf-modal-text); }
.rf-flow-picker__card:hover,
.rf-flow-picker__card.is-selected { border-color:var(--rf-modal-primary); background:color-mix(in srgb,var(--rf-modal-primary) 9%,var(--rf-modal-bg)); box-shadow:none; }
.rf-flow-field__input { border-color:var(--rf-modal-border-strong); background:var(--rf-modal-bg); color:var(--rf-modal-text); }
.rf-flow-field__input:focus { border-color:var(--rf-modal-primary); box-shadow:0 0 0 3px color-mix(in srgb,var(--rf-modal-primary) 14%,transparent); }
.rf-flow-btn--primary { background:var(--rf-modal-primary); }
.rf-flow-steps__item.is-active { color:var(--rf-modal-primary); background:color-mix(in srgb,var(--rf-modal-primary) 10%,var(--rf-modal-bg)); }

[dir='rtl'] .rf-flow-btn .ki-left {
    transform: scaleX(-1);
}

@media (max-width: 768px) {
    .rf-flow-modal__split {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto;
    }

    .rf-flow-modal__preview-pane {
        border-inline-start: 0;
        border-top: 1px solid #E2E8F0;
    }
}
</style>
