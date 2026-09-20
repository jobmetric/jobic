import { pageData } from '../common/page';
import '../../css/crud.css';
export const copy = () => pageData().users;
export function escape(value) {
    const span = document.createElement('span');
    span.textContent = String(value ?? '');
    return span.innerHTML;
}
export function icon(name) {
    return `<svg class="ui-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-${name}"></use></svg>`;
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
export function toolbar(kind) {
    const root = document.querySelector('[data-crud-toolbar]');
    root.replaceChildren();
    const routes = pageData().userRoutes;
    if (kind === 'list') {
        const add = document.createElement('a');
        add.href = `${routes.index}/create`;
        add.className = 'vuero-button is-primary';
        add.dataset.crudAction = 'create';
        add.innerHTML = `${icon('plus')}${escape(copy().create)}`;
        root.append(add, dropdown('actions', pageData().userToolbar.list));
        return;
    }
    const cancel = document.createElement('a');
    cancel.href = routes.index;
    cancel.className = 'vuero-button crud-cancel';
    cancel.dataset.crudAction = 'cancel';
    cancel.textContent = copy().cancel;
    root.append(cancel);
    if (document.querySelector('[data-readonly]')) {
        const edit = document.createElement('a');
        edit.href = document.querySelector('[data-user-form]').action + '/edit';
        edit.className = 'vuero-button is-primary';
        edit.dataset.crudAction = 'edit';
        edit.innerHTML = `${icon('edit')}${escape(copy().edit)}`;
        root.append(edit);
        return;
    }
    const group = document.createElement('div');
    group.className = 'crud-split-save';
    const save = button('save', 'submit');
    save.className = 'vuero-button is-primary';
    group.append(save, dropdown('save_options', pageData().userToolbar.form, true));
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
export function showDialog({title, description, items = [], confirm = null, remove = null, accordion = false}) {
    const dialog = document.querySelector('[data-crud-dialog]');
    const heading = dialog.querySelector('[data-dialog-title]');
    const body = dialog.querySelector('[data-dialog-body]');
    heading.textContent = title;
    body.replaceChildren();
    const intro = document.createElement('p'); intro.textContent = description; body.append(intro);
    items.forEach((item, index) => {
        if (accordion) {
            const section = document.createElement('details'); section.className = 'crud-help-section';
            const title = document.createElement('summary'); title.textContent = Object.values(pageData().crudHelp)[index];
            const content = document.createElement('p'); content.textContent = item;
            section.append(title, content); section.open = index === 0; body.append(section); return;
        }
        const row = document.createElement('div'); row.className = 'crud-dialog-item';
        const label = document.createElement('span'); label.textContent = item.name ?? item; row.append(label);
        if (remove) {
            const control = document.createElement('button'); control.type = 'button'; control.className = 'crud-icon-button';
            control.setAttribute('aria-label', copy().unselect); control.innerHTML = icon('x');
            control.onclick = () => { remove(item.id); row.remove(); heading.textContent = copy().selected.replace(':count', body.querySelectorAll('.crud-dialog-item').length); }; row.append(control);
        }
        body.append(row);
    });
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
