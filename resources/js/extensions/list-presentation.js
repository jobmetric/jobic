import {copy, escape, icon, showDialog} from './shared';
import {pageData} from '../common/page';

export const flattenRows = rows => rows.flatMap(row => [row, ...flattenRows(row.children || [])]);
export const selectionEntry = row => ({id: row.id, name: row.name, namespace: row.namespace, _version: row._version, status: row.status,
    installed: row.installed, local: row.local, owner: row.owner, blocked: row.blocked || (row.status === 'inactive' && !(row.actions || []).includes('activate')), state_reason: row.state_reason || Object.values(row.action_reasons || {}).join(' '),
    actions: row.actions || [], childCount: flattenRows(row.children || []).length});
export function statusMarkup(row) {
    return `<span class="crud-status ${row.status === 'active' ? 'is-active' : ''}">${escape(copy()[row.status])}</span>${row.state_reason ? `<small class="extension-state-reason">${escape(row.state_reason)}</small>` : ''}`;
}
export function actionsMarkup(row, isPlugins) {
    const access = pageData().extensionAccess;
    const operations = isPlugins ? (row.status === 'active' ? ['deactivate'] : ['activate', 'delete']) : (row.actions || []);
    let menu = operations.filter(op => isPlugins || (access[op] && !(op === 'delete' && row.installed && !access.uninstall)))
        .map(op => `<button type="button" data-operation="${op}" data-id="${escape(row.id)}">${escape(copy()[op === 'delete' && isPlugins ? 'delete_plugin' : op])}</button>`).join('');
    if (isPlugins) return `<div class="crud-row-actions"><a class="crud-icon-button" href="${escape(row.edit_url)}" aria-label="${escape(copy().edit)}">${icon('edit')}</a><details class="crud-dropdown"><summary class="crud-icon-button" aria-label="${escape(copy().more)}">${icon('more-horizontal')}</summary><div class="crud-dropdown__menu">${menu}</div></details></div>`;
    if (row.status === 'active' && row.plugins_url && access.plugins) menu += `<a href="${escape(row.plugins_url)}">${escape(copy()[row.multiple ? 'manage_instances' : 'edit'])}</a>`;
    if (row.installed && row.local && !row.owner && access.upgrade) menu += `<a href="${escape(pageData().extensionRoutes.upgradeForm)}?namespace=${encodeURIComponent(row.namespace)}">${escape(copy().upgrade)}</a>`;
    menu += `<button type="button" data-details="${escape(row.id)}">${escape(copy().view)}</button>`;
    return `<div class="crud-row-actions">${row.status === 'active' && row.plugins_url && access.plugins ? `<a class="crud-icon-button" href="${escape(row.plugins_url)}" aria-label="${escape(copy()[row.multiple ? 'manage_instances' : 'edit'])}" data-tooltip="${escape(copy()[row.multiple ? 'manage_instances' : 'edit'])}">${icon('settings')}</a>` : ''}<details class="crud-dropdown"><summary class="crud-icon-button" aria-label="${escape(copy().more)}">${icon('more-horizontal')}</summary><div class="crud-dropdown__menu">${menu}</div></details></div>`;
}
export function nameMarkup(row, expanded = false) {
    const count = flattenRows(row.children || []).length;
    return `<div class="extension-tree-name">${count ? `<button class="crud-icon-button" type="button" data-toggle-children="${escape(row.id)}" aria-expanded="${expanded}" aria-label="${escape(copy()[expanded ? 'hide_children' : 'show_children'])}">${icon(expanded ? 'chevron-down' : 'chevron-right')}</button>` : ''}<span>${escape(row.name)}${count ? `<small>${escape(copy().child_extensions)} · ${count}</small>` : ''}${row.context_only ? `<small>${escape(copy().parent_context)}</small>` : ''}</span></div>`;
}
export function childrenMarkup(row) {
    const render = (entries, depth = 0) => entries.map(child => `<div class="extension-child-row" data-child-id="${escape(child.id)}" style="--child-depth:${Math.min(depth, 4)}">
        <div class="extension-child-select">${child.context_only ? '' : `<input type="checkbox" data-row-id="${escape(child.id)}" aria-label="${escape(copy().select_row)}">`}</div>
        <span class="extension-child-symbol">${icon({Component:'box',Module:'apps',Theme:'layout',Plugin:'code'}[child.type] || 'box')}</span>
        <div class="extension-child-name"><strong>${escape(child.name)}</strong><small>${escape(child.type_label)} <span aria-hidden="true">·</span> <bdi>${escape(child.version)}</bdi>${child.plugins_url ? ` <span aria-hidden="true">·</span> ${Number(child.plugins_count)} ${escape(copy().plugins_count)}` : ''}</small></div>
        <div class="extension-child-state">${statusMarkup(child)}</div>
        ${actionsMarkup(child, false)}
        </div>${render(child.children || [], depth + 1)}`).join('');
    return `<div class="extension-children"><header class="extension-children__header"><span>${icon('box')}${escape(copy().child_extensions)}</span><small>${flattenRows(row.children || []).length}</small></header><div class="extension-children__items">${render(row.children || [])}</div></div>`;
}
export function bulkAvailability(entries, isPlugins) {
    if (!entries.length) return [];
    const active = entries.every(row => row.status === 'active');
    const inactive = entries.every(row => row.status === 'inactive');
    const available = entries.every(row => row.status === 'available');
    if (isPlugins) return active ? ['deactivate'] : inactive ? ['activate', 'delete'] : [];
    const access = pageData().extensionAccess;
    const allowed = op => entries.every(row => row.actions.includes(op));
    return [
        active && access.deactivate && allowed('deactivate') && 'deactivate',
        inactive && access.activate && allowed('activate') && 'activate',
        (inactive || available) && access.delete && access.uninstall && allowed('delete') && entries.every(row => row.local && !row.owner) && 'delete',
    ].filter(Boolean);
}
export function showSelection(entries, remove) {
    const states = new Set(entries.map(row => row.status));
    let remaining = [...entries];
    return showDialog({title: copy().selected.replace(':count', entries.length),
        description: states.size > 1 ? copy().selection_mixed_hint : entries.some(row => row.blocked) ? copy().selection_blocked_hint : copy().selection_hint,
        items: [...entries].sort((a,b)=>a.status.localeCompare(b.status)||a.name.localeCompare(b.name)).map(row => ({...row,
            group: copy()[row.status] || copy().selection_unavailable,
            note: [row.state_reason, row.owner && copy().owner + ': ' + row.owner.split('\\').at(-1), row.childCount && copy().child_extensions + ': ' + row.childCount].filter(Boolean).join(' · ')})), remove: id => { remove(id); remaining = remaining.filter(row => row.id !== id); return new Set(remaining.map(row => row.status)).size > 1 ? copy().selection_mixed_hint : remaining.some(row => row.blocked) ? copy().selection_blocked_hint : copy().selection_hint; }});
}
export function showExtensionDetails(row) {
    const content = document.createElement('div'); content.className = 'extension-details';
    const summary = document.createElement('div'); summary.className = 'extension-details__hero';
    const symbol = document.createElement('span'); symbol.className = 'extension-details__symbol'; symbol.innerHTML = icon({Component:'box',Module:'apps',Theme:'layout',Plugin:'code'}[row.type] || 'box');
    const tags = document.createElement('div'); tags.className = 'extension-details__tags';
    [row.type_label, row.version, copy()[row.status], copy()[row.local ? 'filter_source_local' : 'filter_source_composer']].forEach(value => {const tag=document.createElement('span');tag.textContent=value;tags.append(tag);});
    summary.append(symbol,tags);content.append(summary);
    if(row.state_reason) {const reason=document.createElement('p');reason.className='extension-details__notice';reason.textContent=row.state_reason;content.append(reason);}
    const facts=document.createElement('dl');facts.className='extension-details__facts';
    const metadata=row.manifest || {};
    const values={identity:row.namespace,installed_version:row.installed_version || '—',review_mode:copy()[row.multiple?'multiple':'single'],configured_state:copy()[row.configured_active?'active':'inactive'],author:metadata.author || '—',license:metadata.license || '—',priority:metadata.priority ?? 0,plugins_count:row.plugins_count};
    if(row.owner) values.owner=row.owner;
    Object.entries(values).forEach(([key,value])=>{const field=document.createElement('div'),label=document.createElement('dt'),text=document.createElement('dd');label.textContent=copy()[key];text.textContent=String(value);if(['identity','owner'].includes(key)){field.className='extension-details__identity';text.dir='ltr';}field.append(label,text);facts.append(field);});content.append(facts);
    const list=(label,items)=>{const section=document.createElement('section'),heading=document.createElement('h3'),ul=document.createElement('ul');heading.textContent=label;items.forEach(value=>{const li=document.createElement('li');li.textContent=value;ul.append(li);});section.append(heading,ul);content.append(section);};
    list(copy().depends,row.depends.length?row.depends:[copy().no_dependencies]);
    if(metadata.children?.length) list(copy().child_extensions,metadata.children.map(child=>`${child.type} · ${child.name}`));
    if(metadata.keywords?.length) list(copy().keywords,metadata.keywords);
    Object.values(row.action_reasons || {}).filter((value,index,array)=>value!==row.state_reason&&array.indexOf(value)===index).forEach(reason=>{const p=document.createElement('p');p.className='extension-details__notice';p.textContent=reason;content.append(p);});
    const website=metadata.homepage || metadata.website;
    if(website) {const link=document.createElement('a');link.href=website;link.textContent=copy().homepage;link.target='_blank';link.rel='noopener noreferrer';content.append(link);}
    const raw=document.createElement('details'),heading=document.createElement('summary'),hint=document.createElement('p'),pre=document.createElement('pre');heading.textContent=copy().manifest_public;hint.textContent=copy().manifest_public_hint;pre.textContent=JSON.stringify(metadata,null,2);raw.append(heading,hint,pre);content.append(raw);
    return showDialog({title:row.name,description:row.description,content,wide:true,symbol:'box'});
}
