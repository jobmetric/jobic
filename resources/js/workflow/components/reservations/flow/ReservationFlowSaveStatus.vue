<script setup>
import { computed } from 'vue';

const props = defineProps({
    copy: { type: Object, required: true },
    loading: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    saveFailed: { type: Boolean, default: false },
});

const status = computed(() => {
    if (props.loading) {
        return 'idle';
    }

    if (props.saving) {
        return 'saving';
    }

    if (props.saveFailed) {
        return 'failed';
    }

    return 'saved';
});
</script>

<template>
    <div
        v-if="status !== 'idle'"
        class="rf-save-status"
        :class="`is-${status}`"
        role="status"
        aria-live="polite"
    >
        <span v-if="status === 'saving'" class="rf-save-status__icon rf-save-status__spinner" aria-hidden="true" />
        <i v-else-if="status === 'saved'" class="ki-filled ki-check rf-save-status__icon" aria-hidden="true" />
        <i v-else-if="status === 'failed'" class="ki-filled ki-information-2 rf-save-status__icon" aria-hidden="true" />

        <span class="rf-save-status__text">
            <template v-if="status === 'saving'">{{ copy.saving }}</template>
            <template v-else-if="status === 'saved'">{{ copy.reservation_flow_save_status_saved }}</template>
            <template v-else-if="status === 'failed'">{{ copy.reservation_flow_save_status_failed }}</template>
        </span>
    </div>
</template>

<style scoped>
.rf-save-status {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    font-size: 0.8125rem;
    font-weight: 500;
    white-space: nowrap;
}

.rf-save-status__icon {
    font-size: 0.95rem;
    line-height: 1;
}

.rf-save-status.is-saving {
    color: #64748B;
}

.rf-save-status.is-saved {
    color: #178A4B;
}

.rf-save-status.is-failed {
    color: #DC2626;
}

.rf-save-status__spinner {
    width: 0.85rem;
    height: 0.85rem;
    border: 2px solid #CBD5E1;
    border-top-color: #64748B;
    border-radius: 9999px;
    animation: rf-spin 0.7s linear infinite;
}

@keyframes rf-spin {
    to {
        transform: rotate(360deg);
    }
}
</style>
