import '../../css/file-manager.css';
import PanelFileManager from '../media/file-manager';
import { pageData } from '../common/page';
import { iconMarkup } from '../common/icons';
import { announce } from '../common/toast';

const escape = value => String(value ?? '').replace(/[&<>"']/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[character]));

export function bindMediaManager(form) {
    const dialog = document.querySelector('[data-media-manager]');
    if (!dialog) return;
    const toast = document.querySelector('[data-ui-toast]');
    const toastParent = toast?.parentNode;
    const toastNext = toast?.nextSibling;
    dialog.addEventListener('close', () => {
        if (toast && toastParent) toastParent.insertBefore(toast, toastNext?.parentNode === toastParent ? toastNext : null);
    });
    const manager = new PanelFileManager(dialog.querySelector('[data-file-manager]'), {
        browseUrl: dialog.dataset.browseUrl, uploadUrl: dialog.dataset.uploadUrl,
        folderStoreUrl: dialog.dataset.folderUrl, itemsUrl: dialog.dataset.itemsUrl,
        pasteUrl: `${dialog.dataset.itemsUrl}/paste`, compressUrl: `${dialog.dataset.itemsUrl}/compress`,
        uploadGroups: {
            image: {extensions:['jpg','jpeg','png','gif','webp','bmp']},
            document: {extensions:['pdf','xls','xlsx','csv','doc','docx','ppt','pptx','txt','md','stl','obj']},
            audio: {extensions:['mp3','wav','ogg','flac','aac','m4a']},
            video: {extensions:['mp4','mpeg','mpg','webm','mov','mkv','avi']}, archive: {extensions:['zip']},
        },
    });
    manager.toast = (message, type) => announce(message, type === 'error');
    manager.bind();
    manager.setView(manager.view);
    manager.applyDetailsLayout();
    manager.updateUploadCount();
    window.addEventListener('cms:presentation-refreshed', async () => {
        manager.updateDirectionLabels();
        if (dialog.open) await manager.load();
    });
    dialog.addEventListener('keydown', event => {
        if (event.key !== 'Escape') return;
        const dismiss = !manager.elements.conflict.classList.contains('hidden')
            ? () => manager.elements.conflict.querySelector('[data-choice="cancel"],[data-answer="no"]')?.click()
            : !manager.elements.prompt.classList.contains('hidden') ? () => manager.closePrompt(null)
            : !manager.elements.previewModal.classList.contains('hidden') ? () => manager.closePreview()
            : !manager.elements.context.classList.contains('hidden') ? () => manager.hideContextMenu() : null;
        if (dismiss) { event.preventDefault(); event.stopImmediatePropagation(); dismiss(); }
    }, true);
    dialog.querySelectorAll('[data-file-picker-close]').forEach(button => button.addEventListener('click', () => manager.cancelPicker()));
    dialog.addEventListener('cancel', event => {
        if (!manager.elements.context.classList.contains('hidden')) {
            event.preventDefault(); manager.hideContextMenu();
        } else if (!manager.elements.prompt.classList.contains('hidden') || !manager.elements.conflict.classList.contains('hidden') || !manager.elements.previewModal.classList.contains('hidden')) {
            event.preventDefault(); manager.closePreview(); manager.closePrompt(null);
        } else manager.cancelPicker();
    });
    form.addEventListener('click', event => {
        const remove = event.target.closest('[data-media-remove]');
        if (remove) {
            const picker = remove.closest('[data-media-picker]');
            remove.closest('[data-media-id]').remove(); update(picker);
            return;
        }
        const open = event.target.closest('[data-media-open]');
        if (!open) return;
        const picker = open.closest('[data-media-picker]');
        const replacing = open.closest('[data-media-id]');
        const multiple = !replacing && picker.dataset.multiple === 'true';
        const groups = JSON.parse(picker.dataset.groups || '[]');
        dialog.showModal();
        if (toast) dialog.append(toast);
        manager.openPicker({
            multiple, max: multiple ? 100 : 1, groups,
            selected: [...(replacing ? [replacing] : picker.querySelectorAll('[data-media-record]'))].map(node => {
                const item = JSON.parse(node.dataset.mediaRecord);
                return {...item, type:'file', group:item.group || item.mime_type?.split('/')[0], url:item.url || item.src, thumbnail_url:item.thumbnail_url || item.src};
            }),
            onConfirm(items) {
                const root = picker.querySelector('[data-media-selected]');
                const cards = items.map(item => {
                    const card = document.createElement('article');
                    card.dataset.mediaId = item.id; card.dataset.mediaRecord = JSON.stringify(item);
                    card.innerHTML = `<button type="button" class="media-selector-preview" data-media-open aria-label="${escape(pageData().taxonomies?.media_replace || 'Replace image')}">${item.thumbnail_url ? `<img src="${escape(item.url || item.thumbnail_url)}" alt="${escape(item.name)}">` : `<span class="taxonomy-media-picker__file">${escape(item.extension)}</span>`}</button><span title="${escape(item.name)}">${escape(item.name)}</span><button type="button" data-media-remove aria-label="${escape(pageData().taxonomies?.media_remove || '')}">${iconMarkup('trash', 16)}</button>`;
                    return card;
                });
                if (replacing) {
                    const duplicate = [...root.children].some(node => node !== replacing && node.dataset.mediaId === cards[0]?.dataset.mediaId);
                    if (!duplicate && cards[0]) replacing.replaceWith(cards[0]);
                } else root.replaceChildren(...cards);
                update(picker);
            },
        });
    });
    function update(picker) {
        const input = picker.querySelector('input[type=hidden]');
        input.value = JSON.stringify([...picker.querySelectorAll('[data-media-id]')].map(node => Number(node.dataset.mediaId)));
        const addButton = picker.querySelector('.taxonomy-media-picker__open');
        if (addButton) addButton.hidden = picker.dataset.multiple !== 'true' && picker.querySelector('[data-media-id]') !== null;
        input.dispatchEvent(new Event('change', {bubbles:true}));
    }
}
