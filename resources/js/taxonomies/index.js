import { toolbar, copy, escape, icon, showDialog } from "./shared";
import { pageData } from "../common/page";
import { sendSettings } from "../admin/settings";
import { announce } from "../common/toast";
import { enhanceSelects } from "../common/selects";
const $ = window.jQuery;
const page = pageData().taxonomyPage;
const selected = new Set();
let rows = [];
toolbar("list");
const columns = [
    {
        data: "id",
        orderable: false,
        render: (id) => `<input type="checkbox" data-row-id="${Number(id)}">`,
    },
];
page.columns.forEach((key) =>
    columns.push(
        key === "status"
            ? {
                  data: key,
                  render: (value) =>
                      `<span class="crud-status ${value ? "is-active" : ""}">${escape(copy()[value ? "active" : "inactive"])}</span>`,
              }
            : key === "created_at"
              ? {
                    data: key,
                    render: (value) =>
                        value
                            ? new Intl.DateTimeFormat(
                                  document.documentElement.lang,
                              ).format(new Date(value))
                            : "—",
                }
              : key === "media"
                ? {
                      data: key,
                      orderable: false,
                      render: (value) =>
                          value
                              ? `<img class="crud-taxonomy-thumb" src="${escape(value)}" alt="">`
                              : "—",
                  }
                : key === "translations"
                  ? {
                        data: "translations",
                        orderable: false,
                        render: (value, mode, row) => {
                            const count = Object.values(value || {}).filter(
                                (fields) => String(fields.name || "").trim(),
                            ).length;
                            return `<button type="button" class="vuero-button" data-translations="${Number(row.id)}">${icon("languages")}${count}/${Object.keys(page.languages).length}</button>`;
                        },
                    }
                  : { data: key, render: $.fn.dataTable.render.text() },
    ),
);
columns.push({
    data: "id",
    orderable: false,
    render: (id) =>
        `<div class="crud-row-actions">${page.rowActions
            .map((action) => {
                const item =
                    typeof action === "string" ? { key: action } : action;
                if (item.key === "edit")
                    return `<a class="crud-icon-button" href="${page.index}/${Number(id)}/edit">${icon("edit")}</a>`;
                if (item.key === "delete")
                    return `<button class="crud-icon-button" data-delete="${Number(id)}">${icon("trash")}</button>`;
                const url = String(item.url || "#").replace(":id", Number(id));
                return `<a class="crud-icon-button" href="${escape(url)}" title="${escape(item.label || item.key)}" data-taxonomy-action="${escape(item.key)}" data-id="${Number(id)}">${icon(item.icon || "apps")}</a>`;
            })
            .join(
                "",
            )}<span data-taxonomy-row-actions="${Number(id)}"></span></div>`,
});
const table = $("#taxonomy-table").DataTable({
    serverSide: true,
    searching: false,
    order: [[page.columns.indexOf("ordering") + 1, "asc"]],
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    dom: 'rt<"crud-table-footer"lip>',
    columns,
    ajax: async (request, callback) => {
        const params = new URLSearchParams({
            draw: request.draw,
            start: request.start,
            length: request.length,
            "search[value]": document.querySelector("[data-search]").value,
            "order[0][column]": request.order[0]?.column || 1,
            "order[0][dir]": request.order[0]?.dir || "asc",
        });
        new FormData(document.querySelector("[data-filter-form]")).forEach(
            (v, k) => {
                if (v !== "") params.set(k, v);
            },
        );
        try {
            const response = await fetch(`${page.data}?${params}`, {
                headers: { Accept: "application/json" },
            });
            const result = await response.json();
            if (!result.ok) throw new Error();
            rows = result.data.data;
            callback(result.data);
            document.querySelector("[data-table-empty]").hidden =
                result.data.recordsFiltered !== 0;
        } catch {
            callback({
                draw: request.draw,
                data: [],
                recordsTotal: 0,
                recordsFiltered: 0,
            });
            announce(copy().failed, true);
        }
    },
    drawCallback() {
        const footer = document.querySelector(
            "#taxonomy-table_wrapper .crud-table-footer",
        );
        if (footer)
            document.querySelector("[data-table-footer]").append(footer);
        window.dispatchEvent(
            new CustomEvent("jobic:taxonomies:draw", {
                detail: { type: page.type, rows },
            }),
        );
    },
});
let timer;
document.querySelector("[data-search]").oninput = () => {
    clearTimeout(timer);
    timer = setTimeout(() => table.ajax.reload(), 300);
};
const dialog = document.querySelector("[data-filter-dialog]");
document.querySelector("[data-open-filters]").onclick = () => {
    dialog.showModal();
    enhanceSelects(dialog);
};
document.querySelector("[data-close-filters]").onclick = () => dialog.close();
document.querySelector("[data-filter-form]").onsubmit = (e) => {
    e.preventDefault();
    dialog.close();
    table.ajax.reload();
};
document.querySelector("[data-filter-form]").onreset = () =>
    setTimeout(() => table.ajax.reload(), 0);
document.querySelector("[data-retry]").onclick = () => table.ajax.reload();
document.querySelector("#taxonomy-table").onchange = (e) => {
    if (e.target.matches("[data-row-id]")) {
        e.target.checked
            ? selected.add(Number(e.target.dataset.rowId))
            : selected.delete(Number(e.target.dataset.rowId));
    }
};
document.querySelector("[data-select-all]").onchange = (e) =>
    document.querySelectorAll("[data-row-id]").forEach((node) => {
        node.checked = e.target.checked;
        node.dispatchEvent(new Event("change", { bubbles: true }));
    });
async function mutate(ids, operation) {
    if (!ids.length) {
        announce(copy().selection_required, true);
        return;
    }
    if (
        !(await showDialog({
            title: copy().confirm_title,
            description: copy().confirm.replace(":count", ids.length),
            confirm: copy().confirm_operation,
        }))
    )
        return;
    const data = new FormData();
    ids.forEach((id) => data.append("ids[]", id));
    data.set("operation", operation);
    try {
        const result = await sendSettings(page.bulk, data);
        selected.clear();
        announce(result.message);
        table.ajax.reload();
    } catch (error) {
        announce(error.message || copy().failed, true);
    }
}
document.querySelector("[data-crud-toolbar]").onclick = (e) => {
    const action = e.target.closest("[data-crud-action]")?.dataset.crudAction;
    if (["activate", "deactivate", "delete"].includes(action))
        mutate([...selected], action);
    else if (action === "help")
        showDialog({
            title: page.label,
            description: "",
            items: page.help,
            accordion: true,
        });
    else if (action)
        window.dispatchEvent(
            new CustomEvent("jobic:taxonomies:toolbar-action", {
                detail: { type: page.type, action, ids: [...selected] },
            }),
        );
};
document.querySelector("#taxonomy-table").onclick = (e) => {
    const translation = e.target.closest("[data-translations]");
    if (translation) {
        const row = rows.find(
            (item) =>
                Number(item.id) === Number(translation.dataset.translations),
        );
        const items = Object.entries(page.languages).map(
            ([locale, language]) => ({
                name: `${language.name}: ${row?.translations?.[locale]?.name || "—"}`,
            }),
        );
        showDialog({
            title: copy().translations,
            description: copy().translations_hint,
            items,
            confirm: copy().edit_translations,
        }).then((edit) => {
            if (edit)
                location.assign(
                    `${page.index}/${translation.dataset.translations}/edit?translations=1`,
                );
        });
    }
    const button = e.target.closest("[data-delete]");
    if (button) mutate([Number(button.dataset.delete)], "delete");
    const custom = e.target.closest("[data-taxonomy-action]");
    if (custom)
        window.dispatchEvent(
            new CustomEvent("jobic:taxonomies:row-action", {
                detail: {
                    type: page.type,
                    action: custom.dataset.taxonomyAction,
                    id: Number(custom.dataset.id),
                },
            }),
        );
};
