import { iconMarkup } from '../common/icons.js';

const names = {
    'abstract-14': 'list-tree', 'abstract-26': 'list-tree', 'arrow-right': 'arrow-right',
    'arrow-right-left': 'arrows-left-right', calendar: 'calendar', check: 'check',
    'check-circle': 'check-circle', copy: 'copy', cross: 'x', 'cross-circle': 'x',
    'dots-vertical': 'settings', down: 'chevron-down', 'element-11': 'list-tree',
    flag: 'flag', flash: 'activity', 'file-text': 'file-text', history: 'clock',
    'information-2': 'info', left: 'arrow-left',
    lock: 'lock', magnifier: 'search', maximize: 'maximize', minus: 'minus',
    'mouse-circle': 'mouse-pointer', 'notepad-edit': 'edit', plus: 'plus',
    'plus-squared': 'plus', 'question-2': 'help-circle', right: 'arrow-right',
    route: 'list-tree', 'setting-2': 'settings', 'shield-tick': 'shield',
    time: 'clock', trash: 'trash',
};

/** Keep the reference canvas interactions while rendering Jobic's independent SVG icons. */
export function mountWorkflowIcons() {
    document.body.classList.add('workflow-icons-active');
    const update = () => {
        document.querySelectorAll('i.ki-filled').forEach((element) => {
            const key = [...element.classList].find((name) => name.startsWith('ki-') && name !== 'ki-filled')?.slice(3);
            const icon = names[key] ?? 'info';
            if (element.dataset.workflowIcon === icon) return;
            element.innerHTML = iconMarkup(icon, 18);
            element.dataset.workflowIcon = icon;
            element.setAttribute('aria-hidden', 'true');
        });
    };
    update();
    const observer = new MutationObserver(update);
    observer.observe(document.body, {subtree: true, childList: true, attributes: true, attributeFilter: ['class']});
    return () => {
        observer.disconnect();
        document.body.classList.remove('workflow-icons-active');
    };
}
