import { pageData } from '../common/page';
import { iconMarkup } from '../common/icons';
import '../../css/crud.css';
import '../../css/crud-fields.css';
export const copy = () => {
    const labels = pageData().extensions;
    return pageData().pluginContext ? {...labels, delete: labels.delete_plugin,
        destructive_hint: labels.plugin_destructive_hint, operation_hint: labels.plugin_operation_hint,
        help_title: labels.plugin_help_title, help_intro: labels.plugin_help_intro, help_items: labels.plugin_help_items} : labels;
};
export function escape(value) {
    const span = document.createElement('span');
    span.textContent = String(value ?? '');
    return span.innerHTML.replaceAll('"', '&quot;').replaceAll("'", '&#39;');
}
export function operationError(error, operation) {
    if (error.status && error.status < 500 && error.message) return error.message;
    return copy().operation_error[operation] || copy().failed;
}
export function icon(name) {
    return iconMarkup(name, 18);
}
function button(key, type = 'button') {
    const node = document.createElement('button');
    node.type = type;
    node.dataset.crudAction = key;
    node.textContent = copy()[key];
    if (type === 'submit') { node.name = 'intent'; node.value = key; }
    return node;
}
function dropdown(label, items, submit = false) {
    const details = document.createElement('details');
    details.className = 'crud-dropdown';
    const toggle = document.createElement('summary');
    toggle.className = 'vuero-button';
    toggle.setAttribute('aria-label', copy()[label]);
    toggle.innerHTML = `${submit ? '' : escape(copy()[label])}${icon('chevron-down')}`;
    const menu = document.createElement('div');
    menu.className = 'crud-dropdown__menu';
    items.forEach(key => menu.append(button(key, submit ? 'submit' : 'button')));
    details.append(toggle, menu);
    return details;
}
export function toolbar(kind, {canCreate} = {}) {
    const root = document.querySelector('[data-crud-toolbar]');
    root.replaceChildren();
    const routes = pageData().extensionRoutes;
    if (kind === 'list') {
        const add = document.createElement('a');
        add.href = routes.create;
        add.className = 'vuero-button is-primary';
        add.dataset.crudAction = 'create';
        add.innerHTML = `${icon('plus')}${escape(pageData().pluginContext ? copy().new_plugin : copy().create)}`;
        if (pageData().pluginContext ? (canCreate ?? pageData().pluginContext.canCreate) : pageData().extensionAccess.upload) root.append(add);
        const actions = dropdown('actions', ['activate', 'deactivate', 'delete', 'help']);
        actions.querySelectorAll('[data-crud-action]').forEach(button => {button.hidden = button.dataset.crudAction !== 'help';});
        root.append(actions);
        return;
    }
    const cancel = document.createElement('a');
    cancel.href = pageData().singleInstanceReturn?.url || routes.index;
    cancel.className = 'vuero-button crud-cancel';
    cancel.dataset.crudAction = 'cancel';
    cancel.textContent = copy().cancel;
    root.append(cancel);
    if (document.querySelector('[data-readonly]')) {
        const edit = document.createElement('a');
        edit.href = document.querySelector('[data-extension-form]').action + '/edit';
        edit.className = 'vuero-button is-primary';
        edit.dataset.crudAction = 'edit';
        edit.innerHTML = `${icon('edit')}${escape(copy().edit)}`;
        root.append(edit);
        return;
    }
    if (document.querySelector('[data-upload-form]')) {
        const back = button('back_to_upload');
        back.className = 'vuero-button crud-cancel';
        back.dataset.archiveBack = '';
        back.hidden = true;
        const save = button('save', 'submit');
        save.className = 'vuero-button is-primary';
        const finish = document.createElement('a');
        finish.href = routes.index;
        finish.className = 'vuero-button is-primary';
        finish.dataset.archiveFinish = '';
        finish.textContent = copy().manage_extensions;
        finish.hidden = true;
        root.append(back, save, finish);
        return;
    }
    const group = document.createElement('div');
    group.className = 'crud-split-save';
    const save = button('save', 'submit');
    save.className = 'vuero-button is-primary';
    group.append(save, dropdown('save_options', pageData().pluginContext && !pageData().pluginContext.canCreate ? ['close'] : ['new', 'close'], true));
    root.append(group);
}
// Native details remain toggleable, and all menus close on outside click or Escape.
document.addEventListener('click', event => {
    document.querySelectorAll('.crud-dropdown[open]').forEach(menu => {
        if (!menu.contains(event.target) || event.target.closest('button,a')) menu.open = false;
    });
});
document.addEventListener('keydown', event => {
    if (event.key !== 'Escape') return;
    document.querySelectorAll('.crud-dropdown[open]').forEach(menu => { menu.open = false; menu.querySelector('summary').focus(); });
});
export function showDialog({title, description, items = [], confirm = null, remove = null, accordion = false, content = null, wide = false, symbol = 'info'}) {
    const dialog = document.querySelector('[data-crud-dialog]');
    dialog.classList.toggle('is-extension-details', wide);
    dialog.querySelector('.crud-dialog-symbol').innerHTML = icon(symbol);
    const heading = dialog.querySelector('[data-dialog-title]');
    const body = dialog.querySelector('[data-dialog-body]');
    heading.textContent = title;
    body.replaceChildren();
    const intro = document.createElement('p'); intro.textContent = description; body.append(intro);
    const groups = new Map();
    items.forEach((item, index) => {
        if (accordion) {
            const section = document.createElement('details'); section.className = 'crud-help-section';
            const title = document.createElement('summary'); title.textContent = Object.values(pageData().crudHelp)[index];
            const content = document.createElement('p'); content.textContent = item;
            section.append(title, content); section.open = index === 0; body.append(section); return;
        }
        const row = document.createElement('div'); row.className = 'crud-dialog-item';
        const label = document.createElement('span'); label.textContent = item.name ?? item;
        if (item.note) { const note = document.createElement('small'); note.className = 'extension-selection-note'; note.textContent = item.note; label.append(document.createElement('br'), note); } row.append(label);
        if (remove) {
            const control = document.createElement('button'); control.type = 'button'; control.className = 'crud-icon-button';
            control.setAttribute('aria-label', copy().unselect); control.innerHTML = icon('x');
            control.onclick = () => {
                const message = remove(item.id);
                if (typeof message === 'string') intro.textContent = message;
                const group = row.closest('.extension-selection-group'); row.remove();
                if (group && !group.querySelector('.crud-dialog-item')) group.remove();
                const count = body.querySelectorAll('.crud-dialog-item').length;
                heading.textContent = copy().selected.replace(':count', count);
                if (count === 0) dialog.close();
            }; row.append(control);
        }
        if (item.group) {
            if (!groups.has(item.group)) { const group=document.createElement('section'),heading=document.createElement('h3');group.className='extension-selection-group';heading.textContent=item.group;group.append(heading);body.append(group);groups.set(item.group,group); }
            groups.get(item.group).append(row);
        } else body.append(row);
    });
    if (content) body.append(content);
    const accept = dialog.querySelector('[data-dialog-confirm]');
    accept.hidden = !confirm; accept.textContent = confirm || '';
    dialog.querySelector('[data-dialog-cancel]').textContent = confirm ? copy().cancel : copy().dismiss;
    dialog.querySelector('[data-dialog-close]').setAttribute('aria-label', copy().dismiss);
    return new Promise(resolve => {
        let accepted = false;
        accept.onclick = () => { accepted = true; dialog.close(); };
        dialog.querySelectorAll('[data-dialog-close],[data-dialog-cancel]').forEach(node => { node.onclick = () => dialog.close(); });
        dialog.addEventListener('close', () => resolve(accepted), {once: true});
        dialog.showModal();
    });
}
