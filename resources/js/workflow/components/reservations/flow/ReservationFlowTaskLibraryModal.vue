<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

import {
    buildTaskCategoryNav,
    buildTaskDriverCatalog,
    defaultConfigForDriver,
    driverNeedsConfiguration,
    filterTaskDriverCatalog,
    findTaskDriver,
} from '../../../utils/reservationFlowTaskCatalog.js';

const props = defineProps({
    copy: { type: Object, required: true },
    open: { type: Boolean, default: false },
    availableDrivers: { type: Array, default: () => [] },
    existingTasks: { type: Array, default: () => [] },
    transitionLabel: { type: String, default: '' },
    transitionRoute: { type: String, default: '' },
    mode: { type: String, default: 'add' },
    editTask: { type: Object, default: null },
    saving: { type: Boolean, default: false },
});

const emit = defineEmits(['close', 'add-task', 'update-task']);

const step = ref('library');
const searchQuery = ref('');
const activeCategory = ref('all');
const selectedDriverId = ref('');
const configDraft = ref({});
const multiSelections = ref({});
const multiOptionSelects = ref({});
const configError = ref('');
const submitting = ref(false);
const searchInputRef = ref(null);
const modalRef = ref(null);
const listRef = ref(null);
const highlightedIndex = ref(0);
const addTriggerRef = ref(null);

let previousBodyOverflow = '';

const catalog = computed(() => buildTaskDriverCatalog(props.availableDrivers, props.copy));

const categories = computed(() => buildTaskCategoryNav(catalog.value, props.copy));

const filteredDrivers = computed(() => filterTaskDriverCatalog(catalog.value, {
    category: activeCategory.value,
    query: searchQuery.value,
}));

const selectedDriver = computed(() => findTaskDriver(catalog.value, selectedDriverId.value));

const isEditMode = computed(() => props.mode === 'edit' && Boolean(props.editTask));

const modalTitle = computed(() => (
    isEditMode.value ? props.copy.reservation_flow_edit_task : props.copy.reservation_flow_add_task
));

const primaryActionLabel = computed(() => {
    if (submitting.value || props.saving) {
        return props.copy.saving;
    }

    if (step.value === 'config' && isEditMode.value) {
        return props.copy.reservation_flow_save_changes;
    }

    return props.copy.reservation_flow_add_task;
});

const canSubmit = computed(() => {
    if (submitting.value || props.saving) {
        return false;
    }

    if (step.value === 'library') {
        return Boolean(selectedDriver.value);
    }

    return Boolean(selectedDriver.value || isEditMode.value);
});

function badgeClass(category) {
    return {
        restriction: 'is-restriction',
        validation: 'is-validation',
        action: 'is-action',
    }[category] ?? 'is-action';
}

function syncSelectionToFilter() {
    if (!filteredDrivers.value.length) {
        selectedDriverId.value = '';
        highlightedIndex.value = 0;
        return;
    }

    const currentIndex = filteredDrivers.value.findIndex((row) => row.id === selectedDriverId.value);

    if (currentIndex === -1) {
        selectedDriverId.value = filteredDrivers.value[0].id;
        highlightedIndex.value = 0;
        return;
    }

    highlightedIndex.value = currentIndex;
}

function resetForOpen() {
    searchQuery.value = '';
    activeCategory.value = 'all';
    configError.value = '';
    submitting.value = false;
    multiSelections.value = {};
    multiOptionSelects.value = {};

    if (isEditMode.value && props.editTask) {
        const driverId = props.editTask.driver ?? props.editTask.driver_key ?? '';
        selectedDriverId.value = driverId;
        configDraft.value = { ...(props.editTask.config ?? {}) };
        step.value = 'config';
        return;
    }

    step.value = 'library';
    selectedDriverId.value = catalog.value[0]?.id ?? '';
    configDraft.value = {};
    highlightedIndex.value = 0;
}

function lockBodyScroll() {
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
}

function unlockBodyScroll() {
    document.body.style.overflow = previousBodyOverflow ?? '';
}

function requestClose() {
    if (submitting.value || props.saving) {
        return;
    }

    emit('close');
}

function selectDriver(driver) {
    if (!driver) {
        return;
    }

    selectedDriverId.value = driver.id;
    highlightedIndex.value = filteredDrivers.value.findIndex((row) => row.id === driver.id);
}

function chooseDriver(driver) {
    selectDriver(driver);
    if (driverNeedsConfiguration(driver)) goToConfigStep();
}

function goToConfigStep() {
    if (!selectedDriver.value) {
        return;
    }

    if (!isEditMode.value) {
        configDraft.value = defaultConfigForDriver(selectedDriver.value);
        multiSelections.value = {};
    }

    configError.value = '';
    step.value = 'config';
}

function backToLibrary() {
    if (isEditMode.value) {
        requestClose();
        return;
    }

    configError.value = '';
    step.value = 'library';
    nextTick(() => searchInputRef.value?.focus?.());
}

function validateConfig() {
    for (const [key, field] of Object.entries(selectedDriver.value?.fields ?? {})) {
        const value = configDraft.value[key];
        const missing = field.type === 'multiselect'
            ? !Array.isArray(value) || value.length === 0
            : !String(value ?? '').trim();
        if (field.required && missing) {
            configError.value = field.label;
            return false;
        }
    }
    configError.value = '';
    return true;
}

function selectedMultiOptions(key, field) {
    const selected = new Set((Array.isArray(configDraft.value[key]) ? configDraft.value[key] : []).map(String));
    return Object.entries(field.options ?? {}).filter(([value]) => selected.has(String(value)));
}

function availableMultiOptions(key, field) {
    const selected = new Set((Array.isArray(configDraft.value[key]) ? configDraft.value[key] : []).map(String));
    return Object.entries(field.options ?? {}).filter(([value]) => !selected.has(String(value)));
}

function setMultiOptionSelect(key, element) {
    if (element) multiOptionSelects.value[key] = element;
}

async function addMultiOption(key) {
    const selectedValue = String(multiSelections.value[key] ?? '');
    if (!selectedValue) return;

    const values = Array.isArray(configDraft.value[key]) ? configDraft.value[key] : [];
    if (!values.some((value) => String(value) === selectedValue)) {
        const value = /^\d+$/.test(selectedValue) ? Number(selectedValue) : selectedValue;
        configDraft.value[key] = [...values, value];
    }

    multiSelections.value[key] = '';
    await nextTick();
    multiOptionSelects.value[key]?.dispatchEvent(new Event('change', { bubbles: true }));
}

function removeMultiOption(key, selectedValue) {
    const values = Array.isArray(configDraft.value[key]) ? configDraft.value[key] : [];
    configDraft.value[key] = values.filter((value) => String(value) !== String(selectedValue));
}

async function submit() {
    if (!canSubmit.value) {
        return;
    }

    if (step.value === 'library') {
        if (!selectedDriver.value) {
            return;
        }

        if (driverNeedsConfiguration(selectedDriver.value) && !isEditMode.value) {
            goToConfigStep();
            return;
        }

        submitting.value = true;

        try {
            emit('add-task', {
                driver: selectedDriver.value.driverClass,
                config: defaultConfigForDriver(selectedDriver.value),
                status: true,
                type: selectedDriver.value.category,
                title: selectedDriver.value.title,
                description: selectedDriver.value.description,
                driver_key: selectedDriver.value.driverKey,
            });
        } finally {
            submitting.value = false;
        }

        return;
    }

    if (!validateConfig()) {
        return;
    }

    submitting.value = true;

    try {
        const payload = {
            driver: selectedDriver.value?.driverClass ?? props.editTask?.driver,
            config: { ...configDraft.value },
            type: selectedDriver.value?.category ?? props.editTask?.type,
            title: selectedDriver.value?.title ?? props.editTask?.title,
            description: selectedDriver.value?.description ?? props.editTask?.description,
            driver_key: selectedDriver.value?.driverKey ?? props.editTask?.driver_key,
        };

        if (isEditMode.value) {
            emit('update-task', {
                taskId: props.editTask.id,
                config: payload.config,
            });
            return;
        }

        emit('add-task', {
            ...payload,
            status: true,
        });
    } finally {
        submitting.value = false;
    }
}

function onSearchKeydown(event) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        if (!filteredDrivers.value.length) {
            return;
        }

        highlightedIndex.value = Math.min(filteredDrivers.value.length - 1, highlightedIndex.value + 1);
        selectDriver(filteredDrivers.value[highlightedIndex.value]);
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        if (!filteredDrivers.value.length) {
            return;
        }

        highlightedIndex.value = Math.max(0, highlightedIndex.value - 1);
        selectDriver(filteredDrivers.value[highlightedIndex.value]);
    }
}

function onListKeydown(event) {
    if (event.key === 'ArrowDown') {
        event.preventDefault();
        highlightedIndex.value = Math.min(filteredDrivers.value.length - 1, highlightedIndex.value + 1);
        selectDriver(filteredDrivers.value[highlightedIndex.value]);
        return;
    }

    if (event.key === 'ArrowUp') {
        event.preventDefault();
        highlightedIndex.value = Math.max(0, highlightedIndex.value - 1);
        selectDriver(filteredDrivers.value[highlightedIndex.value]);
        return;
    }

    if (event.key === 'Enter' || event.key === ' ') {
        event.preventDefault();
        const driver = filteredDrivers.value[highlightedIndex.value];
        if (driver) {
            selectDriver(driver);
        }
    }
}

function onDocumentKeydown(event) {
    if (!props.open) {
        return;
    }

    if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === 'k') {
        event.preventDefault();
        searchInputRef.value?.focus?.();
        return;
    }

    if (event.key === '/' && document.activeElement !== searchInputRef.value) {
        event.preventDefault();
        searchInputRef.value?.focus?.();
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        if (step.value === 'config' && !isEditMode.value) {
            backToLibrary();
            return;
        }

        requestClose();
    }
}

function trapFocus(event) {
    if (!props.open || !modalRef.value) {
        return;
    }

    if (event.key !== 'Tab') {
        return;
    }

    const focusable = modalRef.value.querySelectorAll(
        'button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])',
    );

    if (!focusable.length) {
        return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];

    if (event.shiftKey && document.activeElement === first) {
        event.preventDefault();
        last.focus();
        return;
    }

    if (!event.shiftKey && document.activeElement === last) {
        event.preventDefault();
        first.focus();
    }
}

watch(filteredDrivers, syncSelectionToFilter);

watch(() => props.open, async (isOpen) => {
    if (!isOpen) {
        unlockBodyScroll();
        return;
    }

    resetForOpen();
    lockBodyScroll();
    await nextTick();

    if (step.value === 'library') {
        searchInputRef.value?.focus?.();
    }
});

watch(activeCategory, syncSelectionToFilter);
watch(searchQuery, syncSelectionToFilter);

onMounted(() => {
    document.addEventListener('keydown', onDocumentKeydown, true);
    document.addEventListener('keydown', trapFocus, true);
});

onBeforeUnmount(() => {
    unlockBodyScroll();
    document.removeEventListener('keydown', onDocumentKeydown, true);
    document.removeEventListener('keydown', trapFocus, true);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="open"
                class="rf-task-library-backdrop"
                role="presentation"
                @click.self="requestClose"
            >
                <Transition
                    appear
                    enter-active-class="transition duration-250 ease-out"
                    enter-from-class="opacity-0 scale-[0.98] translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-[0.98] translate-y-2"
                >
                    <div
                        v-if="open"
                        ref="modalRef"
                        class="rf-task-library"
                        role="dialog"
                        aria-modal="true"
                        :aria-labelledby="'rf-task-library-title'"
                        :aria-describedby="'rf-task-library-desc'"
                        @click.stop
                    >
                        <header class="rf-task-library__header">
                            <div class="rf-task-library__header-main">
                                <div class="rf-task-library__icon-wrap" aria-hidden="true">
                                    <i class="ki-filled ki-setting-2" />
                                </div>
                                <div class="min-w-0">
                                    <h2 id="rf-task-library-title" class="rf-task-library__title">{{ modalTitle }}</h2>
                                </div>
                            </div>
                            <button
                                type="button"
                                class="rf-task-library__close"
                                :aria-label="copy.cancel"
                                :disabled="submitting || saving"
                                @click="requestClose"
                            >
                                <i class="ki-filled ki-cross" aria-hidden="true" />
                            </button>

                            <div v-if="step === 'library'" class="rf-task-library__search-wrap">
                                <label class="rf-task-library__search-label" :for="'rf-task-library-search'">
                                    {{ copy.reservation_flow_task_library_search_label }}
                                </label>
                                <div class="rf-task-library__search">
                                    <i class="ki-filled ki-magnifier rf-task-library__search-icon" aria-hidden="true" />
                                    <input
                                        :id="'rf-task-library-search'"
                                        ref="searchInputRef"
                                        v-model="searchQuery"
                                        type="search"
                                        class="rf-task-library__search-input"
                                        :placeholder="copy.reservation_flow_task_library_search_placeholder"
                                        autocomplete="off"
                                        @keydown="onSearchKeydown"
                                    />
                                    <button
                                        v-if="searchQuery"
                                        type="button"
                                        class="rf-task-library__search-clear"
                                        :aria-label="copy.reservation_flow_task_library_clear_search"
                                        @click="searchQuery = ''"
                                    >
                                        <i class="ki-filled ki-cross" aria-hidden="true" />
                                    </button>
                                </div>
                            </div>
                        </header>

                        <div v-if="step === 'library'" class="rf-task-library__body">
                            <nav class="rf-task-library__categories" :aria-label="copy.reservation_flow_task_library_categories">
                                <button
                                    v-for="category in categories"
                                    :key="category.id"
                                    type="button"
                                    class="rf-task-library__category"
                                    :class="{ 'is-active': activeCategory === category.id }"
                                    :aria-current="activeCategory === category.id ? 'true' : undefined"
                                    @click="activeCategory = category.id"
                                >
                                    <i :class="`ki-filled ${category.icon}`" aria-hidden="true" />
                                    <span>{{ category.label }}</span>
                                    <span v-if="category.count > 0" class="rf-task-library__category-count">{{ category.count }}</span>
                                </button>
                            </nav>

                            <div
                                ref="listRef"
                                class="rf-task-library__list-pane"
                                tabindex="0"
                                :aria-label="copy.reservation_flow_task_library_task_list"
                                @keydown="onListKeydown"
                            >
                                <div v-if="filteredDrivers.length" class="rf-task-library__list">
                                    <button
                                        v-for="(driver, index) in filteredDrivers"
                                        :key="driver.id"
                                        type="button"
                                        class="rf-task-library__card"
                                        :class="{ 'is-selected': selectedDriverId === driver.id, 'is-highlighted': highlightedIndex === index }"
                                        :aria-pressed="selectedDriverId === driver.id"
                                        @click="chooseDriver(driver)"
                                        @focus="highlightedIndex = index"
                                    >
                                        <span class="rf-task-library__card-icon" :class="badgeClass(driver.category)" aria-hidden="true">
                                            <i :class="`ki-filled ${driver.icon}`" />
                                        </span>
                                        <span class="rf-task-library__card-body">
                                            <span class="rf-task-library__card-title">{{ driver.title }}</span>
                                            <span class="rf-task-library__card-desc">{{ driver.description }}</span>
                                        </span>
                                        <span class="rf-task-library__card-badge" :class="badgeClass(driver.category)">
                                            {{ driver.badgeLabel }}
                                        </span>
                                        <i class="ki-filled ki-right rf-task-library__card-chevron" aria-hidden="true" />
                                    </button>
                                </div>

                                <div v-else class="rf-task-library__empty">
                                    <h3>{{ copy.reservation_flow_task_library_empty_title }}</h3>
                                    <p>{{ copy.reservation_flow_task_library_empty_desc }}</p>
                                    <button
                                        v-if="searchQuery"
                                        type="button"
                                        class="rf-btn rf-btn--secondary rf-btn--sm"
                                        @click="searchQuery = ''"
                                    >
                                        {{ copy.reservation_flow_task_library_clear_search }}
                                    </button>
                                </div>
                            </div>

                            <aside class="rf-task-library__detail" :aria-live="selectedDriver ? 'polite' : 'off'">
                                <template v-if="selectedDriver">
                                    <span class="rf-task-library__detail-badge" :class="badgeClass(selectedDriver.category)">
                                        {{ selectedDriver.badgeLabel }}
                                    </span>
                                    <h3 class="rf-task-library__detail-title">{{ selectedDriver.title }}</h3>
                                    <p class="rf-task-library__detail-desc">{{ selectedDriver.description }}</p>

                                    <div class="rf-task-library__detail-section">
                                        <h4>{{ copy.reservation_flow_task_library_how_it_works }}</h4>
                                        <p>{{ selectedDriver.howItWorks || selectedDriver.description }}</p>
                                    </div>

                                    <div v-if="selectedDriver.useCase" class="rf-task-library__detail-section">
                                        <h4>{{ copy.reservation_flow_task_library_use_case }}</h4>
                                        <p>{{ selectedDriver.useCase }}</p>
                                    </div>

                                    <div class="rf-task-library__detail-section">
                                        <h4>{{ copy.reservation_flow_task_library_config_required }}</h4>
                                        <p>
                                            {{
                                                selectedDriver.configRequired
                                                    ? copy.reservation_flow_task_library_config_after_adding
                                                    : copy.reservation_flow_task_library_no_config
                                            }}
                                        </p>
                                    </div>

                                    <div class="rf-task-library__detail-callout">
                                        <i class="ki-filled ki-information-2" aria-hidden="true" />
                                        <p>
                                            {{
                                                selectedDriver.configRequired
                                                    ? copy.reservation_flow_task_library_config_next_step
                                                    : copy.reservation_flow_task_library_immediate_add
                                            }}
                                        </p>
                                    </div>
                                </template>

                                <div v-else class="rf-task-library__detail-empty">
                                    <p>{{ copy.reservation_flow_task_library_select_task }}</p>
                                </div>
                            </aside>
                        </div>

                        <div v-else class="rf-task-library__config">
                            <div class="rf-task-library__config-head">
                                <div class="rf-task-library__config-meta">
                                    <span class="rf-task-library__config-icon" aria-hidden="true"><i :class="`ki-filled ${(selectedDriver ?? editTask)?.icon ?? 'ki-setting-2'}`" /></span>
                                    <div class="rf-task-library__config-copy">
                                    <span
                                        v-if="selectedDriver || editTask"
                                        class="rf-task-library__detail-badge"
                                        :class="badgeClass((selectedDriver ?? editTask)?.category ?? (selectedDriver ?? editTask)?.type)"
                                    >
                                        {{ (selectedDriver ?? editTask)?.badgeLabel ?? copy[`reservation_flow_task_type_${(selectedDriver ?? editTask)?.type}`] }}
                                    </span>
                                    <h3>{{ (selectedDriver ?? editTask)?.title }}</h3>
                                    <p>{{ (selectedDriver ?? editTask)?.description }}</p>
                                    <small class="rf-task-library__config-route"><i class="ki-filled ki-arrow-right-left" aria-hidden="true" />{{ transitionRoute || transitionLabel }}</small>
                                    </div>
                                </div>
                            </div>

                            <div class="rf-task-library__config-body">
                                <template v-if="Object.keys(selectedDriver?.fields ?? {}).length">
                                    <label v-for="(field, key) in selectedDriver.fields" :key="key" class="rf-field">
                                        <span class="rf-field__label">{{ field.label }}<span v-if="field.required" aria-hidden="true"> *</span></span>
                                        <template v-if="field.type === 'multiselect'">
                                            <select :ref="(element) => setMultiOptionSelect(key, element)" v-model="multiSelections[key]"
                                                class="kt-input rf-field__input rf-task-role-picker" :data-placeholder="copy.choose"
                                                :disabled="submitting || saving" @change="addMultiOption(key)">
                                                <option value=""></option>
                                                <option v-for="[value, label] in availableMultiOptions(key, field)" :key="value" :value="value">{{ label }}</option>
                                            </select>
                                            <ul v-if="selectedMultiOptions(key, field).length" class="rf-task-role-list" :aria-label="field.label">
                                                <li v-for="[value, label] in selectedMultiOptions(key, field)" :key="value" class="rf-task-role-item">
                                                    <span class="rf-task-role-item__icon"><i class="ki-filled ki-shield-tick" aria-hidden="true" /></span>
                                                    <span class="rf-task-role-item__name">{{ label }}</span>
                                                    <button type="button" class="rf-task-role-item__remove" :disabled="submitting || saving"
                                                        :aria-label="copy.state_view_role_remove.replace(':role', label)"
                                                        @click="removeMultiOption(key, value)">
                                                        <i class="ki-filled ki-cross" aria-hidden="true" />
                                                    </button>
                                                </li>
                                            </ul>
                                        </template>
                                        <select v-else-if="field.options" v-model="configDraft[key]" class="kt-input rf-field__input" :disabled="submitting || saving">
                                            <option value="" disabled>{{ copy.choose }}</option>
                                            <option v-for="(label, value) in field.options" :key="value" :value="value">{{ label }}</option>
                                        </select>
                                        <textarea v-else-if="field.type === 'textarea'" v-model="configDraft[key]" class="kt-input rf-field__input" rows="3" :disabled="submitting || saving" />
                                        <input v-else v-model="configDraft[key]" :type="field.type ?? 'text'" :min="field.min" :max="field.max" class="kt-input rf-field__input" :disabled="submitting || saving" />
                                        <small v-if="field.help" class="rf-field__help">{{ field.help }}</small>
                                    </label>
                                </template>
                                <p v-else class="rf-task-library__config-empty">
                                    {{ copy.reservation_flow_task_library_no_config }}
                                </p>
                                <p v-if="configError" class="rf-field__error">{{ configError }}</p>
                            </div>
                        </div>

                        <footer class="rf-task-library__footer">
                            <p class="rf-task-library__footer-route"><i class="ki-filled ki-arrow-right-left" aria-hidden="true" /><span>{{ copy.reservation_flow_task_library_adding_to }} <strong>{{ transitionRoute || transitionLabel }}</strong></span></p>
                            <div class="rf-task-library__footer-actions">
                                <button
                                    v-if="step === 'config' && !isEditMode"
                                    type="button"
                                    class="rf-btn rf-btn--ghost"
                                    :disabled="submitting || saving"
                                    @click="backToLibrary"
                                >
                                    {{ copy.reservation_flow_back_step }}
                                </button>
                                <button
                                    ref="addTriggerRef"
                                    type="button"
                                    class="rf-btn rf-btn--primary"
                                    :disabled="!canSubmit"
                                    @click="submit"
                                >
                                    <i class="ki-filled ki-check" aria-hidden="true" />
                                    {{ primaryActionLabel }}
                                </button>
                            </div>
                        </footer>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.rf-task-library-backdrop {
    position: fixed;
    inset: 0;
    z-index: 430;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(15, 23, 42, 0.48);
    backdrop-filter: blur(2px);
}

.rf-task-library {
    --task-modal-bg: #FFFFFF;
    --task-modal-surface: #F8FAFC;
    --task-modal-text: #0F172A;
    --task-modal-secondary: #64748B;
    --task-modal-muted: #94A3B8;
    --task-modal-border: #E2E8F0;
    --task-modal-border-strong: #CBD5E1;
    --task-modal-primary: #2eae98;
    --task-modal-indigo: #716BFF;
    --task-modal-indigo-soft: #F0EEFF;

    display: flex;
    flex-direction: column;
    width: min(1080px, calc(100vw - 40px));
    height: min(82vh, calc(100dvh - 40px));
    max-height: calc(100dvh - 40px);
    border: 1px solid var(--task-modal-border);
    border-radius: 20px;
    background: var(--task-modal-bg);
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.24);
    overflow: hidden;
}

.rf-task-library__header {
    position: relative;
    flex: 0 0 auto;
    display:flex;
    align-items:center;
    min-height:105px;
    padding:20px 30px;
    border-bottom: 1px solid var(--task-modal-border);
}

.rf-task-library__header-main {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding-inline-end: 2.5rem;
}

.rf-task-library__icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    border-radius: 14px;
    border: 1px solid #E0E7FF;
    background: var(--task-modal-indigo-soft);
    color: var(--task-modal-indigo);
    font-size: 1.15rem;
    flex-shrink: 0;
}

.rf-task-library__eyebrow {
    margin: 0;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--task-modal-indigo);
}

.rf-task-library__title {
    margin: 0.2rem 0 0;
    font-size: 1.65rem;
    font-weight: 700;
    color: var(--task-modal-text);
    line-height: 1.2;
}

.rf-task-library__desc {
    margin: 0.45rem 0 0;
    font-size: 0.875rem;
    color: var(--task-modal-secondary);
    line-height: 1.5;
    max-width: 46rem;
}

.rf-task-library__transition {
    margin: 0.55rem 0 0;
    font-size: 0.8125rem;
    color: var(--task-modal-secondary);
}

.rf-task-library__transition-label {
    margin-inline-end: 0.35rem;
    color: var(--task-modal-muted);
}

.rf-task-library__transition-route {
    display: inline-block;
    margin-inline-start: 0.45rem;
    color: var(--task-modal-muted);
}

.rf-task-library__close {
    position: absolute;
    top: 24px;
    inset-inline-end: 24px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.25rem;
    height: 2.25rem;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: var(--task-modal-secondary);
    cursor: pointer;
}

.rf-task-library__close:hover,
.rf-task-library__close:focus-visible {
    background: #F1F5F9;
    outline: none;
    box-shadow: 0 0 0 2px rgba(20, 110, 245, 0.25);
}

.rf-task-library__search-wrap {
    position:absolute;
    left:50%;
    top:50%;
    transform:translate(-50%,-50%);
    width:min(480px,44%);
    margin:0;
}

.rf-task-library__search-label {
    position: absolute;
    width: 1px;
    height: 1px;
    overflow: hidden;
    clip: rect(0 0 0 0);
}

.rf-task-library__search {
    position: relative;
}

.rf-task-library__search-icon {
    position: absolute;
    inset-inline-start: 0.85rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--task-modal-muted);
    pointer-events: none;
}

.rf-task-library__search-input {
    width: 100%;
    height: 2.65rem;
    padding-inline: 2.35rem 2.35rem;
    border: 1px solid var(--task-modal-border-strong);
    border-radius: 11px;
    background: #FFFFFF;
    font-size: 0.875rem;
    color: var(--task-modal-text);
}

.rf-task-library__search-input:focus {
    outline: none;
    border-color: var(--task-modal-primary);
    box-shadow: 0 0 0 3px rgba(20, 110, 245, 0.14);
}

.rf-task-library__search-clear {
    position: absolute;
    inset-inline-end: 0.45rem;
    top: 50%;
    transform: translateY(-50%);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.75rem;
    height: 1.75rem;
    border: 0;
    border-radius: 9999px;
    background: transparent;
    color: var(--task-modal-muted);
    cursor: pointer;
}

.rf-task-library__body {
    flex: 1;
    min-height: 0;
    display: grid;
    grid-template-columns: 165px minmax(300px, 1fr) 280px;
    overflow: hidden;
}

.rf-task-library__categories {
    padding: 16px 14px;
    border-inline-end: 1px solid var(--task-modal-border);
    overflow-y: auto;
}

.rf-task-library__category {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    width: 100%;
    min-height: 44px;
    padding: 0.55rem 0.7rem;
    margin-bottom: 0.25rem;
    border: 0;
    border-radius: 10px;
    background: transparent;
    color: #334155;
    font-size: 0.8125rem;
    font-weight: 600;
    text-align: start;
    cursor: pointer;
}

.rf-task-library__category.is-active {
    background: var(--task-modal-indigo-soft);
    color: var(--task-modal-indigo);
    box-shadow: inset 3px 0 0 var(--task-modal-indigo);
}

[dir='rtl'] .rf-task-library__category.is-active {
    box-shadow: inset -3px 0 0 var(--task-modal-indigo);
}

.rf-task-library__category:hover,
.rf-task-library__category:focus-visible {
    background: #F8FAFC;
    outline: none;
}

.rf-task-library__category-count {
    margin-inline-start: auto;
    min-width: 1.2rem;
    padding: 0 0.3rem;
    border-radius: 9999px;
    background: #EEF2FF;
    font-size: 0.625rem;
    font-weight: 700;
    text-align: center;
}

.rf-task-library__list-pane {
    min-height: 0;
    padding: 16px 18px;
    border-inline-end: 1px solid var(--task-modal-border);
    overflow-y: auto;
}

.rf-task-library__list {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.rf-task-library__card {
    display: grid;
    grid-template-columns: auto 1fr auto auto;
    align-items: center;
    gap: 10px;
    width: 100%;
    min-height: 66px;
    padding: 10px 12px;
    border: 1px solid #E5E7EB;
    border-radius: 12px;
    background: #FFFFFF;
    text-align: start;
    cursor: pointer;
    transition: border-color 0.15s ease, background 0.15s ease, box-shadow 0.15s ease;
}

.rf-task-library__card:hover {
    border-color: #CBD5E1;
    background: #FCFDFF;
}

.rf-task-library__card.is-selected,
.rf-task-library__card.is-highlighted {
    border-color: var(--task-modal-indigo);
    background: var(--task-modal-indigo-soft);
    box-shadow: 0 0 0 3px rgba(113, 107, 255, 0.12);
}

.rf-task-library__card-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    border-radius: 11px;
    font-size: 1rem;
}

.rf-task-library__card-body {
    min-width: 0;
}

.rf-task-library__card-title {
    display: block;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #172033;
}

.rf-task-library__card-desc {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-top: 0.15rem;
    font-size: 0.8125rem;
    color: var(--task-modal-secondary);
    line-height: 1.45;
}

.rf-task-library__card-badge,
.rf-task-library__detail-badge {
    display: inline-flex;
    padding: 0.15rem 0.45rem;
    border-radius: 9999px;
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
}

.is-action {
    color: rgb(29 78 216);
    background: rgb(234 243 255);
}

.is-validation {
    color: rgb(22 163 106);
    background: rgb(232 248 240);
}

.is-restriction {
    color: rgb(126 58 180);
    background: rgb(245 236 255);
}

.rf-task-library__card-chevron {
    color: var(--task-modal-muted);
    font-size: 0.75rem;
}

[dir='rtl'] .rf-task-library__card-chevron {
    transform: scaleX(-1);
}

.rf-task-library__empty,
.rf-task-library__detail-empty {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    min-height: 220px;
    color: var(--task-modal-secondary);
}

.rf-task-library__empty h3 {
    margin: 0;
    font-size: 1rem;
    color: var(--task-modal-text);
}

.rf-task-library__empty p {
    margin: 0.35rem 0 0.85rem;
    font-size: 0.8125rem;
}

.rf-task-library__detail {
    min-height: 0;
    padding: 18px;
    overflow-y: auto;
    background: linear-gradient(180deg, #FAFBFF 0%, #F8FAFC 100%);
}

.rf-task-library__detail-title {
    margin: 0.55rem 0 0;
    font-size: 1.35rem;
    font-weight: 700;
    color: var(--task-modal-text);
}

.rf-task-library__detail-desc {
    margin: 0.4rem 0 0;
    font-size: 0.875rem;
    color: var(--task-modal-secondary);
    line-height: 1.5;
}

.rf-task-library__detail-section {
    margin-top: 1rem;
    padding-top: 1rem;
    border-top: 1px solid var(--task-modal-border);
}

.rf-task-library__detail-section h4 {
    margin: 0;
    font-size: 0.75rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    color: var(--task-modal-muted);
}

.rf-task-library__detail-section p {
    margin: 0.4rem 0 0;
    font-size: 0.8125rem;
    color: #334155;
    line-height: 1.55;
}

.rf-task-library__detail-callout {
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

.rf-task-library__config {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.rf-task-library__config-head {
    padding:22px 28px;
    border-bottom:1px solid var(--task-modal-border);
    background:var(--task-modal-surface);
}

.rf-task-library__config-meta { display:flex; align-items:center; gap:15px; max-width:880px; margin:auto; }
.rf-task-library__config-copy { min-width:0; }
.rf-task-library__config-icon { display:inline-flex; align-items:center; justify-content:center; flex:0 0 58px; height:58px; border:1px solid color-mix(in srgb,var(--task-modal-primary) 30%,var(--task-modal-border)); border-radius:15px; background:color-mix(in srgb,var(--task-modal-primary) 11%,var(--task-modal-bg)); color:var(--task-modal-primary); font-size:25px; }
.rf-task-library__config-route { display:inline-flex; align-items:center; gap:6px; margin-top:9px; padding:5px 9px; border:1px solid var(--task-modal-border); border-radius:999px; background:var(--task-modal-bg); color:var(--task-modal-secondary); font-size:11px; }
.rf-task-library__config-route i { color:var(--task-modal-primary); }

.rf-task-library__config-meta h3 {
    margin: 0.45rem 0 0;
    font-size: 1.2rem;
    color: var(--task-modal-text);
}

.rf-task-library__config-meta p {
    margin: 0.35rem 0 0;
    font-size: 0.875rem;
    color: var(--task-modal-secondary);
}

.rf-task-library__config-body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(min(290px,100%),1fr));
    align-content:start;
    gap:15px;
    padding:25px max(28px,calc((100% - 880px)/2));
}

.rf-task-library__config-body .rf-field { padding:17px; border:1px solid var(--task-modal-border); border-radius:12px; background:var(--task-modal-bg); box-shadow:0 3px 12px rgb(17 45 50 / 3%); }
.rf-task-library__config-body .rf-field__label { margin-bottom:9px; color:var(--task-modal-text); font-size:13px; font-weight:700; }
.rf-task-library__config-body .rf-field__input { padding-inline:12px; }
.rf-task-library__config-body .rf-field__input:focus { outline:none; border-color:var(--task-modal-primary); box-shadow:0 0 0 3px color-mix(in srgb,var(--task-modal-primary) 15%,transparent); }
.rf-task-library__config-body .rf-task-role-picker + .select2-container { display:block; width:100%!important; min-width:0; }
.rf-task-library__config-body .rf-task-role-picker + .select2-container .select2-selection--single { display:flex; height:42px; align-items:center; border:1px solid var(--task-modal-border); border-radius:9px; background:var(--task-modal-bg); color:var(--task-modal-text); }
.rf-task-library__config-body .rf-task-role-picker + .select2-container--focus .select2-selection--single { border-color:var(--task-modal-primary); box-shadow:0 0 0 3px color-mix(in srgb,var(--task-modal-primary) 15%,transparent); }
.rf-task-library__config-body .rf-task-role-picker + .select2-container .select2-selection__rendered { width:100%; padding-inline:12px 30px; color:var(--task-modal-text); line-height:40px; }
.rf-task-library__config-body .rf-task-role-picker + .select2-container .select2-selection__placeholder { color:var(--task-modal-secondary); }
.rf-task-role-list { display:grid; grid-template-columns:repeat(auto-fit,minmax(min(100%,180px),1fr)); gap:8px; margin:9px 0 0; padding:0; list-style:none; }
.rf-task-role-item { display:flex; min-width:0; align-items:center; gap:8px; padding:7px 9px; border:1px solid color-mix(in srgb,var(--task-modal-primary) 22%,var(--task-modal-border)); border-radius:9px; background:color-mix(in srgb,var(--task-modal-primary) 6%,var(--task-modal-bg)); color:var(--task-modal-text); }
.rf-task-role-item__icon { display:grid; width:27px; height:27px; flex:0 0 27px; place-items:center; border-radius:7px; background:color-mix(in srgb,var(--task-modal-primary) 14%,var(--task-modal-bg)); color:var(--task-modal-primary); }
.rf-task-role-item__name { min-width:0; flex:1 1 auto; overflow-wrap:anywhere; font-size:12px; font-weight:600; }
.rf-task-role-item__remove { display:grid; width:27px; height:27px; flex:0 0 27px; place-items:center; border:0; border-radius:7px; background:transparent; color:var(--task-modal-secondary); cursor:pointer; }
.rf-task-role-item__remove:hover:not(:disabled) { background:#fee2e2; color:#b42318; }
.rf-task-role-item__remove:disabled { cursor:not-allowed; opacity:.55; }

.rf-task-library__config-empty {
    font-size: 0.875rem;
    color: var(--task-modal-secondary);
}

.rf-task-library__footer {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    min-height: 72px;
    padding: 18px 28px;
    border-top: 1px solid var(--task-modal-border);
    background: var(--task-modal-bg);
}
.rf-task-library__footer-route { display:flex; align-items:center; gap:8px; min-width:0; margin:0; color:var(--task-modal-secondary); font-size:12px; line-height:1.5; }.rf-task-library__footer-route i { color:#2eae98; }.rf-task-library__footer-route strong { color:var(--task-modal-text); }

.rf-task-library__footer-actions {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.rf-btn {
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

.rf-btn--primary {
    background: var(--task-modal-primary);
    color: #FFFFFF;
}

.rf-btn--secondary {
    background: #FFFFFF;
    border-color: var(--task-modal-border-strong);
    color: #334155;
}

.rf-btn--ghost {
    background: transparent;
    color: #334155;
}

.rf-btn--sm {
    height: 2rem;
    padding: 0 0.75rem;
    font-size: 0.75rem;
}

.rf-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.rf-field {
    display: block;
}

.rf-field__label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.75rem;
    color: var(--task-modal-secondary);
}

.rf-field__input {
    width: 100%;
    min-height:42px;
    border:1px solid var(--task-modal-border-strong);
    border-radius:9px;
    background:var(--task-modal-bg);
    color:var(--task-modal-text);
}
.rf-field__help { display:block; margin-top:5px; color:var(--task-modal-secondary); font-size:12px; line-height:1.6; }
:global(html[data-theme="dark"]) .rf-task-library { --task-modal-bg:var(--surface); --task-modal-surface:var(--surface-soft); --task-modal-text:var(--text); --task-modal-secondary:var(--text-soft); --task-modal-muted:var(--muted); --task-modal-border:var(--border); --task-modal-border-strong:var(--border-strong); --task-modal-indigo-soft:var(--primary-soft); --task-modal-indigo:var(--primary); color-scheme:dark; }
:global(html[data-theme="dark"]) .rf-task-library__detail-section p { color:var(--task-modal-text); }
:global(html[data-theme="dark"]) .rf-task-library__detail-callout { background:var(--task-modal-surface); border-color:var(--task-modal-border); color:var(--task-modal-text); }
:global(html[data-theme="dark"]) .rf-task-library__footer,.rf-task-library__config-body { background:var(--task-modal-bg); }

.rf-field__error {
    margin-top: 0.65rem;
    font-size: 0.8125rem;
    color: #DC2626;
}

@media (max-width: 1024px) {
    .rf-task-library__body {
        grid-template-columns: 170px minmax(260px, 1fr) 300px;
    }
}

@media (max-width: 860px) {
    .rf-task-library__header { display:block; min-height:0; padding:18px 20px; }
    .rf-task-library__search-wrap { position:static; transform:none; width:100%; margin-top:15px; }
    .rf-task-library__config-body { padding:18px 20px; }
    .rf-task-library-backdrop {
        padding: 0;
        align-items: stretch;
    }

    .rf-task-library {
        width: 100%;
        height: 100dvh;
        max-height: 100dvh;
        border-radius: 0;
    }

    .rf-task-library__body {
        grid-template-columns: 1fr;
        grid-template-rows: auto 1fr auto;
    }

    .rf-task-library__categories {
        display: flex;
        gap: 0.35rem;
        overflow-x: auto;
        border-inline-end: 0;
        border-bottom: 1px solid var(--task-modal-border);
    }

    .rf-task-library__category {
        width: auto;
        flex: 0 0 auto;
        margin-bottom: 0;
        white-space: nowrap;
    }

    .rf-task-library__category.is-active {
        box-shadow: inset 0 -3px 0 var(--task-modal-indigo);
    }

    .rf-task-library__list-pane {
        border-inline-end: 0;
    }

    .rf-task-library__detail {
        border-top: 1px solid var(--task-modal-border);
        max-height: 34vh;
    }
}
</style>
