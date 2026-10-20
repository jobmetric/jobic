<script setup>
import { computed, ref, watch } from 'vue';

import ReservationFlowModalShell from './ReservationFlowModalShell.vue';
import ReservationFlowIdentityBadge from './ReservationFlowIdentityBadge.vue';
import { reservationFlowNodeBadgeLabel, reservationFlowNodeTheme } from '../../../utils/reservationFlowNodeTheme.js';

const props = defineProps({
    copy: { type: Object, required: true },
    open: { type: Boolean, default: false },
    availableStatuses: { type: Array, default: () => [] },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'submit']);

const name = ref('');
const contentStatus = ref('draft');
const isTerminal = ref(false);
const error = ref('');
const nameInputRef = ref(null);
const automaticStatus = ref('');
const resolvedStatus = computed(() => automaticStatus.value);

const selectedStatus = computed(() => (
    props.availableStatuses.find((row) => row.value === contentStatus.value) ?? null
));

const previewNode = computed(() => ({
    kind: 'state',
    status: resolvedStatus.value,
    name: name.value || props.copy.reservation_flow_add_state_preview_placeholder,
    is_terminal: isTerminal.value,
}));

const previewTheme = computed(() => reservationFlowNodeTheme(previewNode.value));
const previewBadge = computed(() => reservationFlowNodeBadgeLabel(previewNode.value, props.copy));

watch(() => props.open, (isOpen) => {
    if (!isOpen) {
        return;
    }

    name.value = '';
    contentStatus.value = 'draft';
    automaticStatus.value = `stage_${crypto.randomUUID().replaceAll('-', '').slice(0, 12)}`;
    isTerminal.value = false;
    error.value = '';
});

function submit() {
    if (!name.value.trim()) {
        error.value = props.copy.reservation_flow_add_state_name_required;
        nameInputRef.value?.focus?.();
        return;
    }

    emit('submit', {
        name: name.value.trim(),
        status: resolvedStatus.value,
        content_status: contentStatus.value,
        is_terminal: isTerminal.value,
    });
}
</script>

<template>
    <ReservationFlowModalShell
        :open="open"
        :title="copy.reservation_flow_add_state_modal_title"
        :description="copy.reservation_flow_add_state_modal_desc"
        icon="ki-abstract-26"
        size="lg"
        :saving="saving"
        :cancel-label="copy.cancel"
        :aria-label="copy.reservation_flow_add_state_modal_title"
        @close="emit('close')"
    >
        <form class="rf-flow-modal__body" @submit.prevent="submit">
            <div class="rf-flow-modal__split">
                <div class="rf-flow-modal__form-pane">
                    <ReservationFlowIdentityBadge
                        :label="copy.reservation_flow_state_status"
                        :value="resolvedStatus"
                        :hint="copy.automatic_status_hint"
                    />
                    <label class="rf-flow-field">
                        <span class="rf-flow-field__label">{{ copy.reservation_flow_state_name }}</span>
                        <input
                            ref="nameInputRef"
                            v-model="name"
                            class="rf-flow-field__input"
                            autofocus
                        />
                        <span class="rf-flow-field__hint">{{ copy.reservation_flow_add_state_name_help }}</span>
                    </label>

                    <label class="rf-flow-field">
                        <span class="rf-flow-field__label">{{ copy.status_choice_label }}</span>
                        <select v-model="contentStatus" class="rf-flow-field__input">
                            <option v-for="choice in availableStatuses" :key="choice.value" :value="choice.value">{{ choice.label }}</option>
                        </select>
                        <span class="rf-flow-field__hint">{{ copy.content_status_hint }}</span>
                    </label>

                    <label class="rf-flow-field rf-flow-field--toggle">
                        <input v-model="isTerminal" type="checkbox" class="rf-flow-field__checkbox" />
                        <span class="rf-flow-field__toggle-copy">
                            <span class="rf-flow-field__toggle-title">
                                {{ copy.reservation_flow_terminal_badge }}
                            </span>
                            <span class="rf-flow-field__hint">{{ copy.reservation_flow_add_state_terminal_help }}</span>
                        </span>
                    </label>

                    <p v-if="error" class="rf-flow-field__error" role="alert">{{ error }}</p>
                </div>

                <aside class="rf-flow-modal__preview-pane" :aria-label="copy.reservation_flow_add_state_preview_heading">
                    <div class="rf-flow-modal__preview-top">
                        <h3 class="rf-flow-modal__preview-heading">{{ copy.reservation_flow_add_state_preview_heading }}</h3>
                    </div>
                    <p class="rf-flow-modal__preview-desc">{{ copy.reservation_flow_add_state_preview_help }}</p>

                    <div class="rf-flow-node-preview" :style="{ borderColor: previewTheme.border }">
                        <span
                            class="rf-flow-node-preview__icon"
                            :style="{ background: previewTheme.iconBg, color: previewTheme.iconColor }"
                        >
                            <i :class="`ki-filled ${previewTheme.icon}`" aria-hidden="true" />
                        </span>
                        <span class="rf-flow-node-preview__content">
                            <span class="rf-flow-node-preview__title">{{ previewNode.name }}</span>
                            <span
                                class="rf-flow-node-preview__badge"
                                :style="{ background: previewTheme.badgeBg, color: previewTheme.badgeText }"
                            >
                                {{ previewBadge }}
                            </span>
                        </span>
                    </div>

                    <div v-if="selectedStatus" class="rf-flow-modal__preview-section">
                        <h4>{{ copy.status_choice_label }}</h4>
                        <p>{{ selectedStatus.label }}</p>
                    </div>

                    <div class="rf-flow-modal__callout">
                        <i class="ki-filled ki-information-2" aria-hidden="true" />
                        <p>{{ copy.reservation_flow_add_state_name_help }}</p>
                    </div>
                </aside>
            </div>
        </form>

        <template #footer>
            <div class="rf-flow-modal__footer-actions">
                <button type="button" class="rf-flow-btn rf-flow-btn--primary" :disabled="saving" @click="submit">
                    <i class="ki-filled ki-plus" aria-hidden="true" />
                    {{ saving ? copy.saving : copy.reservation_flow_add_state }}
                </button>
            </div>
        </template>
    </ReservationFlowModalShell>
</template>

<style scoped>
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

.rf-flow-modal__preview-top { display:flex; align-items:center; justify-content:space-between; gap:12px; }

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

.rf-flow-field--toggle {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    padding: 0.85rem 0.9rem;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    background: #F8FAFC;
    cursor: pointer;
}

.rf-flow-field__checkbox {
    margin-top: 0.15rem;
    flex-shrink: 0;
}

.rf-flow-field__toggle-copy {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
}

.rf-flow-field__toggle-title {
    font-size: 0.875rem;
    font-weight: 600;
    color: #334155;
}

.rf-flow-node-preview {
    display: flex;
    align-items: flex-start;
    gap: 0.55rem;
    padding: 0.75rem 0.85rem;
    border: 1.5px solid #CBD5E1;
    border-radius: 12px;
    background: #FFFFFF;
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
}

.rf-flow-node-preview__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 9px;
    flex-shrink: 0;
}

.rf-flow-node-preview__content {
    display: flex;
    flex-direction: column;
    gap: 0.2rem;
    min-width: 0;
}

.rf-flow-node-preview__title {
    font-size: 0.875rem;
    font-weight: 700;
    line-height: 1.3;
    color: #172033;
    word-break: break-word;
}

.rf-flow-node-preview__badge {
    align-self: flex-start;
    padding: 0.08rem 0.42rem;
    border-radius: 9999px;
    font-size: 0.5625rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
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

.rf-flow-modal__preview-pane,
.rf-flow-field--toggle { border-color:var(--rf-modal-border); background:var(--rf-modal-surface); }
.rf-flow-modal__preview-heading,
.rf-flow-modal__preview-section h4,
.rf-flow-field__hint { color:var(--rf-modal-muted); }
.rf-flow-modal__preview-desc,
.rf-flow-field__label { color:var(--rf-modal-secondary); }
.rf-flow-modal__preview-section { border-color:var(--rf-modal-border); }
.rf-flow-modal__preview-section p,
.rf-flow-field__toggle-title,
.rf-flow-node-preview__title { color:var(--rf-modal-text); }
.rf-flow-modal__callout { border-color:color-mix(in srgb,var(--rf-modal-primary) 28%,var(--rf-modal-border)); background:color-mix(in srgb,var(--rf-modal-primary) 9%,var(--rf-modal-bg)); color:var(--rf-modal-text); }
.rf-flow-field__input { border-color:var(--rf-modal-border-strong); background:var(--rf-modal-bg); color:var(--rf-modal-text); }
.rf-flow-field__input:focus { border-color:var(--rf-modal-primary); box-shadow:0 0 0 3px color-mix(in srgb,var(--rf-modal-primary) 14%,transparent); }
.rf-flow-node-preview { background:var(--rf-modal-bg); }
.rf-flow-btn--primary { background:var(--rf-modal-primary); }

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
