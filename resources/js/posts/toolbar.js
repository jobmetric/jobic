import {iconMarkup} from '../common/icons';

/** Render navigation and permitted operations from the current presentation registry. */
export function mountPostListToolbar(root, readConfig) {
    const render = () => {
        const config = readConfig();
        const fragment = document.createDocumentFragment();
        const link = (href, label, icon, attribute, className = '') => {
            const control = document.createElement('a');
            control.href = href; control.className = className;
            if (attribute) control.setAttribute(attribute, '');
            control.innerHTML = iconMarkup(icon, 18);
            control.append(document.createTextNode(label));
            return control;
        };
        const create = link(config.createUrl, config.type === 'page' ? config.labels.create_page : config.labels.create, 'plus', null, 'vuero-button is-primary');
        create.dataset.crudAction = 'create'; fragment.append(create);
        if (config.defaultKanbanUrl) fragment.append(link(config.defaultKanbanUrl, config.defaultKanbanLabel, 'kanban', 'data-post-default-kanban-link', 'vuero-button'));
        const operations = document.createElement('details'); operations.className = 'crud-dropdown post-list-operations';
        const toggle = document.createElement('summary'); toggle.className = 'vuero-button';
        toggle.append(document.createTextNode(config.labels.actions)); toggle.insertAdjacentHTML('beforeend', iconMarkup('chevron-down', 18));
        const menu = document.createElement('div'); menu.className = 'crud-dropdown__menu';
        const permitted = config.operations || ['delete'];
        const action = key => {
            const control = document.createElement('button'); control.type = 'button';
            control.dataset.postAction = key; control.dataset.crudAction = key;
            if(key==='delete')control.hidden=true;
            control.textContent = config.labels[key] || key; return control;
        };
        const navigation = [];
        if (config.kanbanUrl) navigation.push(link(config.kanbanUrl, config.kanbanLabel, 'kanban', 'data-post-kanban-link'));
        if (config.workflowUrl) navigation.push(link(config.workflowUrl, config.workflowLabel, 'settings', 'data-post-workflow-link'));
        const groups = [
            ['delete', ...permitted.filter(key => !['delete', 'help', 'unlock'].includes(key))].filter(key => permitted.includes(key)).map(action),
            navigation,
            ['help', 'unlock'].filter(key => permitted.includes(key)).map(action),
        ];
        groups.filter(group => group.length).forEach((group, index) => {
            if (index) { const divider = document.createElement('hr'); divider.className = 'crud-dropdown-divider'; menu.append(divider); }
            menu.append(...group);
        });
        if (menu.children.length) { operations.append(toggle, menu); fragment.append(operations); }
        root.replaceChildren(fragment);
    };
    render();
    window.addEventListener('cms:presentation-refreshed', render);
    return render;
}
