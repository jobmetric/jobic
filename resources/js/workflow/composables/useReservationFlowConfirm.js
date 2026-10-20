import { ref } from 'vue';

const DEFAULT_OPTIONS = {
    title: '',
    message: '',
    warning: '',
    confirmLabel: '',
    cancelLabel: '',
    variant: 'default',
    focus: 'confirm',
    confirmDisabled: false,
    icon: '',
    impactStats: [],
    impactTitle: '',
    impactItems: [],
    impactFooter: '',
};

export function useReservationFlowConfirm() {
    const isOpen = ref(false);
    const current = ref({ ...DEFAULT_OPTIONS });
    let resolver = null;

    function cleanup() {
        resolver = null;
        current.value = { ...DEFAULT_OPTIONS };
    }

    function confirm(options = {}) {
        return new Promise((resolve) => {
            current.value = {
                ...DEFAULT_OPTIONS,
                ...options,
            };
            isOpen.value = true;
            resolver = resolve;
        });
    }

    function handleConfirm() {
        if (current.value.confirmDisabled) {
            return;
        }

        isOpen.value = false;
        resolver?.(true);
        cleanup();
    }

    function handleCancel() {
        isOpen.value = false;
        resolver?.(false);
        cleanup();
    }

    return {
        isOpen,
        current,
        confirm,
        handleConfirm,
        handleCancel,
    };
}
