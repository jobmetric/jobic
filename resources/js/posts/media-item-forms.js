import { iconMarkup } from '../common/icons';

export function bindMediaItemForms(form) {
    const dialogs = [...form.querySelectorAll('[data-media-item-dialog]')];
    if (!dialogs.length) return;

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'media_item_metadata';
    form.append(input);

    const collections = {};
    const sync = () => {
        input.value = JSON.stringify(collections);
        input.dispatchEvent(new Event('change', { bubbles: true }));
    };

    for (const dialog of dialogs) {
        const picker = dialog.parentElement.querySelector('[data-media-picker]');
        const selected = picker.querySelector('[data-media-selected]');
        const collection = picker.dataset.collection;
        const values = { ...JSON.parse(dialog.dataset.values) };
        const preview = dialog.querySelector('[data-media-item-preview]');
        const previewImage = preview.querySelector('img');
        const previewCaption = preview.querySelector('figcaption');
        collections[collection] = values;
        const controls = [...dialog.querySelectorAll('[data-media-item-fields] [name]')].map((element) => {
            const name = element.name;
            element.removeAttribute('name');
            return { element, name, initial: element.value };
        });
        let activeId;
        let trigger;

        const decorate = () => {
            for (const card of selected.querySelectorAll('[data-media-id]')) {
                if (card.querySelector('[data-media-item-edit]')) continue;
                const button = document.createElement('button');
                button.type = 'button';
                button.dataset.mediaItemEdit = '';
                button.setAttribute('aria-label', dialog.querySelector('h2').textContent.trim());
                button.innerHTML = iconMarkup('copy', 16);
                card.append(button);
            }
        };
        decorate();
        new MutationObserver(decorate).observe(selected, { childList: true });

        picker.addEventListener('click', (event) => {
            const button = event.target.closest('[data-media-item-edit]');
            if (!button) return;
            trigger = button;
            const card = button.closest('[data-media-id]');
            activeId = card.dataset.mediaId;
            const image = card.querySelector('img');
            preview.hidden = !image;
            if (image) {
                previewImage.src = image.currentSrc || image.src;
                previewCaption.textContent = image.alt;
            }
            for (const { element, name, initial } of controls) {
                const value = values[activeId]?.[name.replace(/\[\]$/, '')];
                if (element.type === 'checkbox' || element.type === 'radio') {
                    element.checked = Array.isArray(value)
                        ? value.includes(element.value)
                        : String(value ?? '') === element.value;
                } else if (element.type !== 'file') {
                    if (element instanceof HTMLSelectElement && element.multiple) {
                        for (const option of element.options) option.selected = Array.isArray(value) && value.includes(option.value);
                    } else {
                        element.value = value ?? initial;
                    }
                    if (element instanceof HTMLSelectElement) window.jQuery?.(element).trigger('change.select2');
                }
            }
            dialog.showModal();
        });

        dialog.querySelector('[data-media-item-cancel]').onclick = () => dialog.close();
        dialog.querySelector('[data-media-item-apply]').onclick = () => {
            const item = {};
            for (const { element, name } of controls) {
                if (element.type === 'file' || element.disabled) continue;
                if (element instanceof HTMLSelectElement && element.multiple) {
                    item[name.replace(/\[\]$/, '')] = Array.from(element.selectedOptions, (option) => option.value);
                } else if (name.endsWith('[]')) {
                    const key = name.slice(0, -2);
                    item[key] ??= [];
                    if (element.checked) item[key].push(element.value);
                } else if (element.type !== 'radio' || element.checked) {
                    item[name] = element.type === 'checkbox' && !element.checked ? '0' : element.value;
                }
            }
            values[activeId] = item;
            sync();
            dialog.close();
        };
        dialog.addEventListener('close', () => trigger?.focus());
        picker.addEventListener('change', (event) => {
            if (event.target !== picker.querySelector('input[type=hidden]')) return;
            const ids = new Set(JSON.parse(event.target.value).map(String));
            for (const id of Object.keys(values)) {
                if (!ids.has(id)) delete values[id];
            }
            sync();
        });
    }
    input.value = JSON.stringify(collections);
}
