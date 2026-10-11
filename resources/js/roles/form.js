import { toolbar, copy, showDialog } from './shared';
import { loadingButton, pageData } from '../common/page';
import { sendSettings } from '../admin/settings';
import { announce } from '../common/toast';
import { guardUnsaved } from '../common/unsaved';
import { enhanceSelects } from '../common/selects';
import { bindHistory } from './history';

toolbar('form');
const form = document.querySelector('[data-role-form]');
const readonly = form.hasAttribute('data-readonly');
form.noValidate = true;

document.querySelectorAll('[data-role-tab]').forEach(tab => tab.addEventListener('click', () => {
    document.querySelectorAll('[data-role-tab]').forEach(item => item.setAttribute('aria-selected', String(item === tab)));
    document.querySelectorAll('[data-role-panel]').forEach(panel => { panel.hidden = panel.dataset.rolePanel !== tab.dataset.roleTab; });
}));

function updateInherited(tree) {
    function visit(list, inherited) {
        [...list.children].filter(node => node.matches('[data-permission-node], [data-permission-group]')).forEach(node => {
            const checkbox = node.querySelector(':scope > .role-permission-row input');
            if (checkbox) {
                checkbox.classList.toggle('is-inherited', inherited);
                checkbox.disabled = readonly || inherited;
                if (inherited) checkbox.checked = true;
            }
            const children = node.querySelector(':scope > ul');
            if (children) visit(children, inherited || (checkbox?.checked ?? false));
        });
    }
    visit(tree, false);
}

document.querySelectorAll('[data-permission-tree]').forEach(tree => {
    updateInherited(tree);
    tree.addEventListener('change', event => {
        if (!event.target.matches('input[type=checkbox]')) return;
        if (!event.target.checked) {
            event.target.closest('[data-permission-node]')?.querySelectorAll(':scope ul input[type=checkbox]').forEach(child => {
                child.checked = false; child.disabled = readonly; child.classList.remove('is-inherited');
            });
        }
        document.querySelectorAll('[data-permission-tree]').forEach(updateInherited);
    });
});

const ruleList = document.querySelector('[data-rule-list]');
let drivers = pageData().roleForm.drivers;
let rules = JSON.parse(ruleList.dataset.existingRules || '[]');

function syncRulesFromDom() {
    rules = [...ruleList.querySelectorAll('.role-rule-card')].map(card => {
        const driver = card.querySelector('select[name$="[driver]"]')?.value || '';
        const payload = {};
        card.querySelectorAll('[name*="[payload]"]').forEach(control => { const match = control.name.match(/\[payload\]\[([^\]]+)\]/); if (match) payload[match[1]] = control.multiple ? [...control.selectedOptions].map(option => option.value) : control.value; });
        return {driver, payload};
    });
}

function fieldsFor(driver) {
    return (drivers[driver]?.form?.tabs || []).flatMap(tab => tab.fields || []).map(field => field.data).filter(Boolean);
}

function renderRules() {
    ruleList.replaceChildren();
    if (!rules.length) { const empty = document.createElement('p'); empty.className = 'crud-hint role-rules-empty'; empty.textContent = copy().no_rules; ruleList.append(empty); }
    rules.forEach((rule, index) => {
        const card = document.createElement('article'); card.className = 'role-rule-card';
        const header = document.createElement('header'); const selectLabel = document.createElement('label'); selectLabel.textContent = copy().rule_driver;
        const select = document.createElement('select'); select.name = `rules[${index}][driver]`; select.disabled = readonly;
        const placeholder = document.createElement('option'); placeholder.value = ''; placeholder.textContent = copy().choose_driver; select.append(placeholder);
        Object.keys(drivers).forEach(name => { const option = document.createElement('option'); option.value = name; option.textContent = drivers[name].label || name.replaceAll('_', ' '); option.selected = rule.driver === name; select.append(option); });
        selectLabel.append(select); header.append(selectLabel);
        if (!readonly) { const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'crud-icon-button'; remove.setAttribute('aria-label', copy().remove_rule); remove.textContent = '×'; remove.onclick = () => { syncRulesFromDom(); rules.splice(index, 1); renderRules(); }; header.append(remove); }
        card.append(header);
        if (rule.driver && drivers[rule.driver]?.description) { const description = document.createElement('p'); description.className = 'role-rule-description'; description.textContent = drivers[rule.driver].description; card.append(description); }
        const fields = document.createElement('div'); fields.className = 'role-rule-fields';
        fieldsFor(rule.driver).forEach(field => {
            const label = document.createElement('label'); label.textContent = field.label || field.name;
            let control;
            if (field.type === 'select') {
                control = document.createElement('select'); control.multiple = Boolean(field.params?.multiple || field.properties?.includes('multiple')); const selected = [].concat(rule.payload?.[field.name] ?? []).map(String); (field.options || []).forEach(item => { const option = document.createElement('option'); option.value = item.value; option.textContent = item.label; option.selected = selected.includes(String(item.value)); control.append(option); });
            } else { control = document.createElement('input'); control.type = ['number','time'].includes(field.type) ? field.type : 'text'; control.value = rule.payload?.[field.name] ?? ''; control.placeholder = field.placeholder || ''; }
            control.name = `rules[${index}][payload][${field.name}]${control.multiple ? '[]' : ''}`; control.disabled = readonly; if ((field.validation || '').includes('required')) control.required = true; label.append(control);
            if (field.info) { const info = document.createElement('span'); info.className = 'crud-hint'; info.textContent = field.info; label.append(info); }
            fields.append(label);
        });
        card.append(fields); select.onchange = () => { rules[index] = {driver: select.value, payload: {}}; renderRules(); }; ruleList.append(card);
    });
    enhanceSelects(ruleList);
}

document.querySelector('[data-add-rule]')?.addEventListener('click', () => { syncRulesFromDom(); rules.push({driver: '', payload: {}}); renderRules(); });
renderRules();

const historyPanel = bindHistory(form);
const guard = guardUnsaved(form, () => showDialog({title: copy().unsaved_title, description: copy().unsaved_body, confirm: copy().leave}));
form.addEventListener('submit', async event => {
    event.preventDefault(); if (form.dataset.saving || readonly) return;
    const intent = event.submitter?.value || 'save';
    document.querySelectorAll('[data-permission-tree]').forEach(tree => tree.querySelectorAll('.is-inherited').forEach(input => { input.disabled = true; }));
    const data = new FormData(form); const submitted = guard.snapshot();
    form.querySelectorAll('[data-error-for]').forEach(node => { node.textContent = ''; }); form.querySelectorAll('[aria-invalid]').forEach(node => node.removeAttribute('aria-invalid'));
    const buttons = [...form.querySelectorAll('button[type=submit]')]; const restore = buttons.map(loadingButton); form.dataset.saving = 'true'; form.setAttribute('aria-busy', 'true'); form.querySelector('.crud-split-save')?.setAttribute('inert', '');
    try {
        const result = await sendSettings(form.action, data); let version = form.querySelector('[name=_version]');
        if (!version) { version = document.createElement('input'); version.type = 'hidden'; version.name = '_version'; form.append(version); }
        version.value = result.data.version; form.action = result.data.save_url;
        if (!form.querySelector('[name=_method]')) { const method = document.createElement('input'); method.type = 'hidden'; method.name = '_method'; method.value = 'PUT'; form.append(method); }
        history.replaceState({}, '', result.data.edit_url); form.querySelector('h1').textContent = copy().edit_title; guard.saved(submitted); historyPanel.refresh(); announce(result.message);
        if (intent !== 'save') guard.leave(); if (intent === 'close') location.assign(pageData().roleRoutes.index); else if (intent === 'new') location.assign(`${pageData().roleRoutes.index}/create`);
    } catch (error) {
        Object.entries(error.errors || {}).forEach(([name, messages]) => { const field = form.elements.namedItem(name); const message = form.querySelector(`[data-error-for="${CSS.escape(name.split('.')[0])}"]`); if (field) field.setAttribute('aria-invalid', 'true'); if (message) message.textContent = [].concat(messages).join(' '); });
        form.querySelector('[aria-invalid=true]')?.focus(); announce(error.status ? error.message : copy().failed, true);
    } finally { restore.forEach(fn => fn()); form.querySelector('.crud-split-save')?.removeAttribute('inert'); delete form.dataset.saving; form.removeAttribute('aria-busy'); document.querySelectorAll('[data-permission-tree]').forEach(updateInherited); }
});
// Locale changes are AJAX-based; rebuild dynamic controls after the new Domi registry is installed.
window.addEventListener('cms:presentation-refreshed', () => {
    syncRulesFromDom();
    drivers = pageData().roleForm.drivers;
    toolbar('form');
    renderRules();
    historyPanel.refresh();
});
