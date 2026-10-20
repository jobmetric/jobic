<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    copy: { type: Object, required: true },
    open: { type: Boolean, default: false },
    anchor: { type: Object, default: null },
    mode: { type: String, default: 'state' },
    connectedCount: { type: Number, default: 0 },
});

const emit = defineEmits(['close', 'edit', 'add-transition', 'duplicate', 'delete']);

const menuRef = ref(null);
const style = ref({ top: '0px', left: '0px' });

const items = computed(() => {
    if (props.mode === 'start') {
        return [
            { id: 'add-transition', label: props.copy.reservation_flow_add_transition, icon: 'ki-arrow-right' },
        ];
    }

    return [
        { id: 'edit', label: props.copy.reservation_flow_edit_state, icon: 'ki-notepad-edit' },
        { id: 'add-transition', label: props.copy.reservation_flow_add_transition, icon: 'ki-arrow-right' },
        { id: 'duplicate', label: props.copy.reservation_flow_duplicate_state, icon: 'ki-copy' },
        { id: 'divider' },
        { id: 'delete', label: props.copy.reservation_flow_delete_state, icon: 'ki-trash', danger: true },
    ];
});

function positionMenu() {
    const anchor = props.anchor;
    const menu = menuRef.value;

    if (!anchor || !menu) {
        return;
    }

    const rect = anchor.getBoundingClientRect();
    const menuRect = menu.getBoundingClientRect();
    const margin = 8;
    let top = rect.bottom + margin;
    let left = rect.right - menuRect.width;

    if (top + menuRect.height > window.innerHeight - margin) {
        top = rect.top - menuRect.height - margin;
    }

    left = Math.max(margin, Math.min(left, window.innerWidth - menuRect.width - margin));
    top = Math.max(margin, Math.min(top, window.innerHeight - menuRect.height - margin));

    style.value = {
        top: `${top}px`,
        left: `${left}px`,
    };
}

function onAction(id) {
    if (id === 'divider') {
        return;
    }

    emit(id);
    emit('close');
}

function onDocumentPointerDown(event) {
    if (!props.open) {
        return;
    }

    const menu = menuRef.value;
    const anchor = props.anchor;

    if (menu?.contains(event.target) || anchor?.contains(event.target)) {
        return;
    }

    emit('close');
}

function onDocumentKeyDown(event) {
    if (props.open && event.key === 'Escape') {
        emit('close');
    }
}

watch(() => props.open, async (isOpen) => {
    if (!isOpen) {
        return;
    }

    await nextTick();
    positionMenu();
});

onMounted(() => {
    document.addEventListener('pointerdown', onDocumentPointerDown, true);
    document.addEventListener('keydown', onDocumentKeyDown);
    window.addEventListener('resize', positionMenu);
    window.addEventListener('scroll', positionMenu, true);
});

onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onDocumentPointerDown, true);
    document.removeEventListener('keydown', onDocumentKeyDown);
    window.removeEventListener('resize', positionMenu);
    window.removeEventListener('scroll', positionMenu, true);
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="open"
            ref="menuRef"
            class="rf-state-menu"
            role="menu"
            :style="style"
            @click.stop
        >
            <template v-for="item in items" :key="item.id">
                <div v-if="item.id === 'divider'" class="rf-state-menu__divider" role="separator" />
                <button
                    v-else
                    type="button"
                    class="rf-state-menu__item"
                    :class="{ 'is-danger': item.danger }"
                    role="menuitem"
                    @click="onAction(item.id)"
                >
                    <i :class="`ki-filled ${item.icon}`" aria-hidden="true" />
                    <span>{{ item.label }}</span>
                </button>
            </template>
        </div>
    </Teleport>
</template>

<style scoped>
.rf-state-menu {
    position: fixed;
    z-index: 460;
    width: min(210px, calc(100vw - 16px));
    padding: 0.35rem;
    border: 1px solid #E2E7EF;
    border-radius: 9px;
    background: #FFFFFF;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.14);
}

.rf-state-menu__item {
    display: flex;
    align-items: center;
    gap: 0.55rem;
    width: 100%;
    padding: 0.5rem 0.6rem;
    border: 0;
    border-radius: 7px;
    background: transparent;
    font-size: 0.8125rem;
    font-weight: 500;
    color: #334155;
    text-align: start;
    cursor: pointer;
}

.rf-state-menu__item:hover,
.rf-state-menu__item:focus-visible {
    background: #F8FAFC;
    outline: none;
}

.rf-state-menu__item.is-danger {
    color: #DC2626;
}

.rf-state-menu__item.is-danger:hover,
.rf-state-menu__item.is-danger:focus-visible {
    background: #FEF2F2;
}

.rf-state-menu__divider {
    height: 1px;
    margin: 0.25rem 0.35rem;
    background: #E7EAF0;
}
</style>
