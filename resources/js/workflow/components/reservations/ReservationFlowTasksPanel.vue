<script setup>

import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';

import { useReservationFlowConfirmInject } from '../../composables/useReservationFlowConfirmInject.js';



import ReservationFlowTaskLibraryModal from './flow/ReservationFlowTaskLibraryModal.vue';



const props = defineProps({

    copy: { type: Object, required: true },

    tasks: { type: Array, default: () => [] },

    availableDrivers: { type: Array, default: () => [] },

    transitionLabel: { type: String, default: '' },

    transitionRoute: { type: String, default: '' },

    editable: { type: Boolean, default: false },

    saving: { type: Boolean, default: false },

});



const emit = defineEmits(['add-task', 'update-task', 'delete-task']);

const requestConfirm = useReservationFlowConfirmInject();



const activeTab = ref('all');

const libraryOpen = ref(false);

const libraryMode = ref('add');

const editingTask = ref(null);

const addTaskButtonRef = ref(null);

const listRef = ref(null);

const highlightTaskId = ref('');
const taskMenu = ref(null);

function openTaskMenu(event, task) {
    if (!props.editable) return;
    taskMenu.value = { task, x: Math.min(event.clientX, window.innerWidth - 190), y: Math.min(event.clientY, window.innerHeight - 100) };
}
function closeTaskMenu(event) {
    if (!event?.target?.closest?.('.ww-flow-tasks__context')) taskMenu.value = null;
}
function onTaskMenuKeydown(event) {
    if (event.key === 'Escape') taskMenu.value = null;
}
onMounted(() => {
    document.addEventListener('pointerdown', closeTaskMenu, true);
    document.addEventListener('keydown', onTaskMenuKeydown);
});
onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', closeTaskMenu, true);
    document.removeEventListener('keydown', onTaskMenuKeydown);
});



const typeLabels = computed(() => ({

    action: props.copy.reservation_flow_task_type_action,

    validation: props.copy.reservation_flow_task_type_validation,

    restriction: props.copy.reservation_flow_task_type_restriction,

}));



const filteredTasks = computed(() => {

    if (activeTab.value === 'all') {

        return props.tasks;

    }



    return props.tasks.filter((task) => task.type === activeTab.value);

});



const tabs = computed(() => [

    { id: 'all', label: props.copy.reservation_flow_tasks_tab_all, count: props.tasks.length },

    { id: 'action', label: typeLabels.value.action, count: props.tasks.filter((t) => t.type === 'action').length },

    { id: 'validation', label: typeLabels.value.validation, count: props.tasks.filter((t) => t.type === 'validation').length },

    { id: 'restriction', label: typeLabels.value.restriction, count: props.tasks.filter((t) => t.type === 'restriction').length },

]);



const hasDrivers = computed(() => (

    props.availableDrivers.some((group) => (group.tasks ?? []).length > 0)

));



function typeBadgeClass(type) {

    return {

        action: 'is-action',

        validation: 'is-validation',

        restriction: 'is-restriction',

    }[type] ?? 'is-action';

}



function toggleTaskStatus(task) {

    emit('update-task', {

        taskId: task.id,

        status: task.status === false,

    });

}



async function deleteTask(task) {
    const ok = await requestConfirm({
        title: props.copy.reservation_flow_delete_task,
        message: props.copy.reservation_flow_delete_task_confirm,
        confirmLabel: props.copy.reservation_flow_delete_task,
        variant: 'danger',
    });

    if (!ok) {
        return;
    }

    emit('delete-task', { taskId: task.id });
}



function openAddLibrary() {

    libraryMode.value = 'add';

    editingTask.value = null;

    libraryOpen.value = true;

}



function openEditLibrary(task) {

    libraryMode.value = 'edit';

    editingTask.value = task;

    libraryOpen.value = true;

}



function closeLibrary() {

    if (props.saving) {

        return;

    }



    libraryOpen.value = false;

    editingTask.value = null;



    nextTick(() => {

        addTaskButtonRef.value?.focus?.();

    });

}



async function onLibraryAddTask(payload) {
    activeTab.value = 'all';
    emit('add-task', payload);
    libraryOpen.value = false;

    await nextTick();
    await nextTick();

    const lastTask = props.tasks.at(-1);

    if (lastTask?.id) {
        highlightTaskId.value = lastTask.id;
    }

    const cards = listRef.value?.querySelectorAll('.ww-flow-tasks__card');

    if (cards?.length) {
        cards[cards.length - 1]?.scrollIntoView?.({ block: 'nearest', behavior: 'smooth' });
    }

    addTaskButtonRef.value?.focus?.();
}



function onLibraryUpdateTask(payload) {

    emit('update-task', payload);

    libraryOpen.value = false;

    editingTask.value = null;

    addTaskButtonRef.value?.focus?.();

}



</script>



<template>

    <div class="ww-flow-tasks">

        <div class="ww-flow-tasks__head">

            <div>

                <h4 class="ww-flow-tasks__title">{{ copy.reservation_flow_tasks_heading }}</h4>

                <p class="ww-flow-tasks__hint">{{ copy.reservation_flow_tasks_hint }}</p>

            </div>

            <button

                v-if="editable"

                ref="addTaskButtonRef"

                type="button"

                class="ww-flow-tasks__add"

                :disabled="saving || !hasDrivers"

                @click="openAddLibrary"

            >

                <i class="ki-filled ki-plus" />

                {{ copy.reservation_flow_add_task }}

            </button>

        </div>



        <div class="ww-flow-tasks__tabs">

            <button

                v-for="tab in tabs"

                :key="tab.id"

                type="button"

                class="ww-flow-tasks__tab"

                :class="{ 'is-active': activeTab === tab.id }"

                @click="activeTab = tab.id"

            >
                {{ tab.label }}

                <span class="ww-flow-tasks__tab-count">{{ tab.count }}</span>

            </button>

        </div>



        <div v-if="filteredTasks.length" ref="listRef" class="ww-flow-tasks__list">

            <article

                v-for="task in filteredTasks"

                :key="task.id"

                class="ww-flow-tasks__card"

                :class="{ 'is-disabled': task.status === false, 'is-highlighted': highlightTaskId === task.id }"
                @contextmenu.prevent.stop="openTaskMenu($event, task)"

            >
                <div class="ww-flow-tasks__card-top">
                    <span class="ww-flow-tasks__type" :class="typeBadgeClass(task.type)"><i :class="`ki-filled ${task.type === 'action' ? 'ki-flash' : task.type === 'validation' ? 'ki-shield-tick' : 'ki-lock'}`" aria-hidden="true" />{{ typeLabels[task.type] ?? task.type }}</span>
                    <label class="ww-flow-tasks__toggle">
                        <input
                            type="checkbox"
                            role="switch"
                            :aria-label="copy.reservation_flow_task_enabled"
                            :checked="task.status !== false"
                            :disabled="!editable || saving"
                            @change="toggleTaskStatus(task)"
                        />
                        <span class="ww-flow-tasks__toggle-track" aria-hidden="true" />
                    </label>
                </div>
                <h5 class="ww-flow-tasks__card-title">{{ task.title }}</h5>
                <p v-if="task.description" class="ww-flow-tasks__card-description">{{ task.description }}</p>

                <div v-if="editable" class="ww-flow-tasks__card-actions">

                    <button type="button" class="ww-flow-tasks__edit" :disabled="saving" @click="openEditLibrary(task)">
                        <i class="ki-filled ki-notepad-edit" aria-hidden="true" />{{ copy.reservation_flow_edit_task }}

                    </button>

                    <button type="button" class="ww-flow-tasks__delete" :disabled="saving" :aria-label="copy.reservation_flow_delete_task" :title="copy.reservation_flow_delete_task" @click="deleteTask(task)">
                        <i class="ki-filled ki-trash" aria-hidden="true" />

                    </button>

                </div>

            </article>

        </div>



        <div v-else class="ww-flow-tasks__empty"><i class="ki-filled ki-element-11" aria-hidden="true" /><p>{{ copy.reservation_flow_empty_tasks }}</p></div>



        <ReservationFlowTaskLibraryModal

            :copy="copy"

            :open="libraryOpen"

            :available-drivers="availableDrivers"

            :existing-tasks="tasks"

            :transition-label="transitionLabel"

            :transition-route="transitionRoute"

            :mode="libraryMode"

            :edit-task="editingTask"

            :saving="saving"

            @close="closeLibrary"

            @add-task="onLibraryAddTask"

            @update-task="onLibraryUpdateTask"

        />
        <Teleport to="body"><div v-if="taskMenu" class="ww-flow-tasks__context" :style="{ left: `${taskMenu.x}px`, top: `${taskMenu.y}px` }" role="menu"><button type="button" role="menuitem" @click="openEditLibrary(taskMenu.task); taskMenu = null"><i class="ki-filled ki-notepad-edit" aria-hidden="true" />{{ copy.reservation_flow_edit_task }}</button><button type="button" role="menuitem" @click="deleteTask(taskMenu.task); taskMenu = null"><i class="ki-filled ki-trash" aria-hidden="true" />{{ copy.reservation_flow_delete_task }}</button></div></Teleport>

    </div>

</template>



<style scoped>

.ww-flow-tasks__head {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 0.75rem;

}



.ww-flow-tasks__title {

    font-size: 0.75rem;

    font-weight: 700;

    letter-spacing: 0.05em;

    text-transform: uppercase;

    color: var(--secondary-foreground);

}



.ww-flow-tasks__hint {

    margin-top: 0.25rem;

    font-size: 0.75rem;

    color: var(--secondary-foreground);

}



.ww-flow-tasks__tabs {

    display: flex;

    flex-wrap: wrap;

    gap: 0.35rem;

    margin-top: 0.75rem;

}



.ww-flow-tasks__tab {

    display: inline-flex;

    align-items: center;

    gap: 0.35rem;

    padding: 0.3rem 0.55rem;

    border-radius: 9999px;

    border: 1px solid var(--border);

    background: color-mix(in srgb, var(--background) 88%, transparent);

    font-size: 0.6875rem;

    font-weight: 600;

    color: var(--secondary-foreground);

    transition: all 0.15s ease;

}



.ww-flow-tasks__tab.is-active {

    border-color: color-mix(in srgb, var(--primary) 45%, var(--border));

    color: var(--primary);

    background: color-mix(in srgb, var(--primary) 10%, var(--background));

}



.ww-flow-tasks__tab-count {

    min-width: 1.1rem;

    text-align: center;

    padding: 0 0.2rem;

    border-radius: 9999px;

    background: color-mix(in srgb, var(--accent) 65%, transparent);

    font-size: 0.625rem;

}



.ww-flow-tasks__list {

    display: flex;

    flex-direction: column;

    gap: 0.55rem;

    margin-top: 0.75rem;

}



.ww-flow-tasks__card {

    border: 1px solid #E2E7EF;

    border-radius: 9px;

    background: #FFFFFF;

    padding: 11px 12px;

    box-shadow: none;

    transition: border-color 0.15s ease, box-shadow 0.15s ease;

}



.ww-flow-tasks__card.is-highlighted {
    border-color: var(--primary,#2eae98);
    box-shadow: 0 0 0 2px color-mix(in srgb,var(--primary,#2eae98) 14%,transparent);

}



.ww-flow-tasks__card:hover {

    border-color: #CBD5E1;

}



.ww-flow-tasks__card.is-disabled {

    opacity: 0.62;

}



.ww-flow-tasks__card-top {

    display: flex;

    align-items: flex-start;

    justify-content: space-between;

    gap: 0.75rem;

}



.ww-flow-tasks__type {

    display: inline-flex;

    padding: 0.1rem 0.45rem;

    border-radius: 9999px;

    font-size: 0.625rem;

    font-weight: 700;

    letter-spacing: 0.03em;

    text-transform: uppercase;

}



.ww-flow-tasks__type.is-action {

    color: rgb(29 78 216);

    background: rgb(219 234 254 / 0.8);

}



.ww-flow-tasks__type.is-validation {

    color: rgb(22 163 106);

    background: rgb(232 248 240 / 0.9);

}



.ww-flow-tasks__type.is-restriction {

    color: rgb(126 58 180);

    background: rgb(245 236 255 / 0.9);

}



.ww-flow-tasks__config {

    margin-top: 0.65rem;

    padding-top: 0.65rem;

    border-top: 1px dashed var(--border);

}



.ww-flow-tasks__card-actions {

    margin-top: 0.55rem;

    display: flex;

    justify-content: flex-end;

    gap: 0.4rem;

}



.ww-flow-tasks__empty {

    margin-top: 0.75rem;

    font-size: 0.8125rem;

    color: var(--secondary-foreground);

}



:global(html.dark) .ww-flow-tasks__card {

    background: linear-gradient(180deg, color-mix(in srgb, var(--background) 88%, rgb(15 23 42)), var(--background));

    box-shadow: 0 10px 22px rgb(2 6 23 / 0.35);

}
.ww-flow-tasks__head { align-items:center; }.ww-flow-tasks__title { color:var(--rf-text,var(--text)); font-size:13px; text-transform:none; letter-spacing:0; }.ww-flow-tasks__hint { line-height:1.6; }
.ww-flow-tasks__add { display:inline-flex; align-items:center; justify-content:center; gap:6px; flex:0 0 auto; min-height:33px; padding:0 10px; border:1px solid color-mix(in srgb,var(--primary,#2eae98) 50%,var(--rf-border,var(--border))); border-radius:8px; background:color-mix(in srgb,var(--primary,#2eae98) 10%,var(--rf-panel,var(--surface))); color:var(--primary,#2eae98); font-size:11px; font-weight:700; cursor:pointer; }.ww-flow-tasks__add:hover { background:var(--primary,#2eae98); color:#fff; }
.ww-flow-tasks__tabs { display:grid; grid-template-columns:repeat(4,minmax(0,1fr)); gap:4px; overflow:visible; padding:0 0 10px; border:0; border-bottom:1px solid var(--rf-border,var(--border)); border-radius:0; background:transparent; }
.ww-flow-tasks__tab { justify-content:center; gap:3px; min-width:0; padding:6px 2px; border:1px solid transparent; border-radius:7px; background:transparent; color:var(--rf-muted,var(--text-soft)); font-size:10px; white-space:nowrap; }.ww-flow-tasks__tab.is-active { color:var(--primary,#2eae98); border-color:color-mix(in srgb,var(--primary,#2eae98) 35%,var(--rf-border,var(--border))); background:color-mix(in srgb,var(--primary,#2eae98) 9%,var(--rf-panel,var(--surface))); box-shadow:none; }
.ww-flow-tasks__tab-count { min-width:17px; padding:1px 4px; background:var(--rf-canvas,var(--surface-soft)); }
.ww-flow-tasks__list { gap:9px; }.ww-flow-tasks__card { position:relative; border:1px solid var(--rf-border,var(--border)); border-radius:12px; background:var(--rf-panel,var(--surface)); padding:13px; box-shadow:0 2px 8px rgb(0 0 0 / 3%); }.ww-flow-tasks__card:hover { border-color:#2eae98; }
.ww-flow-tasks__card-top { align-items:center; }
.ww-flow-tasks__type { align-items:center; gap:4px; text-transform:none; letter-spacing:0; }
.ww-flow-tasks__type i { font-size:11px; }
.ww-flow-tasks__card-title { margin:10px 0 0; color:var(--rf-text,var(--text)); font-size:12px; font-weight:700; line-height:1.5; overflow-wrap:anywhere; }
.ww-flow-tasks__card-description { margin:4px 0 0; color:var(--rf-muted,var(--text-soft)); font-size:11px; line-height:1.55; }
.ww-flow-tasks__card-actions { margin-top:10px; padding:0; border:0; }
.ww-flow-tasks__card-actions button { display:inline-flex; align-items:center; justify-content:center; gap:5px; min-height:29px; padding:0 8px; border:1px solid var(--rf-border,var(--border)); border-radius:8px; background:var(--rf-panel,var(--surface)); color:var(--rf-text,var(--text)); font-size:11px; cursor:pointer; }
.ww-flow-tasks__card-actions button:hover { border-color:var(--primary,#2eae98); color:var(--primary,#2eae98); }
.ww-flow-tasks__card-actions .ww-flow-tasks__delete { width:29px; padding:0; color:#c55454; }
.ww-flow-tasks__toggle { position:relative; flex:0 0 36px; width:36px; height:20px; cursor:pointer; }
.ww-flow-tasks__toggle input { position:absolute; inset:0; z-index:1; width:100%; height:100%; margin:0; opacity:0; cursor:inherit; }
.ww-flow-tasks__toggle-track { display:block; width:36px; height:20px; border-radius:999px; background:var(--rf-border,var(--border)); transition:background .18s ease; }
.ww-flow-tasks__toggle-track::after { position:absolute; top:3px; inset-inline-start:3px; width:14px; height:14px; border-radius:50%; background:#fff; box-shadow:0 1px 3px rgb(0 0 0 / 18%); content:''; transition:inset-inline-start .18s ease; }
.ww-flow-tasks__toggle input:checked + .ww-flow-tasks__toggle-track { background:var(--primary,#2eae98); }
.ww-flow-tasks__toggle input:checked + .ww-flow-tasks__toggle-track::after { inset-inline-start:19px; }
.ww-flow-tasks__toggle input:focus-visible + .ww-flow-tasks__toggle-track { outline:2px solid var(--primary,#2eae98); outline-offset:3px; }
.ww-flow-tasks__toggle input:disabled { cursor:not-allowed; }
.ww-flow-tasks__toggle input:disabled + .ww-flow-tasks__toggle-track { opacity:.65; }
.ww-flow-tasks__empty { display:flex; flex-direction:column; align-items:center; justify-content:center; gap:8px; min-height:120px; padding:18px; border:1px dashed var(--rf-border,var(--border)); border-radius:12px; background:var(--rf-canvas,var(--surface-soft)); text-align:center; }.ww-flow-tasks__empty i { font-size:22px; color:#2eae98; }.ww-flow-tasks__empty p { margin:0; line-height:1.6; }
:global(html[data-theme=dark]) .ww-flow-tasks__card { background:var(--surface); border-color:var(--border); box-shadow:none; }
.ww-flow-tasks__context { position:fixed; z-index:475; width:180px; padding:5px; border:1px solid var(--border,#e4e8ee); border-radius:10px; background:var(--surface,#fff); color:var(--text,#1f2d48); box-shadow:0 12px 30px rgb(0 0 0 / 18%); }.ww-flow-tasks__context button { display:flex; align-items:center; gap:8px; width:100%; padding:9px; border:0; border-radius:7px; background:transparent; color:inherit; text-align:start; cursor:pointer; font-size:12px; }.ww-flow-tasks__context button:hover { background:var(--surface-soft,#f2f8f6); color:var(--primary,#2eae98); }

</style>
