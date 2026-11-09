import { createApp } from 'vue';
import Workbench from './Workbench.vue';
import { mountWorkflowIcons } from './icons.js';
import { pageData } from '../common/page.js';
import '../common/selects.js';

const root = document.querySelector('[data-workflow-designer]');
if (root) {
    const appearance = pageData().workflow?.appearance ?? {};
    const mediaTheme = window.matchMedia('(prefers-color-scheme: dark)');
    const applyAppearance = () => {
        document.documentElement.dataset.theme = appearance.theme === 'system'
            ? (mediaTheme.matches ? 'dark' : 'light')
            : (appearance.theme ?? 'light');
        document.documentElement.dataset.motion = appearance.motion ?? 'system';
    };
    applyAppearance();
    if (appearance.theme === 'system') {
        mediaTheme.addEventListener('change', applyAppearance);
    }
    createApp(Workbench).mount(root);
    mountWorkflowIcons();
}
