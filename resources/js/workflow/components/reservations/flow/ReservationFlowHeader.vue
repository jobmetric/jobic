<script setup>
defineProps({
    copy: { type: Object, required: true },
    isCustom: { type: Boolean, default: false },
    editable: { type: Boolean, default: false },
    isDirty: { type: Boolean, default: false },
    isSaving: { type: Boolean, default: false },
    saveError: { type: String, default: '' },
    backLabel: { type: String, default: '' },
});

const emit = defineEmits([
    'add-state',
    'add-transition',
    'open-settings',
    'discard',
    'update',
    'customize',
    'back',
]);
</script>

<template>
    <header class="rf-header">
        <div class="rf-header__left">
            <button
                v-if="backLabel"
                type="button"
                class="rf-header__back"
                @click="emit('back')"
            >
                <i class="ki-filled ki-left" aria-hidden="true" />
                {{ backLabel }}
            </button>
            <div class="rf-header__titles">
                <div class="rf-header__title-row">
                    <h1 class="rf-header__title">{{ copy.reservation_flow_page_title }}</h1>
                    <span class="rf-header__badge" :class="isCustom ? 'is-custom' : 'is-default'">
                        {{ isCustom ? copy.reservation_flow_mode_custom : copy.reservation_flow_mode_default }}
                    </span>
                    <span v-if="!editable" class="rf-header__readonly-inline">
                        · {{ copy.reservation_flow_readonly_short }}
                    </span>
                </div>
                <p class="rf-header__subtitle">{{ copy.reservation_flow_page_subtitle }}</p>
            </div>
        </div>

        <div v-if="editable" class="rf-header__center">
            <button type="button" class="rf-btn rf-btn--secondary" :disabled="isSaving" @click="emit('add-state')">
                <i class="ki-filled ki-plus-squared" aria-hidden="true" />
                {{ copy.reservation_flow_add_state }}
            </button>
            <button type="button" class="rf-btn rf-btn--secondary" :disabled="isSaving" @click="emit('add-transition')">
                <i class="ki-filled ki-arrow-right" aria-hidden="true" />
                {{ copy.reservation_flow_add_transition }}
            </button>
        </div>

        <div class="rf-header__right">
            <template v-if="editable">
                <button
                    type="button"
                    class="rf-btn rf-btn--icon"
                    :disabled="isSaving"
                    :aria-label="copy.reservation_flow_workflow_settings"
                    :title="copy.reservation_flow_workflow_settings"
                    @click="emit('open-settings')"
                >
                    <i class="ki-filled ki-setting-2" aria-hidden="true" />
                </button>

                <div class="rf-header__save-group">
                    <button
                        v-if="isDirty"
                        type="button"
                        class="rf-btn rf-btn--secondary"
                        :disabled="isSaving"
                        @click="emit('discard')"
                    >
                        {{ copy.reservation_flow_discard_changes }}
                    </button>

                    <button
                        type="button"
                        class="rf-btn rf-btn--primary"
                        :disabled="!isDirty || isSaving"
                        :title="!isDirty ? copy.reservation_flow_no_changes_to_update : ''"
                        @click="emit('update')"
                    >
                        <span v-if="isSaving" class="rf-btn__spinner" aria-hidden="true" />
                        {{ isSaving ? copy.reservation_flow_updating : copy.reservation_flow_update_workflow }}
                    </button>
                </div>
            </template>

            <template v-else>
                <button
                    type="button"
                    class="rf-btn rf-btn--primary"
                    :disabled="isSaving"
                    @click="emit('customize')"
                >
                    {{ copy.reservation_flow_action_customize }}
                </button>
            </template>
        </div>

        <p v-if="saveError" class="rf-header__error">{{ saveError }}</p>
    </header>
</template>

<style scoped>
.rf-header {
    display: grid;
    grid-template-columns: minmax(320px, 1fr) auto minmax(320px, 1fr);
    align-items: center;
    gap: 0.75rem 1rem;
    padding: 0.85rem 1.25rem;
    border-bottom: 1px solid #E7EAF0;
}

.rf-header__left {
    justify-self: start;
    min-width: 0;
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.rf-header__back {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0;
    border: 0;
    background: transparent;
    font-size: 0.8125rem;
    font-weight: 600;
    color: #64748B;
    cursor: pointer;
}

.rf-header__back:hover {
    color: #334155;
}

.rf-header__titles {
    min-width: 0;
}

.rf-header__center {
    justify-self: center;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rf-header__right {
    justify-self: end;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rf-header__save-group {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rf-header__title-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}

.rf-header__title {
    margin: 0;
    font-size: 1.625rem;
    font-weight: 700;
    line-height: 1.25;
    color: #0F172A;
}

.rf-header__subtitle {
    margin: 0.3rem 0 0;
    font-size: 0.8125rem;
    color: #64748B;
}

.rf-header__badge {
    padding: 0.2rem 0.55rem;
    border-radius: 9999px;
    font-size: 0.6875rem;
    font-weight: 600;
}

.rf-header__badge.is-custom {
    color: #2767D8;
    background: #EAF2FF;
    border: 1px solid #CFE2FF;
}

.rf-header__badge.is-default {
    color: #475569;
    background: #F1F5F9;
    border: 1px solid #E2E8F0;
}

.rf-header__readonly-inline {
    font-size: 0.8125rem;
    color: #64748B;
    font-weight: 500;
}

.rf-header__error {
    grid-column: 1 / -1;
    margin: 0;
    font-size: 0.8125rem;
    color: #DC2626;
}

.rf-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.35rem;
    height: 2.5rem;
    padding: 0 0.85rem;
    border-radius: 9px;
    border: 1px solid transparent;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
    white-space: nowrap;
}

.rf-btn:focus-visible {
    outline: 2px solid rgba(20, 110, 245, 0.35);
    outline-offset: 2px;
}

.rf-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.rf-btn--secondary {
    background: #FFFFFF;
    border-color: #DDE3EC;
    color: #263247;
}

.rf-btn--secondary:hover:not(:disabled) {
    background: #F8FAFC;
}

.rf-btn--primary {
    background: #146EF5;
    border-color: #146EF5;
    color: #FFFFFF;
}

.rf-btn--primary:hover:not(:disabled) {
    background: #0E5EDB;
}

.rf-btn--icon {
    width: 2.5rem;
    height: 2.5rem;
    padding: 0;
    background: #FFFFFF;
    border-color: #DDE3EC;
    color: #475569;
}

.rf-btn--icon:hover:not(:disabled) {
    background: #F8FAFC;
}

.rf-btn__spinner {
    width: 0.85rem;
    height: 0.85rem;
    border: 2px solid rgba(255, 255, 255, 0.35);
    border-top-color: #FFFFFF;
    border-radius: 9999px;
    animation: rf-spin 0.7s linear infinite;
}

@keyframes rf-spin {
    to {
        transform: rotate(360deg);
    }
}

@media (max-width: 1100px) {
    .rf-header {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto auto;
    }

    .rf-header__left,
    .rf-header__center,
    .rf-header__right {
        justify-self: stretch;
    }

    .rf-header__center {
        justify-content: center;
        flex-wrap: wrap;
    }

    .rf-header__right {
        justify-content: flex-end;
        flex-wrap: wrap;
    }
}

@media (max-width: 768px) {
    .rf-header__center,
    .rf-header__right {
        overflow-x: auto;
        flex-wrap: nowrap;
        padding-bottom: 0.15rem;
    }
}
</style>
