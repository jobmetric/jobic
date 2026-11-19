import '../../css/extensions-list.css';
import {flattenRows, selectionEntry, actionsMarkup, statusMarkup, nameMarkup, childrenMarkup, bulkAvailability, showSelection, showExtensionDetails} from './list-presentation';
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
    const expanded = new Set();
    const initialExpanded = new URLSearchParams(location.search).get('expanded');
    if (/^[a-f0-9]{64}$/.test(initialExpanded || '')) expanded.add(initialExpanded);
    const empty = document.querySelector('[data-table-empty]');
    const card = document.querySelector('[data-table-card]');
    const footer = document.querySelector('[data-table-footer]');
    const filterDialog = document.querySelector('[data-filter-dialog]');
    const filterForm = document.querySelector('[data-filter-form]');
    const selectAll = document.querySelector('[data-select-all]');
    const selectionButton = document.querySelector('[data-show-selected]');
    const searchInput = document.querySelector('[data-search]');
    const quickFilters = document.querySelector('[data-quick-filters]');
    const query = new URLSearchParams(location.search);
    const quickValues = {
        all: {}, active: {status: 'active'}, inactive: {status: 'inactive'},
        available: {installation: 'available'}, updates: {updates: 'available'},
    };
    let canCreate;
    let pending = false;
    let loading = false;
    let controller;
    let rows = [];
    let searchTimer;
    let restoreFilterButton;
    const filterNames = [...filterForm.elements].map(field => field.name).filter(Boolean);

    function applyQuickFilter(name) {
        const values = quickValues[name] || quickValues.all;
        ['status', 'installation', 'updates'].forEach(key => {
            const field = filterForm.elements.namedItem(key);
            if (field) field.value = values[key] || '';
        });
        quickFilters?.querySelectorAll('[data-quick-filter]').forEach(button => {
            button.setAttribute('aria-pressed', String(button.dataset.quickFilter === name));
        });
    }
    function currentQuickFilter() {
        return Object.entries(quickValues).find(([, values]) => ['status', 'installation', 'updates']
            .every(key => (filterForm.elements.namedItem(key)?.value || '') === (values[key] || '')))?.[0] || '';
    }
    function syncUrl() {
        const next = new URL(location.href);
        ['view', 'q', ...filterNames].forEach(key => next.searchParams.delete(key));
        const view = currentQuickFilter();
        if (view && view !== 'all') next.searchParams.set('view', view);
        if (searchInput.value.trim()) next.searchParams.set('q', searchInput.value.trim());
        new FormData(filterForm).forEach((value, key) => {
            if (value !== '' && !['status', 'installation', 'updates'].includes(key)) next.searchParams.set(key, value);
        });
        history.replaceState(null, '', next);
    }
    if (query.get('q')) searchInput.value = query.get('q').slice(0, 100);
    filterNames.forEach(name => {
        const field = filterForm.elements.namedItem(name);
        if (field && query.has(name)) field.value = query.get(name);
    });
    applyQuickFilter(quickValues[query.get('view')] ? query.get('view') : currentQuickFilter() || 'all');
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
        const available = bulkAvailability([...selected.values()], isPlugins);
        document.querySelectorAll('[data-crud-toolbar] [data-crud-action]').forEach(button => {
            if (['activate', 'deactivate', 'delete'].includes(button.dataset.crudAction)) button.hidden = !available.includes(button.dataset.crudAction);
        });
    }
    function select(id) {
        if (selected.size >= 100 && !selected.has(id)) { announce(copy().selection_limit, true); return; }
        const row = rows.find(item => item.id === id);
        if (row && !row.context_only) selected.set(id, selectionEntry(row));
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
                'search[value]': searchInput.value,
                'order[0][column]': request.order[0]?.column || 1,
                'order[0][dir]': request.order[0]?.dir || 'asc',
            });
            new FormData(filterForm).forEach((value, key) => { if (value !== '') params.set(key, value); });
            try {
                const response = await fetch(`${routes.data}?${params}`, {headers: {Accept: 'application/json'}, signal: current.signal});
                if (!response.ok) throw new Error();
                const result = await response.json();
                if (!result.ok) throw new Error();
                rows = flattenRows(result.data.data);
                if (isPlugins && typeof result.data.can_create === 'boolean' && canCreate !== result.data.can_create) {
                    canCreate = result.data.can_create; toolbar('list', {canCreate});
                }
                const paused = document.querySelector('[data-extension-paused]');
                if (paused) paused.hidden = Boolean(result.data.owner_active);
                rows.forEach(row => { if (selected.has(row.id)) selected.set(row.id, selectionEntry(row)); });
                callback(result.data);
                document.querySelectorAll('[data-extension-stat]').forEach((node, index) => {
                    node.textContent = new Intl.NumberFormat(document.documentElement.lang).format(result.data.stats[index]);
                });
                Object.entries(result.data.quick_stats || {}).forEach(([key, value]) => {
                    const node = document.querySelector(`[data-quick-count="${key}"]`);
                    if (node) node.textContent = new Intl.NumberFormat(document.documentElement.lang).format(value);
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
            {data: null, orderable: false, render: row => actionsMarkup(row, true)},
        ] : [
            {data: 'id', orderable: false, render: (id, type, row) => row.context_only ? '' : `<input type="checkbox" data-row-id="${escape(id)}" aria-label="${escape(copy().select_row)}">`},
            {data: 'name', render: (name, type, row) => nameMarkup(row, expanded.has(row.id))},
            {data: 'type_label', render: $.fn.dataTable.render.text()},
            {data: 'version', render: $.fn.dataTable.render.text()},
            {data: 'status', render: (value, type, row) => statusMarkup(row)},
            {data: 'plugins_count', render: value => Number(value)},
            {data: null, orderable: false, render: row => actionsMarkup(row, false)},
        ],
        drawCallback() {
            const info = this.api().page.info();
            const controls = document.querySelector('#extension-table_wrapper .crud-table-footer');
            if (controls) footer.append(controls);
            document.querySelector('#extension-table_info').textContent = copy().page.replace(':page', info.pages ? info.page + 1 : 0).replace(':pages', info.pages);
            if (!isPlugins) this.api().rows({page: 'current'}).every(function () {
                const row = this.data();
                if (expanded.has(row.id) && row.children?.length) this.child(childrenMarkup(row)).show();
            });
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
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => { syncUrl(); table.ajax.reload(); }, 300);
    });
    quickFilters?.addEventListener('click', event => {
        const button = event.target.closest('[data-quick-filter]');
        if (!button || loading || pending) return;
        applyQuickFilter(button.dataset.quickFilter);
        $(filterForm).find('select').trigger('change.select2');
        syncUrl(); table.ajax.reload();
    });
    document.querySelector('[data-open-filters]').onclick = () => { filterDialog.showModal(); enhanceSelects(filterDialog); };
    document.querySelector('[data-close-filters]').onclick = () => filterDialog.close();
    filterForm.addEventListener('submit', event => {
        event.preventDefault();
        if (loading || pending) return;
        restoreFilterButton = loadingButton(filterForm.querySelector('[type=submit]'));
        filterDialog.close(); applyQuickFilter(currentQuickFilter() || 'all'); syncUrl(); table.ajax.reload();
    });
    filterForm.addEventListener('reset', () => setTimeout(() => {
        $(filterForm).find('select').trigger('change.select2');
        applyQuickFilter('all'); searchInput.value = ''; filterDialog.close(); syncUrl(); table.ajax.reload();
    }, 0));
    document.querySelector('[data-retry]').onclick = () => table.ajax.reload();
    selectionButton.onclick = () => showSelection([...selected.values()], id => {selected.delete(id);selection();});
    async function mutate(row, operation, button) {
        if (pending || loading) return;
        if (!isPlugins && operation === 'delete' && row.installed) return bulk('delete', button, [selectionEntry(row)]);
        const confirmed = await showDialog({
            title: copy()[operation], description: ['uninstall', 'delete'].includes(operation) ? copy().destructive_hint : copy().operation_hint,
            items: [{name: row.name, note: ['uninstall', 'delete'].includes(operation) && row.children?.length ? copy().child_extensions + ': ' + flattenRows(row.children).map(child => child.name).join(', ') : ''}], confirm: copy().confirm_operation,
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
    async function bulk(operation, button, override = null) {
        if (pending || loading) return;
        if (!override && !selected.size) { announce(copy().selection_required, true); return; }
        const entries = override || [...selected.values()];
        if (!bulkAvailability(entries, isPlugins).includes(operation)) { showSelection(entries, id => {selected.delete(id);selection();}); return; }
        if (!await showDialog({title: copy().batch_title.replace(':operation', copy()[operation]), description: !isPlugins && operation === 'delete' ? copy().bulk_delete_hint : copy().batch_description.replace(':count', entries.length), items: entries, confirm: copy().confirm_operation})) return;
        if (pending || loading) return;
        pending = true; const restore = loadingButton(button); lock();
        try {
            const data = new FormData(); data.set('operation', operation); data.set('confirm', '1');
            entries.forEach((entry, index) => { data.set(`entries[${index}][${isPlugins ? 'id' : 'namespace'}]`, isPlugins ? entry.id : entry.namespace); data.set(`entries[${index}][_version]`, entry._version); });
            const result = await sendSettings(isPlugins ? routes.bulk : routes.bulkOperations[operation], data);
            selected.clear(); announce(result.message); table.ajax.reload(null, false);
        } catch (error) { (error.data?.completed || []).forEach(id => selected.delete(id)); announce(error.message || copy().failed, true); table.ajax.reload(null, false); }
        finally { restore(); pending = false; lock(); selection(); }
    }
    document.querySelector('[data-crud-toolbar]').addEventListener('click', event => {
        const action = event.target.closest('[data-crud-action]')?.dataset.crudAction;
        if (action === 'help') showDialog({title: copy().help_title, description: copy().help_intro, items: copy().help_items, accordion: true});
        else if (['activate', 'deactivate', 'delete'].includes(action)) bulk(action, event.target.closest('[data-crud-action]'));
    });
    document.querySelector('#extension-table').addEventListener('click', event => {
        const toggle = event.target.closest('[data-toggle-children]');
        if (toggle && !pending && !loading) {
            const record = rows.find(row => row.id === toggle.dataset.toggleChildren);
            if (!record) return;
            expanded.has(record.id) ? expanded.delete(record.id) : expanded.add(record.id);
            const parent = table.row(toggle.closest('tr'));
            if (expanded.has(record.id)) parent.child(childrenMarkup(record)).show(); else parent.child.hide();
            toggle.setAttribute('aria-expanded', String(expanded.has(record.id)));
            toggle.setAttribute('aria-label', copy()[expanded.has(record.id) ? 'hide_children' : 'show_children']);
            toggle.innerHTML = icon(expanded.has(record.id) ? 'chevron-down' : 'chevron-right');
            selection(); return;
        }
        const button = event.target.closest('[data-operation],[data-details]');
        if (!button) return;
        const row = rows.find(item => item.id === (button.dataset.id || button.dataset.details));
        if (!row) return;
        if (button.dataset.operation) mutate(row, button.dataset.operation, button);
        else showExtensionDetails(row);
    });
    new MutationObserver(() => {
        toolbar('list', {canCreate}); selection();
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
