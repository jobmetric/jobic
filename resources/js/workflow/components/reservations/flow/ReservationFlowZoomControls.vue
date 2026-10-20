<script setup>
import { computed } from 'vue';

const props = defineProps({
    copy: { type: Object, required: true },
    zoomLevel: { type: Number, required: true },
    zoomMin: { type: Number, default: 0.55 },
    zoomMax: { type: Number, default: 1.6 },
});

const emit = defineEmits(['zoom-in', 'zoom-out', 'fit-view']);

const zoomPercent = computed(() => `${Math.round(props.zoomLevel * 100)}%`);
</script>

<template>
    <div class="rf-zoom" role="group" :aria-label="copy.reservation_flow_zoom_controls">
        <button
            type="button"
            class="rf-zoom__btn"
            :title="copy.reservation_flow_zoom_out"
            :aria-label="copy.reservation_flow_zoom_out"
            :disabled="zoomLevel <= zoomMin"
            @click="emit('zoom-out')"
        >
            <i class="ki-filled ki-minus" aria-hidden="true" />
        </button>
        <span class="rf-zoom__label">{{ zoomPercent }}</span>
        <button
            type="button"
            class="rf-zoom__btn"
            :title="copy.reservation_flow_zoom_in"
            :aria-label="copy.reservation_flow_zoom_in"
            :disabled="zoomLevel >= zoomMax"
            @click="emit('zoom-in')"
        >
            <i class="ki-filled ki-plus" aria-hidden="true" />
        </button>
        <button
            type="button"
            class="rf-zoom__btn"
            :title="copy.reservation_flow_fit_view"
            :aria-label="copy.reservation_flow_fit_view"
            @click="emit('fit-view')"
        >
            <i class="ki-filled ki-maximize" aria-hidden="true" />
        </button>
    </div>
</template>

<style scoped>
.rf-zoom {
    position: absolute;
    bottom: 16px;
    inset-inline-start: 16px;
    z-index: 8;
    display: inline-flex;
    align-items: center;
    height: 38px;
    padding: 0 0.25rem;
    border: 1px solid #E2E7EF;
    border-radius: 9px;
    background: #FFFFFF;
    box-shadow: 0 5px 16px rgba(15, 23, 42, 0.08);
}

.rf-zoom__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border: 0;
    border-radius: 7px;
    background: transparent;
    color: #475569;
    cursor: pointer;
}

.rf-zoom__btn:hover:not(:disabled) {
    background: #F1F5F9;
}

.rf-zoom__btn:disabled {
    opacity: 0.45;
    cursor: not-allowed;
}

.rf-zoom__btn:focus-visible {
    outline: 2px solid rgba(20, 110, 245, 0.35);
    outline-offset: 1px;
}

.rf-zoom__label {
    min-width: 2.75rem;
    text-align: center;
    font-size: 0.75rem;
    font-weight: 600;
    color: #334155;
}
</style>
