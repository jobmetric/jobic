<script setup>
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    eyebrow: { type: String, default: '' },
    title: { type: String, required: true },
    description: { type: String, default: '' },
    icon: { type: String, default: 'ki-abstract-26' },
    saving: { type: Boolean, default: false },
    closeDisabled: { type: Boolean, default: false },
    cancelLabel: { type: String, default: 'Cancel' },
    size: { type: String, default: 'md' },
    ariaLabel: { type: String, default: '' },
});

const emit = defineEmits(['close']);

const modalRef = ref(null);
let previousBodyOverflow = '';

function lockBodyScroll() {
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
}

function unlockBodyScroll() {
    document.body.style.overflow = previousBodyOverflow ?? '';
}

function requestClose() {
    if (props.closeDisabled || props.saving) {
        return;
    }

    emit('close');
}

function onDocumentKeydown(event) {
    if (!props.open) {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        requestClose();
    }
}

function trapFocus(event) {
    if (!props.open || !modalRef.value || event.key !== 'Tab') {
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

watch(() => props.open, async (isOpen) => {
    if (!isOpen) {
        unlockBodyScroll();
        return;
    }

    lockBodyScroll();
    await nextTick();
    modalRef.value?.querySelector('input, select, textarea, button')?.focus?.();
});

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
                class="rf-flow-modal-backdrop"
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
                        class="rf-flow-modal"
                        :class="`is-${size}`"
                        role="dialog"
                        aria-modal="true"
                        :aria-label="ariaLabel || title"
                        @click.stop
                    >
                        <header class="rf-flow-modal__header">
                            <div class="rf-flow-modal__header-main">
                                <div class="rf-flow-modal__icon-wrap" aria-hidden="true">
                                    <i :class="`ki-filled ${icon}`" />
                                </div>
                                <div class="min-w-0">
                                    <p v-if="eyebrow" class="rf-flow-modal__eyebrow">{{ eyebrow }}</p>
                                    <h2 class="rf-flow-modal__title">{{ title }}</h2>
                                    <p v-if="description" class="rf-flow-modal__desc">{{ description }}</p>
                                    <slot name="header-extra" />
                                </div>
                            </div>
                            <button
                                type="button"
                                class="rf-flow-modal__close"
                                :aria-label="cancelLabel"
                                :disabled="closeDisabled || saving"
                                @click="requestClose"
                            >
                                <i class="ki-filled ki-cross" aria-hidden="true" />
                            </button>
                        </header>

                        <div class="rf-flow-modal__content">
                            <slot />
                        </div>

                        <footer v-if="$slots.footer" class="rf-flow-modal__footer">
                            <slot name="footer" />
                        </footer>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.rf-flow-modal-backdrop {
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

.rf-flow-modal {
    --rf-modal-bg: #FFFFFF;
    --rf-modal-surface: #F8FAFC;
    --rf-modal-text: #0F172A;
    --rf-modal-secondary: #64748B;
    --rf-modal-muted: #94A3B8;
    --rf-modal-border: #E2E8F0;
    --rf-modal-border-strong: #CBD5E1;
    --rf-modal-primary: #2eae98;
    --rf-modal-indigo: #2eae98;
    --rf-modal-indigo-soft: #e8f7f3;

    display: flex;
    flex-direction: column;
    width: min(720px, calc(100vw - 40px));
    max-height: min(82vh, calc(100dvh - 40px));
    border: 1px solid var(--rf-modal-border);
    border-radius: 20px;
    background: var(--rf-modal-bg);
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.24);
    overflow: hidden;
}

.rf-flow-modal.is-lg {
    width: min(840px, calc(100vw - 40px));
}

.rf-flow-modal.is-xl {
    width: min(1020px, calc(100vw - 40px));
}

.rf-flow-modal__header {
    position: relative;
    flex: 0 0 auto;
    padding: 28px 30px 20px;
    border-bottom: 1px solid var(--rf-modal-border);
}

.rf-flow-modal__header-main {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding-inline-end: 2.5rem;
}

.rf-flow-modal__icon-wrap {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 46px;
    height: 46px;
    border-radius: 14px;
    border: 1px solid #E0E7FF;
    background: var(--rf-modal-indigo-soft);
    color: var(--rf-modal-indigo);
    font-size: 1.15rem;
    flex-shrink: 0;
}

.rf-flow-modal__eyebrow {
    margin: 0;
    font-size: 0.6875rem;
    font-weight: 700;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    color: var(--rf-modal-indigo);
}

.rf-flow-modal__title {
    margin: 0.2rem 0 0;
    font-size: 1.65rem;
    font-weight: 700;
    color: var(--rf-modal-text);
    line-height: 1.2;
}

.rf-flow-modal__desc {
    margin: 0.45rem 0 0;
    font-size: 0.875rem;
    color: var(--rf-modal-secondary);
    line-height: 1.5;
    max-width: 40rem;
}

.rf-flow-modal__close {
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
    color: var(--rf-modal-secondary);
    cursor: pointer;
}

.rf-flow-modal__close:hover,
.rf-flow-modal__close:focus-visible {
    background: var(--rf-modal-surface);
    outline: none;
    box-shadow: 0 0 0 2px color-mix(in srgb,var(--rf-modal-primary) 25%,transparent);
}

.rf-flow-modal__content {
    flex: 1;
    min-height: 0;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

.rf-flow-modal__footer {
    flex: 0 0 auto;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    min-height: 72px;
    padding: 18px 28px;
    border-top: 1px solid var(--rf-modal-border);
    background: var(--rf-modal-bg);
}

:global(html[data-theme='dark']) .rf-flow-modal {
    --rf-modal-bg:var(--surface);
    --rf-modal-surface:var(--surface-soft);
    --rf-modal-text:var(--text);
    --rf-modal-secondary:var(--text-soft);
    --rf-modal-muted:var(--muted);
    --rf-modal-border:var(--border);
    --rf-modal-border-strong:var(--border-strong);
    --rf-modal-indigo-soft:var(--primary-soft);
    --rf-modal-indigo:var(--primary);
    color-scheme:dark;
}

.rf-flow-modal.is-xl .rf-flow-modal__footer {
    justify-content: space-between;
}

.rf-flow-modal__footer > :only-child {
    margin-inline-start: auto;
}

@media (max-width: 768px) {
    .rf-flow-modal-backdrop {
        padding: 0;
        align-items: stretch;
    }

    .rf-flow-modal,
    .rf-flow-modal.is-lg,
    .rf-flow-modal.is-xl {
        width: 100%;
        max-height: 100dvh;
        height: 100dvh;
        border-radius: 0;
    }

    .rf-flow-modal.is-xl .rf-flow-modal__footer {
        flex-wrap: wrap;
    }
}
</style>
