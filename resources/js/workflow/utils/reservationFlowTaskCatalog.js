/** Normalize registered post workflow task drivers into a searchable catalog. */

const CATEGORY_IDS = ['all', 'restriction', 'validation', 'action'];

const CATEGORY_ICONS = {
    all: 'ki-element-11',
    restriction: 'ki-lock',
    validation: 'ki-shield-tick',
    action: 'ki-flash',
};

function driverKeyFromClass(className) {
    const value = String(className ?? '').trim();

    if (!value) {
        return '';
    }

    const parts = value.split('\\');

    return parts[parts.length - 1] ?? value;
}

function humanizeDriverKey(key) {
    return String(key ?? '')
        .replace(/Task$/i, '')
        .replace(/([a-z])([A-Z])/g, '$1 $2')
        .replace(/_/g, ' ')
        .trim();
}

function normalizeCategory(type) {
    const value = String(type ?? 'action').toLowerCase();

    if (value === 'restriction' || value === 'validation' || value === 'action') {
        return value;
    }

    return 'action';
}

/**
 * @param {Array} availableDrivers
 * @param {Record<string, string>} copy
 */
export function buildTaskDriverCatalog(availableDrivers = [], copy = {}) {
    const rows = [];

    availableDrivers.forEach((group) => {
        (group.tasks ?? []).forEach((task) => {
            const driverClass = String(task.class ?? '').trim();
            const driverKey = String(task.key ?? '').trim() || driverKeyFromClass(driverClass);
            const category = normalizeCategory(task.type);
            const title = String(task.title ?? '').trim() || humanizeDriverKey(driverKey);
            const description = String(task.description ?? '').trim();

            rows.push({
                id: driverClass || driverKey,
                driverClass,
                driverKey,
                title,
                description,
                category,
                icon: task.icon ? (String(task.icon).startsWith('ki-') ? task.icon : `ki-${task.icon}`)
                    : (CATEGORY_ICONS[category] ?? 'ki-setting-2'),
                badgeLabel: copy[`reservation_flow_task_type_${category}`] ?? category,
                badgeTone: category,
                keywords: [
                    driverKey,
                    driverClass,
                    title,
                    description,
                    category,
                    copy[`reservation_flow_task_type_${category}`] ?? '',
                ].filter(Boolean).map((item) => String(item).toLowerCase()),
                configRequired: Boolean(Object.keys(task.fields ?? {}).length),
                fields: task.fields ?? {},
                defaultConfig: {
                    ...Object.fromEntries(Object.entries(task.fields ?? {})
                        .filter(([, field]) => field.default !== undefined)
                        .map(([key, field]) => [key, field.default])),
                    ...(task.defaultConfig ?? {}),
                },
                howItWorks: task.howItWorks ?? '',
                useCase: task.useCase ?? '',
                rawDriver: task,
            });
        });
    });

    return rows;
}

/**
 * @param {ReturnType<typeof buildTaskDriverCatalog>} catalog
 * @param {{ category?: string, query?: string }} filters
 */
export function filterTaskDriverCatalog(catalog, { category = 'all', query = '' } = {}) {
    const normalizedQuery = String(query ?? '').trim().toLowerCase();

    return catalog.filter((row) => {
        if (category !== 'all' && row.category !== category) {
            return false;
        }

        if (!normalizedQuery) {
            return true;
        }

        return row.keywords.some((keyword) => keyword.includes(normalizedQuery))
            || row.title.toLowerCase().includes(normalizedQuery)
            || row.description.toLowerCase().includes(normalizedQuery)
            || row.driverKey.toLowerCase().includes(normalizedQuery);
    });
}

/**
 * @param {ReturnType<typeof buildTaskDriverCatalog>} catalog
 * @param {Record<string, string>} copy
 */
export function buildTaskCategoryNav(catalog, copy) {
    const counts = {
        all: catalog.length,
        restriction: 0,
        validation: 0,
        action: 0,
    };

    catalog.forEach((row) => {
        counts[row.category] += 1;
    });

    return CATEGORY_IDS
        .filter((id) => id === 'all' || counts[id] > 0)
        .map((id) => ({
            id,
            label: copy[`reservation_flow_task_library_category_${id}`] ?? id,
            icon: CATEGORY_ICONS[id],
            count: counts[id],
        }));
}

export function findTaskDriver(catalog, driverId) {
    const key = String(driverId ?? '');

    return catalog.find((row) => row.id === key || row.driverClass === key || row.driverKey === key) ?? null;
}

export function driverNeedsConfiguration(driver) {
    return Boolean(driver?.configRequired);
}

export function defaultConfigForDriver(driver) {
    return driver?.defaultConfig ? { ...driver.defaultConfig } : {};
}
