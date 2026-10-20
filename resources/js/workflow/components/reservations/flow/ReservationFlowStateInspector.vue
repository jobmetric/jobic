<script setup>
import { computed, ref, watch } from 'vue';

import { reservationFlowNodeBadgeLabel, reservationFlowNodeTheme } from '../../../utils/reservationFlowNodeTheme.js';
import ReservationFlowIdentityBadge from './ReservationFlowIdentityBadge.vue';

const props = defineProps({
    copy: { type: Object, required: true },
    state: { type: Object, required: true },
    incoming: { type: Array, default: () => [] },
    outgoing: { type: Array, default: () => [] },
    stateName: { type: Function, required: true },
    form: { type: Object, required: true },
    availableStatuses: { type: Array, default: () => [] },
    editable: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    activeTab: { type: String, default: 'details' },
});

const emit = defineEmits([
    'save',
    'delete',
    'deselect',
    'select-transition',
    'add-transition',
    'update:active-tab',
]);

const localTab = ref(props.activeTab);

watch(() => props.activeTab, (tab) => {
    if (tab) {
        localTab.value = tab;
    }
});

watch(localTab, (tab) => {
    emit('update:active-tab', tab);
});

const theme = computed(() => reservationFlowNodeTheme(props.state));
const badgeLabel = computed(() => reservationFlowNodeBadgeLabel(props.state, props.copy));

const tabs = computed(() => [
    { id: 'details', label: props.copy.reservation_flow_inspector_tab_details },
    { id: 'incoming', label: props.copy.reservation_flow_inspector_tab_incoming, count: props.incoming.length },
    { id: 'outgoing', label: props.copy.reservation_flow_inspector_tab_outgoing, count: props.outgoing.length },
]);

function transitionFromLabel(transition) {
    if (transition.is_start_edge || transition.from == null) {
        return props.copy.reservation_flow_from_any;
    }

    return props.stateName(transition.from) || transition.from_name || '';
}

function transitionToLabel(transition) {
    return props.stateName(transition.to) || transition.to_name || '';
}

function addIncomingTransition() {
    emit('add-transition', { to: props.state.id });
}

function addOutgoingTransition() {
    emit('add-transition', { from: props.state.id });
}
</script>

<template>
    <div class="rf-state-inspector">
        <div class="rf-inspector-head">
            <div class="rf-inspector-head__icon" :style="{ background: theme.iconBg, color: theme.iconColor }">
                <i :class="`ki-filled ${theme.icon}`" aria-hidden="true" />
            </div>
            <div class="rf-inspector-head__body">
                <div class="rf-inspector-head__row">
                    <h3 class="rf-inspector-head__title">{{ state.name }}</h3>
                    <span v-if="badgeLabel" class="rf-inspector-head__badge" :style="{ background: theme.badgeBg, color: theme.badgeText }">
                        {{ badgeLabel }}
                    </span>
                </div>
            </div>
            <button
                type="button"
                class="rf-inspector-head__close"
                :aria-label="copy.reservation_flow_inspector_close"
                @click="emit('deselect')"
            >
                <i class="ki-filled ki-cross" aria-hidden="true" />
            </button>
        </div>

        <div class="rf-inspector-tabs" role="tablist">
            <button
                v-for="tab in tabs"
                :key="tab.id"
                type="button"
                class="rf-inspector-tabs__btn"
                :class="{ 'is-active': localTab === tab.id }"
                role="tab"
                :aria-selected="localTab === tab.id"
                @click="localTab = tab.id"
            >
                {{ tab.label }}
                <span v-if="tab.count != null" class="rf-count-badge">{{ tab.count }}</span>
            </button>
        </div>

        <div class="rf-inspector-body">
            <div v-if="localTab === 'details'" class="rf-inspector-panel">
                <ReservationFlowIdentityBadge
                    :label="copy.reservation_flow_state_status"
                    :value="state.status"
                    :hint="copy.automatic_status_hint"
                />
                <label class="rf-field">
                    <span class="rf-field__label">{{ copy.reservation_flow_state_name }}</span>
                    <input v-model="form.name" class="rf-field__input rf-inspector-input" :disabled="!editable" />
                </label>
                <label class="rf-field">
                    <span class="rf-field__label">{{ copy.status_choice_label }}</span>
                    <select v-model="form.content_status" class="rf-field__input rf-inspector-input" :disabled="!editable"><option v-for="choice in availableStatuses" :key="choice.value" :value="choice.value">{{ choice.label }}</option></select>
                    <span class="rf-field__hint">{{ copy.content_status_hint }}</span>
                </label>
                <label class="rf-field rf-field--checkbox">
                    <input v-model="form.is_terminal" type="checkbox" class="kt-checkbox" :disabled="!editable" />
                    <span>{{ copy.reservation_flow_terminal_badge }}</span>
                </label>
                <label class="rf-field rf-field--checkbox">
                    <input v-model="form.allows_approval" type="checkbox" class="kt-checkbox" :disabled="!editable" />
                    <span>{{ copy.approval }}</span>
                </label>
                <p class="rf-field__hint">{{ copy.reservation_flow_editor_drag_hint }}</p>
                <p v-if="editable && !state.is_start" class="rf-field__hint">{{ copy.reservation_flow_inspector_draft_hint }}</p>
            </div>

            <div v-else-if="localTab === 'incoming'" class="rf-inspector-panel">
                <div class="rf-section-head">
                    <h4 class="rf-section-head__title">
                        {{ copy.reservation_flow_incoming_transitions }}
                        <span class="rf-count-badge">{{ incoming.length }}</span>
                    </h4>
                    <button
                        v-if="editable"
                        type="button"
                        class="rf-section-head__add"
                        :aria-label="copy.reservation_flow_add_transition"
                        @click="addIncomingTransition"
                    >
                        <i class="ki-filled ki-plus" aria-hidden="true" />
                    </button>
                </div>
                <p v-if="incoming.length === 0" class="rf-empty-list">{{ copy.reservation_flow_empty_incoming }}</p>
                <button
                    v-for="transition in incoming"
                    :key="transition.id"
                    type="button"
                    class="rf-transition-row"
                    @click="emit('select-transition', transition.id)"
                >
                    <span class="rf-transition-row__label">{{ transition.label ?? transition.name }}</span>
                    <span class="rf-transition-row__route">
                        {{ transitionFromLabel(transition) }} → {{ state.name }}
                    </span>
                </button>
            </div>

            <div v-else class="rf-inspector-panel">
                <div class="rf-section-head">
                    <h4 class="rf-section-head__title">
                        {{ copy.reservation_flow_outgoing_transitions }}
                        <span class="rf-count-badge">{{ outgoing.length }}</span>
                    </h4>
                    <button
                        v-if="editable"
                        type="button"
                        class="rf-section-head__add"
                        :aria-label="copy.reservation_flow_add_transition"
                        @click="addOutgoingTransition"
                    >
                        <i class="ki-filled ki-plus" aria-hidden="true" />
                    </button>
                </div>
                <p v-if="outgoing.length === 0" class="rf-empty-list">{{ copy.reservation_flow_empty_outgoing }}</p>
                <button
                    v-for="transition in outgoing"
                    :key="transition.id"
                    type="button"
                    class="rf-transition-row"
                    @click="emit('select-transition', transition.id)"
                >
                    <span class="rf-transition-row__label">{{ transition.label ?? transition.name }}</span>
                    <span class="rf-transition-row__route">
                        {{ state.name }} → {{ transitionToLabel(transition) }}
                    </span>
                </button>
            </div>
        </div>

        <div v-if="editable" class="rf-inspector-foot">
            <button type="button" class="rf-btn rf-btn--primary" :disabled="saving" @click="emit('save')"><i class="ki-filled ki-check" aria-hidden="true" />{{ copy.apply_inspector_changes }}</button>
            <button
                v-if="!state.is_start"
                type="button"
                class="rf-btn rf-btn--danger rf-btn--icon"
                :disabled="saving"
                :aria-label="copy.reservation_flow_delete_state"
                :title="copy.reservation_flow_delete_state"
                @click="emit('delete')"
            >
                <i class="ki-filled ki-trash" aria-hidden="true" />
            </button>
        </div>
    </div>
</template>

<style scoped>
.rf-state-inspector {
    display: flex;
    flex-direction: column;
    height: 100%;
    min-height: 0;
}

.rf-inspector-head {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 1rem 1rem 0.75rem;
    border-bottom: 1px solid #E7EAF0;
}

.rf-inspector-head__icon {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.35rem;
    height: 2.35rem;
    border-radius: 9px;
    font-size: 1rem;
}

.rf-inspector-head__body {
    flex: 1;
    min-width: 0;
}

.rf-inspector-head__row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.4rem;
}

.rf-inspector-head__title {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0F172A;
}

.rf-inspector-head__badge {
    padding: 0.1rem 0.45rem;
    border-radius: 9999px;
    font-size: 0.625rem;
    font-weight: 600;
}

.rf-inspector-head__meta {
    margin: 0.2rem 0 0;
    font-size: 0.75rem;
    color: #94A3B8;
}

.rf-inspector-head__close {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border: 0;
    border-radius: 8px;
    background: transparent;
    color: #64748B;
    cursor: pointer;
}

.rf-inspector-head__close:hover {
    background: #F1F5F9;
}

.rf-inspector-tabs {
    display: flex;
    gap: 0.25rem;
    padding: 0.65rem 1rem 0;
    border-bottom: 1px solid #E7EAF0;
}

.rf-inspector-tabs__btn {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.45rem 0.55rem;
    border: 0;
    border-bottom: 2px solid transparent;
    background: transparent;
    font-size: 0.75rem;
    font-weight: 600;
    color: #64748B;
    cursor: pointer;
}

.rf-inspector-tabs__btn.is-active {
    color: #146EF5;
    border-bottom-color: #146EF5;
}

.rf-count-badge {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 1.25rem;
    height: 1.125rem;
    padding: 0 0.3rem;
    margin-inline-start: 0.3rem;
    border-radius: 9999px;
    background: #F1F5F9;
    font-size: 0.625rem;
    font-weight: 700;
    color: #334155;
    line-height: 1;
}

.rf-inspector-body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
}

.rf-inspector-panel {
    padding: 1rem;
}

.rf-section-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.65rem;
}

.rf-section-head__title {
    display: inline-flex;
    align-items: center;
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0F172A;
}

.rf-section-head__add {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border: 1px solid #DDE3EC;
    border-radius: 8px;
    background: #FFFFFF;
    color: #475569;
    cursor: pointer;
}

.rf-section-head__add:hover {
    background: #F8FAFC;
}

.rf-inspector-foot {
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:8px;
    padding: 0.85rem 1rem 1rem;
    border-top: 1px solid #E7EAF0;
}

.rf-field {
    display: block;
    margin-bottom: 0.85rem;
}

.rf-field__label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.75rem;
    color: #64748B;
}

.rf-field__input {
    width: 100%;
}

.rf-field--checkbox {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    font-size: 0.8125rem;
}

.rf-field__hint {
    margin: 0 0 0.85rem;
    font-size: 0.75rem;
    color: #94A3B8;
}

.rf-empty-list {
    margin: 0;
    font-size: 0.8125rem;
    color: #94A3B8;
}

.rf-transition-row {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    gap: 0.15rem;
    width: 100%;
    margin-bottom: 0.45rem;
    padding: 0.65rem 0.75rem;
    border: 1px solid #E2E7EF;
    border-radius: 9px;
    background: #FFFFFF;
    text-align: start;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.rf-transition-row:hover {
    border-color: #CBD5E1;
    background: #F8FAFC;
}

.rf-transition-row__label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #0F172A;
}

.rf-transition-row__route {
    font-size: 0.6875rem;
    color: #64748B;
}

.rf-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 2.35rem;
    padding: 0 0.85rem;
    border-radius: 9px;
    border: 1px solid transparent;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    gap:7px;
}

.rf-btn--primary { background:var(--primary,#2eae98); color:#fff; }
.rf-btn--icon { width:2.35rem; padding:0; background:var(--rf-panel,var(--surface,#fff)); }

.rf-btn--block {
    width: 100%;
}

.rf-btn--danger {
    background: #FFFFFF;
    border-color: #FECACA;
    color: #DC2626;
}

.rf-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
</style>
