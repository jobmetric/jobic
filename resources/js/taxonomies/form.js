import { toolbar, copy, showDialog } from "./shared";
import { loadingButton, pageData } from "../common/page";
import { sendSettings } from "../admin/settings";
import { announce } from "../common/toast";
import { guardUnsaved } from "../common/unsaved";
import { bindHistory } from "./history";
toolbar("form");
const form = document.querySelector("[data-taxonomy-form]");
form.noValidate = true;
const guard = guardUnsaved(form, () =>
    showDialog({
        title: copy().unsaved_title,
        description: copy().unsaved_body,
        confirm: copy().leave,
    }),
);
const historyPanel = bindHistory(form);
const linkBox = form.querySelector('[data-menu-link]');
if (linkBox) {
    linkBox.querySelector('[data-menu-link-driver]').addEventListener('change', async (event) => {
        const fields = linkBox.querySelector('[data-menu-link-fields]');
        const loading = linkBox.querySelector('[data-menu-link-loading]');
        loading.hidden = false;
        fields.setAttribute('inert', '');
        try {
            const url = linkBox.dataset.fieldsUrl.replace('__DRIVER__', encodeURIComponent(event.target.value));
            const response = await fetch(url, {headers: {Accept: 'text/html'}});
            if (!response.ok) throw new Error();
            fields.innerHTML = await response.text();
        } catch {
            announce(copy().failed, true);
        } finally {
            loading.hidden = true;
            fields.removeAttribute('inert');
        }
    });
}
form.addEventListener("submit", async (event) => {
    event.preventDefault();
    if (form.dataset.saving) return;
    const intent = event.submitter?.value || "save";
    const snapshot = guard.snapshot();
    form.querySelectorAll("[data-error-for]").forEach((node) => {
        node.textContent = "";
    });
    form.querySelectorAll("[aria-invalid]").forEach((node) => {
        node.removeAttribute("aria-invalid");
        node.removeAttribute("aria-describedby");
    });
    form.querySelectorAll(".crud-dropdown").forEach((menu) => {
        menu.open = false;
    });
    form.querySelector(".crud-split-save")?.setAttribute("inert", "");
    const restores = [...form.querySelectorAll("button[type=submit]")].map(
        loadingButton,
    );
    form.dataset.saving = "true";
    form.setAttribute("aria-busy", "true");
    try {
        const result = await sendSettings(form.action, new FormData(form));
        guard.saved(snapshot);
        announce(result.message);
        if (intent !== "save") guard.leave();
        if (intent === "close") location.assign(pageData().taxonomyPage.index);
        else if (intent === "new")
            location.assign(`${pageData().taxonomyPage.index}/create`);
        else if (form.action === result.data.edit_url) {
            historyPanel.refresh();
        } else location.assign(result.data.edit_url);
    } catch (error) {
        Object.entries(error.errors || {}).forEach(([name, messages]) => {
            const field = form.elements.namedItem(name);
            const message = form.querySelector(
                `[data-error-for="${CSS.escape(name)}"]`,
            );
            if (field && message) {
                message.textContent = [].concat(messages).join(" ");
                field.setAttribute("aria-invalid", "true");
                field.setAttribute("aria-describedby", message.id);
            }
        });
        form.querySelector("[aria-invalid=true]")?.focus();
        announce(error.status ? error.message : copy().failed, true);
    } finally {
        restores.forEach((restore) => restore());
        form.querySelector(".crud-split-save")?.removeAttribute("inert");
        delete form.dataset.saving;
        form.removeAttribute("aria-busy");
    }
});
window.addEventListener("cms:presentation-refreshed", () => toolbar("form"));
