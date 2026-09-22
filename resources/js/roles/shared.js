import { pageData } from '../common/page';
import '../../css/crud.css';
import '../../css/crud-fields.css';
import '../../css/roles.css';

export const copy = () => pageData().roles;
export const escape = value => { const node = document.createElement('span'); node.textContent = String(value ?? ''); return node.innerHTML; };
export const icon = name => `<svg class="ui-icon" width="18" height="18" fill="none" viewBox="0 0 24 24" aria-hidden="true"><use href="#icon-${name}"></use></svg>`;

function button(key, type = 'button') {
    const node = document.createElement('button'); node.type = type; node.dataset.crudAction = key; node.textContent = copy()[key];
    if (type === 'submit') { node.name = 'intent'; node.value = key; }
    return node;
}

function dropdown(label, items, submit = false) {
    const details = document.createElement('details'); details.className = 'crud-dropdown';
    const toggle = document.createElement('summary'); toggle.className = 'vuero-button'; toggle.setAttribute('aria-label', copy()[label]); toggle.innerHTML = `${submit ? '' : escape(copy()[label])}${icon('chevron-down')}`;
    const menu = document.createElement('div'); menu.className = 'crud-dropdown__menu'; items.forEach(key => menu.append(button(key, submit ? 'submit' : 'button')));
    details.append(toggle, menu); return details;
}

export function toolbar(kind) {
    const root = document.querySelector('[data-crud-toolbar]'); root.replaceChildren(); const routes = pageData().roleRoutes;
    if (kind === 'list') {
        if (pageData().roleAccess?.manage) { const add = document.createElement('a'); add.href = `${routes.index}/create`; add.className = 'vuero-button is-primary'; add.dataset.crudAction = 'create'; add.innerHTML = `${icon('plus')}${escape(copy().create)}`; root.append(add); }
        root.append(dropdown('actions', pageData().roleAccess?.manage ? pageData().roleToolbar.list : ['help'])); return;
    }
    const cancel = document.createElement('a'); cancel.href = routes.index; cancel.className = 'vuero-button crud-cancel'; cancel.dataset.crudAction = 'cancel'; cancel.textContent = copy().cancel; root.append(cancel);
    if (document.querySelector('[data-readonly]')) {
        if (!pageData().roleAccess?.manage) return;
        const edit = document.createElement('a'); edit.href = document.querySelector('[data-role-form]').action + '/edit'; edit.className = 'vuero-button is-primary'; edit.innerHTML = `${icon('edit')}${escape(copy().edit)}`; root.append(edit); return;
    }
    const group = document.createElement('div'); group.className = 'crud-split-save'; const save = button('save', 'submit'); save.className = 'vuero-button is-primary'; group.append(save, dropdown('save_options', pageData().roleToolbar.form, true)); root.append(group);
}

export function showDialog({title, description, items = [], confirm = null, remove = null, accordion = false}) {
    const dialog = document.querySelector('[data-crud-dialog]'); const body = dialog.querySelector('[data-dialog-body]'); dialog.querySelector('[data-dialog-title]').textContent = title; body.replaceChildren();
    const intro = document.createElement('p'); intro.textContent = description; body.append(intro);
    items.forEach((item, index) => {
        if (accordion) { const section = document.createElement('details'); section.className = 'crud-help-section'; const summary = document.createElement('summary'); summary.textContent = Object.values(pageData().crudHelp)[index]; const text = document.createElement('p'); text.textContent = item; section.append(summary, text); section.open = index === 0; body.append(section); return; }
        const row = document.createElement('div'); row.className = 'crud-dialog-item'; const label = document.createElement('span'); label.textContent = item.name ?? item; row.append(label);
        if (remove) { const control = document.createElement('button'); control.type = 'button'; control.className = 'crud-icon-button'; control.innerHTML = icon('x'); control.onclick = () => { remove(item.id); row.remove(); }; row.append(control); }
        body.append(row);
    });
    const accept = dialog.querySelector('[data-dialog-confirm]'); accept.hidden = !confirm; accept.textContent = confirm || ''; dialog.querySelector('[data-dialog-cancel]').textContent = confirm ? copy().cancel : copy().dismiss;
    return new Promise(resolve => { let accepted = false; accept.onclick = () => { accepted = true; dialog.close(); }; dialog.querySelectorAll('[data-dialog-close],[data-dialog-cancel]').forEach(node => { node.onclick = () => dialog.close(); }); dialog.addEventListener('close', () => resolve(accepted), {once: true}); dialog.showModal(); });
}

document.addEventListener('click', event => document.querySelectorAll('.crud-dropdown[open]').forEach(menu => { if (!menu.contains(event.target) || event.target.closest('button,a')) menu.open = false; }));
document.addEventListener('keydown', event => { if (event.key === 'Escape') document.querySelectorAll('.crud-dropdown[open]').forEach(menu => { menu.open = false; }); });
