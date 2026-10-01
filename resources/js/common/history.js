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
        content.textContent = value;
        side.append(title, content);
        comparison.append(side);
    });
    change.append(comparison);
    section.append(change);
}
