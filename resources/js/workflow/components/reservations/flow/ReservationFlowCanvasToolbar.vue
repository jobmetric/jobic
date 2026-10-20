<script setup>
defineProps({
    copy: { type: Object, required: true },
    editable: { type: Boolean, default: false },
    activeTool: { type: String, default: 'select' },
});

const emit = defineEmits(['select-tool', 'add-state', 'add-transition', 'fit-view']);
</script>

<template>
    <div class="rf-canvas-toolbar" role="toolbar" :aria-label="copy.reservation_flow_canvas_toolbar">
        <button
            type="button"
            class="rf-canvas-toolbar__btn"
            :class="{ 'is-active': activeTool === 'select' }"
            :title="copy.reservation_flow_tool_select"
            :aria-label="copy.reservation_flow_tool_select"
            @click="emit('select-tool', 'select')"
        >
            <i class="ki-filled ki-mouse-circle" aria-hidden="true" />
        </button>

        <button
            v-if="editable"
            type="button"
            class="rf-canvas-toolbar__btn"
            :class="{ 'is-active': activeTool === 'add-state' }"
            :title="copy.reservation_flow_add_state"
            :aria-label="copy.reservation_flow_add_state"
            @click="emit('add-state')"
        >
            <i class="ki-filled ki-plus-squared" aria-hidden="true" />
        </button>

        <button
            v-if="editable"
            type="button"
            class="rf-canvas-toolbar__btn"
            :title="copy.reservation_flow_add_transition"
            :aria-label="copy.reservation_flow_add_transition"
            @click="emit('add-transition')"
        >
            <i class="ki-filled ki-arrow-right" aria-hidden="true" />
        </button>

        <button
            type="button"
            class="rf-canvas-toolbar__btn"
            :title="copy.reservation_flow_fit_view"
            :aria-label="copy.reservation_flow_fit_view"
            @click="emit('fit-view')"
        >
            <i class="ki-filled ki-maximize" aria-hidden="true" />
        </button>
    </div>
</template>

<style scoped>
.rf-canvas-toolbar {
    position: absolute;
    top: 16px;
    inset-inline-start: 16px;
    z-index: 8;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
    padding: 5px;
    border: 1px solid #E2E7EF;
    border-radius: 10px;
    background: #FFFFFF;
    box-shadow: 0 5px 16px rgba(15, 23, 42, 0.08);
}

.rf-canvas-toolbar__btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border: 0;
    border-radius: 8px;
    background: #FFFFFF;
    color: #64748B;
    cursor: pointer;
    transition: background 0.15s ease, color 0.15s ease;
}

.rf-canvas-toolbar__btn:hover {
    background: #F1F5F9;
    color: #334155;
}

.rf-canvas-toolbar__btn.is-active {
    background: #146EF5;
    color: #FFFFFF;
}

.rf-canvas-toolbar__btn:focus-visible {
    outline: 2px solid rgba(20, 110, 245, 0.35);
    outline-offset: 2px;
}
</style>
