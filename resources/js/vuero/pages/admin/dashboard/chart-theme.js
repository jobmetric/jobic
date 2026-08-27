const value = (styles, property, fallback) => styles.getPropertyValue(property).trim() || fallback;

export const readChartTheme = () => {
    const styles = getComputedStyle(document.documentElement);

    return {
        primary: value(styles, '--primary', '#2aac8c'),
        accent: '#797bf2',
        info: value(styles, '--blue', '#5596e6'),
        orange: value(styles, '--orange', '#ff9f43'),
        purple: value(styles, '--purple', '#8269b2'),
        success: value(styles, '--green', '#05d69e'),
        text: value(styles, '--text', '#283252'),
        muted: value(styles, '--muted', '#a2a5b9'),
        border: value(styles, '--border', '#e6e6e6'),
        surface: value(styles, '--surface', '#ffffff'),
        soft: value(styles, '--surface-soft', '#f5f5f5'),
        dark: document.documentElement.dataset.theme === 'dark',
    };
};

export const chartPalette = (theme) => [
    theme.accent,
    theme.info,
    theme.primary,
    theme.purple,
    theme.orange,
];
