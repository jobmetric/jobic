const sources = import.meta.glob('../../icons/jobic/*.svg', {
    eager: true,
    query: '?raw',
    import: 'default',
});

const iconContents = Object.fromEntries(Object.entries(sources).map(([path, svg]) => {
    const match = String(svg).match(/<svg\b[^>]*>([\s\S]*?)<\/svg>/);
    return [path.split('/').pop().replace(/\.svg$/, ''), match?.[1] ?? ''];
}));

export function iconMarkup(name, size = 18, className = '') {
    const key = /^[a-z0-9-]+$/.test(String(name)) ? String(name) : '';
    const content = iconContents[key] ?? '';
    const dimension = Number.isFinite(Number(size)) ? Math.max(1, Number(size)) : 18;
    const classes = String(className).split(/\s+/).filter(token => /^[a-z0-9_-]+$/i.test(token)).join(' ');

    return `<svg class="ui-icon ${classes}" width="${dimension}" height="${dimension}" style="width:${dimension}px;height:${dimension}px" viewBox="0 0 24 24" fill="none" aria-hidden="true">${content}</svg>`;
}
