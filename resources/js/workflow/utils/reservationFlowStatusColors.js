/** @type {Record<string, { bg: string, border: string, accent: string }>} */
const STATUS_COLORS = {
    draft: {
        bg: 'rgb(241 245 249 / 0.92)',
        border: 'rgb(148 163 184 / 0.55)',
        accent: 'rgb(100 116 139)',
    },
    pending: {
        bg: 'rgb(254 243 199 / 0.55)',
        border: 'rgb(245 158 11 / 0.5)',
        accent: 'rgb(180 83 9)',
    },
    open: {
        bg: 'rgb(219 234 254 / 0.55)',
        border: 'rgb(59 130 246 / 0.5)',
        accent: 'rgb(29 78 216)',
    },
    confirmed: {
        bg: 'rgb(224 231 255 / 0.55)',
        border: 'rgb(99 102 241 / 0.5)',
        accent: 'rgb(67 56 202)',
    },
    started: {
        bg: 'rgb(204 251 241 / 0.55)',
        border: 'rgb(20 184 166 / 0.5)',
        accent: 'rgb(15 118 110)',
    },
    completed: {
        bg: 'rgb(220 252 231 / 0.55)',
        border: 'rgb(34 197 94 / 0.5)',
        accent: 'rgb(21 128 61)',
    },
    cancelled: {
        bg: 'rgb(254 226 226 / 0.55)',
        border: 'rgb(239 68 68 / 0.5)',
        accent: 'rgb(185 28 28)',
    },
};

/**
 * @param {string} status
 */
function hashStatusColor(status) {
    let hash = 0;
    const value = String(status || 'unknown');

    for (let index = 0; index < value.length; index += 1) {
        hash = value.charCodeAt(index) + ((hash << 5) - hash);
    }

    const hue = Math.abs(hash) % 360;

    return {
        bg: `hsl(${hue} 68% 94% / 0.72)`,
        border: `hsl(${hue} 58% 52% / 0.55)`,
        accent: `hsl(${hue} 52% 34%)`,
    };
}

/**
 * @param {string|null|undefined} status
 * @returns {{ bg: string, border: string, accent: string }}
 */
export function reservationStatusColors(status) {
    const key = String(status || '').toLowerCase();

    return STATUS_COLORS[key] ?? hashStatusColor(key || 'unknown');
}

/**
 * @param {string|null|undefined} status
 */
export function reservationStatusNodeStyle(status) {
    const colors = reservationStatusColors(status);

    return {
        background: colors.bg,
        borderColor: colors.border,
        '--flow-state-accent': colors.accent,
    };
}
