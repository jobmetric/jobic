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
function taxonomyPath(names, fallback) {
    const parts = names?.length ? names : [fallback];
    return parts.map((part) => `<span>${escape(part)}</span>`).join(`<i class="role-path-separator">${icon("chevron-right")}</i>`);
}
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
                            const current =
                                page.languages[page.activeLocale] ||
                                Object.values(page.languages)[0];
                            const flag = current?.flag_url
                                ? `<img src="${escape(current.flag_url)}" alt="">`
                                : icon("languages");
                            return `<details class="crud-dropdown crud-locale-dropdown"><summary class="crud-icon-button" aria-label="${escape(copy().translations)}">${flag}${icon("chevron-down")}</summary><div class="crud-dropdown__menu">${Object.entries(
                                page.languages,
                            )
                                .map(([locale, language]) => {
                                    const complete =
                                        String(
                                            value?.[locale]?.name || "",
                                        ).trim() !== "";
                                    const languageFlag = language.flag_url
                                        ? `<img src="${escape(language.flag_url)}" alt="">`
                                        : `<span class="crud-locale-code">${escape(locale.toUpperCase())}</span>`;
                                    return `<button type="button" class="${complete ? "is-complete" : ""}" data-translation-id="${Number(row.id)}" data-translation-locale="${escape(locale)}">${languageFlag}<span>${escape(language.name)}</span>${complete ? icon("check") : icon("plus")}</button>`;
                                })
                                .join("")}</div></details>`;
                        },
                    }
                  : key === "name" && page.hierarchical
                    ? { data: key, render: (value, mode, row) => `<span class="role-path">${taxonomyPath(row.path_names, value)}</span>` }
                    : { data: key, render: $.fn.dataTable.render.text() },
    ),
);
page.columns.forEach((key, index) => {
    columns[index + 1].className = `taxonomy-column-${key}`;
});
columns.push({
    data: "id",
    orderable: false,
    className: "taxonomy-column-actions",
    render: (id) => {
        const actions = page.rowActions.map((action) =>
            typeof action === "string" ? { key: action } : action,
        );
        const external = actions.filter(
            (item) => !["edit", "delete"].includes(item.key),
        );
        return `<div class="crud-row-actions">${external
            .map((item) => {
                const url = String(item.url || "#").replace(":id", Number(id));
                return `<a class="crud-icon-button" href="${escape(url)}" aria-label="${escape(item.label || item.key)}" data-tooltip="${escape(item.label || item.key)}" data-taxonomy-action="${escape(item.key)}" data-id="${Number(id)}">${icon(item.icon || "apps")}</a>`;
            })
            .join(
                "",
            )}<a class="crud-icon-button" href="${page.index}/${Number(id)}/edit" aria-label="${escape(copy().edit)}" data-tooltip="${escape(copy().edit)}">${icon("edit")}</a><details class="crud-dropdown"><summary class="crud-icon-button" aria-label="${escape(copy().more || copy().actions)}">${icon("more-horizontal")}</summary><div class="crud-dropdown__menu"><button type="button" data-delete="${Number(id)}">${icon("trash")}${escape(copy().delete)}</button></div></details><span data-taxonomy-row-actions="${Number(id)}"></span></div>`;
    },
});
const table = $("#taxonomy-table").DataTable({
    serverSide: true,
    searching: false,
    order: [[page.columns.indexOf("ordering") + 1, "asc"]],
    pageLength: 10,
    lengthMenu: [10, 25, 50, 100],
    dom: 'rt<"crud-table-footer"lip>',
    language: {
        ...pageData().language?.datatable,
        emptyTable: "",
        zeroRecords: "",
        info: "",
        infoEmpty: "",
        lengthMenu: `${copy().length} _MENU_`,
        paginate: { previous: copy().previous, next: copy().next },
    },
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
        const info = this.api().page.info();
        const footer = document.querySelector(
            "#taxonomy-table_wrapper .crud-table-footer",
        );
        if (footer)
            document.querySelector("[data-table-footer]").append(footer);
        const infoNode = document.querySelector("#taxonomy-table_info");
        if (infoNode)
            infoNode.textContent = copy()
                .page.replace(":page", info.pages ? info.page + 1 : 0)
                .replace(":pages", info.pages);
        enhanceSelects(document.querySelector("[data-table-footer]"));
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
            description: page.helpIntro,
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
const translationDialog = document.querySelector(
    "[data-row-translation-dialog]",
);
const translationForm = translationDialog.querySelector(
    "[data-row-translation-form]",
);
const translationFields = translationDialog.querySelector(
    "[data-translation-fields]",
);
let translationRecord = null;
let translationLocale = null;
function closeTranslation() {
    translationDialog.close();
    translationForm.reset();
    translationFields.replaceChildren();
}
translationDialog
    .querySelectorAll("[data-close-row-translation]")
    .forEach((button) => button.addEventListener("click", closeTranslation));
function openTranslation(id, locale) {
    const row = rows.find((item) => Number(item.id) === Number(id));
    const language = page.languages[locale];
    if (!row || !language) return;
    translationRecord = row;
    translationLocale = locale;
    translationDialog.querySelector("[data-translation-title]").textContent =
        language.name;
    translationDialog.querySelector("[data-translation-subtitle]").textContent =
        copy().translation_for.replace(":item", row.name);
    translationFields.replaceChildren(
        ...page.translationFields.map((field) => {
            const label = document.createElement("label");
            label.className = "crud-field";
            label.textContent = field.label;
            const input = document.createElement(
                field.type === "textarea" ? "textarea" : "input",
            );
            input.className = "input";
            input.name = `fields[${field.name}]`;
            input.value = row.translations?.[locale]?.[field.name] || "";
            if (field.name === "name") input.required = true;
            const error = document.createElement("small");
            error.dataset.translationError = field.name;
            label.append(input, error);
            return label;
        }),
    );
    translationDialog.showModal();
    translationFields.querySelector("input,textarea")?.focus();
}
translationForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const submit = translationForm.querySelector("button[type=submit]");
    const restore = submit.innerHTML;
    submit.disabled = true;
    translationFields
        .querySelectorAll("[data-translation-error]")
        .forEach((node) => {
            node.textContent = "";
        });
    const data = new FormData(translationForm);
    const url = page.translationUrl
        .replace("__ID__", translationRecord.id)
        .replace("__LOCALE__", translationLocale);
    try {
        const result = await sendSettings(url, data);
        translationRecord.translations[translationLocale] = result.data.fields;
        announce(result.message);
        closeTranslation();
        table.ajax.reload(null, false);
    } catch (error) {
        Object.entries(error.errors || {}).forEach(([key, messages]) => {
            const field = key.replace(/^fields\./, "");
            const node = translationFields.querySelector(
                `[data-translation-error="${CSS.escape(field)}"]`,
            );
            if (node) node.textContent = [].concat(messages).join(" ");
        });
        announce(error.message || copy().failed, true);
    } finally {
        submit.disabled = false;
        submit.innerHTML = restore;
    }
});
document.querySelector("#taxonomy-table").onclick = (e) => {
    const translation = e.target.closest("[data-translation-id]");
    if (translation)
        openTranslation(
            translation.dataset.translationId,
            translation.dataset.translationLocale,
        );
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
