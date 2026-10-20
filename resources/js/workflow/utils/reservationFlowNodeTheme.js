/**
 * Visual-only node theme tokens for the reservation flow editor.
 * Does not change backend status values or saved geometry.
 */

const START_THEME = {
    border: '#D7DCE5',
    iconBg: '#F1F5F9',
    iconColor: '#64748B',
    badgeBg: '#EAF2FF',
    badgeText: '#2767D8',
    icon: 'ki-calendar',
};

const TERMINAL_THEME = {
    border: '#2EAE98',
    iconBg: '#E9F8F3',
    iconColor: '#168D78',
    badgeBg: '#E9F8F3',
    badgeText: '#168D78',
    icon: 'ki-flag',
};

const STATUS_THEMES = {
    open: {
        border: '#79AFFF',
        iconBg: '#E8F2FF',
        iconColor: '#2879F6',
        badgeBg: '#E8F2FF',
        badgeText: '#1670F8',
        icon: 'ki-abstract-26',
    },
    confirmed: {
        border: '#8A7CFF',
        iconBg: '#EFEDFF',
        iconColor: '#716BFF',
        badgeBg: '#EFEDFF',
        badgeText: '#635BFF',
        icon: 'ki-check-circle',
    },
    started: {
        border: '#45C7BE',
        iconBg: '#E3F9F6',
        iconColor: '#16A89A',
        badgeBg: '#E3F9F6',
        badgeText: '#0F8F82',
        icon: 'ki-time',
    },
    completed: {
        border: '#77CF89',
        iconBg: '#E7F8EB',
        iconColor: '#25A851',
        badgeBg: '#E7F8EB',
        badgeText: '#228B47',
        icon: 'ki-flag',
    },
    cancelled: {
        border: '#FF8F8F',
        iconBg: '#FDECEC',
        iconColor: '#EF4444',
        badgeBg: '#FDECEC',
        badgeText: '#DC3535',
        icon: 'ki-cross-circle',
    },
};

/**
 * @param {{ kind?: string, status?: string, is_terminal?: boolean, is_start?: boolean }} node
 */
export function reservationFlowNodeTheme(node) {
    if (node?.kind === 'start') {
        return { ...START_THEME };
    }

    if (node?.is_terminal) {
        return { ...TERMINAL_THEME };
    }

    const key = String(node?.status ?? '').toLowerCase();
    const theme = STATUS_THEMES[key];

    if (theme) {
        return { ...theme };
    }

    return {
        border: '#CBD5E1',
        iconBg: '#F8FAFC',
        iconColor: '#64748B',
        badgeBg: '#F1F5F9',
        badgeText: '#475569',
        icon: 'ki-abstract-14',
    };
}

/**
 * @param {{ kind?: string, is_terminal?: boolean, is_start?: boolean }} node
 * @param {{ reservation_flow_start_state?: string, reservation_flow_terminal_badge?: string, reservation_flow_badge_active?: string }} copy
 */
export function reservationFlowNodeBadgeLabel(node, copy = {}) {
    if (node?.kind === 'start') {
        return copy.reservation_flow_start_state ?? 'Start';
    }

    if (node?.is_terminal) {
        return copy.reservation_flow_terminal_badge ?? 'Terminal';
    }

    return '';
}
