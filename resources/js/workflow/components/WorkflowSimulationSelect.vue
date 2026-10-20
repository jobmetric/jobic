<script setup>
import { computed, nextTick, onBeforeUnmount, ref } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number], default: null },
    options: { type: Array, required: true },
    label: { type: String, required: true },
    disabled: { type: Boolean, default: false },
});
const emit = defineEmits(['update:modelValue']);
const trigger = ref(null);
const menu = ref(null);
const open = ref(false);
const bounds = ref({ left: 0, top: 0, width: 0, maxHeight: 260 });
const activeIndex = ref(0);
const selected = computed(() => props.options.find((option) => option.value === props.modelValue));
const direction = document.documentElement.dir || 'ltr';

function close() {
    open.value = false;
}

function position() {
    if (!trigger.value) return;
    const rect = trigger.value.getBoundingClientRect();
    const below = window.innerHeight - rect.bottom;
    const above = rect.top;
    const maxHeight = Math.min(260, Math.max(110, (below >= 150 ? below : above) - 12));
    const top = below >= 150 || below >= above ? rect.bottom + 5 : rect.top - maxHeight - 5;
    bounds.value = {left: rect.left, top, width: rect.width, maxHeight};
}

async function toggle() {
    if (props.disabled) return;
    if (open.value) return close();
    activeIndex.value = Math.max(0, props.options.findIndex((option) => option.value === props.modelValue));
    position();
    open.value = true;
    await nextTick();
    menu.value?.querySelector('[data-active="true"]')?.scrollIntoView({block: 'nearest'});
}

function choose(option) {
    emit('update:modelValue', option.value);
    close();
    trigger.value?.focus();
}

function onKeydown(event) {
    if (props.disabled) return;
    if (event.key === 'Escape') { if (open.value) { event.preventDefault(); event.stopPropagation(); close(); trigger.value?.focus(); } return; }
    if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
        event.preventDefault();
        if (!open.value) { toggle(); return; }
        activeIndex.value = (activeIndex.value + (event.key === 'ArrowDown' ? 1 : -1) + props.options.length) % props.options.length;
        nextTick(() => menu.value?.querySelector('[data-active="true"]')?.scrollIntoView({block: 'nearest'}));
    }
    if ((event.key === 'Enter' || event.key === ' ') && open.value) { event.preventDefault(); choose(props.options[activeIndex.value]); }
}

function onOutside(event) {
    if (open.value && !trigger.value?.contains(event.target) && !menu.value?.contains(event.target)) close();
}
function onScroll(event) {
    if (open.value && !menu.value?.contains(event.target)) close();
}
document.addEventListener('pointerdown', onOutside);
window.addEventListener('resize', close);
window.addEventListener('scroll', onScroll, true);
onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', onOutside);
    window.removeEventListener('resize', close);
    window.removeEventListener('scroll', onScroll, true);
});
</script>

<template>
    <div class="rf-sim-select">
        <button ref="trigger" type="button" class="rf-sim-select__trigger" :aria-label="label" :disabled="disabled"
            aria-haspopup="listbox" :aria-expanded="open" @click="toggle" @keydown="onKeydown">
            <span>{{ selected?.label ?? label }}</span><i class="ki-filled ki-down" aria-hidden="true" />
        </button>
        <Teleport to="body">
            <div v-if="open" ref="menu" class="rf-sim-select__menu" role="listbox" :aria-label="label"
                :dir="direction"
                :style="{left: `${bounds.left}px`, top: `${bounds.top}px`, width: `${bounds.width}px`, maxHeight: `${bounds.maxHeight}px`}">
                <button v-for="(option, index) in options" :key="String(option.value)" type="button" role="option"
                    class="rf-sim-select__option" :aria-selected="option.value === modelValue"
                    :data-active="index === activeIndex" @mouseenter="activeIndex = index" @click="choose(option)">
                    <span>{{ option.label }}</span><i v-if="option.value === modelValue" class="ki-filled ki-check" aria-hidden="true" />
                </button>
            </div>
        </Teleport>
    </div>
</template>

<style scoped>
.rf-sim-select { min-width:0; }
.rf-sim-select__trigger { display:flex; align-items:center; justify-content:space-between; gap:8px; width:100%; min-height:37px; padding:8px 10px; border:1px solid var(--rf-border); border-radius:9px; background:var(--rf-panel); color:var(--rf-text); font:inherit; font-size:12px; text-align:start; cursor:pointer; }
.rf-sim-select__trigger:focus-visible { outline:2px solid #2eae98; outline-offset:2px; }
.rf-sim-select__trigger:disabled { opacity:.65; cursor:not-allowed; }
.rf-sim-select__trigger i { color:var(--rf-muted); }
.rf-sim-select__menu { position:fixed; z-index:1400; overflow:auto; padding:5px; border:1px solid var(--border,#e4e8ee); border-radius:10px; background:var(--surface,#fff); color:var(--text,#1f2d48); box-shadow:0 16px 35px rgb(0 0 0 / 18%); font-family:var(--font),sans-serif; }
.rf-sim-select__option { display:flex; align-items:center; justify-content:space-between; gap:8px; width:100%; padding:9px 10px; border:0; border-radius:7px; background:transparent; color:inherit; font:inherit; font-size:12px; text-align:start; cursor:pointer; }
.rf-sim-select__option:hover,.rf-sim-select__option[data-active="true"] { background:var(--surface-soft,#eef8f5); }
.rf-sim-select__option[aria-selected="true"] { color:#168d78; font-weight:700; }
</style>
