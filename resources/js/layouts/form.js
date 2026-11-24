import { toolbar, copy, escape, showDialog } from "./shared";
import { loadingButton, pageData } from "../common/page";
import { iconMarkup } from "../common/icons";
import { sendSettings } from "../admin/settings";
import { announce } from "../common/toast";
import { guardUnsaved } from "../common/unsaved";
import "../../css/layouts.css";

const form = document.querySelector("[data-layout-form]");

if (form) {
    toolbar("form");
    form.noValidate = true;

    const editor = form.querySelector("[data-layout-editor]");
    const themePositions = JSON.parse(editor.dataset.themePositions || "{}");
    const moduleNodes = [...editor.querySelectorAll(".layout-module")];
    const modules = new Map(
        moduleNodes.map((node) => [
            node.dataset.pluginId,
            {
                id: node.dataset.pluginId,
                name: node.dataset.moduleName,
                owner: node.dataset.moduleOwner,
            },
        ]),
    );
    const picker = document.querySelector("[data-module-picker]");
    const pickerList = picker.querySelector("[data-picker-list]");
    const pickerSearch = picker.querySelector("[data-picker-search]");
    let pickerPosition = null;
    let dragged = null;
    let dropBefore = null;
    let dropPlaceholder = null;
    let dragArmed = false;
    let currentTheme = form.elements.namedItem("theme").value;

    const clearDropState = () => {
        dropBefore = null;
        dropPlaceholder?.remove();
        dropPlaceholder = null;
        editor
            .querySelectorAll(".is-drag-over")
            .forEach((node) => node.classList.remove("is-drag-over"));
    };

    const placeDropPlaceholder = (zone, before) => {
        if (!dropPlaceholder) {
            dropPlaceholder = document.createElement("div");
            dropPlaceholder.className = "layout-drop-placeholder";
            dropPlaceholder.textContent = copy().drop_placeholder;
        }
        const height = dragged?.classList.contains("layout-placement")
            ? dragged.getBoundingClientRect().height
            : 58;
        dropPlaceholder.style.height = `${Math.max(58, height)}px`;
        if (
            dropPlaceholder.parentElement === zone &&
            dropPlaceholder.nextElementSibling === before
        )
            return;
        zone.insertBefore(dropPlaceholder, before);
    };

    const findDropBefore = (zone, pointerY) =>
        [...zone.querySelectorAll(".layout-placement:not(.is-dragging)")].find(
            (item) => {
                const bounds = item.getBoundingClientRect();
                return pointerY < bounds.top + bounds.height / 2;
            },
        ) || null;

    const placementMarkup = (module) =>
        `<div class="layout-placement" draggable="true" tabindex="-1" data-plugin-id="${escape(module.id)}" data-module-name="${escape(module.name)}" data-module-owner="${escape(module.owner)}"><span class="layout-placement-order" data-order-index></span><span class="layout-placement-handle" title="${escape(copy().drag_module)}">${iconMarkup("grip-vertical", 17)}</span><span class="layout-placement-copy"><strong>${escape(module.name)}</strong><small>${escape(module.owner)}</small></span><span class="layout-placement-controls"><button type="button" data-move-up aria-label="${escape(copy().move_module_up)}">${iconMarkup("chevron-up", 16)}</button><button type="button" data-move-down aria-label="${escape(copy().move_module_down)}">${iconMarkup("chevron-down", 16)}</button><button type="button" data-remove aria-label="${escape(copy().remove_module)}">${iconMarkup("x", 16)}</button></span></div>`;

    const positionMarkup = (key, label) =>
        `<section class="layout-position" data-position="${escape(key)}"><header><div class="layout-position-heading"><span class="layout-position-icon">${iconMarkup("layout", 18)}</span><div><h3>${escape(label)}</h3><code>${escape(key)}</code></div></div><div class="layout-position-actions"><span class="layout-position-count" data-position-module-count>0</span><button type="button" class="vuero-button" data-add-module>${iconMarkup("plus", 16)}${escape(copy().add_module)}</button></div></header><div data-dropzone><p class="layout-drop-hint"><span class="layout-drop-icon">${iconMarkup("box", 20)}</span><strong>${escape(copy().drop_title)}</strong><span>${escape(copy().drop_hint)}</span></p></div></section>`;

    const updatePositionCounts = () => {
        editor.querySelectorAll("[data-position]").forEach((position) => {
            const placements = [
                ...position.querySelectorAll(".layout-placement"),
            ];
            position.querySelector("[data-position-module-count]").textContent =
                placements.length;
            placements.forEach((placement, index) => {
                placement.querySelector("[data-order-index]").textContent =
                    index + 1;
                placement.querySelector("[data-move-up]").disabled =
                    index === 0;
                placement.querySelector("[data-move-down]").disabled =
                    index === placements.length - 1;
            });
        });
    };

    const sync = ({ announceChange = true } = {}) => {
        const target = form.querySelector("[data-placement-inputs]");
        target.replaceChildren();
        let index = 0;
        editor.querySelectorAll("[data-position]").forEach((position) => {
            position
                .querySelectorAll(".layout-placement")
                .forEach((item, ordering) => {
                    Object.entries({
                        plugin_id: item.dataset.pluginId,
                        position: position.dataset.position,
                        ordering,
                    }).forEach(([key, value]) => {
                        const input = document.createElement("input");
                        input.type = "hidden";
                        input.name = `plugins[${index}][${key}]`;
                        input.value = value;
                        target.append(input);
                    });
                    index += 1;
                });
        });
        updatePositionCounts();
        if (announceChange)
            form.dispatchEvent(new Event("change", { bubbles: true }));
    };

    const addPlacement = (position, module, before = null) => {
        const zone = position.querySelector("[data-dropzone]");
        if (before)
            before.insertAdjacentHTML("beforebegin", placementMarkup(module));
        else zone.insertAdjacentHTML("beforeend", placementMarkup(module));
        sync();
        return true;
    };

    const filterModules = (nodes, value) => {
        const query = value
            .trim()
            .toLocaleLowerCase(document.documentElement.lang);
        let visible = 0;
        nodes.forEach((node) => {
            const matches =
                !query ||
                `${node.dataset.moduleName} ${node.dataset.moduleOwner}`
                    .toLocaleLowerCase(document.documentElement.lang)
                    .includes(query);
            node.hidden = !matches;
            if (matches) visible += 1;
        });
        form.querySelectorAll(".layout-module-group").forEach((group) => {
            group.hidden = ![...group.querySelectorAll(".layout-module")].some(
                (node) => !node.hidden,
            );
        });
        return visible;
    };

    const openPicker = (position) => {
        pickerPosition = position;
        const label = position.querySelector("h3").textContent.trim();
        picker.querySelector("[data-picker-title]").textContent =
            copy().choose_module_for.replace(":position", label);
        pickerList.replaceChildren();
        modules.forEach((module) => {
            const button = document.createElement("button");
            button.type = "button";
            button.className = "layout-picker-module";
            button.dataset.pluginId = module.id;
            button.dataset.moduleName = module.name;
            button.dataset.moduleOwner = module.owner;
            button.innerHTML = `<span class="layout-module-icon">${iconMarkup("box", 17)}</span><span><strong>${escape(module.name)}</strong><small>${escape(module.owner)}</small></span>${iconMarkup(button.disabled ? "check" : "plus", 17)}`;
            pickerList.append(button);
        });
        pickerSearch.value = "";
        picker.showModal();
        pickerSearch.focus();
    };

    editor.addEventListener("pointerdown", (event) => {
        dragArmed = Boolean(event.target.closest(".layout-placement-handle"));
    });
    editor.addEventListener("dragstart", (event) => {
        dragged = event.target.closest(".layout-placement,.layout-module");
        if (!dragged) return;
        if (dragged.classList.contains("layout-placement") && !dragArmed) {
            event.preventDefault();
            dragged = null;
            return;
        }
        event.dataTransfer.effectAllowed = dragged.classList.contains(
            "layout-module",
        )
            ? "copy"
            : "move";
        event.dataTransfer.setData("text/plain", dragged.dataset.pluginId);
        dragged?.classList.add("is-dragging");
    });
    editor.addEventListener("dragend", () => {
        dragged?.classList.remove("is-dragging");
        dragged = null;
        dragArmed = false;
        clearDropState();
    });
    editor.addEventListener("dragover", (event) => {
        const zone = event.target.closest("[data-dropzone]");
        if (!zone) return;
        event.preventDefault();
        event.dataTransfer.dropEffect = dragged?.classList.contains(
            "layout-module",
        )
            ? "copy"
            : "move";
        editor
            .querySelectorAll(".is-drag-over")
            .forEach((node) => node.classList.remove("is-drag-over"));
        zone.classList.add("is-drag-over");
        dropBefore = findDropBefore(zone, event.clientY);
        placeDropPlaceholder(zone, dropBefore);
    });
    editor.addEventListener("drop", (event) => {
        const zone = event.target.closest("[data-dropzone]");
        if (!zone || !dragged) return;
        event.preventDefault();
        if (dragged.classList.contains("layout-placement")) {
            zone.insertBefore(dragged, dropPlaceholder);
            clearDropState();
            sync();
            return;
        }
        addPlacement(
            zone.closest("[data-position]"),
            modules.get(dragged.dataset.pluginId),
            dropPlaceholder,
        );
        clearDropState();
    });
    editor.addEventListener("click", async (event) => {
        const moveUp = event.target.closest("[data-move-up]");
        const moveDown = event.target.closest("[data-move-down]");
        if (moveUp || moveDown) {
            const placement = event.target.closest(".layout-placement");
            const sibling = moveUp
                ? placement.previousElementSibling
                : placement.nextElementSibling;
            if (!sibling?.classList.contains("layout-placement")) return;
            if (moveUp) sibling.before(placement);
            else sibling.after(placement);
            sync();
            placement.focus({ preventScroll: true });
            return;
        }
        const remove = event.target.closest("[data-remove]");
        if (remove) {
            const placement = remove.closest(".layout-placement");
            const confirmed = await showDialog({
                title: copy().remove_module_title,
                description: copy().remove_module_body.replace(
                    ":name",
                    placement.dataset.moduleName,
                ),
                confirm: copy().remove_module_confirm,
            });
            if (!confirmed) return;
            placement.remove();
            sync();
            return;
        }
        const add = event.target.closest("[data-add-module]");
        if (add) openPicker(add.closest("[data-position]"));
    });

    form.querySelector("[data-module-search]").addEventListener(
        "input",
        (event) => {
            const count = filterModules(moduleNodes, event.target.value);
            form.querySelector("[data-module-no-results]").hidden = count !== 0;
        },
    );

    pickerSearch.addEventListener("input", (event) =>
        filterModules([...pickerList.children], event.target.value),
    );
    pickerList.addEventListener("click", (event) => {
        const button = event.target.closest("[data-plugin-id]");
        if (!button || button.disabled || !pickerPosition) return;
        if (addPlacement(pickerPosition, modules.get(button.dataset.pluginId)))
            picker.close();
    });
    picker
        .querySelector("[data-close-module-picker]")
        .addEventListener("click", () => picker.close());
    picker.addEventListener("close", () => {
        pickerPosition = null;
    });

    form.elements
        .namedItem("theme")
        .addEventListener("change", async (event) => {
            if (event.target.value === currentTheme) return;
            if (editor.querySelector(".layout-placement")) {
                const confirmed = await showDialog({
                    title: copy().theme_change_title,
                    description: copy().theme_change_body,
                    confirm: copy().theme_change_confirm,
                });
                if (!confirmed) {
                    event.target.value = currentTheme;
                    window.jQuery?.(event.target).trigger("change.select2");
                    return;
                }
            }
            currentTheme = event.target.value;
            const positions = themePositions[event.target.value] || {};
            const container = editor.querySelector(".layout-positions");
            container.replaceChildren();
            Object.entries(positions).forEach(([key, label]) =>
                container.insertAdjacentHTML(
                    "beforeend",
                    positionMarkup(key, label),
                ),
            );
            form.querySelector("[data-position-count]").textContent =
                `${Object.keys(positions).length} ${copy().positions}`;
            sync();
        });
    const guard = guardUnsaved(form, () =>
        showDialog({
            title: copy().unsaved_title,
            description: copy().unsaved_body,
            confirm: copy().leave,
        }),
    );
    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        if (form.dataset.saving) return;
        const intent = event.submitter?.value || "save";
        const data = new FormData(form);
        form.querySelectorAll("[data-error-for]").forEach((node) => {
            node.textContent = "";
        });
        form.querySelectorAll("[aria-invalid]").forEach((node) => {
            node.removeAttribute("aria-invalid");
            node.removeAttribute("aria-describedby");
        });
        form.querySelector("[data-settings-errors]").textContent = "";
        const restore = [...form.querySelectorAll('[type="submit"]')].map(
            loadingButton,
        );
        form.dataset.saving = "true";
        form.setAttribute("aria-busy", "true");
        form.querySelectorAll("fieldset,.layout-builder-card").forEach(
            (node) => {
                node.inert = true;
            },
        );
        form.querySelector(".crud-split-save")?.setAttribute("inert", "");
        try {
            const result = await sendSettings(form.action, data);
            if (result.data?.form_action) {
                form.action = result.data.form_action;
                let method = form.elements.namedItem("_method");
                if (!method) {
                    method = document.createElement("input");
                    method.type = "hidden";
                    method.name = "_method";
                    form.append(method);
                }
                method.value = result.data.form_method || "PUT";
                let version = form.elements.namedItem("_version");
                if (!version) {
                    version = document.createElement("input");
                    version.type = "hidden";
                    version.name = "_version";
                    form.append(version);
                }
                version.value = result.data.version;
                history.replaceState(
                    {},
                    "",
                    `${pageData().layoutRoutes.index}/${result.data.id}/edit`,
                );
                form.querySelector("h1").textContent = copy().edit_layout;
            }
            guard.saved(guard.snapshot());
            announce(result.message);
            if (intent === "close") {
                guard.leave();
                location.assign(pageData().layoutRoutes.index);
            }
            if (intent === "new") {
                guard.leave();
                location.assign(form.dataset.newUrl);
            }
        } catch (error) {
            Object.entries(error.errors || {}).forEach(([name, messages]) => {
                const root = name.split(".")[0];
                const errorNode =
                    form.querySelector(
                        `[data-error-for="${CSS.escape(name)}"]`,
                    ) ||
                    form.querySelector(
                        `[data-error-for="${CSS.escape(root)}"]`,
                    );
                const field =
                    form.elements.namedItem(name) ||
                    form.elements.namedItem(root) ||
                    form.elements.namedItem(`${root}[]`);
                if (errorNode)
                    errorNode.textContent = [].concat(messages).join(" ");
                if (field?.setAttribute) {
                    field.setAttribute("aria-invalid", "true");
                    if (errorNode)
                        field.setAttribute("aria-describedby", errorNode.id);
                }
            });
            form.querySelector("[data-settings-errors]").textContent =
                (error.status ? error.message : copy().failed) || copy().failed;
            (
                [...form.querySelectorAll('[aria-invalid="true"]')].find(
                    (node) => node.getClientRects().length,
                ) || form.querySelector("[data-settings-errors]")
            )?.focus();
            announce(error.status ? error.message : copy().failed, true);
        } finally {
            restore.forEach((restoreButton) => restoreButton());
            delete form.dataset.saving;
            form.removeAttribute("aria-busy");
            form.querySelector(".crud-split-save")?.removeAttribute("inert");
            form.querySelectorAll("fieldset,.layout-builder-card").forEach(
                (node) => {
                    node.inert = false;
                },
            );
        }
    });
    window.addEventListener("cms:presentation-refreshed", () =>
        toolbar("form"),
    );
    sync({ announceChange: false });
    guard.saved(guard.snapshot());
}
