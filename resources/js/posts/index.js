import { pageData } from '../common/page';

const config = window.JobicPostList;
const table = window.jQuery?.('#posts-table');
if (table?.length && window.jQuery.fn.DataTable) {
    const $ = window.jQuery;
    const dataTable = table.DataTable({
        processing: true, serverSide: true, searching: true, lengthChange: true,
        ajax: { url: config.dataUrl, dataSrc: result => { document.querySelector('[data-post-empty]').hidden = result.data.recordsFiltered > 0; return result.data.data; }, error: () => { document.querySelector('[data-post-error]').hidden = false; } },
        columns: [
            { data: 'title', render: $.fn.dataTable.render.text() },
            { data: 'status', render: status => $.fn.dataTable.render.text().display(config.labels.statuses[status] || status) },
            { data: 'updated_at', render: value => value ? new Intl.DateTimeFormat(config.locale, {dateStyle: 'medium', timeStyle: 'short'}).format(new Date(value)) : '' },
            { data: null, orderable: false, searchable: false, render: row => `<a class="vuero-button" href="${encodeURI(row.edit_url)}">${String(config.labels.edit)}</a> <button class="vuero-button is-danger" type="button" data-post-delete="${Number(row.id)}" data-url="${encodeURI(row.delete_url)}">${String(config.labels.delete)}</button>` },
        ], order: [[2, 'desc']], language: window.Domi?.datatables || {},
    });
    document.querySelector('[data-post-search]')?.addEventListener('input', event => dataTable.search(event.target.value).draw());
    table.on('click', '[data-post-delete]', async event => {
        const button = event.currentTarget;
        if (!window.confirm(config.labels.confirm_delete)) return;
        button.disabled = true;
        try {
            const response = await fetch(button.dataset.url, {method: 'DELETE', headers: {'Accept':'application/json','X-Requested-With':'XMLHttpRequest','X-CSRF-TOKEN':document.querySelector('meta[name="csrf-token"]')?.content || ''}});
            const result = await response.json();
            if (!response.ok || !result.ok) throw new Error(result.message || config.labels.failed);
            dataTable.ajax.reload(null, false);
        } catch (error) { window.alert(error.message || config.labels.failed); button.disabled = false; }
    });
}
