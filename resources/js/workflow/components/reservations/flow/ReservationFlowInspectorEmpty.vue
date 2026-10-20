<script setup>
defineProps({
    copy: { type: Object, required: true },
});
</script>

<template>
    <div class="rf-inspector-empty">
        <div class="rf-inspector-empty__icon" aria-hidden="true">
            <i class="ki-filled ki-mouse-circle" />
        </div>
        <h3 class="rf-inspector-empty__title">{{ copy.reservation_flow_editor_inspector_idle }}</h3>
        <p class="rf-inspector-empty__desc">{{ copy.reservation_flow_inspector_empty_desc }}</p>
        <ul class="rf-inspector-empty__hints">
            <li>{{ copy.reservation_flow_inspector_hint_drag }}</li>
            <li>{{ copy.reservation_flow_inspector_hint_transition }}</li>
            <li>{{ copy.reservation_flow_inspector_hint_reconnect }}</li>
        </ul>
    </div>
</template>

<style scoped>
.rf-inspector-empty {
    padding: 1.25rem;
}

.rf-inspector-empty__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.5rem;
    height: 2.5rem;
    border-radius: 10px;
    background: #F1F5F9;
    color: #64748B;
    font-size: 1.1rem;
}

.rf-inspector-empty__title {
    margin: 0.85rem 0 0;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0F172A;
}

.rf-inspector-empty__desc {
    margin: 0.45rem 0 0;
    font-size: 0.8125rem;
    color: #64748B;
    line-height: 1.5;
}

.rf-inspector-empty__hints {
    margin: 0.85rem 0 0;
    padding-inline-start: 1.1rem;
    font-size: 0.75rem;
    color: #94A3B8;
    line-height: 1.6;
}
</style>
