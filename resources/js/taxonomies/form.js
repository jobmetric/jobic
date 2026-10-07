import { toolbar, copy, showDialog } from "./shared";
import { loadingButton, pageData } from "../common/page";
import { sendSettings } from "../admin/settings";
import { announce } from "../common/toast";
import { guardUnsaved } from "../common/unsaved";
import { bindHistory } from "./history";
import { bindMediaManager } from "./media-manager";
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
const editLockToken = form.elements.namedItem("_edit_lock");
if (editLockToken) {
    let lost = false;
    const lockPayload = () => {
        const data = new FormData();
        data.set("_edit_lock", editLockToken.value);
        return data;
    };
    const renew = async () => {
        try {
            await sendSettings(form.dataset.editLockRenewUrl, lockPayload());
            lost = false;
        } catch (error) {
            if (error.status !== 423) return;
            if (!lost) announce(copy().edit_lock_lost, true);
            lost = true;
            form.querySelectorAll('button[type="submit"]').forEach((button) => { button.disabled = true; });
        }
    };
    const timer = window.setInterval(renew, 60_000);
    document.addEventListener("visibilitychange", () => {
        if (!document.hidden) renew();
    });
    window.addEventListener("pagehide", () => {
        window.clearInterval(timer);
        const data = new URLSearchParams();
        data.set("_token", document.querySelector('meta[name="csrf-token"]')?.content || "");
        data.set("_edit_lock", editLockToken.value);
        navigator.sendBeacon(form.dataset.editLockReleaseUrl, data);
    }, { once: true });
}
const historyPanel = bindHistory(form);
bindMediaManager(form);
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
        const version = form.elements.namedItem("_version");
        if (version && result.data.version) version.value = result.data.version;
        guard.saved(snapshot);
        announce(result.message);
        if (intent !== "save") guard.leave();
        if (intent === "close") location.assign(pageData().taxonomyPage.index);
        else if (intent === "new")
            location.assign(`${pageData().taxonomyPage.index}/create`);
        else if (new URL(location.href).pathname === new URL(result.data.edit_url, location.href).pathname) {
            historyPanel.refresh();
        } else {
            guard.leave();
            location.assign(result.data.edit_url);
        }
    } catch (error) {
        let displayedErrors = 0;
        Object.entries(error.errors || {}).forEach(([name, messages]) => {
            const parts = name.split('.');
            const inputName = parts.shift() + parts.map(part => `[${part}]`).join('');
            const field = form.elements.namedItem(name) || form.elements.namedItem(inputName);
            const message = form.querySelector(
                `[data-error-for="${CSS.escape(name)}"]`,
            );
            if (message) {
                message.textContent = [].concat(messages).join(" ");
                message.id ||= `taxonomy-error-${name.replaceAll('.', '-')}`;
                displayedErrors++;
            }
            if (field && message) {
                field.setAttribute("aria-invalid", "true");
                field.setAttribute("aria-describedby", message.id);
            }
        });
        form.querySelector("[aria-invalid=true]")?.focus();
        if (error.status !== 422 || displayedErrors === 0) {
            announce(error.status ? error.message : copy().failed, true);
        }
    } finally {
        restores.forEach((restore) => restore());
        form.querySelector(".crud-split-save")?.removeAttribute("inert");
        delete form.dataset.saving;
        form.removeAttribute("aria-busy");
    }
});
window.addEventListener("cms:presentation-refreshed", () => toolbar("form"));
