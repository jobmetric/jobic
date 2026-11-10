<script setup>
import { computed, ref } from 'vue';
import RolePicker from '../../RolePicker.vue';

import ReservationFlowTasksPanel from '../ReservationFlowTasksPanel.vue';
import ReservationFlowIdentityBadge from './ReservationFlowIdentityBadge.vue';

const props = defineProps({
    copy: { type: Object, required: true },
    transition: { type: Object, required: true },
    form: { type: Object, required: true },
    availableRoles: {type: Array, default: () => []},
    availableTaskDrivers: { type: Array, default: () => [] },
    editable: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    stateName: { type: Function, required: true },
});

const emit = defineEmits([
    'save',
    'delete',
    'deselect',
    'add-task',
    'update-task',
    'delete-task',
]);

const activeTab = ref('details');

const tasks = computed(() => props.transition.tasks ?? []);

const tabs = computed(() => [
    { id: 'details', label: props.copy.reservation_flow_inspector_tab_details },
    { id: 'connection', label: props.copy.reservation_flow_inspector_tab_connection },
    ...(!props.transition.is_start_edge ? [{ id: 'tasks', label: props.copy.reservation_flow_inspector_tab_tasks, count: tasks.value.length }] : []),
]);

const routeLabel = computed(() => {
    const from = props.transition.is_start_edge || props.transition.from == null
        ? props.copy.reservation_flow_from_any
        : props.stateName(props.transition.from);

    const to = props.stateName(props.transition.to);

    return `${from} → ${to}`;
});

function portLabel(side) {
    return props.copy[`port_${side}`] ?? side;
}
</script>

<template>
    <div class="rf-transition-inspector">
        <div class="rf-inspector-head">
            <div class="rf-inspector-head__icon is-transition">
                <i class="ki-filled ki-arrow-right" aria-hidden="true" />
            </div>
            <div class="rf-inspector-head__body">
                <h3 class="rf-inspector-head__title">{{ transition.label ?? transition.name }}</h3>
                <p class="rf-inspector-head__meta">{{ routeLabel }}</p>
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
                :class="{ 'is-active': activeTab === tab.id }"
                role="tab"
                :aria-selected="activeTab === tab.id"
                @click="activeTab = tab.id"
            >
                {{ tab.label }}
                <span v-if="tab.count != null" class="rf-count-badge">{{ tab.count }}</span>
            </button>
        </div>

        <div class="rf-inspector-body">
            <div v-if="activeTab === 'details'" class="rf-inspector-panel">
                <ReservationFlowIdentityBadge
                    :label="copy.reservation_flow_transition_button_key"
                    :value="transition.slug"
                    :hint="copy.transition_key_help"
                />
                <label class="rf-field">
                    <span class="rf-field__label">{{ copy.reservation_flow_transition_button_label }}</span>
                    <input v-model="form.label" class="kt-input rf-field__input" :disabled="!editable" />
                </label>
                <div v-if="!transition.is_start_edge" class="rf-field">
                    <span class="rf-field__label">{{ copy.transition_roles }}</span>
                    <RolePicker v-model="form.roles" :roles="availableRoles" :disabled="!editable" :copy="copy" />
                    <span class="rf-field__hint">{{ copy.transition_roles_hint }}</span>
                    <p v-if="transition.tasks?.some(task => task.driver?.endsWith('RequirePermission'))" class="rf-field__hint">{{ copy.legacy_roles }}</p>
                </div>
                <p v-if="editable" class="rf-field__hint">{{ copy.reservation_flow_inspector_draft_hint }}</p>
            </div>

            <div v-else-if="activeTab === 'connection'" class="rf-inspector-panel">
                <dl class="rf-connection-list">
                    <div class="rf-connection-list__row">
                        <dt>{{ copy.reservation_flow_transition_from }}</dt>
                        <dd>
                            {{ transition.is_start_edge || transition.from == null ? copy.reservation_flow_from_any : stateName(transition.from) }}
                        </dd>
                    </div>
                    <div v-if="transition.anchors?.from_side" class="rf-connection-list__row">
                        <dt>{{ copy.reservation_flow_anchor_from }}</dt>
                        <dd>{{ portLabel(transition.anchors.from_side) }}</dd>
                    </div>
                    <div class="rf-connection-list__row">
                        <dt>{{ copy.reservation_flow_transition_to }}</dt>
                        <dd>{{ stateName(transition.to) }}</dd>
                    </div>
                    <div v-if="transition.anchors?.to_side" class="rf-connection-list__row">
                        <dt>{{ copy.reservation_flow_anchor_to }}</dt>
                        <dd>{{ portLabel(transition.anchors.to_side) }}</dd>
                    </div>
                </dl>
                <p class="rf-field__hint">{{ copy.reservation_flow_editor_reconnect_hint }}</p>
            </div>

            <div v-else class="rf-inspector-panel">
                <p v-if="transition.is_start_edge" class="rf-field__hint">{{ copy.reservation_flow_start_edge_hint }}</p>
                <ReservationFlowTasksPanel
                    v-else
                    :copy="copy"
                    :tasks="tasks"
                    :available-drivers="availableTaskDrivers"
                    :transition-label="transition.label ?? transition.name"
                    :transition-route="routeLabel"
                    :editable="editable"
                    :saving="saving"
                    @add-task="emit('add-task', $event)"
                    @update-task="emit('update-task', $event)"
                    @delete-task="emit('delete-task', $event)"
                />
            </div>
        </div>

        <div v-if="editable && !transition.is_start_edge" class="rf-inspector-foot">
            <button type="button" class="rf-btn rf-btn--primary" :disabled="saving" @click="emit('save')"><i class="ki-filled ki-check" aria-hidden="true" />{{ copy.apply_inspector_changes }}</button>
            <button
                type="button"
                class="rf-btn rf-btn--danger rf-btn--icon"
                :disabled="saving"
                :aria-label="copy.reservation_flow_delete_transition"
                :title="copy.reservation_flow_delete_transition"
                @click="emit('delete')"
            >
                <i class="ki-filled ki-trash" aria-hidden="true" />
            </button>
        </div>
    </div>
</template>

<style scoped>
.rf-transition-inspector {
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

.rf-inspector-head__icon.is-transition {
    background: #EEF2FF;
    color: #4F46E5;
}

.rf-inspector-head__body {
    flex: 1;
    min-width: 0;
}

.rf-inspector-head__title {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0F172A;
}

.rf-inspector-head__meta {
    margin: 0.2rem 0 0;
    font-size: 0.75rem;
    color: #64748B;
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

.rf-field__hint {
    margin: 0 0 0.85rem;
    font-size: 0.75rem;
    color: #94A3B8;
}

.rf-connection-list {
    margin: 0 0 0.85rem;
    display:grid;
    gap:8px;
}

.rf-connection-list__row {
    display: grid;
    grid-template-columns: 1fr 1.2fr;
    gap: 0.5rem;
    padding: 11px 12px;
    border: 1px solid var(--rf-border,var(--border));
    border-radius:10px;
    background:var(--rf-panel,var(--surface));
    font-size: 0.8125rem;
}

.rf-connection-list__row dt {
    color: #64748B;
}

.rf-connection-list__row dd {
    margin: 0;
    font-weight: 600;
    color: #0F172A;
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

.rf-btn--icon { width:2.35rem; padding:0; }

.rf-btn--block {
    width: 100%;
}

.rf-btn--primary {
    background: var(--primary,#2eae98);
    color: #FFFFFF;
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

.rf-inspector-head,
.rf-inspector-tabs,
.rf-inspector-foot {
    border-color: var(--rf-border, var(--border));
}

.rf-inspector-head__title,
.rf-connection-list__row dd {
    color: var(--rf-text, var(--text));
}

.rf-inspector-head__meta,
.rf-inspector-tabs__btn,
.rf-field__label,
.rf-field__hint,
.rf-connection-list__row dt {
    color: var(--rf-muted, var(--secondary-foreground));
}

.rf-inspector-head__icon.is-transition {
    background: color-mix(in srgb, var(--primary, #2eae98) 11%, var(--rf-panel, var(--surface)));
    color: var(--primary, #2eae98);
}

.rf-inspector-head__close:hover,
.rf-count-badge {
    background: var(--rf-canvas, var(--surface-soft));
    color: var(--rf-text, var(--text));
}

.rf-inspector-tabs__btn.is-active {
    color: var(--primary, #2eae98);
    border-bottom-color: var(--primary, #2eae98);
}

.rf-field__input {
    height: 42px;
    border: 1px solid var(--rf-border, var(--border));
    border-radius: 9px;
    background: var(--rf-panel, var(--surface));
    color: var(--rf-text, var(--text));
    box-shadow: none;
}

.rf-field__input:focus {
    border-color: var(--primary, #2eae98);
    box-shadow: 0 0 0 3px color-mix(in srgb, var(--primary, #2eae98) 13%, transparent);
}

.rf-connection-list__row {
    border-color: var(--rf-border, var(--border));
}

.rf-btn--danger {
    background: var(--rf-panel, var(--surface));
}
</style>
