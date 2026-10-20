<script setup>
defineProps({
    copy: { type: Object, required: true },
    isCustom: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['customize']);
</script>

<template>
    <div class="rf-banner" :class="isCustom ? 'is-custom' : 'is-default'">
        <i class="ki-filled ki-information-2 rf-banner__icon" aria-hidden="true" />
        <div class="rf-banner__body">
            <p class="rf-banner__title">
                {{ isCustom ? copy.reservation_flow_banner_custom_title : copy.reservation_flow_banner_default_title }}
            </p>
            <p class="rf-banner__desc">
                {{ isCustom ? copy.reservation_flow_banner_custom_desc : copy.reservation_flow_banner_default_desc }}
            </p>
        </div>
        <button
            v-if="!isCustom"
            type="button"
            class="rf-banner__action"
            :disabled="saving"
            @click="emit('customize')"
        >
            {{ copy.reservation_flow_action_customize }}
        </button>
    </div>
</template>

<style scoped>
.rf-banner {
    display: flex;
    align-items: flex-start;
    gap: 0.65rem;
    margin: 0 1.25rem 0.85rem;
    padding: 0.65rem 0.85rem;
    border-radius: 8px;
    border: 1px solid #CFE2FF;
    background: #F0F6FF;
}

.rf-banner.is-default {
    border-color: #CFE2FF;
    background: #F0F6FF;
}

.rf-banner__icon {
    flex-shrink: 0;
    margin-top: 0.1rem;
    font-size: 1rem;
    color: #2767D8;
}

.rf-banner__body {
    flex: 1;
    min-width: 0;
}

.rf-banner__title {
    margin: 0;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #1E3A5F;
}

.rf-banner__desc {
    margin: 0.15rem 0 0;
    font-size: 0.75rem;
    color: #475569;
}

.rf-banner__action {
    flex-shrink: 0;
    align-self: center;
    height: 2rem;
    padding: 0 0.75rem;
    border: 1px solid #146EF5;
    border-radius: 8px;
    background: #146EF5;
    color: #FFFFFF;
    font-size: 0.75rem;
    font-weight: 600;
    cursor: pointer;
}

.rf-banner__action:hover:not(:disabled) {
    background: #0E5EDB;
}

.rf-banner__action:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
</style>
