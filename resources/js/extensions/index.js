import { toolbar, copy, escape, icon, showDialog } from './shared';
import { loadingButton, pageData } from '../common/page';
import { sendSettings } from '../admin/settings';
import { announce } from '../common/toast';
import { enhanceSelects } from '../common/selects';

function initialize() {
    const $ = window.jQuery;
    const routes = pageData().extensionRoutes;
    const isPlugins = Boolean(pageData().pluginContext);
    const selected = new Map();
    const empty = document.querySelector('[data-table-empty]');
    const card = document.querySelector('[data-table-card]');
    const footer = document.querySelector('[data-table-footer]');
    const filterDialog = document.querySelector('[data-filter-dialog]');
    const filterForm = document.querySelector('[data-filter-form]');
    const selectAll = document.querySelector('[data-select-all]');
    const selectionButton = document.querySelector('[data-show-selected]');
    let pending = false;
    let loading = false;
    let controller;
    let rows = [];
    let searchTimer;
    let restoreFilterButton;
    toolbar('list');

    function lock() {
        const busy = pending || loading;
        card.setAttribute('aria-busy', String(busy));
        document.querySelector('[data-table-loading]').hidden = !busy;
        document.querySelector('.crud-table-scroll').inert = busy;
        document.querySelectorAll('[data-list-controls]').forEach(node => { node.inert = busy; });
        filterForm.inert = busy;
    }
    function selection() {
        selectionButton.hidden = selected.size === 0;
        selectionButton.innerHTML = `${icon('check')}${escape(copy().selected.replace(':count', selected.size))}`;
        const checks = [...document.querySelectorAll('[data-row-id]')];
        checks.forEach(node => { node.checked = selected.has(node.dataset.rowId); });
        selectAll.checked = checks.length > 0 && checks.every(node => node.checked);
        selectAll.indeterminate = checks.some(node => node.checked) && !selectAll.checked;
    }
    function select(id) {
        if (selected.size >= 100 && !selected.has(id)) { announce(copy().selection_limit, true); return; }
        const row = rows.find(item => item.id === id);
        if (row) selected.set(id, {id, name: row.name, _version: row._version});
    }
    $.fn.dataTable.ext.errMode = 'none';
    const table = $('#extension-table').DataTable({
        serverSide: true, processing: false, searching: false,
        order: [[1, 'asc']], pageLength: 10, lengthMenu: [10, 25, 50, 100],
        dom: 'rt<"crud-table-footer"lip>',
        language: {
            ...pageData().language?.datatable,
            emptyTable: '', zeroRecords: '', info: '', infoEmpty: '',
            lengthMenu: `${copy().length} _MENU_`,
            paginate: {previous: copy().previous, next: copy().next},
        },
        ajax: async (request, callback) => {
            controller?.abort();
            const current = new AbortController(); controller = current;
            loading = true; lock();
            document.querySelector('[data-table-error]').hidden = true;
            empty.hidden = true;
            const params = new URLSearchParams({
                draw: request.draw, start: request.start, length: request.length,
                'search[value]': document.querySelector('[data-search]').value,
                'order[0][column]': request.order[0]?.column || 1,
                'order[0][dir]': request.order[0]?.dir || 'asc',
            });
            new FormData(filterForm).forEach((value, key) => { if (value !== '') params.set(key, value); });
            try {
                const response = await fetch(`${routes.data}?${params}`, {headers: {Accept: 'application/json'}, signal: current.signal});
                if (!response.ok) throw new Error();
                const result = await response.json();
                if (!result.ok) throw new Error();
                rows = result.data.data;
                rows.forEach(row => { if (selected.has(row.id)) selected.set(row.id, {id: row.id, name: row.name, _version: row._version}); });
                callback(result.data);
                document.querySelectorAll('[data-extension-stat]').forEach((node, index) => {
                    node.textContent = new Intl.NumberFormat(document.documentElement.lang).format(result.data.stats[index]);
                });
                empty.hidden = result.data.recordsFiltered !== 0;
                card.append(empty);
            } catch (error) {
                if (error.name === 'AbortError') return;
                rows = [];
                callback({draw: request.draw, data: [], recordsTotal: 0, recordsFiltered: 0});
                document.querySelector('[data-table-error]').hidden = false;
                announce(copy().failed, true);
            } finally {
                if (controller === current) {
                    loading = false; lock(); selection(); enhanceSelects(footer);
                    restoreFilterButton?.(); restoreFilterButton = undefined;
                }
            }
        },
        columns: isPlugins ? [
            {data: 'id', orderable: false, render: id => `<input type="checkbox" data-row-id="${escape(id)}" aria-label="${escape(copy().select_row)}">`},
            {data: 'name', render: $.fn.dataTable.render.text()},
            {data: 'status', render: value => `<span class="crud-status ${value === 'active' ? 'is-active' : ''}">${escape(copy()[value])}</span>`},
            {data: 'created_at_display', render: $.fn.dataTable.render.text()},
            {data: 'updated_at_display', render: $.fn.dataTable.render.text()},
            {data: null, orderable: false, render: row => `<div class="crud-row-actions"><a class="crud-icon-button" href="${escape(row.edit_url)}" aria-label="${escape(copy().edit)}">${icon('edit')}</a><details class="crud-dropdown"><summary class="crud-icon-button" aria-label="${escape(copy().more)}">${icon('more-horizontal')}</summary><div class="crud-dropdown__menu">${[row.status === 'active' ? 'deactivate' : 'activate', 'delete'].map(op => `<button type="button" data-operation="${op}" data-id="${escape(row.id)}">${escape(copy()[op === 'delete' ? 'delete_plugin' : op])}</button>`).join('')}</div></details></div>`},
        ] : [
            {data: 'id', orderable: false, render: id => `<input type="checkbox" data-row-id="${escape(id)}" aria-label="${escape(copy().select_row)}">`},
            {data: 'name', render: $.fn.dataTable.render.text()},
            {data: 'type_label', render: $.fn.dataTable.render.text()},
            {data: 'version', render: $.fn.dataTable.render.text()},
            {data: 'status', render: value => `<span class="crud-status ${value === 'active' ? 'is-active' : ''}">${escape(copy()[value])}</span>`},
            {data: 'plugins_count', render: value => Number(value)},
            {data: null, orderable: false, render: row => {
                const access = pageData().extensionAccess;
                const operations = row.installed ? (row.status === 'active' ? ['deactivate'] : ['activate', 'uninstall']) : ['install'];
                if (row.owner) operations.splice(0, operations.length, ...(row.installed ? [row.status === 'active' ? 'deactivate' : 'activate'] : []));
                if (row.status === 'missing') operations.splice(0, operations.length);
                if (!row.installed && row.local && !row.owner) operations.push('delete');
                let html = operations.filter(op => access[op]).map(op => `<button type="button" data-operation="${op}" data-id="${escape(row.id)}">${escape(copy()[op])}</button>`).join('');
                if (row.installed && access.plugins && row.status !== 'missing') html += `<a href="${escape(row.plugins_url)}">${escape(copy().plugins)}</a>`;
                if (row.installed && row.local && !row.owner && access.upgrade) html += `<a href="${escape(routes.upgradeForm)}?namespace=${encodeURIComponent(row.namespace)}">${escape(copy().upgrade)}</a>`;
                html += `<button type="button" data-details="${escape(row.id)}">${escape(copy().view)}</button>`;
                return `<div class="crud-row-actions">${row.installed && access.plugins && row.status !== 'missing' ? `<a class="crud-icon-button" href="${escape(row.plugins_url)}" aria-label="${escape(copy().plugins)}" data-tooltip="${escape(copy().plugins)}">${icon('edit')}</a>` : ''}<details class="crud-dropdown"><summary class="crud-icon-button" aria-label="${escape(copy().more)}">${icon('more-horizontal')}</summary><div class="crud-dropdown__menu">${html}</div></details></div>`;
            }},
        ],
        drawCallback() {
            const info = this.api().page.info();
            const controls = document.querySelector('#extension-table_wrapper .crud-table-footer');
            if (controls) footer.append(controls);
            document.querySelector('#extension-table_info').textContent = copy().page.replace(':page', info.pages ? info.page + 1 : 0).replace(':pages', info.pages);
            selection();
        },
    });

    document.querySelector('#extension-table').addEventListener('change', event => {
        if (!event.target.matches('[data-row-id]') || loading || pending) return;
        const id = event.target.dataset.rowId;
        event.target.checked ? select(id) : selected.delete(id);
        selection();
    });
    selectAll.addEventListener('change', () => {
        document.querySelectorAll('[data-row-id]').forEach(node => {
            const id = node.dataset.rowId;
            selectAll.checked ? select(id) : selected.delete(id);
        });
        selection();
    });
    document.querySelector('[data-search]').addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => table.ajax.reload(), 300);
    });
    document.querySelector('[data-open-filters]').onclick = () => { filterDialog.showModal(); enhanceSelects(filterDialog); };
    document.querySelector('[data-close-filters]').onclick = () => filterDialog.close();
    filterForm.addEventListener('submit', event => {
        event.preventDefault();
        if (loading || pending) return;
        restoreFilterButton = loadingButton(filterForm.querySelector('[type=submit]'));
        filterDialog.close(); table.ajax.reload();
    });
    filterForm.addEventListener('reset', () => setTimeout(() => {
        $(filterForm).find('select').trigger('change.select2');
        filterDialog.close(); table.ajax.reload();
    }, 0));
    document.querySelector('[data-retry]').onclick = () => table.ajax.reload();
    selectionButton.onclick = () => showDialog({
        title: copy().selected.replace(':count', selected.size), description: copy().selection_hint,
        items: [...selected.values()], remove: id => { selected.delete(id); selection(); },
    });
    async function mutate(row, operation, button) {
        if (pending || loading) return;
        const confirmed = await showDialog({
            title: copy()[operation], description: ['uninstall', 'delete'].includes(operation) ? copy().destructive_hint : copy().operation_hint,
            items: [{name: row.name}], confirm: copy().confirm_operation,
        });
        if (!confirmed || pending || loading) return;
        pending = true; const restore = loadingButton(button); lock();
        try {
            const data = new FormData();
            if (!isPlugins) data.set('namespace', row.namespace);
            data.set('_version', row._version); data.set('confirm', '1');
            const result = await sendSettings(isPlugins ? row.commands[operation] : routes[operation], data);
            selected.delete(row.id); announce(result.message); table.ajax.reload(null, false);
        } catch (error) { announce(error.status ? error.message : copy().failed, true); }
        finally { restore(); pending = false; lock(); selection(); }
    }
    async function bulk(operation, button) {
        if (pending || loading) return;
        if (!selected.size) { announce(copy().selection_required, true); return; }
        const entries = [...selected.values()];
        if (!await showDialog({title: copy().batch_title.replace(':operation', copy()[operation]), description: copy().batch_description.replace(':count', entries.length), items: entries, confirm: copy().confirm_operation})) return;
        if (pending || loading) return;
        pending = true; const restore = loadingButton(button); lock();
        try {
            const data = new FormData(); data.set('operation', operation); data.set('confirm', '1');
            entries.forEach((entry, index) => { data.set(`entries[${index}][id]`, entry.id); data.set(`entries[${index}][_version]`, entry._version); });
            const result = await sendSettings(routes.bulk, data);
            selected.clear(); announce(result.message); table.ajax.reload(null, false);
        } catch (error) { announce(error.message || copy().failed, true); }
        finally { restore(); pending = false; lock(); selection(); }
    }
    document.querySelector('[data-crud-toolbar]').addEventListener('click', event => {
        const action = event.target.closest('[data-crud-action]')?.dataset.crudAction;
        if (action === 'help') showDialog({title: copy().help_title, description: copy().help_intro, items: copy().help_items, accordion: true});
        else if (isPlugins && ['activate', 'deactivate', 'delete'].includes(action)) bulk(action, event.target.closest('[data-crud-action]'));
    });
    document.querySelector('#extension-table').addEventListener('click', event => {
        const button = event.target.closest('[data-operation],[data-details]');
        if (!button) return;
        const row = rows.find(item => item.id === (button.dataset.id || button.dataset.details));
        if (!row) return;
        if (button.dataset.operation) mutate(row, button.dataset.operation, button);
        else showDialog({title: row.name, description: row.description, items: [
            {name: `${copy().version}: ${row.version}`}, {name: `${copy().installed_version}: ${row.installed_version || '—'}`},
            {name: `${copy().depends}: ${row.depends.join(', ') || '—'}`},
            ...(row.owner ? [{name: `${copy().owner}: ${row.owner}`}] : []),
        ]});
    });
    new MutationObserver(() => {
        toolbar('list');
        const settings = table.settings()[0];
        settings.oLanguage.oPaginate.sNext = copy().next;
        settings.oLanguage.oPaginate.sPrevious = copy().previous;
        (isPlugins ? ['name', 'status', 'created_at', 'updated_at', 'actions'] : ['name', 'type', 'version', 'status', 'plugins_count', 'actions']).forEach((key, index) => {
            document.querySelectorAll('#extension-table thead th')[index + 1].textContent = copy()[key];
        });
        const label = document.querySelector('#extension-table_length label');
        if (label?.firstChild?.nodeType === Node.TEXT_NODE) label.firstChild.textContent = `${copy().length} `;
        table.ajax.reload(null, false);
    }).observe(document.documentElement, {attributes: true, attributeFilter: ['lang']});
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
else initialize();
