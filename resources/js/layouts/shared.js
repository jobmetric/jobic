import { pageData } from "../common/page";
import { iconMarkup } from "../common/icons";
import "../../css/crud.css";
import "../../css/crud-fields.css";

export const copy = () => pageData().layouts;
export function escape(value) {
    const span = document.createElement("span");
    span.textContent = String(value ?? "");
    return span.innerHTML.replaceAll('"', "&quot;").replaceAll("'", "&#39;");
}
export const icon = (name) => iconMarkup(name, 18);
function button(key, type = "button") {
    const node = document.createElement("button");
    node.type = type;
    node.dataset.crudAction = key;
    node.textContent = copy()[key];
    if (type === "submit") {
        node.name = "intent";
        node.value = key;
    }
    return node;
}
function dropdown(label, items, submit = false) {
    const details = document.createElement("details");
    details.className = "crud-dropdown";
    const toggle = document.createElement("summary");
    toggle.className = "vuero-button";
    toggle.setAttribute("aria-label", copy()[label]);
    toggle.innerHTML = `${submit ? "" : escape(copy()[label])}${icon("chevron-down")}`;
    const menu = document.createElement("div");
    menu.className = "crud-dropdown__menu";
    items.forEach((key) =>
        menu.append(button(key, submit ? "submit" : "button")),
    );
    details.append(toggle, menu);
    return details;
}
export function toolbar(kind) {
    const root = document.querySelector("[data-crud-toolbar]");
    root.replaceChildren();
    const routes = pageData().layoutRoutes;
    if (kind === "list") {
        if (
            pageData().layoutAccess.manage &&
            pageData().layoutAccess.has_themes
        ) {
            const add = document.createElement("a");
            add.href = routes.create;
            add.className = "vuero-button is-primary";
            add.innerHTML = `${icon("plus")}${escape(copy().create)}`;
            root.append(add);
        } else if (
            !pageData().layoutAccess.has_themes &&
            pageData().layoutAccess.extensions_view
        ) {
            const themes = document.createElement("a");
            themes.href = routes.extensions;
            themes.className = "vuero-button is-primary";
            themes.innerHTML = `${icon("layout")}${escape(copy().manage_themes)}`;
            root.append(themes);
        }
        const actions = dropdown("actions", [
            "activate",
            "deactivate",
            "delete",
            "restore",
            "force_delete",
            "help",
        ]);
        actions.querySelectorAll("[data-crud-action]").forEach((node) => {
            node.hidden = node.dataset.crudAction !== "help";
        });
        root.append(actions);
        return;
    }
    const cancel = document.createElement("a");
    cancel.href = routes.index;
    cancel.className = "vuero-button crud-cancel";
    cancel.textContent = copy().cancel;
    root.append(cancel);
    const group = document.createElement("div");
    group.className = "crud-split-save";
    const save = button("save", "submit");
    save.className = "vuero-button is-primary";
    group.append(save, dropdown("save_options", ["new", "close"], true));
    root.append(group);
}
document.addEventListener("click", (event) => {
    document.querySelectorAll(".crud-dropdown[open]").forEach((menu) => {
        if (!menu.contains(event.target) || event.target.closest("button,a"))
            menu.open = false;
    });
});
document.addEventListener("keydown", (event) => {
    if (event.key === "Escape")
        document.querySelectorAll(".crud-dropdown[open]").forEach((menu) => {
            menu.open = false;
            menu.querySelector("summary").focus();
        });
});
export function showDialog({
    title,
    description,
    items = [],
    confirm = null,
    remove = null,
    accordion = false,
}) {
    const dialog = document.querySelector("[data-crud-dialog]");
    const heading = dialog.querySelector("[data-dialog-title]");
    const body = dialog.querySelector("[data-dialog-body]");
    heading.textContent = title;
    body.replaceChildren();
    const intro = document.createElement("p");
    intro.textContent = description;
    body.append(intro);
    items.forEach((item, index) => {
        if (accordion) {
            const section = document.createElement("details");
            section.className = "crud-help-section";
            const summary = document.createElement("summary");
            summary.textContent = Object.values(pageData().crudHelp)[index];
            const content = document.createElement("p");
            content.textContent = item;
            section.append(summary, content);
            section.open = index === 0;
            body.append(section);
            return;
        }
        const row = document.createElement("div");
        row.className = "crud-dialog-item";
        const label = document.createElement("span");
        label.textContent = item.name ?? item;
        row.append(label);
        if (remove) {
            const control = document.createElement("button");
            control.type = "button";
            control.className = "crud-icon-button";
            control.setAttribute("aria-label", copy().unselect);
            control.innerHTML = icon("x");
            control.onclick = () => {
                remove(item.id);
                row.remove();
                const count = body.querySelectorAll(".crud-dialog-item").length;
                heading.textContent = copy().selected.replace(":count", count);
                if (!count) dialog.close();
            };
            row.append(control);
        }
        body.append(row);
    });
    const accept = dialog.querySelector("[data-dialog-confirm]");
    accept.hidden = !confirm;
    accept.textContent = confirm || "";
    dialog.querySelector("[data-dialog-cancel]").textContent = confirm
        ? copy().cancel
        : copy().dismiss;
    dialog
        .querySelector("[data-dialog-close]")
        .setAttribute("aria-label", copy().dismiss);
    return new Promise((resolve) => {
        let accepted = false;
        accept.onclick = () => {
            accepted = true;
            dialog.close();
        };
        dialog
            .querySelectorAll("[data-dialog-close],[data-dialog-cancel]")
            .forEach((node) => {
                node.onclick = () => dialog.close();
            });
        dialog.addEventListener("close", () => resolve(accepted), {
            once: true,
        });
        dialog.showModal();
    });
}
