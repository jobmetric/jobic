import { toolbar, copy, escape, icon, showDialog } from './shared';
import { loadingButton, pageData } from '../common/page';
import { sendSettings } from '../admin/settings';
import { announce } from '../common/toast';
import { enhanceSelects } from '../common/selects';

function initialize() {
    const $ = window.jQuery;
    const routes = pageData().menuRoutes;
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
        checks.forEach(node => { node.checked = selected.has(Number(node.dataset.rowId)); });
        selectAll.checked = checks.length > 0 && checks.every(node => node.checked);
        selectAll.indeterminate = checks.some(node => node.checked) && !selectAll.checked;
    }
    function select(id) {
        if (selected.size >= 100 && !selected.has(id)) { announce(copy().selection_limit, true); return; }
        const row = rows.find(item => item.id === id);
        if (row) selected.set(id, {id, name: row.name});
    }
    $.fn.dataTable.ext.errMode = 'none';
    const table = $('#menu-table').DataTable({
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
                rows.forEach(row => { if (selected.has(row.id)) selected.set(row.id, {id: row.id, name: row.name}); });
                callback(result.data);
                document.querySelectorAll('[data-menu-stat]').forEach((node, index) => {
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
        columns: [
            {data: 'id', orderable: false, render: id => `<input type="checkbox" data-row-id="${Number(id)}" aria-label="${escape(copy().select_row)}">`},
            {data: 'name', render: $.fn.dataTable.render.text()},

            {data: 'ordering'},
            {data: 'status', render: value => `<span class="crud-status ${value ? 'is-active' : ''}">${escape(copy()[value ? 'active' : 'inactive'])}</span>`},
            {data: 'created_at', render: value => value ? new Intl.DateTimeFormat(document.documentElement.lang).format(new Date(value)) : '—'},
            {data: 'id', orderable: false, render: id => `<div class="crud-row-actions"><a class="crud-icon-button" href="${routes.index}/${Number(id)}/edit" aria-label="${escape(copy().edit)}" data-tooltip="${escape(copy().edit)}">${icon('edit')}</a><details class="crud-dropdown"><summary class="crud-icon-button" aria-label="${escape(copy().more)}">${icon('more-horizontal')}</summary><div class="crud-dropdown__menu"><a href="${routes.index}/${Number(id)}">${icon('eye')}${escape(copy().view)}</a><button type="button" data-delete="${Number(id)}">${icon('trash')}${escape(copy().delete)}</button></div></details></div>`},
        ],
        drawCallback() {
            const info = this.api().page.info();
            const controls = document.querySelector('#menu-table_wrapper .crud-table-footer');
            if (controls) footer.append(controls);
            document.querySelector('#menu-table_info').textContent = copy().page.replace(':page', info.pages ? info.page + 1 : 0).replace(':pages', info.pages);
            selection();
        },
    });

    document.querySelector('#menu-table').addEventListener('change', event => {
        if (!event.target.matches('[data-row-id]') || loading || pending) return;
        const id = Number(event.target.dataset.rowId);
        event.target.checked ? select(id) : selected.delete(id);
        selection();
    });
    selectAll.addEventListener('change', () => {
        document.querySelectorAll('[data-row-id]').forEach(node => {
            const id = Number(node.dataset.rowId);
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
    async function mutate(ids, operation) {
        if (pending || loading) return;
        if (!ids.length) { announce(copy().selection_required, true); return; }
        const items = ids.map(id => selected.get(id) || rows.find(row => row.id === id)).filter(Boolean);
        const confirmed = await showDialog({
            title: copy().batch_title.replace(':operation', copy()[operation]),
            description: copy().batch_description.replace(':count', ids.length),
            items, confirm: copy().confirm_operation,
        });
        if (!confirmed || pending || loading) return;
        pending = true; lock();
        try {
            const data = new FormData(); ids.forEach(id => data.append('ids[]', id)); data.set('operation', operation);
            const result = await sendSettings(routes.bulk, data);
            ids.forEach(id => selected.delete(id));
            announce(result.message); table.ajax.reload();
        } catch (error) { announce(error.status ? error.message : copy().failed, true); }
        finally { pending = false; lock(); selection(); }
    }
    document.querySelector('[data-crud-toolbar]').addEventListener('click', event => {
        const action = event.target.closest('[data-crud-action]')?.dataset.crudAction;
        if (action === 'help') showDialog({title: copy().help_title, description: copy().help_intro, items: copy().help_items, accordion: true});
        else if (['activate', 'deactivate', 'delete'].includes(action)) mutate([...selected.keys()], action);
    });
    document.querySelector('#menu-table').addEventListener('click', event => {
        const button = event.target.closest('[data-delete]');
        if (button) mutate([Number(button.dataset.delete)], 'delete');
    });
    new MutationObserver(() => {
        toolbar('list');
        const settings = table.settings()[0];
        settings.oLanguage.oPaginate.sNext = copy().next;
        settings.oLanguage.oPaginate.sPrevious = copy().previous;
        ['name', 'ordering', 'status', 'created_at', 'actions'].forEach((key, index) => {
            document.querySelectorAll('#menu-table thead th')[index + 1].textContent = copy()[key];
        });
        const label = document.querySelector('#menu-table_length label');
        if (label?.firstChild?.nodeType === Node.TEXT_NODE) label.firstChild.textContent = `${copy().length} `;
        table.ajax.reload(null, false);
    }).observe(document.documentElement, {attributes: true, attributeFilter: ['lang']});
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
else initialize();
