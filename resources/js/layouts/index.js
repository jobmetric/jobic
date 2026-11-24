import "../../css/extensions-list.css";
import "../../css/layouts.css";
import { toolbar, copy, escape, icon, showDialog } from "./shared";
import { loadingButton, pageData } from "../common/page";
import { sendSettings } from "../admin/settings";
import { announce } from "../common/toast";
import { enhanceSelects } from "../common/selects";

function initialize() {
    const $ = window.jQuery,
        routes = pageData().layoutRoutes,
        access = pageData().layoutAccess,
        selected = new Map();
    const card = document.querySelector("[data-table-card]"),
        empty = document.querySelector("[data-table-empty]"),
        footer = document.querySelector("[data-table-footer]");
    const filters = document.querySelector("[data-filter-form]"),
        dialog = document.querySelector("[data-filter-dialog]"),
        search = document.querySelector("[data-search]");
    const selectAll = document.querySelector("[data-select-all]"),
        selectionButton = document.querySelector("[data-show-selected]"),
        query = new URLSearchParams(location.search);
    let rows = [],
        loading = false,
        pending = false,
        controller,
        timer,
        restoreFilterButton;
    toolbar("list");
    const lock = () => {
        const busy = loading || pending;
        card.setAttribute("aria-busy", String(busy));
        document.querySelector("[data-table-loading]").hidden = !busy;
        document.querySelector(".crud-table-scroll").inert = busy;
        document.querySelectorAll("[data-list-controls]").forEach((node) => {
            node.inert = busy;
        });
        filters.inert = busy;
    };
    const availability = (entries) => {
        if (!entries.length) return [];
        const states = new Set(entries.map((item) => item.state));
        const actions = [];
        if (states.size === 1 && states.has("active") && access.manage)
            actions.push("deactivate");
        if (states.size === 1 && states.has("inactive") && access.manage)
            actions.push("activate");
        if (!states.has("deleted") && access.delete) actions.push("delete");
        if (states.size === 1 && states.has("deleted") && access.restore)
            actions.push("restore");
        return actions;
    };
    const selection = () => {
        selectionButton.hidden = selected.size === 0;
        selectionButton.innerHTML = `${icon("check")}${escape(copy().selected.replace(":count", selected.size))}`;
        const checks = [...document.querySelectorAll("[data-row-id]")];
        checks.forEach((node) => {
            node.checked = selected.has(Number(node.dataset.rowId));
        });
        selectAll.checked =
            checks.length > 0 && checks.every((node) => node.checked);
        selectAll.indeterminate =
            checks.some((node) => node.checked) && !selectAll.checked;
        const available = availability([...selected.values()]);
        document
            .querySelectorAll("[data-crud-toolbar] [data-crud-action]")
            .forEach((node) => {
                if (
                    ["activate", "deactivate", "delete", "restore"].includes(
                        node.dataset.crudAction,
                    )
                )
                    node.hidden = !available.includes(node.dataset.crudAction);
            });
    };
    const select = (id) => {
        if (selected.size >= 100 && !selected.has(id)) {
            announce(copy().selection_limit, true);
            return;
        }
        const row = rows.find((item) => item.id === id);
        if (row)
            selected.set(id, {
                id,
                name: row.name,
                state: row.state,
                _version: row.version,
            });
    };
    const setQuick = (value) => {
        filters.elements.status.value = value === "all" ? "" : value;
        $(filters.elements.status).trigger("change.select2");
        document
            .querySelectorAll("[data-quick-filter]")
            .forEach((node) =>
                node.setAttribute(
                    "aria-pressed",
                    String(node.dataset.quickFilter === value),
                ),
            );
    };
    const syncUrl = () => {
        const url = new URL(location.href);
        ["q", "theme", "status"].forEach((key) => url.searchParams.delete(key));
        if (search.value.trim()) url.searchParams.set("q", search.value.trim());
        new FormData(filters).forEach((value, key) => {
            if (value) url.searchParams.set(key, value);
        });
        history.replaceState(null, "", url);
    };
    if (query.get("q")) search.value = query.get("q").slice(0, 100);
    ["theme", "status"].forEach((name) => {
        if (query.has(name)) filters.elements[name].value = query.get(name);
    });
    setQuick(filters.elements.status.value || "all");
    const updateEmpty = (result) => {
        const title = empty.querySelector("[data-empty-title]");
        const hint = empty.querySelector("[data-empty-hint]");
        const add = empty.querySelector("[data-empty-add]");
        const clear = empty.querySelector("[data-empty-clear]");
        const hasSearch = search.value.trim() !== "";
        const hasFilters = [...new FormData(filters).values()].some(
            (value) => value !== "",
        );

        if (result.recordsTotal === 0) {
            title.textContent = copy().empty;
            hint.textContent = access.has_themes
                ? copy().empty_hint
                : copy().theme_required_hint;
            if (add)
                add.hidden = access.has_themes
                    ? !access.manage
                    : !access.extensions_view;
            clear.hidden = true;
        } else if (hasSearch) {
            title.textContent = copy().search_empty;
            hint.textContent = copy().search_empty_hint;
            if (add) add.hidden = true;
            clear.hidden = false;
        } else if (hasFilters) {
            title.textContent = copy().filtered_empty;
            hint.textContent = copy().filtered_empty_hint;
            if (add) add.hidden = true;
            clear.hidden = false;
        }

        empty.hidden = result.recordsFiltered !== 0;
    };
    const actions = (row) => {
        const primary =
            !row.deleted && access.manage
                ? row.state === "active"
                    ? "deactivate"
                    : "activate"
                : row.deleted && access.restore
                  ? "restore"
                  : null;
        const primaryMarkup = primary
            ? `<button class="extension-primary-action" type="button" data-operation="${primary}" data-id="${row.id}">${escape(copy()[primary])}</button>`
            : "";
        const editMarkup =
            !row.deleted && access.manage
                ? `<a class="crud-icon-button" href="${routes.index}/${row.id}/edit" aria-label="${escape(copy().edit)}" data-tooltip="${escape(copy().edit)}">${icon("edit")}</a>`
                : "";
        const menu =
            !row.deleted && access.delete
                ? `<details class="crud-dropdown"><summary class="crud-icon-button" aria-label="${escape(copy().more)}">${icon("more-horizontal")}</summary><div class="crud-dropdown__menu"><button type="button" data-operation="delete" data-id="${row.id}">${escape(copy().delete)}</button></div></details>`
                : "";

        return `<div class="crud-row-actions">${primaryMarkup}${editMarkup}${menu}</div>`;
    };
    $.fn.dataTable.ext.errMode = "none";
    const table = $("#layout-table").DataTable({
        serverSide: true,
        processing: false,
        searching: false,
        order: [[1, "asc"]],
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
        ajax: async (request, callback) => {
            controller?.abort();
            const current = new AbortController();
            controller = current;
            loading = true;
            lock();
            document.querySelector("[data-table-error]").hidden = true;
            empty.hidden = true;
            const params = new URLSearchParams({
                draw: request.draw,
                start: request.start,
                length: request.length,
                "search[value]": search.value,
                "order[0][column]": request.order[0]?.column || 1,
                "order[0][dir]": request.order[0]?.dir || "asc",
            });
            new FormData(filters).forEach((value, key) => {
                if (value) params.set(key, value);
            });
            try {
                const response = await fetch(`${routes.data}?${params}`, {
                    headers: { Accept: "application/json" },
                    signal: current.signal,
                });
                if (!response.ok) throw new Error();
                const result = await response.json();
                if (!result.ok) throw new Error();
                rows = result.data.data;
                rows.forEach((row) => {
                    if (selected.has(row.id))
                        selected.set(row.id, {
                            id: row.id,
                            name: row.name,
                            state: row.state,
                            _version: row.version,
                        });
                });
                callback(result.data);
                document
                    .querySelectorAll("[data-layout-stat]")
                    .forEach((node, index) => {
                        node.textContent = new Intl.NumberFormat(
                            document.documentElement.lang,
                        ).format(result.data.stats[index]);
                    });
                Object.entries(result.data.quick_stats).forEach(
                    ([key, value]) => {
                        document.querySelector(
                            `[data-quick-count="${key}"]`,
                        ).textContent = new Intl.NumberFormat(
                            document.documentElement.lang,
                        ).format(value);
                    },
                );
                updateEmpty(result.data);
                card.append(empty);
            } catch (error) {
                if (error.name === "AbortError") return;
                rows = [];
                callback({
                    draw: request.draw,
                    data: [],
                    recordsTotal: 0,
                    recordsFiltered: 0,
                });
                document.querySelector("[data-table-error]").hidden = false;
                announce(copy().failed, true);
            } finally {
                if (controller === current) {
                    loading = false;
                    lock();
                    selection();
                    enhanceSelects(footer);
                    restoreFilterButton?.();
                    restoreFilterButton = undefined;
                }
            }
        },
        columns: [
            {
                data: "id",
                orderable: false,
                render: (id) =>
                    `<input type="checkbox" data-row-id="${Number(id)}" aria-label="${escape(copy().select_row)}">`,
            },
            { data: "name", render: $.fn.dataTable.render.text() },
            { data: "theme_label", render: $.fn.dataTable.render.text() },
            { data: "page_types_label", render: $.fn.dataTable.render.text() },
            { data: "plugins", render: (value) => Number(value) },
            {
                data: "state",
                render: (value) =>
                    `<span class="crud-status ${value === "active" ? "is-active" : ""}">${escape(copy()[value])}</span>`,
            },
            { data: null, orderable: false, render: actions },
        ],
        drawCallback() {
            const info = this.api().page.info(),
                controls = document.querySelector(
                    "#layout-table_wrapper .crud-table-footer",
                );
            if (controls) footer.append(controls);
            document.querySelector("#layout-table_info").textContent = copy()
                .page.replace(":page", info.pages ? info.page + 1 : 0)
                .replace(":pages", info.pages);
            selection();
        },
    });
    document
        .querySelector("#layout-table")
        .addEventListener("change", (event) => {
            if (!event.target.matches("[data-row-id]") || loading || pending)
                return;
            const id = Number(event.target.dataset.rowId);
            event.target.checked ? select(id) : selected.delete(id);
            selection();
        });
    selectAll.addEventListener("change", () => {
        document.querySelectorAll("[data-row-id]").forEach((node) => {
            const id = Number(node.dataset.rowId);
            selectAll.checked ? select(id) : selected.delete(id);
        });
        selection();
    });
    search.addEventListener("input", () => {
        clearTimeout(timer);
        timer = setTimeout(() => {
            syncUrl();
            table.ajax.reload();
        }, 300);
    });
    document
        .querySelector("[data-quick-filters]")
        .addEventListener("click", (event) => {
            const node = event.target.closest("[data-quick-filter]");
            if (!node || loading || pending) return;
            setQuick(node.dataset.quickFilter);
            syncUrl();
            table.ajax.reload();
        });
    document.querySelector("[data-open-filters]").onclick = () => {
        dialog.showModal();
        enhanceSelects(dialog);
    };
    document.querySelector("[data-close-filters]").onclick = () =>
        dialog.close();
    filters.addEventListener("submit", (event) => {
        event.preventDefault();
        if (loading || pending) return;
        restoreFilterButton = loadingButton(
            filters.querySelector("[type=submit]"),
        );
        dialog.close();
        syncUrl();
        table.ajax.reload();
    });
    filters.addEventListener("reset", () =>
        setTimeout(() => {
            $(filters).find("select").trigger("change.select2");
            search.value = "";
            setQuick("all");
            dialog.close();
            syncUrl();
            table.ajax.reload();
        }, 0),
    );
    document.querySelector("[data-retry]").onclick = () => table.ajax.reload();
    empty.querySelector("[data-empty-clear]").onclick = () => {
        filters.reset();
        search.value = "";
        setQuick("all");
        syncUrl();
        table.ajax.reload();
    };
    selectionButton.onclick = () =>
        showDialog({
            title: copy().selected.replace(":count", selected.size),
            description: copy().selection_hint,
            items: [...selected.values()],
            remove: (id) => {
                selected.delete(id);
                selection();
            },
        });
    async function mutate(entries, operation, button) {
        if (pending || loading || !availability(entries).includes(operation))
            return;
        const confirmed = await showDialog({
            title: copy()[operation],
            description: copy().batch_description.replace(
                ":count",
                entries.length,
            ),
            items: entries,
            confirm: copy().confirm_operation,
        });
        if (!confirmed) return;
        pending = true;
        lock();
        const restore = loadingButton(button);
        try {
            const data = new FormData();
            entries.forEach((entry, index) => {
                data.set(`entries[${index}][id]`, entry.id);
                data.set(`entries[${index}][_version]`, entry._version);
            });
            const result = await sendSettings(routes.bulk[operation], data);
            entries.forEach((entry) => selected.delete(entry.id));
            announce(result.message);
            table.ajax.reload(null, false);
        } catch (error) {
            announce(error.message || copy().failed, true);
            table.ajax.reload(null, false);
        } finally {
            restore();
            pending = false;
            lock();
            selection();
        }
    }
    document
        .querySelector("[data-crud-toolbar]")
        .addEventListener("click", (event) => {
            const action =
                event.target.closest("[data-crud-action]")?.dataset.crudAction;
            if (action === "help")
                showDialog({
                    title: copy().help_title,
                    description: copy().help_intro,
                    items: copy().help_items,
                    accordion: true,
                });
            else if (
                ["activate", "deactivate", "delete", "restore"].includes(action)
            )
                mutate(
                    [...selected.values()],
                    action,
                    event.target.closest("[data-crud-action]"),
                );
        });
    document
        .querySelector("#layout-table")
        .addEventListener("click", (event) => {
            const button = event.target.closest("[data-operation]");
            if (!button) return;
            const row = rows.find(
                (item) => item.id === Number(button.dataset.id),
            );
            if (row)
                mutate(
                    [
                        {
                            id: row.id,
                            name: row.name,
                            state: row.state,
                            _version: row.version,
                        },
                    ],
                    button.dataset.operation,
                    button,
                );
        });
}
if (document.readyState === "loading")
    document.addEventListener("DOMContentLoaded", initialize);
else initialize();
