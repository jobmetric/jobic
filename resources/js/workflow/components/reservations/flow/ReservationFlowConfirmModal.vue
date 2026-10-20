<script setup>
import {
    computed,
    nextTick,
    onBeforeUnmount,
    onMounted,
    ref,
    watch,
} from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    copy: { type: Object, required: true },
    title: { type: String, default: '' },
    message: { type: String, default: '' },
    warning: { type: String, default: '' },
    confirmLabel: { type: String, default: '' },
    cancelLabel: { type: String, default: '' },
    variant: { type: String, default: 'default' },
    focus: { type: String, default: 'confirm' },
    confirmDisabled: { type: Boolean, default: false },
    icon: { type: String, default: '' },
    impactStats: { type: Array, default: () => [] },
    impactTitle: { type: String, default: '' },
    impactItems: { type: Array, default: () => [] },
    impactFooter: { type: String, default: '' },
});

const emit = defineEmits(['confirm', 'cancel']);

const modalRef = ref(null);
const confirmButtonRef = ref(null);
const cancelButtonRef = ref(null);

let previousBodyOverflow = '';

const resolvedIcon = computed(() => {
    if (props.icon) {
        return props.icon;
    }

    if (props.variant === 'danger') {
        return 'ki-trash';
    }

    if (props.variant === 'warning') {
        return 'ki-information-2';
    }

    return 'ki-question-2';
});

const resolvedConfirmLabel = computed(() => props.confirmLabel || props.copy.confirm || 'Confirm');
const resolvedCancelLabel = computed(() => props.cancelLabel || props.copy.cancel || 'Cancel');
const hasImpact = computed(() => props.impactStats.length > 0 || props.impactItems.length > 0 || Boolean(props.impactFooter));
const renderedImpactItems = computed(() => props.impactItems.map((item) => {
    const meta = String(item.meta ?? '').trim();
    const routeParts = meta.split(/\s*->\s*/).filter(Boolean);

    return {
        ...item,
        meta,
        routeFrom: routeParts.length === 2 ? routeParts[0] : '',
        routeTo: routeParts.length === 2 ? routeParts[1] : '',
    };
}));

const isVisible = computed(() => Boolean(props.open) && Boolean(String(props.title ?? '').trim()));

function impactStatIcon(stat) {
    const label = String(stat?.label ?? '').toLowerCase();

    if (label.includes('task')) {
        return 'ki-check-circle';
    }

    if (label.includes('transition')) {
        return 'ki-route';
    }

    return 'ki-information-2';
}

function lockBodyScroll() {
    previousBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
}

function unlockBodyScroll() {
    document.body.style.overflow = previousBodyOverflow ?? '';
}

function requestCancel() {
    emit('cancel');
}

function requestConfirm() {
    if (props.confirmDisabled) {
        return;
    }

    emit('confirm');
}

function onDocumentKeydown(event) {
    if (!isVisible.value) {
        return;
    }

    if (event.key === 'Escape') {
        event.preventDefault();
        requestCancel();
        return;
    }

    if (event.key === 'Enter' && !props.confirmDisabled) {
        const tag = String(event.target?.tagName ?? '').toLowerCase();

        if (tag === 'textarea') {
            return;
        }

        event.preventDefault();
        requestConfirm();
    }
}

function trapFocus(event) {
    if (!isVisible.value || !modalRef.value || event.key !== 'Tab') {
        return;
    }

    const focusable = modalRef.value.querySelectorAll(
        'button:not([disabled])',
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

watch(isVisible, async (visible) => {
    if (!visible) {
        unlockBodyScroll();
        return;
    }

    lockBodyScroll();

    await nextTick();

    if (props.focus === 'cancel') {
        cancelButtonRef.value?.focus?.();
        return;
    }

    confirmButtonRef.value?.focus?.();
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
                v-if="isVisible"
                class="rf-confirm-backdrop"
                role="presentation"
                @click.self="requestCancel"
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
                        v-if="isVisible"
                        ref="modalRef"
                        class="rf-confirm"
                        :class="`is-${variant}`"
                        role="alertdialog"
                        aria-modal="true"
                        :aria-labelledby="'rf-confirm-title'"
                        :aria-describedby="message || warning ? 'rf-confirm-desc' : undefined"
                        @click.stop
                    >
                        <div class="rf-confirm__header">
                            <div class="rf-confirm__intro">
                                <div
                                    class="rf-confirm__icon"
                                    :class="`is-${variant}`"
                                    aria-hidden="true"
                                >
                                    <i :class="`ki-filled ${resolvedIcon}`" />
                                </div>
                                <div class="rf-confirm__content">
                                    <h2 id="rf-confirm-title" class="rf-confirm__title">{{ title }}</h2>
                                    <p v-if="message" id="rf-confirm-desc" class="rf-confirm__message">{{ message }}</p>
                                </div>
                            </div>

                            <div v-if="hasImpact" class="rf-confirm-impact" aria-live="polite">
                                <div class="rf-confirm-impact__heading">
                                    <span class="rf-confirm-impact__heading-icon" aria-hidden="true">
                                        <i class="ki-filled ki-information-2" />
                                    </span>
                                    <span class="rf-confirm-impact__heading-copy">
                                        <span class="rf-confirm-impact__heading-title">{{ copy.workflow_impact_title }}</span>
                                        <span class="rf-confirm-impact__heading-text">{{ copy.workflow_impact_hint }}</span>
                                    </span>
                                </div>

                                <div v-if="impactStats.length" class="rf-confirm-impact__stats">
                                    <div
                                        v-for="stat in impactStats"
                                        :key="stat.label"
                                        class="rf-confirm-impact__stat"
                                    >
                                        <span class="rf-confirm-impact__stat-icon" aria-hidden="true">
                                            <i :class="`ki-filled ${impactStatIcon(stat)}`" />
                                        </span>
                                        <span class="rf-confirm-impact__stat-copy">
                                            <span class="rf-confirm-impact__stat-value">{{ stat.value }}</span>
                                            <span class="rf-confirm-impact__stat-label">{{ stat.label }}</span>
                                        </span>
                                    </div>
                                </div>

                                <div v-if="impactItems.length" class="rf-confirm-impact__list">
                                    <div v-if="impactTitle" class="rf-confirm-impact__list-header">
                                        <span class="rf-confirm-impact__list-icon" aria-hidden="true">
                                            <i class="ki-filled ki-route" />
                                        </span>
                                        <h3>{{ impactTitle }}</h3>
                                    </div>
                                    <ul>
                                        <li v-for="item in renderedImpactItems" :key="`${item.title}-${item.meta}`">
                                            <span class="rf-confirm-impact__item-icon" aria-hidden="true">
                                                <i class="ki-filled ki-trash" />
                                            </span>
                                            <span class="rf-confirm-impact__item-copy">
                                                <span class="rf-confirm-impact__item-title">{{ item.title }}</span>
                                                <span
                                                    v-if="item.routeFrom && item.routeTo"
                                                    class="rf-confirm-impact__route"
                                                >
                                                    <span>{{ item.routeFrom }}</span>
                                                    <i class="ki-filled ki-arrow-right" aria-hidden="true" />
                                                    <span>{{ item.routeTo }}</span>
                                                </span>
                                                <span v-else-if="item.meta" class="rf-confirm-impact__item-meta">{{ item.meta }}</span>
                                            </span>
                                        </li>
                                    </ul>
                                </div>

                                <p v-if="impactFooter" class="rf-confirm-impact__footer">
                                    <i class="ki-filled ki-information-2" aria-hidden="true" />
                                    <span>{{ impactFooter }}</span>
                                </p>
                            </div>

                            <p v-if="warning" class="rf-confirm__warning">{{ warning }}</p>
                        </div>

                        <footer class="rf-confirm__footer">
                            <button
                                ref="cancelButtonRef"
                                type="button"
                                class="rf-confirm-btn rf-confirm-btn--secondary"
                                @click="requestCancel"
                            >
                                {{ resolvedCancelLabel }}
                            </button>
                            <button
                                ref="confirmButtonRef"
                                type="button"
                                class="rf-confirm-btn"
                                :class="variant === 'danger' ? 'rf-confirm-btn--danger' : 'rf-confirm-btn--primary'"
                                :disabled="confirmDisabled"
                                @click="requestConfirm"
                            >
                                {{ resolvedConfirmLabel }}
                            </button>
                        </footer>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

<style scoped>
.rf-confirm-backdrop {
    position: fixed;
    inset: 0;
    z-index: 440;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
    background: rgba(15, 23, 42, 0.48);
    backdrop-filter: blur(2px);
}

.rf-confirm {
    width: min(520px, calc(100vw - 40px));
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    background: #FFFFFF;
    box-shadow: 0 28px 80px rgba(15, 23, 42, 0.24);
    overflow: hidden;
}

.rf-confirm.is-danger {
    border-color: #FECACA;
}

.rf-confirm.is-warning {
    border-color: #FDE68A;
}

.rf-confirm__header {
    padding: 24px;
}

.rf-confirm__intro {
    display: flex;
    align-items: flex-start;
    gap: 14px;
}

.rf-confirm__icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 44px;
    height: 44px;
    border-radius: 12px;
    font-size: 1.15rem;
    flex-shrink: 0;
    background: #F0EEFF;
    color: #716BFF;
    border: 1px solid #E0E7FF;
}

.rf-confirm__icon.is-danger {
    background: #FEF2F2;
    color: #DC2626;
    border-color: #FECACA;
}

.rf-confirm__icon.is-warning {
    background: #FFFBEB;
    color: #D97706;
    border-color: #FDE68A;
}

.rf-confirm__title {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 700;
    line-height: 1.3;
    color: #0F172A;
}

.rf-confirm__content {
    min-width: 0;
    flex: 1;
}

.rf-confirm__message {
    margin: 0.45rem 0 0;
    font-size: 0.875rem;
    color: #64748B;
    line-height: 1.55;
}

.rf-confirm-impact {
    width: 100%;
    margin-top: 1.15rem;
    border: 1px solid #E2E8F0;
    border-radius: 16px;
    background: #FFFFFF;
    box-shadow: 0 14px 36px rgba(15, 23, 42, 0.08);
    overflow: hidden;
}

.rf-confirm-impact__heading {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
    padding: 0.9rem 0.95rem;
    border-bottom: 1px solid #F1F5F9;
    background:
        radial-gradient(circle at top left, rgba(239, 68, 68, 0.12), transparent 30%),
        linear-gradient(135deg, #FFF7F7 0%, #FFFFFF 62%);
}

.rf-confirm-impact__heading-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2rem;
    height: 2rem;
    border-radius: 10px;
    background: #FEF2F2;
    color: #DC2626;
    font-size: 0.95rem;
    flex-shrink: 0;
}

.rf-confirm-impact__heading-copy {
    display: grid;
    gap: 0.12rem;
    min-width: 0;
}

.rf-confirm-impact__heading-title {
    color: #0F172A;
    font-size: 0.88rem;
    font-weight: 800;
    line-height: 1.25;
}

.rf-confirm-impact__heading-text {
    color: #64748B;
    font-size: 0.76rem;
    font-weight: 550;
    line-height: 1.4;
}

.rf-confirm-impact__stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
    gap: 0.65rem;
    padding: 0.9rem;
    border-bottom: 1px solid #F1F5F9;
    background: #FFFFFF;
}

.rf-confirm-impact__stat {
    display: flex;
    align-items: center;
    gap: 0.7rem;
    min-width: 0;
    padding: 0.75rem;
    border: 1px solid rgba(220, 38, 38, 0.14);
    border-radius: 13px;
    background: rgba(255, 255, 255, 0.84);
    box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
}

.rf-confirm-impact__stat-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 2.15rem;
    height: 2.15rem;
    border-radius: 11px;
    background: #FEF2F2;
    color: #DC2626;
    font-size: 1rem;
    flex-shrink: 0;
}

.rf-confirm-impact__stat-copy {
    display: grid;
    gap: 0.1rem;
    min-width: 0;
}

.rf-confirm-impact__stat-value {
    color: #0F172A;
    font-size: 1.05rem;
    font-weight: 800;
    line-height: 1;
}

.rf-confirm-impact__stat-label {
    color: #334155;
    font-size: 0.76rem;
    font-weight: 700;
    line-height: 1.25;
    overflow-wrap: anywhere;
}

.rf-confirm-impact__list {
    padding: 0.95rem;
}

.rf-confirm-impact__list-header {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    margin-bottom: 0.75rem;
}

.rf-confirm-impact__list-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.65rem;
    height: 1.65rem;
    border-radius: 9px;
    background: #F8FAFC;
    color: #B91C1C;
    font-size: 0.85rem;
}

.rf-confirm-impact__list h3 {
    margin: 0;
    font-size: 0.72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.055em;
    color: #991B1B;
}

.rf-confirm-impact__list ul {
    display: grid;
    gap: 0.55rem;
    margin: 0;
    padding: 0;
    list-style: none;
}

.rf-confirm-impact__list li {
    display: flex;
    align-items: flex-start;
    gap: 0.7rem;
    min-width: 0;
    padding: 0.68rem 0.72rem;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    background: #FCFCFD;
    transition: border-color 0.15s ease, background 0.15s ease;
}

.rf-confirm-impact__list li:hover {
    border-color: #FCA5A5;
    background: #FFFFFF;
}

.rf-confirm-impact__item-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.9rem;
    height: 1.9rem;
    margin-top: 0.05rem;
    border-radius: 10px;
    background: #FEF2F2;
    color: #DC2626;
    font-size: 0.86rem;
    flex-shrink: 0;
}

.rf-confirm-impact__item-copy {
    display: grid;
    gap: 0.28rem;
    min-width: 0;
    flex: 1;
}

.rf-confirm-impact__item-title {
    color: #0F172A;
    font-size: 0.83rem;
    font-weight: 700;
}

.rf-confirm-impact__route {
    display: flex;
    align-items: center;
    flex-wrap: wrap;
    gap: 0.32rem;
    color: #475569;
    font-size: 0.76rem;
    font-weight: 650;
    line-height: 1.4;
}

.rf-confirm-impact__route span {
    min-width: 0;
    max-width: 100%;
    padding: 0.12rem 0.42rem;
    border: 1px solid #E2E8F0;
    border-radius: 999px;
    background: #FFFFFF;
    color: #334155;
    overflow-wrap: anywhere;
}

.rf-confirm-impact__route i {
    color: #DC2626;
    font-size: 0.72rem;
}

.rf-confirm-impact__item-meta {
    color: #475569;
    font-size: 0.76rem;
    line-height: 1.4;
    overflow-wrap: anywhere;
}

.rf-confirm-impact__footer {
    display: flex;
    align-items: flex-start;
    gap: 0.5rem;
    margin: 0;
    padding: 0.8rem 0.95rem 0.9rem;
    border-top: 1px solid #F1F5F9;
    background: #FFF7ED;
    color: #991B1B;
    font-size: 0.8125rem;
    font-weight: 700;
    line-height: 1.45;
}

.rf-confirm-impact__footer i {
    margin-top: 0.08rem;
    color: #EA580C;
    flex-shrink: 0;
}

.rf-confirm__warning {
    margin: 0.55rem 0 0;
    padding: 0.65rem 0.75rem;
    border-radius: 10px;
    background: #FEF2F2;
    border: 1px solid #FECACA;
    font-size: 0.8125rem;
    color: #991B1B;
    line-height: 1.5;
    white-space: pre-line;
}

.rf-confirm.is-warning .rf-confirm__warning {
    background: #FFFBEB;
    border-color: #FDE68A;
    color: #92400E;
}

.rf-confirm__footer {
    display: flex;
    justify-content: flex-end;
    gap: 0.5rem;
    padding: 16px 24px 20px;
    border-top: 1px solid #E2E8F0;
    background: #FFFFFF;
}

.rf-confirm-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 2.4rem;
    padding: 0 1rem;
    border-radius: 10px;
    border: 1px solid transparent;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
}

.rf-confirm-btn--secondary {
    background: #FFFFFF;
    border-color: #CBD5E1;
    color: #334155;
}

.rf-confirm-btn--primary {
    background: #146EF5;
    color: #FFFFFF;
}

.rf-confirm-btn--danger {
    background: #DC2626;
    color: #FFFFFF;
}

.rf-confirm-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.rf-confirm-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(20, 110, 245, 0.2);
}

.rf-confirm-btn--danger:focus-visible {
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.22);
}

@media (max-width: 768px) {
    .rf-confirm-backdrop {
        padding: 0;
        align-items: stretch;
    }

    .rf-confirm {
        width: 100%;
        max-width: none;
        min-height: 100dvh;
        border-radius: 0;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }
}
</style>
