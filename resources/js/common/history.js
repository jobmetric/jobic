/** Render one audit change as an accessible before/after comparison. */
export function appendHistoryChange(section, {label, beforeLabel, afterLabel, before, after}) {
    const change = document.createElement('section');
    change.className = 'crud-history-change';

    const heading = document.createElement('h3');
    heading.textContent = label;
    change.append(heading);

    const comparison = document.createElement('div');
    comparison.className = 'crud-history-comparison';
    [['before', beforeLabel, before], ['after', afterLabel, after]].forEach(([kind, caption, value]) => {
        const side = document.createElement('div');
        side.className = `crud-history-value is-${kind}`;
        const title = document.createElement('strong');
        title.textContent = caption;
        const content = document.createElement('pre');
        content.textContent = formatHistoryValue(value);
        side.append(title, content);
        comparison.append(side);
    });
    change.append(comparison);
    section.append(change);
}

/** Pretty-print structured audit values while leaving normal text unchanged. */
export function formatHistoryValue(value) {
    if (value === null || value === undefined || value === '') return '—';
    if (typeof value === 'object') return JSON.stringify(value, null, 2);
    if (typeof value !== 'string') return String(value);
    const trimmed = value.trim();
    if (!trimmed.startsWith('{') && !trimmed.startsWith('[')) return value;
    try {
        const parsed = JSON.parse(trimmed);
        return parsed && typeof parsed === 'object' ? JSON.stringify(parsed, null, 2) : value;
    } catch {
        return value;
    }
}

/** Render a localized audit heading with the actor and date on opposite sides. */
export function appendHistoryHeading(section, entry, wording) {
    const summary = document.createElement('summary');
    summary.className = 'crud-history-summary';
    const actor = document.createElement('span');
    actor.textContent = wording.history_by
        .replace(':operation', wording[`history_${entry.operation}`])
        .replace(':actor', entry.actor || wording.history_actor_unknown);
    const date = document.createElement('time');
    date.dateTime = entry.created_at;
    date.textContent = entry.created_at_display;
    summary.append(actor, date);
    section.append(summary);
}
