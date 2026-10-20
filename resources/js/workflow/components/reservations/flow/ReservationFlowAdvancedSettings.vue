<script setup>
defineProps({
    copy: { type: Object, required: true },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['reset']);
</script>

<template>
    <details class="rf-advanced">
        <summary class="rf-advanced__summary">
            <span class="rf-advanced__icon" aria-hidden="true">
                <i class="ki-filled ki-setting-2" />
            </span>
            <span class="rf-advanced__text">
                <span class="rf-advanced__title">{{ copy.reservation_flow_advanced_title }}</span>
                <span class="rf-advanced__desc">{{ copy.reservation_flow_advanced_desc }}</span>
            </span>
            <i class="ki-filled ki-down rf-advanced__chevron" aria-hidden="true" />
        </summary>

        <div class="rf-advanced__body">
            <div class="rf-danger-zone">
                <h4 class="rf-danger-zone__title">{{ copy.reservation_flow_danger_zone_title }}</h4>
                <p class="rf-danger-zone__desc">{{ copy.reservation_flow_danger_zone_desc }}</p>
                <button
                    type="button"
                    class="rf-danger-zone__btn"
                    :disabled="saving"
                    @click="emit('reset')"
                >
                    {{ copy.reservation_flow_action_reset }}
                </button>
            </div>
        </div>
    </details>
</template>

<style scoped>
.rf-advanced {
    margin: 0 1.25rem 1rem;
    border: 1px solid #E7EAF0;
    border-radius: 10px;
    background: #FFFFFF;
    overflow: hidden;
}

.rf-advanced__summary {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.85rem 1rem;
    cursor: pointer;
    list-style: none;
}

.rf-advanced__summary::-webkit-details-marker {
    display: none;
}

.rf-advanced__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 8px;
    background: #F1F5F9;
    color: #64748B;
}

.rf-advanced__text {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.1rem;
}

.rf-advanced__title {
    font-size: 0.8125rem;
    font-weight: 700;
    color: #0F172A;
}

.rf-advanced__desc {
    font-size: 0.75rem;
    color: #64748B;
}

.rf-advanced__chevron {
    color: #94A3B8;
    transition: transform 0.15s ease;
}

.rf-advanced[open] .rf-advanced__chevron {
    transform: rotate(180deg);
}

.rf-advanced__body {
    padding: 0 1rem 1rem;
    border-top: 1px solid #E7EAF0;
}

.rf-danger-zone {
    margin-top: 0.85rem;
    padding: 0.85rem;
    border: 1px solid #FECACA;
    border-radius: 9px;
    background: #FEF2F2;
}

.rf-danger-zone__title {
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 700;
    color: #B91C1C;
}

.rf-danger-zone__desc {
    margin: 0.35rem 0 0.75rem;
    font-size: 0.75rem;
    color: #7F1D1D;
    line-height: 1.5;
}

.rf-danger-zone__btn {
    height: 2.25rem;
    padding: 0 0.85rem;
    border: 1px solid #FCA5A5;
    border-radius: 8px;
    background: #FFFFFF;
    color: #DC2626;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
}

.rf-danger-zone__btn:hover:not(:disabled) {
    background: #FEE2E2;
}

.rf-danger-zone__btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
</style>
