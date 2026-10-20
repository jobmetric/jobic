<script setup>
import ReservationFlowModalShell from './ReservationFlowModalShell.vue';

const props = defineProps({
    copy: { type: Object, required: true },
    open: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    showAllTransitionLabels: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'reset', 'update:showAllTransitionLabels']);

function toggleShowAllLabels(event) {
    emit('update:showAllTransitionLabels', event.target.checked);
}
</script>

<template>
    <ReservationFlowModalShell
        :open="open"
        :eyebrow="copy.reservation_flow_workflow_settings"
        :title="copy.reservation_flow_workflow_settings_title"
        :description="copy.reservation_flow_workflow_settings_subtitle"
        icon="ki-setting-2"
        :saving="saving"
        :cancel-label="copy.cancel"
        :aria-label="copy.reservation_flow_workflow_settings_title"
        @close="emit('close')"
    >
        <div class="rf-flow-modal__body">
            <section class="rf-flow-settings-section">
                <h3 class="rf-flow-settings-section__title">{{ copy.reservation_flow_workflow_display_settings }}</h3>
                <p class="rf-flow-settings-section__desc">{{ copy.reservation_flow_workflow_display_settings_desc }}</p>

                <label class="rf-flow-settings-toggle">
                    <input
                        type="checkbox"
                        class="rf-flow-settings-toggle__input"
                        :checked="showAllTransitionLabels"
                        :disabled="saving"
                        @change="toggleShowAllLabels"
                    />
                    <span class="rf-flow-settings-toggle__copy">
                        <span class="rf-flow-settings-toggle__title">{{ copy.reservation_flow_show_all_transition_labels }}</span>
                        <span class="rf-flow-settings-toggle__hint">{{ copy.reservation_flow_show_all_transition_labels_help }}</span>
                    </span>
                </label>

                <div class="rf-flow-modal__callout">
                    <i class="ki-filled ki-information-2" aria-hidden="true" />
                    <p>{{ copy.reservation_flow_show_all_transition_labels_note }}</p>
                </div>
            </section>

            <section class="rf-flow-settings-section rf-flow-settings-section--danger">
                <h3 class="rf-flow-settings-section__title">{{ copy.reservation_flow_danger_zone_title }}</h3>
                <p class="rf-flow-settings-section__desc">{{ copy.reservation_flow_danger_zone_desc }}</p>
                <ul class="rf-flow-settings-section__list">
                    <li>{{ copy.reservation_flow_reset_removes_states }}</li>
                    <li>{{ copy.reservation_flow_reset_removes_transitions }}</li>
                    <li>{{ copy.reservation_flow_reset_removes_positions }}</li>
                    <li>{{ copy.reservation_flow_reset_removes_anchors }}</li>
                    <li>{{ copy.reservation_flow_reset_removes_tasks }}</li>
                </ul>
                <button
                    type="button"
                    class="rf-flow-settings-danger-btn"
                    :disabled="saving"
                    @click="emit('reset')"
                >
                    {{ copy.reservation_flow_action_reset }}
                </button>
            </section>
        </div>

        <template #footer>
            <button type="button" class="rf-flow-btn rf-flow-btn--secondary" :disabled="saving" @click="emit('close')">
                {{ copy.cancel }}
            </button>
        </template>
    </ReservationFlowModalShell>
</template>

<style scoped>
.rf-flow-modal__body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    padding: 22px 28px;
}

.rf-flow-settings-section + .rf-flow-settings-section {
    margin-top: 1.25rem;
}

.rf-flow-settings-section {
    padding: 1rem 1.05rem;
    border: 1px solid #E2E8F0;
    border-radius: 14px;
    background: #FFFFFF;
}

.rf-flow-settings-section--danger {
    border-color: #FECACA;
    background: #FEF2F2;
}

.rf-flow-settings-section__title {
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0F172A;
}

.rf-flow-settings-section--danger .rf-flow-settings-section__title {
    color: #B91C1C;
}

.rf-flow-settings-section__desc {
    margin: 0.4rem 0 0.85rem;
    font-size: 0.8125rem;
    color: #64748B;
    line-height: 1.5;
}

.rf-flow-settings-section--danger .rf-flow-settings-section__desc {
    color: #7F1D1D;
}

.rf-flow-settings-section__list {
    margin: 0 0 0.85rem;
    padding-inline-start: 1.1rem;
    font-size: 0.75rem;
    color: #991B1B;
    line-height: 1.6;
}

.rf-flow-settings-toggle {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.85rem 0.9rem;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    background: #F8FAFC;
    cursor: pointer;
}

.rf-flow-settings-toggle__input {
    margin-top: 0.15rem;
    flex-shrink: 0;
}

.rf-flow-settings-toggle__copy {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.rf-flow-settings-toggle__title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
}

.rf-flow-settings-toggle__hint {
    font-size: 0.75rem;
    color: #94A3B8;
    line-height: 1.45;
}

.rf-flow-modal__callout {
    display: flex;
    gap: 0.55rem;
    margin-top: 0.85rem;
    padding: 0.8rem 0.9rem;
    border-radius: 10px;
    border: 1px solid #DBEAFE;
    background: #EFF6FF;
    color: #1E40AF;
    font-size: 0.8125rem;
    line-height: 1.5;
}

.rf-flow-settings-danger-btn {
    height: 2.4rem;
    padding: 0 0.95rem;
    border: 1px solid #FCA5A5;
    border-radius: 10px;
    background: #FFFFFF;
    color: #DC2626;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
}

.rf-flow-settings-danger-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.rf-flow-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 2.4rem;
    padding: 0 1rem;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
}

.rf-flow-btn--secondary {
    background: #FFFFFF;
    border-color: #CBD5E1;
    color: #334155;
}

.rf-flow-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
</style>
