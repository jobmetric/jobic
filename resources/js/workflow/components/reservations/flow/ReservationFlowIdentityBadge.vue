<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: String, required: true },
    hint: { type: String, default: '' },
});
</script>

<template>
    <div class="rf-identity-badge" :title="hint" :aria-label="`${label}: ${value}`">
        <span class="rf-identity-badge__label">{{ label }}</span>
        <code class="rf-identity-badge__value" dir="ltr">{{ value }}</code>
    </div>
</template>

<style scoped>
.rf-identity-badge {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    width: 100%;
    min-width: 0;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--rf-modal-border, var(--rf-border, var(--border, #e2e8f0)));
    color: var(--rf-modal-secondary, var(--rf-muted, var(--text-soft, #64748b)));
    font-size: 0.6875rem;
    line-height: 1.3;
    user-select: text;
}

.rf-identity-badge__label { flex: 0 0 auto; font-weight: 600; }
.rf-identity-badge__value {
    min-width: 0;
    max-width: 70%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    padding: 0.3rem 0.55rem;
    border: 1px solid var(--rf-modal-border, var(--rf-border, var(--border, #e2e8f0)));
    border-radius: 999px;
    background: var(--rf-modal-surface, var(--rf-canvas, var(--surface-soft, #f8fafc)));
    color: var(--rf-modal-text, var(--rf-text, var(--text, #334155)));
    font: inherit;
    font-weight: 700;
    direction: ltr;
}
</style>
