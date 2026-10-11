import { pageData } from '../common/page';
import { iconMarkup } from '../common/icons';
import { resolvePreviewDriver } from './previews/registry';
const fmText = key => pageData().fileManager?.texts?.[key] ?? '';
function fileManagerIconMarkup(name, size = 18, className = '') {
    return iconMarkup(name, size, className);
}

export default class PanelFileManager {
    constructor(root, config) {
        this.root = root;
        this.config = config;
        this.mode = root.dataset.mode || 'standalone';
        this.rootId = Number(root.dataset.rootId);
        this.folderId = this.rootId;
        this.history = [];
        this.breadcrumbs = [];
        this.items = [];
        this.total = 0;
        this.selected = new Set();
        this.selectedItems = new Map();
        this.clipboard = null;
        this.permissions = {};
        this.page = 1;
        this.lastPage = 1;
        this.loading = false;
        this.loadingMore = false;
        this.browseController = null;
        this.activeLoadToken = null;
        this.focusedId = null;
        this.selectionAnchorId = null;
        this.suppressedClick = null;
        this.draggedItemIds = [];
        this.dragTargetId = null;
        this.marqueeState = null;
        this.suppressBackgroundClick = false;
        this.previewItem = null;
        this.previewZoom = 1;
        this.previewFitZoom = 1;
        this.previewStructuredState = null;
        this.audioPreviewState = null;
        this.videoPreviewState = null;
        this.pdfPreviewState = null;
        this.pdfJsPromise = null;
        this.archiveBusy = false;
        this.detailsOpen = localStorage.getItem('panel-file-manager-details-open') !== '0';
        this.detailsWidth = Math.max(190, Math.min(460, Number(localStorage.getItem('panel-file-manager-details-width') || 270)));
        this.view = localStorage.getItem('panel-file-manager-view') || 'grid';
        this.pickerOptions = { multiple: false, max: 1, group: null, selected: [] };
        this.elements = {
            items: root.querySelector('[data-fm-items]'),
            loading: root.querySelector('[data-fm-loading]'),
            empty: root.querySelector('[data-fm-empty]'),
            breadcrumbs: root.querySelector('[data-fm-breadcrumbs]'),
            details: root.querySelector('[data-fm-details]'),
            detailsToggle: root.querySelector('[data-fm-details-toggle]'),
            detailsResizer: root.querySelector('[data-fm-details-resizer]'),
            actionsMenu: root.querySelector('[data-fm-actions-menu]'),
            actionsSummary: root.querySelector('[data-fm-actions-summary]'),
            selectionCount: root.querySelector('[data-fm-selection-count]'),
            loadMore: root.querySelector('[data-fm-load-more]'),
            fileInput: root.querySelector('[data-fm-file-input]'),
            search: root.querySelector('[data-fm-search]'),
            scope: root.querySelector('[data-fm-search-scope]'),
            group: root.querySelector('[data-fm-group]'),
            sort: root.querySelector('[data-fm-sort]'),
            direction: root.querySelector('[data-fm-direction]'),
            drop: root.querySelector('[data-fm-drop]'),
            uploads: root.querySelector('[data-fm-uploads]'),
            uploadList: root.querySelector('[data-fm-upload-list]'),
            uploadCount: root.querySelector('[data-fm-upload-count]'),
            conflict: root.querySelector('[data-fm-conflict]'),
            prompt: root.querySelector('[data-fm-prompt]'),
            previewModal: root.querySelector('[data-fm-preview-modal]'),
            previewStage: root.querySelector('[data-fm-preview-stage]'),
            previewTabs: root.querySelector('[data-fm-sheet-tabs]'),
            zoomTools: root.querySelector('[data-fm-zoom-tools]'),
            context: root.querySelector('[data-fm-context-menu]'),
            selectAll: root.querySelector('[data-fm-select-all]'),
            footerCount: root.querySelector('[data-fm-footer-count]'),
            selectedSummary: root.querySelector('[data-fm-selected-summary]'),
            toastStack: root.querySelector('[data-fm-toast-stack]'),
            pickerConfirm: root.querySelector('[data-fm-picker-confirm]'),
            pickerSummary: root.querySelector('[data-fm-picker-summary]'),
            back: root.querySelector('[data-fm-back]'),
        };
    }

    init() {
        this.bind();
        this.updateDirectionLabels();
        this.setView(this.view);
        this.applyDetailsLayout();
        this.updateUploadCount();
        this.load();
        this.root._panelFileManager = this;
        if (this.mode === 'standalone') PanelFileManager.activeInstance = this;
    }

    bind() {
        this.root.querySelector('[data-fm-refresh]')?.addEventListener('click', () => this.load());
        this.elements.back?.addEventListener('click', () => this.goBack());
        this.root.querySelector('[data-fm-upload]')?.addEventListener('click', () => this.elements.fileInput?.click());
        this.root.querySelector('[data-fm-new-folder]')?.addEventListener('click', () => this.createFolder());
        this.root.querySelector('[data-fm-copy]')?.addEventListener('click', () => this.setClipboard('copy'));
        this.root.querySelector('[data-fm-cut]')?.addEventListener('click', () => this.setClipboard('move'));
        this.root.querySelector('[data-fm-paste]')?.addEventListener('click', () => this.paste());
        this.root.querySelector('[data-fm-compress]')?.addEventListener('click', () => this.compressSelected());
        this.root.querySelector('[data-fm-rename]')?.addEventListener('click', () => this.rename());
        this.root.querySelector('[data-fm-delete]')?.addEventListener('click', () => this.destroySelected());
        this.root.querySelector('[data-fm-delete-toolbar]')?.addEventListener('click', () => this.destroySelected());
        this.elements.pickerConfirm?.addEventListener('click', () => this.confirmPicker());
        this.root.querySelectorAll('[data-fm-view]').forEach((button) => button.addEventListener('click', () => this.setView(button.dataset.fmView)));
        this.root.querySelector('[data-fm-uploads-toggle]')?.addEventListener('click', () => {
            if (this.elements.uploads?.classList.contains('hidden')) {
                this.elements.uploads.classList.remove('hidden', 'is-minimized');
            } else {
                this.elements.uploads.classList.toggle('hidden');
            }
        });
        this.root.querySelector('[data-fm-uploads-close]')?.addEventListener('click', () => this.elements.uploads?.classList.add('hidden'));
        this.root.querySelector('[data-fm-uploads-minimize]')?.addEventListener('click', () => this.elements.uploads?.classList.toggle('is-minimized'));
        this.root.querySelector('[data-fm-empty-upload-trigger]')?.addEventListener('click', () => this.elements.fileInput?.click());
        this.elements.detailsToggle?.addEventListener('click', () => this.toggleDetails());
        this.elements.details?.addEventListener('click', (event) => {
            if (event.target.closest('[data-fm-details-close]')) this.toggleDetails(false);
        });
        this.bindDetailsResize();
        this.root.querySelector('[data-fm-filter-reset]')?.addEventListener('click', () => this.resetFilters());
        this.elements.prompt?.addEventListener('pointerdown', (event) => {
            if (event.target === this.elements.prompt) this.closePrompt(null);
        });
        this.root.querySelectorAll('[data-fm-preview-close]').forEach((element) => element.addEventListener('click', () => this.closePreview()));
        const audioHelp = this.root.querySelector('[data-audio-help]');
        const audioHelpTrigger = audioHelp?.querySelector('[data-audio-help-trigger]');
        const audioHelpMenu = audioHelp?.querySelector('[data-audio-help-menu]');
        audioHelpTrigger?.addEventListener('click', () => {
            const expanded = audioHelpTrigger.getAttribute('aria-expanded') === 'true';
            audioHelpTrigger.setAttribute('aria-expanded', expanded ? 'false' : 'true');
            audioHelpMenu?.classList.toggle('hidden', expanded);
        });
        this.root.addEventListener('click', (event) => {
            if (event.target.closest('[data-audio-help]')) return;
            audioHelpTrigger?.setAttribute('aria-expanded', 'false');
            audioHelpMenu?.classList.add('hidden');
        });
        this.root.querySelector('[data-fm-zoom-in]')?.addEventListener('click', () => this.changeImageZoom(1.25));
        this.root.querySelector('[data-fm-zoom-out]')?.addEventListener('click', () => this.changeImageZoom(.8));
        this.root.querySelector('[data-fm-zoom-fit]')?.addEventListener('click', () => this.fitPreviewImage());
        this.elements.previewStage?.addEventListener('wheel', (event) => {
            if (!this.previewItem || this.previewItem.group !== 'image') return;
            event.preventDefault();
            this.changeImageZoom(event.deltaY < 0 ? 1.12 : .89);
        }, { passive: false });
        this.bindPreviewPan();

        this.root.querySelectorAll('.panel-file-manager__actions-popover button').forEach((button) => {
            button.addEventListener('click', () => button.closest('details')?.removeAttribute('open'));
        });
        const toolbarMenus = [...this.root.querySelectorAll('.panel-file-manager__menu')];
        toolbarMenus.forEach((menu) => {
            const summary = menu.querySelector(':scope > summary');
            summary?.setAttribute('aria-expanded', menu.open ? 'true' : 'false');
            summary?.addEventListener('click', (event) => {
                if (menu.classList.contains('is-disabled')) {
                    event.preventDefault();
                    event.stopPropagation();
                    return;
                }
                toolbarMenus.forEach((otherMenu) => {
                    if (otherMenu !== menu) otherMenu.removeAttribute('open');
                });
            });
            menu.addEventListener('toggle', () => {
                if (menu.open && menu.classList.contains('is-disabled')) menu.removeAttribute('open');
                if (menu.open) {
                    toolbarMenus.forEach((otherMenu) => {
                        if (otherMenu !== menu) otherMenu.removeAttribute('open');
                    });
                }
                summary?.setAttribute('aria-expanded', menu.open ? 'true' : 'false');
            });
        });

        let searchTimer;
        this.elements.search?.addEventListener('input', () => {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(() => { this.page = 1; this.load(); }, 300);
        });
        [this.elements.scope, this.elements.group, this.elements.sort, this.elements.direction].forEach((field) => {
            field?.addEventListener('change', () => {
                if (field === this.elements.sort) this.updateDirectionLabels();
                this.page = 1;
                this.load();
            });
        });
        this.elements.fileInput?.addEventListener('change', () => {
            this.uploadFiles(Array.from(this.elements.fileInput.files || []));
            this.elements.fileInput.value = '';
        });
        this.elements.selectAll?.addEventListener('click', (event) => event.stopPropagation());
        this.elements.selectAll?.addEventListener('change', () => this.selectVisible(this.elements.selectAll.checked));

        let dragDepth = 0;
        this.elements.drop?.addEventListener('dragenter', (event) => {
            if (!this.isExternalFileDrag(event)) return;
            event.preventDefault();
            dragDepth += 1;
            this.elements.drop.classList.add('is-dragging');
        });
        this.elements.drop?.addEventListener('dragover', (event) => {
            if (!this.isExternalFileDrag(event)) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
        });
        this.elements.drop?.addEventListener('dragleave', (event) => {
            if (!this.isExternalFileDrag(event)) return;
            event.preventDefault();
            dragDepth = Math.max(0, dragDepth - 1);
            if (!dragDepth) this.elements.drop.classList.remove('is-dragging');
        });
        this.elements.drop?.addEventListener('drop', (event) => {
            if (!this.isExternalFileDrag(event)) return;
            event.preventDefault();
            dragDepth = 0;
            this.elements.drop.classList.remove('is-dragging');
            this.uploadFiles(Array.from(event.dataTransfer?.files || []));
        });
        this.elements.drop?.addEventListener('scroll', () => {
            const distanceToEnd = this.elements.drop.scrollHeight - this.elements.drop.scrollTop - this.elements.drop.clientHeight;
            if (distanceToEnd < 180) this.loadNextPage();
        }, { passive: true });

        this.elements.drop?.addEventListener('pointerdown', (event) => this.startMarqueeSelection(event));
        document.addEventListener('pointermove', (event) => this.updateMarqueeSelection(event));
        document.addEventListener('pointerup', (event) => this.finishMarqueeSelection(event));
        document.addEventListener('pointercancel', (event) => this.finishMarqueeSelection(event));
        this.elements.drop?.addEventListener('click', (event) => {
            if (this.suppressBackgroundClick) {
                this.suppressBackgroundClick = false;
                event.preventDefault();
                event.stopPropagation();
                return;
            }
            if (event.target === this.elements.drop || event.target === this.elements.items) this.clearSelection();
        });
        this.elements.drop?.addEventListener('contextmenu', (event) => {
            const element = event.target.closest('[data-fm-item]');
            const item = element ? this.item(Number(element.dataset.fmItem)) : null;
            if (item && !this.selected.has(item.id)) this.select(item.id);
            this.showContextMenu(event, item);
        });
        document.addEventListener('pointerdown', (event) => {
            if (!event.target.closest('[data-fm-context-menu]')) this.hideContextMenu();
            const clickedMenu = event.target.closest('.panel-file-manager__menu');
            this.root.querySelectorAll('.panel-file-manager__menu[open]').forEach((menu) => {
                if (menu !== clickedMenu) menu.removeAttribute('open');
            });
        });

        this.root.addEventListener('pointerdown', () => { PanelFileManager.activeInstance = this; }, true);
        this.root.addEventListener('focusin', () => { PanelFileManager.activeInstance = this; });

        // Capture file-manager shortcuts before focused widgets (and the browser's
        // default page actions) can consume them. `event.code` is intentionally
        // used below so Ctrl+C/X/V/A also work while the OS keyboard layout is
        // Persian or any other non-Latin layout.
        this.keyboardHandler = (event) => {
            if (PanelFileManager.activeInstance !== this) return;
            this.handleKeyboard(event);
        };
        document.addEventListener('keydown', this.keyboardHandler, true);
    }

    handleKeyboard(event) {
        if (event.key === 'Escape') {
            if (!this.elements.previewModal?.classList.contains('hidden')) this.closePreview();
            this.hideContextMenu();
            this.root.querySelectorAll('.panel-file-manager__menu[open]').forEach((menu) => menu.removeAttribute('open'));
            if (!this.elements.prompt?.classList.contains('hidden')) this.closePrompt(null);
            return;
        }
        if (event.isComposing) return;
        const target = event.target instanceof Element ? event.target : document.activeElement;
        const isEditable = Boolean(target?.closest?.('input, select, textarea, [contenteditable="true"], [role="textbox"]'));
        const commandPressed = event.ctrlKey || event.metaKey;
        const physicalKey = event.code?.startsWith('Key')
            ? event.code.slice(3).toLowerCase()
            : String(event.key || '').toLowerCase();
        if (commandPressed && !event.altKey && physicalKey === 'f' && !this.elements.previewModal?.classList.contains('hidden') && this.elements.previewStage?.classList.contains('is-archive')) {
            event.preventDefault();
            event.stopImmediatePropagation();
            const search = this.elements.previewStage.querySelector('[data-archive-search]');
            search?.focus({ preventScroll: true });
            search?.select();
            return;
        }
        if (isEditable) return;

        const consume = () => {
            event.preventDefault();
            event.stopImmediatePropagation();
        };

        if (event.key.startsWith('Arrow') && !document.activeElement?.matches?.('[data-fm-item]')) {
            const first = this.elements.items.querySelector('[data-fm-item]');
            if (first) {
                event.preventDefault();
                this.focusItem(Number(first.dataset.fmItem));
            }
            return;
        }
        if (event.key === 'Delete' && this.selected.size) {
            consume();
            if (!event.repeat) this.destroySelected();
            return;
        }
        if (event.key === 'F2' && this.selected.size === 1) {
            consume();
            if (!event.repeat) this.rename();
            return;
        }
        if (!commandPressed || event.altKey) return;
        if (physicalKey === 'a') {
            consume();
            this.selectVisible(true);
            return;
        }
        if (physicalKey === 'c' && this.selected.size) {
            consume();
            this.setClipboard('copy');
            return;
        }
        if (physicalKey === 'x' && this.selected.size) {
            consume();
            if (!event.repeat) this.setClipboard('move');
            return;
        }
        if (physicalKey === 'v') {
            consume();
            if (!event.repeat) this.paste();
        }
    }

    bindDetailsResize() {
        const handle = this.elements.detailsResizer;
        if (!handle) return;
        let startX = 0;
        let startWidth = 0;
        const resize = (event) => {
            const max = Math.min(480, Math.max(240, this.root.clientWidth * 0.48));
            this.detailsWidth = Math.round(Math.max(190, Math.min(max, startWidth + (event.clientX - startX))));
            this.root.style.setProperty('--fm-details-width', `${this.detailsWidth}px`);
            handle.setAttribute('aria-valuenow', String(this.detailsWidth));
        };
        const stop = () => {
            document.removeEventListener('pointermove', resize);
            document.removeEventListener('pointerup', stop);
            document.body.classList.remove('panel-file-manager-resizing');
            localStorage.setItem('panel-file-manager-details-width', String(this.detailsWidth));
        };
        handle.addEventListener('pointerdown', (event) => {
            if (!this.detailsOpen || window.innerWidth <= 800) return;
            event.preventDefault();
            startX = event.clientX;
            startWidth = this.elements.details.getBoundingClientRect().width;
            handle.setPointerCapture?.(event.pointerId);
            document.body.classList.add('panel-file-manager-resizing');
            document.addEventListener('pointermove', resize);
            document.addEventListener('pointerup', stop, { once: true });
        });
        handle.addEventListener('keydown', (event) => {
            if (!['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
            event.preventDefault();
            const delta = event.key === 'ArrowRight' ? 12 : -12;
            this.detailsWidth = Math.max(190, Math.min(480, this.detailsWidth + delta));
            this.root.style.setProperty('--fm-details-width', `${this.detailsWidth}px`);
            handle.setAttribute('aria-valuenow', String(this.detailsWidth));
            localStorage.setItem('panel-file-manager-details-width', String(this.detailsWidth));
        });
    }

    toggleDetails(force = null) {
        this.detailsOpen = force === null ? !this.detailsOpen : Boolean(force);
        localStorage.setItem('panel-file-manager-details-open', this.detailsOpen ? '1' : '0');
        this.applyDetailsLayout();
    }

    applyDetailsLayout() {
        this.root.style.setProperty('--fm-details-width', `${this.detailsWidth}px`);
        this.elements.detailsResizer?.setAttribute('aria-valuenow', String(this.detailsWidth));
        this.root.classList.toggle('is-details-collapsed', !this.detailsOpen);
        if (this.elements.detailsToggle) {
            this.elements.detailsToggle.setAttribute('aria-pressed', this.detailsOpen ? 'true' : 'false');
            this.elements.detailsToggle.classList.toggle('is-active', this.detailsOpen);
            this.elements.detailsToggle.title = `${fmText(0)}`;
            this.elements.detailsToggle.setAttribute('aria-label', `${fmText(0)}`);
        }
    }

    resetFilters() {
        if (this.elements.search) this.elements.search.value = '';
        if (this.elements.scope) this.elements.scope.value = 'folder';
        if (this.elements.group) this.elements.group.value = '';
        if (this.elements.sort) this.elements.sort.value = 'name';
        if (this.elements.direction) this.elements.direction.value = 'asc';
        this.updateDirectionLabels();
        this.page = 1;
        this.load();
    }

    updateDirectionLabels() {
        if (!this.elements.direction) return;
        const dateBased = ['updated_at', 'created_at'].includes(this.elements.sort?.value);
        const labels = dateBased
            ? { asc: `${fmText(1)}`, desc: `${fmText(2)}` }
            : { asc: `${fmText(3)}`, desc: `${fmText(4)}` };
        [...this.elements.direction.options].forEach((option) => { option.textContent = labels[option.value] || option.textContent; });
    }

    async load({ append = false } = {}) {
        if (append && (this.loadingMore || this.loading || this.page > this.lastPage)) return;
        if (append) {
            this.loadingMore = true;
            this.setLoadMoreState(true);
        } else {
            this.loading = true;
            this.page = 1;
            this.lastPage = 1;
            this.toggleLoading(true);
        }
        this.browseController?.abort();
        this.browseController = new AbortController();
        const loadToken = Symbol('file-manager-load');
        this.activeLoadToken = loadToken;
        const params = new URLSearchParams({ parent_id: this.folderId, page: this.page });
        if (this.elements.search?.value) params.set('search', this.elements.search.value);
        if (this.elements.scope?.value) params.set('scope', this.elements.scope.value);
        // The picker collection limits what can be selected, not what the file
        // manager is allowed to display. Hiding other groups made successful
        // uploads (for example a PDF in an image picker) look as if they failed.
        const group = this.elements.group?.value;
        if (group) params.set('group', group);
        if (this.elements.sort?.value) params.set('sort', this.elements.sort.value);
        if (this.elements.direction?.value) params.set('direction', this.elements.direction.value);

        try {
            const response = await fetch(`${this.config.browseUrl}?${params}`, {
                headers: { Accept: 'application/json' },
                signal: this.browseController.signal,
            });
            const payload = await response.json();
            if (this.activeLoadToken !== loadToken) return;
            if (!response.ok) throw new Error(payload.message || `${fmText(5)}`);
            const incoming = payload.data || [];
            let renderedItems = incoming;
            if (append) {
                const known = new Set(this.items.map((item) => Number(item.id)));
                renderedItems = incoming.filter((item) => !known.has(Number(item.id)));
                this.items.push(...renderedItems);
            } else {
                this.items = incoming;
                this.elements.drop.scrollTop = 0;
            }
            this.total = Number(payload.meta?.total || this.items.length);
            this.page = Number(payload.meta?.current_page || this.page);
            this.lastPage = Number(payload.meta?.last_page || 1);
            this.breadcrumbs = payload.breadcrumbs || [];
            this.permissions = payload.permissions || {};
            this.renderItems({ append, incoming: renderedItems });
            this.renderBreadcrumbs();
            this.setLoadMoreState(false);
            this.applyPermissions();
            this.elements.back.disabled = this.folderId === this.rootId;
            this.elements.footerCount.textContent = `${this.total.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(6)}`;
        } catch (error) {
            if (error.name !== 'AbortError') {
                if (append) this.page = Math.max(1, this.page - 1);
                this.toast(error.message, 'error');
            }
        } finally {
            if (this.activeLoadToken !== loadToken) return;
            if (append) {
                this.loadingMore = false;
                this.setLoadMoreState(false);
            } else {
                this.loading = false;
                this.toggleLoading(false);
            }
        }
    }

    loadNextPage() {
        if (this.loading || this.loadingMore || this.page >= this.lastPage) return;
        this.page += 1;
        this.load({ append: true });
    }

    renderItems({ append = false, incoming = [] } = {}) {
        if (this.mode !== 'picker') {
            this.selected = new Set([...this.selected].filter((id) => this.items.some((item) => item.id === id)));
            this.selectedItems = new Map([...this.selected].map((id) => [id, this.item(id)]).filter(([, item]) => item));
        }
        this.items.forEach((item) => { if (this.selected.has(item.id)) this.selectedItems.set(item.id, item); });
        if (append) {
            this.elements.items.insertAdjacentHTML('beforeend', incoming.map((item) => this.itemHtml(item)).join(''));
        } else {
            this.elements.items.innerHTML = this.items.map((item) => this.itemHtml(item)).join('');
        }
        this.elements.empty.classList.toggle('hidden', this.items.length > 0);
        if (!this.focusedId || !this.items.some((item) => item.id === this.focusedId)) {
            this.focusedId = this.items[0]?.id || null;
        }
        this.elements.items.querySelectorAll('[data-fm-item]:not([data-fm-bound])').forEach((element) => {
            element.dataset.fmBound = 'true';
            const id = Number(element.dataset.fmItem);
            element.tabIndex = id === this.focusedId ? 0 : -1;
            element.addEventListener('focus', () => { this.focusedId = id; this.updateRovingTabIndex(); });
            element.addEventListener('dragstart', (event) => this.startItemDrag(event, id));
            element.addEventListener('dragend', () => this.finishItemDrag());
            if (this.item(id)?.type === 'folder') {
                element.addEventListener('dragenter', (event) => this.enterFolderDropTarget(event, id));
                element.addEventListener('dragover', (event) => this.overFolderDropTarget(event, id));
                element.addEventListener('dragleave', (event) => this.leaveFolderDropTarget(event, element));
                element.addEventListener('drop', (event) => this.dropItemsOnFolder(event, id));
            }
            element.addEventListener('pointerdown', (event) => {
                if (event.button !== 0 || (!event.ctrlKey && !event.metaKey) || event.target.closest('.panel-file-manager__item-check')) return;
                event.preventDefault();
                this.focusedId = id;
                element.focus({ preventScroll: true });
                this.suppressedClick = { id, until: Date.now() + 750 };
                setTimeout(() => {
                    if (this.suppressedClick?.id === id && this.suppressedClick.until <= Date.now()) this.suppressedClick = null;
                }, 800);
                this.select(id, true);
            });
            element.addEventListener('click', (event) => {
                if (this.suppressedClick?.id === id && this.suppressedClick.until > Date.now()) {
                    this.suppressedClick = null;
                    return;
                }
                // The second click which belongs to a double-click must not undo the
                // selection made by its first click. The dblclick handler will open it.
                if (event.detail > 1) return;
                this.focusedId = id;
                element.focus({ preventScroll: true });
                if (event.shiftKey) {
                    this.selectRange(id, event.ctrlKey || event.metaKey);
                } else {
                    const clickedCheck = Boolean(event.target.closest('.panel-file-manager__item-check'));
                    this.select(id, clickedCheck || event.ctrlKey || event.metaKey);
                }
            });
            element.addEventListener('dblclick', () => this.activateItem(id));
            element.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') { event.preventDefault(); this.activateItem(id); }
                if (event.key === ' ' || event.key === 'Spacebar') {
                    event.preventDefault();
                    this.select(id, event.ctrlKey || event.metaKey);
                }
                if (['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) {
                    event.preventDefault();
                    const nextId = this.moveFocus(id, event.key);
                    if (event.shiftKey && nextId) this.selectRange(nextId, event.ctrlKey || event.metaKey);
                }
            });
        });
        this.renderSelection();
    }

    isExternalFileDrag(event) {
        const types = Array.from(event.dataTransfer?.types || []);
        return !types.includes('application/x-panel-file-manager-items') && types.includes('Files');
    }

    isInternalItemDrag(event) {
        const types = Array.from(event.dataTransfer?.types || []);
        return this.draggedItemIds.length > 0 || types.includes('application/x-panel-file-manager-items');
    }

    startItemDrag(event, id) {
        if (!this.permissions.manage || !event.dataTransfer) {
            event.preventDefault();
            return;
        }
        if (!this.selected.has(id)) this.select(id);
        const ids = this.selectedEntries().map((item) => Number(item.id));
        if (!ids.length) {
            event.preventDefault();
            return;
        }
        this.draggedItemIds = ids;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('application/x-panel-file-manager-items', JSON.stringify(ids));
        event.dataTransfer.setData('text/plain', ids.join(','));
        this.root.classList.add('is-item-dragging');
        this.elements.items.querySelectorAll('[data-fm-item]').forEach((element) => {
            const dragged = ids.includes(Number(element.dataset.fmItem));
            element.classList.toggle('is-drag-source', dragged);
            element.setAttribute('aria-grabbed', dragged ? 'true' : 'false');
        });

        const ghost = document.createElement('div');
        ghost.className = 'panel-file-manager__drag-ghost';
        ghost.innerHTML = `${fileManagerIconMarkup('folder-upload', 24)}<strong>${ids.length.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(6)}</strong><span>${fmText(7)}</span>`;
        document.body.appendChild(ghost);
        event.dataTransfer.setDragImage(ghost, 26, 24);
        setTimeout(() => ghost.remove());
    }

    draggedIds(event) {
        try {
            const encoded = event.dataTransfer?.getData('application/x-panel-file-manager-items');
            const parsed = encoded ? JSON.parse(encoded) : this.draggedItemIds;
            return [...new Set((Array.isArray(parsed) ? parsed : []).map(Number).filter(Number.isInteger))];
        } catch {
            return [...this.draggedItemIds];
        }
    }

    enterFolderDropTarget(event, folderId) {
        if (!this.isInternalItemDrag(event)) return;
        event.preventDefault();
        event.stopPropagation();
        this.showFolderDropTarget(folderId, this.draggedItemIds.includes(Number(folderId)));
    }

    overFolderDropTarget(event, folderId) {
        if (!this.isInternalItemDrag(event)) return;
        event.preventDefault();
        event.stopPropagation();
        const blocked = this.draggedItemIds.includes(Number(folderId));
        event.dataTransfer.dropEffect = blocked ? 'none' : 'move';
        this.showFolderDropTarget(folderId, blocked);
    }

    leaveFolderDropTarget(event, element) {
        if (!this.isInternalItemDrag(event) || element.contains(event.relatedTarget)) return;
        element.classList.remove('is-drop-target', 'is-drop-blocked');
        element.removeAttribute('data-drop-label');
        if (this.dragTargetId === Number(element.dataset.fmItem)) this.dragTargetId = null;
    }

    showFolderDropTarget(folderId, blocked = false) {
        if (this.dragTargetId !== Number(folderId)) {
            this.elements.items.querySelectorAll('.is-drop-target,.is-drop-blocked').forEach((element) => {
                element.classList.remove('is-drop-target', 'is-drop-blocked');
                element.removeAttribute('data-drop-label');
            });
        }
        const target = this.elements.items.querySelector(`[data-fm-item="${Number(folderId)}"]`);
        if (!target) return;
        const count = this.draggedItemIds.length;
        target.classList.toggle('is-drop-target', !blocked);
        target.classList.toggle('is-drop-blocked', blocked);
        target.dataset.dropLabel = blocked
            ? `${fmText(8)}`
            : `${count.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(9)}`;
        this.dragTargetId = Number(folderId);
    }

    async dropItemsOnFolder(event, folderId) {
        if (!this.isInternalItemDrag(event)) return;
        event.preventDefault();
        event.stopPropagation();
        const ids = this.draggedIds(event);
        const blocked = ids.includes(Number(folderId));
        this.finishItemDrag();
        if (blocked) {
            this.toast(`${fmText(10)}`, 'warning');
            return;
        }
        await this.moveItemsToFolder(ids, Number(folderId));
    }

    finishItemDrag() {
        this.draggedItemIds = [];
        this.dragTargetId = null;
        this.root.classList.remove('is-item-dragging');
        this.elements.drop?.classList.remove('is-dragging');
        this.elements.items.querySelectorAll('.is-drag-source,.is-drop-target,.is-drop-blocked').forEach((element) => {
            element.classList.remove('is-drag-source', 'is-drop-target', 'is-drop-blocked');
            element.removeAttribute('aria-grabbed');
            element.removeAttribute('data-drop-label');
        });
    }

    startMarqueeSelection(event) {
        if (event.button !== 0 || event.pointerType === 'touch') return;
        if (event.target.closest('[data-fm-item],button,input,select,textarea,a,[data-fm-load-more]')) return;
        const drop = this.elements.drop;
        if (!drop || !drop.contains(event.target)) return;
        const bounds = drop.getBoundingClientRect();
        const box = document.createElement('div');
        box.className = 'panel-file-manager__marquee hidden';
        drop.appendChild(box);
        this.marqueeState = {
            pointerId: event.pointerId,
            startClientX: event.clientX,
            startClientY: event.clientY,
            startX: event.clientX - bounds.left + drop.scrollLeft,
            startY: event.clientY - bounds.top + drop.scrollTop,
            currentClientX: event.clientX,
            currentClientY: event.clientY,
            baseline: new Set(this.selected),
            toggle: event.ctrlKey || event.metaKey,
            additive: event.shiftKey,
            moved: false,
            box,
        };
        try { drop.setPointerCapture(event.pointerId); } catch {}
    }

    updateMarqueeSelection(event) {
        const state = this.marqueeState;
        const drop = this.elements.drop;
        if (!state || !drop || event.pointerId !== state.pointerId) return;
        state.currentClientX = event.clientX;
        state.currentClientY = event.clientY;
        const distance = Math.hypot(event.clientX - state.startClientX, event.clientY - state.startClientY);
        if (!state.moved && distance < 5) return;
        if (!state.moved) {
            state.moved = true;
            state.box.classList.remove('hidden');
            document.body.classList.add('panel-file-manager-marquee-active');
        }
        event.preventDefault();

        const bounds = drop.getBoundingClientRect();
        const edge = 34;
        if (event.clientY < bounds.top + edge) drop.scrollTop = Math.max(0, drop.scrollTop - 14);
        if (event.clientY > bounds.bottom - edge) drop.scrollTop += 14;
        const currentX = event.clientX - bounds.left + drop.scrollLeft;
        const currentY = event.clientY - bounds.top + drop.scrollTop;
        const left = Math.min(state.startX, currentX);
        const top = Math.min(state.startY, currentY);
        const width = Math.abs(currentX - state.startX);
        const height = Math.abs(currentY - state.startY);
        Object.assign(state.box.style, { left: `${left}px`, top: `${top}px`, width: `${width}px`, height: `${height}px` });

        const selectionRect = {
            left: bounds.left + left - drop.scrollLeft,
            top: bounds.top + top - drop.scrollTop,
            right: bounds.left + left + width - drop.scrollLeft,
            bottom: bounds.top + top + height - drop.scrollTop,
        };
        const inside = [...this.elements.items.querySelectorAll('[data-fm-item]')]
            .filter((element) => {
                const rect = element.getBoundingClientRect();
                return rect.right >= selectionRect.left
                    && rect.left <= selectionRect.right
                    && rect.bottom >= selectionRect.top
                    && rect.top <= selectionRect.bottom;
            })
            .map((element) => Number(element.dataset.fmItem));
        const next = state.toggle || state.additive ? new Set(state.baseline) : new Set();
        inside.forEach((id) => {
            if (state.toggle && state.baseline.has(id)) next.delete(id);
            else next.add(id);
        });
        this.selected = next;
        this.selectedItems = new Map([...next]
            .map((id) => [id, this.selectedItems.get(id) || this.item(id)])
            .filter(([, item]) => item));
        this.refreshItemSelection();
        this.renderSelection();
    }

    finishMarqueeSelection(event) {
        const state = this.marqueeState;
        if (!state || event.pointerId !== state.pointerId) return;
        this.marqueeState = null;
        state.box.remove();
        document.body.classList.remove('panel-file-manager-marquee-active');
        try { this.elements.drop?.releasePointerCapture(event.pointerId); } catch {}
        if (!state.moved) return;
        this.suppressBackgroundClick = true;
        setTimeout(() => { this.suppressBackgroundClick = false; }, 120);
        const selectedVisible = this.items.filter((item) => this.selected.has(item.id));
        const last = selectedVisible.at(-1);
        this.selectionAnchorId = last?.id || this.selectionAnchorId;
        if (last) this.renderDetails(last);
        else this.resetDetails();
    }

    itemHtml(item) {
        const selected = this.selected.has(item.id);
        const pickerIncompatible = this.isPickerItemIncompatible(item);
        const visual = item.type === 'folder'
            ? '<i class="panel-file-manager__folder-icon" aria-hidden="true"></i>'
            : (item.thumbnail_url
                ? `<img src="${this.escape(item.group === 'image' ? item.url : item.thumbnail_url)}" alt="">`
                : this.fileIcon(item));
        const typeLabel = item.type === 'folder' ? `${fmText(11)}` : this.fileTypeLabel(item);
        const sizeLabel = item.type === 'folder' ? `${Number(item.children_count || 0).toLocaleString(document.documentElement.lang || 'fa')} ${fmText(6)}` : this.formatSize(item.size);
        const dateLabel = item.updated_at_display || '—';
        return `<article class="panel-file-manager__item is-${item.type} ${selected ? 'is-selected' : ''} ${pickerIncompatible ? 'is-picker-incompatible' : ''}" data-fm-item="${item.id}" tabindex="${item.id === this.focusedId ? '0' : '-1'}" role="option" aria-selected="${selected}" draggable="${this.permissions.manage ? 'true' : 'false'}" ${pickerIncompatible ? 'data-picker-incompatible="true"' : ''}>
            <button type="button" class="panel-file-manager__item-check" aria-label="${fmText(12)} ${this.escape(item.name)}" title="${fmText(13)}">${fileManagerIconMarkup('check', 17)}</button>
            <div class="panel-file-manager__thumb">${visual}</div>
            <div class="panel-file-manager__item-primary"><strong title="${this.escape(item.name)}">${this.escape(item.name)}</strong><small>${pickerIncompatible ? `${fmText(14)}` : this.escape(sizeLabel)}</small></div>
            <span class="panel-file-manager__item-type">${this.escape(typeLabel)}</span>
            <span class="panel-file-manager__item-size">${this.escape(sizeLabel)}</span>
            <time class="panel-file-manager__item-date">${this.escape(dateLabel)}</time>
        </article>`;
    }

    fileIcon(item) {
        const icons = { image: 'file-image', document: 'file-text', video: 'file-video', audio: 'file-audio', archive: 'file-archive' };
        const group = this.escape(item.group || 'file');
        const extension = this.escape((item.extension || 'FILE').toUpperCase());
        return `<span class="panel-file-manager__file-icon is-${group}">${fileManagerIconMarkup(icons[item.group] || 'file-text', 22)}<b>${extension}</b></span>`;
    }

    fileTypeLabel(item) {
        if (item?.type === 'folder' || item?.group === 'folder') return `${fmText(11)}`;
        return ({ image: `${fmText(15)}`, document: `${fmText(16)}`, video: `${fmText(17)}`, audio: `${fmText(18)}`, archive: `${fmText(19)}` })[item.group] || (item.extension ? String(item.extension).toUpperCase() : `${fmText(20)}`);
    }

    isPickerItemIncompatible(item) {
        return this.mode === 'picker'
            && item?.type === 'file'
            && Boolean(this.pickerOptions.groups?.length)
            && !this.pickerOptions.groups.some(group => group === item.group || group === item.mime_type || group === item.mime_group || (group.endsWith('/*') && item.mime_type?.startsWith(group.slice(0, -1))));
    }

    updateRovingTabIndex() {
        this.elements.items.querySelectorAll('[data-fm-item]').forEach((element) => {
            element.tabIndex = Number(element.dataset.fmItem) === this.focusedId ? 0 : -1;
        });
    }

    focusItem(id) {
        const element = this.elements.items.querySelector(`[data-fm-item="${Number(id)}"]`);
        if (!element) return;
        this.focusedId = Number(id);
        this.updateRovingTabIndex();
        element.focus({ preventScroll: true });
        element.scrollIntoView({ block: 'nearest', inline: 'nearest' });
    }

    moveFocus(id, direction) {
        const current = this.elements.items.querySelector(`[data-fm-item="${Number(id)}"]`);
        if (!current) return;
        const currentRect = current.getBoundingClientRect();
        const currentX = currentRect.left + (currentRect.width / 2);
        const currentY = currentRect.top + (currentRect.height / 2);
        const vertical = direction === 'ArrowUp' || direction === 'ArrowDown';
        const forward = direction === 'ArrowRight' || direction === 'ArrowDown';
        const candidates = [...this.elements.items.querySelectorAll('[data-fm-item]')]
            .filter((element) => element !== current)
            .map((element) => {
                const rect = element.getBoundingClientRect();
                const x = rect.left + (rect.width / 2);
                const y = rect.top + (rect.height / 2);
                const primary = vertical ? y - currentY : x - currentX;
                const secondary = vertical ? Math.abs(x - currentX) : Math.abs(y - currentY);
                return { element, primary, secondary };
            })
            .filter((candidate) => forward ? candidate.primary > 2 : candidate.primary < -2)
            .map((candidate) => ({ ...candidate, distance: Math.abs(candidate.primary), inLane: candidate.secondary <= Math.max(55, Math.abs(candidate.primary) * .65) }));
        const pool = candidates.some((candidate) => candidate.inLane) ? candidates.filter((candidate) => candidate.inLane) : candidates;
        pool.sort((a, b) => ((a.distance * 10) + a.secondary) - ((b.distance * 10) + b.secondary));
        if (!pool[0]) return null;
        const nextId = Number(pool[0].element.dataset.fmItem);
        this.focusItem(nextId);
        return nextId;
    }

    activateItem(id) {
        const item = this.item(id);
        if (!item) return;
        if (item.type === 'folder') { this.openFolder(id); return; }
        if (this.mode === 'picker') {
            if (this.isPickerItemIncompatible(item)) {
                if (!this.selected.has(id)) this.select(id);
                this.renderDetails(item);
                this.toast(this.pickerSelectionIssue() || `${fmText(21)}`, 'warning');
                return;
            }
            if (!this.selected.has(id)) this.select(id);
            this.confirmPicker();
            return;
        }
        if (item.url) this.openPreview(item);
    }

    renderBreadcrumbs() {
        this.elements.breadcrumbs.innerHTML = this.breadcrumbs.map((crumb, index) => `<button type="button" data-crumb="${crumb.id}">${fileManagerIconMarkup(index ? 'chevron-left' : 'layout-navbar', 16)}${this.escape(crumb.name)}</button>`).join('');
        this.elements.breadcrumbs.querySelectorAll('[data-crumb]').forEach((button) => button.addEventListener('click', () => this.openFolder(Number(button.dataset.crumb))));
    }

    select(id, additive = false) {
        const item = this.item(id);
        if (!item) return;
        // `max` limits what the picker can finally return; it must not limit
        // the file-manager selection itself, because bulk copy/cut/delete also
        // have to work inside a single-file picker.
        if (!additive) {
            this.selected.clear();
            this.selectedItems.clear();
        }
        if (this.selected.has(id)) {
            this.selected.delete(id);
            this.selectedItems.delete(id);
        } else {
            this.selected.add(id);
            this.selectedItems.set(id, item);
        }
        this.refreshItemSelection();
        this.renderSelection();
        this.renderDetails(item);
        this.selectionAnchorId = id;
    }

    selectRange(id, preserve = false) {
        const anchorId = this.selectionAnchorId || this.focusedId || id;
        const anchorIndex = this.items.findIndex((item) => item.id === Number(anchorId));
        const targetIndex = this.items.findIndex((item) => item.id === Number(id));
        if (anchorIndex < 0 || targetIndex < 0) { this.select(id, preserve); return; }
        if (!preserve) {
            this.selected.clear();
            this.selectedItems.clear();
        }
        const [start, end] = anchorIndex <= targetIndex ? [anchorIndex, targetIndex] : [targetIndex, anchorIndex];
        this.items.slice(start, end + 1).forEach((item) => {
            this.selected.add(item.id);
            this.selectedItems.set(item.id, item);
        });
        this.refreshItemSelection();
        this.renderSelection();
        this.renderDetails(this.item(id));
    }

    selectVisible(checked) {
        this.selected.clear();
        this.selectedItems.clear();
        if (checked) {
            const candidates = this.items;
            candidates.forEach((item) => { this.selected.add(item.id); this.selectedItems.set(item.id, item); });
            this.selectionAnchorId = candidates[0]?.id || null;
        }
        this.refreshItemSelection();
        this.renderSelection();
        if (!checked) this.resetDetails();
    }

    clearSelection() {
        this.selected.clear();
        this.selectedItems.clear();
        this.selectionAnchorId = null;
        this.refreshItemSelection();
        this.renderSelection();
        this.resetDetails();
    }

    refreshItemSelection() {
        this.elements.items.querySelectorAll('[data-fm-item]').forEach((element) => {
            const selected = this.selected.has(Number(element.dataset.fmItem));
            element.classList.toggle('is-selected', selected);
            element.setAttribute('aria-selected', selected ? 'true' : 'false');
        });
    }

    renderSelection() {
        const count = this.selected.size;
        if (this.elements.selectedSummary) {
            this.elements.selectedSummary.textContent = `${count.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(22)}`;
            this.elements.selectedSummary.classList.toggle('hidden', count === 0);
        }
        this.elements.selectionCount.textContent = count.toLocaleString(document.documentElement.lang || 'fa');
        this.elements.selectionCount.classList.toggle('hidden', count === 0);
        const hasActions = count > 0 || Boolean(this.clipboard?.ids?.length);
        this.elements.actionsMenu?.classList.toggle('is-disabled', !hasActions);
        if (!hasActions) this.elements.actionsMenu?.removeAttribute('open');
        const actionsTrigger = this.elements.actionsMenu?.querySelector(':scope > summary');
        actionsTrigger?.setAttribute('aria-disabled', hasActions ? 'false' : 'true');
        if (actionsTrigger) actionsTrigger.tabIndex = hasActions ? 0 : -1;
        if (this.elements.actionsSummary) {
            this.elements.actionsSummary.textContent = count
                ? `${count.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(22)}`
                : (this.clipboard?.ids?.length ? `${this.clipboard.ids.length.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(23)}` : `${fmText(24)}`);
        }
        const actionButtons = {
            copy: this.root.querySelector('[data-fm-copy]'),
            cut: this.root.querySelector('[data-fm-cut]'),
            paste: this.root.querySelector('[data-fm-paste]'),
            compress: this.root.querySelector('[data-fm-compress]'),
            rename: this.root.querySelector('[data-fm-rename]'),
            delete: this.root.querySelector('[data-fm-delete]'),
        };
        if (actionButtons.copy) actionButtons.copy.disabled = count === 0;
        if (actionButtons.cut) actionButtons.cut.disabled = count === 0;
        if (actionButtons.paste) actionButtons.paste.disabled = !this.clipboard?.ids?.length;
        if (actionButtons.compress) actionButtons.compress.disabled = count === 0 || this.archiveBusy;
        if (actionButtons.rename) actionButtons.rename.disabled = count !== 1;
        if (actionButtons.delete) actionButtons.delete.disabled = count === 0;
        const selectionNoun = count === 1 ? `${fmText(25)}` : `${fmText(26)}`;
        const actionDescriptions = {
            copy: `${fmText(27)} ${selectionNoun}`,
            cut: `${fmText(28)} ${selectionNoun} ${fmText(29)}`,
            delete: `${fmText(30)} ${selectionNoun}`,
            compress: `${fmText(31)} ZIP ${fmText(32)} ${selectionNoun}`,
        };
        Object.entries(actionDescriptions).forEach(([action, description]) => {
            const element = this.root.querySelector(`[data-fm-action-description="${action}"]`);
            if (element) element.textContent = description;
        });
        const selectableVisible = this.items;
        const selectedVisible = selectableVisible.filter((item) => this.selected.has(item.id)).length;
        this.elements.selectAll.checked = selectableVisible.length > 0 && selectedVisible === selectableVisible.length;
        this.elements.selectAll.indeterminate = selectedVisible > 0 && selectedVisible < selectableVisible.length;
        if (this.mode === 'picker') {
            const selectedItems = this.selectedEntries();
            const files = this.selectedFiles();
            const issue = this.pickerSelectionIssue();
            this.elements.pickerConfirm.disabled = files.length === 0 || Boolean(issue);
            this.elements.pickerSummary.textContent = files.length > this.pickerOptions.max
                ? `${files.length.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(33)} ${this.pickerOptions.max.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(34)}`
                : (issue
                    ? `${selectedItems.length.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(35)}`
                    : (files.length ? `${files.length.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(36)}` : `${fmText(37)}`));
        }
    }

    selectedEntries() {
        return [...this.selected]
            .map((id) => this.selectedItems.get(id) || this.item(id))
            .filter(Boolean);
    }

    selectedFiles() {
        return this.selectedEntries().filter((item) => item.type === 'file');
    }

    pickerSelectionIssue() {
        if (this.mode !== 'picker') return null;
        const items = this.selectedEntries();
        if (!items.length) return `${fmText(38)}`;
        if (items.some((item) => item.type !== 'file')) {
            return `${fmText(39)}`;
        }
        const incompatible = items.filter((item) => this.isPickerItemIncompatible(item));
        if (incompatible.length) {
            const allowed = this.pickerOptions.groups.map(group => this.fileTypeLabel({ group, extension: group })).join(' / ');
            return `${fmText(40)}${allowed}» ${fmText(41)} ${incompatible.length.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(42)}`;
        }
        if (items.length > this.pickerOptions.max) {
            return `${fmText(43)} ${this.pickerOptions.max.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(44)} ${items.length.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(45)}`;
        }
        return null;
    }

    resetDetails() {
        this.elements.details.innerHTML = `${this.detailsHeader()}<div class="panel-file-manager__details-empty">${fileManagerIconMarkup('mouse-pointer', 35)}<strong>${fmText(46)}</strong><p>${fmText(47)}</p></div>`;
    }

    detailsHeader() {
        return `<header class="panel-file-manager__sidebar-head"><div>${fileManagerIconMarkup('info', 18)}<strong>${fmText(48)}</strong></div><button type="button" data-fm-details-close aria-label="${fmText(49)}" title="${fmText(50)}">${fileManagerIconMarkup('x', 16)}</button></header>`;
    }

    renderDetails(item) {
        if (!item) return;
        const preview = item.type === 'folder'
            ? '<div class="panel-file-manager__preview-icon"><i class="panel-file-manager__folder-icon"></i></div>'
            : (item.thumbnail_url
                ? `<img src="${this.escape(item.thumbnail_url)}" alt="">`
                : `<div class="panel-file-manager__preview-icon">${this.fileIcon(item)}</div>`);
        this.elements.details.innerHTML = `${this.detailsHeader()}
            <div class="panel-file-manager__preview">${preview}</div>
            <strong class="panel-file-manager__detail-name">${this.escape(item.name)}</strong>
            <ul class="panel-file-manager__meta">
                <li><span>${fmText(51)}</span><strong>${item.type === 'folder' ? `${fmText(11)}` : this.escape(item.mime_type || item.group || `${fmText(20)}`)}</strong></li>
                <li><span>${item.type === 'folder' ? `${fmText(52)}` : `${fmText(53)}`}</span><strong>${item.type === 'folder' ? `${Number(item.children_count || 0).toLocaleString(document.documentElement.lang || 'fa')} ${fmText(6)}` : this.formatSize(item.size)}</strong></li>
                <li><span>${fmText(54)}</span><strong>${Number(item.usages_count || 0).toLocaleString(document.documentElement.lang || 'fa')} ${fmText(6)}</strong></li>
                <li><span>${fmText(55)}</span><strong>${this.escape(item.updated_at_display || '—')}</strong></li>
            </ul>
            <div class="panel-file-manager__detail-actions">
                ${item.type === 'folder' ? `<button type="button" data-details-open>${fileManagerIconMarkup('folder', 16)} ${fmText(56)}</button>` : (item.url ? `<button type="button" data-details-preview>${fileManagerIconMarkup('eye', 16)} ${fmText(57)}</button><a data-details-download>${fileManagerIconMarkup('download', 16)} ${fmText(58)}</a>` : '')}
            </div>`;
        this.elements.details.querySelector('[data-details-open]')?.addEventListener('click', () => this.openFolder(item.id));
        this.elements.details.querySelector('[data-details-preview]')?.addEventListener('click', () => this.openPreview(item));
        const download = this.elements.details.querySelector('[data-details-download]');
        if (download) { download.href = item.url; download.download = item.name; }
        this.showUsages(item);
    }

    async openPreview(item) {
        if (!item?.url || !this.elements.previewModal) return;
        this.destroyPreviewSelects();
        this.previewDispose?.();
        this.previewDispose = null;
        this.destroyAudioPreview?.();
        this.destroyVideoPreview?.();
        this.destroyPdfPreview?.();
        const generation = this.previewGeneration = (this.previewGeneration || 0) + 1;
        const modal = this.elements.previewModal;
        const mime = String(item.mime_type || '');
        const extension = String(item.extension || '').toLowerCase();
        this.previewItem = item;
        this.previewStructuredState = null;
        this.elements.previewTabs.classList.add('hidden');
        this.elements.previewTabs.innerHTML = '';
        this.elements.zoomTools.classList.add('hidden');
        this.elements.previewStage.className = 'panel-file-manager__preview-stage';
        this.elements.previewStage.innerHTML = `<div class="panel-file-manager__preview-loading"><span></span><strong>${fmText(60)}</strong></div>`;
        modal.querySelector('[data-fm-preview-title]').textContent = item.name;
        modal.querySelector('[data-fm-preview-meta]').textContent = `${this.fileTypeLabel(item)} · ${this.formatSize(item.size)}`;
        const download = modal.querySelector('[data-fm-preview-download]');
        download.href = item.url;
        download.download = item.name;
        const audioHelp = modal.querySelector('[data-audio-help]');
        const hasPlayerHelp = item.group === 'audio' || item.group === 'video';
        audioHelp?.classList.toggle('hidden', !hasPlayerHelp);
        audioHelp?.querySelector('[data-audio-help-trigger]')?.setAttribute('aria-expanded', 'false');
        audioHelp?.querySelector('[data-audio-help-menu]')?.classList.add('hidden');
        const helpItems = audioHelp?.querySelector('[data-audio-help-menu] > div');
        if (helpItems && hasPlayerHelp) {
            helpItems.innerHTML = item.group === 'video'
                ? `<span><kbd>Space / K</kbd><b>${fmText(61)}</b></span><span><kbd>← →</kbd><b>${fmText(62)}</b></span><span><kbd>↑ ↓</kbd><b>${fmText(63)}</b></span><span><kbd>M</kbd><b>${fmText(64)}</b></span><span><kbd>F / P</kbd><b>${fmText(65)}</b></span><span><kbd>R / S</kbd><b>${fmText(66)}</b></span><span><kbd>, .</kbd><b>${fmText(67)}</b></span>`
                : `<span><kbd>Space</kbd><b>${fmText(61)}</b></span><span><kbd>← →</kbd><b>${fmText(62)}</b></span><span><kbd>↑ ↓</kbd><b>${fmText(63)}</b></span><span><kbd>M</kbd><b>${fmText(64)}</b></span><span><kbd>R</kbd><b>${fmText(68)}</b></span><span><kbd>[ ]</kbd><b>${fmText(69)} A ${fmText(70)} B</b></span>`;
        }
        modal.classList.remove('hidden');

        this.updatePreviewNavigation();
        const driver = resolvePreviewDriver(item);
        if (driver) {
            try {
                const loaded = await driver.load();
                if (generation !== this.previewGeneration) return;
                const dispose = await loaded.mount(this, item);
                if (generation !== this.previewGeneration) dispose?.();
                else this.previewDispose = dispose;
            } catch (error) {
                if (generation === this.previewGeneration) this.elements.previewStage.textContent = error.message || fmText(71);
            }
        } else {
            this.elements.previewStage.innerHTML = `<div class="panel-file-manager__unsupported-preview">${this.fileIcon(item)}<strong>${fmText(71)}</strong><p>${fmText(72)}</p></div>`;
        }
        modal.querySelector('[data-fm-preview-close]:not(.panel-file-manager__preview-backdrop)')?.focus();
    }

    renderImagePreview(item) {
        const stage = this.elements.previewStage;
        stage.classList.add('is-image');
        stage.innerHTML = `<div class="panel-file-manager__image-canvas"><img src="${this.escape(item.url)}" alt="${this.escape(item.name)}" draggable="false"></div>`;
        this.elements.zoomTools.classList.remove('hidden');
        const image = stage.querySelector('img');
        image.addEventListener('load', () => this.fitPreviewImage(), { once: true });
    }

    formatAudioTime(seconds) {
        const safe = Math.max(0, Math.floor(Number(seconds) || 0));
        const locale = document.documentElement.lang || 'en';
        return `${Math.floor(safe / 60).toLocaleString(locale, {minimumIntegerDigits:2})}:${(safe % 60).toLocaleString(locale, {minimumIntegerDigits:2})}`;
    }

    previewLabels() { return pageData().fileManager?.preview || {}; }

    destroyPreviewSelects() {
        const $ = window.jQuery;
        if (!$?.fn.select2) return;
        this.elements.previewStage.querySelectorAll('select.select2-hidden-accessible').forEach(select => $(select).off('.cms').select2('destroy'));
    }

    updatePreviewNavigation() {
        const modal = this.elements.previewModal;
        const files = this.items.filter(item => item.type === 'file' && item.url);
        const index = files.findIndex(item => item.id === this.previewItem?.id);
        for (const [direction, offset, label] of [['previous', -1, 73], ['next', 1, 76]]) {
            let button = modal.querySelector(`[data-preview-navigation="${direction}"]`);
            if (!button) {
                button = document.createElement('button'); button.type = 'button';
                button.dataset.previewNavigation = direction; button.className = 'fm-preview-navigation';
                button.innerHTML = fileManagerIconMarkup(offset < 0 ? 'chevron-left' : 'chevron-right', 17);
                modal.append(button);
            }
            button.setAttribute('aria-label', fmText(label));
            button.title = fmText(label);
            button.disabled = index < 0 || !files[index + offset];
            button.onclick = () => { if(files[index + offset]) this.openPreview(files[index + offset]); };
        }
    }

    fitPreviewImage() {
        const image = this.elements.previewStage?.querySelector('.panel-file-manager__image-canvas img');
        if (!image?.naturalWidth || !image.naturalHeight) return;
        const stage = this.elements.previewStage;
        const availableWidth = Math.max(100, stage.clientWidth - 48);
        const availableHeight = Math.max(100, stage.clientHeight - 48);
        this.previewFitZoom = Math.min(1, availableWidth / image.naturalWidth, availableHeight / image.naturalHeight);
        this.setImageZoom(this.previewFitZoom, false);
    }

    changeImageZoom(multiplier) {
        if (this.previewItem?.group !== 'image') return;
        this.setImageZoom(this.previewZoom * multiplier, true);
    }

    setImageZoom(value, preserveCenter = true) {
        const stage = this.elements.previewStage;
        const image = stage?.querySelector('.panel-file-manager__image-canvas img');
        if (!image?.naturalWidth) return;
        const oldWidth = Math.max(1, stage.scrollWidth);
        const oldHeight = Math.max(1, stage.scrollHeight);
        const centerX = (stage.scrollLeft + (stage.clientWidth / 2)) / oldWidth;
        const centerY = (stage.scrollTop + (stage.clientHeight / 2)) / oldHeight;
        this.previewZoom = Math.max(.1, Math.min(5, value));
        image.style.width = `${Math.round(image.naturalWidth * this.previewZoom)}px`;
        image.style.height = `${Math.round(image.naturalHeight * this.previewZoom)}px`;
        this.root.querySelector('[data-fm-zoom-value]').textContent = `${Math.round(this.previewZoom * 100).toLocaleString(document.documentElement.lang || 'fa')}${fmText(92)}`;
        requestAnimationFrame(() => {
            stage.scrollLeft = preserveCenter ? Math.max(0, (centerX * stage.scrollWidth) - (stage.clientWidth / 2)) : Math.max(0, (stage.scrollWidth - stage.clientWidth) / 2);
            stage.scrollTop = preserveCenter ? Math.max(0, (centerY * stage.scrollHeight) - (stage.clientHeight / 2)) : Math.max(0, (stage.scrollHeight - stage.clientHeight) / 2);
        });
    }

    bindPreviewPan() {
        const stage = this.elements.previewStage;
        if (!stage) return;
        let origin = null;
        stage.addEventListener('pointerdown', (event) => {
            if (this.previewItem?.group !== 'image' || event.button !== 0) return;
            origin = { x: event.clientX, y: event.clientY, left: stage.scrollLeft, top: stage.scrollTop };
            stage.setPointerCapture?.(event.pointerId);
            stage.classList.add('is-panning');
        });
        stage.addEventListener('pointermove', (event) => {
            if (!origin) return;
            stage.scrollLeft = origin.left - (event.clientX - origin.x);
            stage.scrollTop = origin.top - (event.clientY - origin.y);
        });
        const stop = () => { origin = null; stage.classList.remove('is-panning'); };
        stage.addEventListener('pointerup', stop);
        stage.addEventListener('pointercancel', stop);
    }

    async loadStructuredPreview(item, options = {}) {
        const params = new URLSearchParams();
        Object.entries(options).forEach(([key, value]) => params.set(key, String(value)));
        try {
            const response = await fetch(`${this.config.itemsUrl}/${item.id}/preview?${params}`, { headers: { Accept: 'application/json' } });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(Object.values(payload.errors || {}).flat()[0] || payload.message || `${fmText(248)}`);
            if (this.previewItem?.id !== item.id) return;
            if (payload.data?.kind === 'spreadsheet') this.renderSpreadsheetPreview(item, payload.data, Number(options.offset || 0) > 0);
            else if (payload.data?.kind === 'archive') this.renderArchivePreview(item, payload.data, Number(options.offset || 0) > 0);
        } catch (error) {
            if (this.previewItem?.id !== item.id) return;
            this.elements.previewStage.innerHTML = `<div class="panel-file-manager__preview-error">${fileManagerIconMarkup('info', 32)}<strong>${fmText(249)}</strong><p>${this.escape(error.message)}</p></div>`;
        }
    }

    renderSpreadsheetPreview(item, data, append = false) {
        this.elements.previewStage.className = 'panel-file-manager__preview-stage is-spreadsheet';
        this.elements.previewTabs.classList.remove('hidden');
        this.elements.previewTabs.innerHTML = data.sheets.map((sheet) => `<button type="button" class="${sheet.index === data.active_sheet ? 'is-active' : ''}" data-sheet-index="${sheet.index}">${fileManagerIconMarkup('file-spreadsheet', 16)}${this.escape(sheet.name)}</button>`).join('');
        this.elements.previewTabs.querySelectorAll('[data-sheet-index]').forEach((button) => button.addEventListener('click', () => {
            const sheet = Number(button.dataset.sheetIndex);
            if (sheet === this.previewStructuredState?.sheet) return;
            this.elements.previewStage.innerHTML = `<div class="panel-file-manager__preview-loading"><span></span><strong>${fmText(250)}</strong></div>`;
            this.loadStructuredPreview(item, { sheet, offset: 0 });
        }));

        if (!append) {
            const columns = data.columns.map((column) => `<th style="min-width:${Math.max(60, Math.min(360, Number(column.width || 12) * 8))}px">${this.escape(column.letter)}</th>`).join('');
            this.elements.previewStage.innerHTML = `<div class="panel-file-manager__spreadsheet-scroll"><table><thead><tr><th class="is-row-number">#</th>${columns}</tr></thead><tbody data-spreadsheet-body></tbody></table><div class="panel-file-manager__structured-loader hidden" data-structured-loader><span></span>${fmText(251)}</div></div>`;
        }
        this.appendSpreadsheetRows(data.rows || []);
        this.previewStructuredState = { kind: 'spreadsheet', item, sheet: data.active_sheet, nextOffset: data.next_offset, loading: false };
        const scroll = this.elements.previewStage.querySelector('.panel-file-manager__spreadsheet-scroll');
        if (scroll && !scroll.dataset.bound) {
            scroll.dataset.bound = 'true';
            scroll.addEventListener('scroll', () => this.loadMoreStructuredPreview(scroll));
        }
    }

    appendSpreadsheetRows(rows) {
        const body = this.elements.previewStage.querySelector('[data-spreadsheet-body]');
        if (!body) return;
        body.insertAdjacentHTML('beforeend', rows.map((row) => `<tr><th class="is-row-number">${Number(row.number).toLocaleString(document.documentElement.lang || 'fa')}</th>${row.cells.map((cell) => this.spreadsheetCellHtml(cell)).join('')}</tr>`).join(''));
    }

    spreadsheetCellHtml(cell) {
        const style = cell.style || {};
        const declarations = [];
        if (style.bold) declarations.push('font-weight:800');
        if (style.italic) declarations.push('font-style:italic');
        if (/^#[0-9A-F]{6}$/i.test(style.color || '')) declarations.push(`color:${style.color}`);
        if (/^#[0-9A-F]{6}$/i.test(style.background || '')) declarations.push(`background:${style.background}`);
        if (['left', 'center', 'right'].includes(style.align)) declarations.push(`text-align:${style.align}`);
        if (style.wrap) declarations.push('white-space:normal');
        const value = this.escape(cell.value ?? '');
        const content = cell.link ? `<a href="${this.escape(cell.link)}" target="_blank" rel="noopener">${value}</a>` : value;
        return `<td style="${declarations.join(';')}" dir="auto">${content}</td>`;
    }

    renderArchivePreview(item, data, append = false) {
        this.elements.previewStage.className = 'panel-file-manager__preview-stage is-archive';
        this.elements.previewTabs.classList.add('hidden');
        if (!append) {
            const totalEntries = Number(data.total_entries || 0);
            const fileCount = Number(data.file_count ?? (data.entries || []).filter((entry) => !entry.directory).length);
            const folderCount = Number(data.folder_count ?? (data.entries || []).filter((entry) => entry.directory).length);
            const encryptedCount = Number(data.encrypted_count || 0);
            const uncompressed = Number(data.total_uncompressed || 0);
            const compressed = Number(data.total_compressed || 0);
            const savedPercent = uncompressed > 0 ? Math.max(0, Math.min(99, Math.round((1 - (compressed / uncompressed)) * 100))) : 0;
            this.elements.previewStage.innerHTML = `<div class="panel-file-manager__archive-shell">
                <header class="panel-file-manager__archive-overview">
                    <div class="panel-file-manager__archive-identity">
                        <span class="panel-file-manager__archive-art" aria-hidden="true">
                            ${iconMarkup('file-archive', 46)}
                            <b>ZIP</b>
                        </span>
                        <div><span class="panel-file-manager__archive-eyebrow"><i></i> ${fmText(252)}</span><strong dir="auto">${this.escape(item.name)}</strong><small>${totalEntries.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(253)}</small></div>
                    </div>
                    <div class="panel-file-manager__archive-metrics">
                        <article><span>${fileManagerIconMarkup('file-text', 19)}</span><div><small>${fmText(254)}</small><strong>${fileCount.toLocaleString(document.documentElement.lang || 'fa')}</strong></div></article>
                        <article><span>${fileManagerIconMarkup('folder', 19)}</span><div><small>${fmText(255)}</small><strong>${folderCount.toLocaleString(document.documentElement.lang || 'fa')}</strong></div></article>
                        <article><span>${fileManagerIconMarkup('folder-download', 19)}</span><div><small>${fmText(256)}</small><strong dir="ltr">${this.formatSize(uncompressed)}</strong></div></article>
                        <article class="is-accent"><span>${fileManagerIconMarkup('chart-bar', 19)}</span><div><small>${fmText(257)}</small><strong dir="ltr">${savedPercent.toLocaleString(document.documentElement.lang || 'fa')}${fmText(92)}</strong></div></article>
                    </div>
                </header>
                <div class="panel-file-manager__archive-toolbar">
                    <label class="panel-file-manager__archive-search">${fileManagerIconMarkup('search', 16)}<input type="search" data-archive-search placeholder="${fmText(258)}" autocomplete="off"><kbd>Ctrl + F</kbd></label>
                    <div class="panel-file-manager__archive-summary">
                        <span><b data-archive-visible-count>${fmText(191)}</b> ${fmText(259)}</span>
                        ${encryptedCount ? `<span class="is-encrypted">${fileManagerIconMarkup('lock', 15)}${encryptedCount.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(260)}</span>` : ''}
                        <span class="is-size">${fileManagerIconMarkup('archive', 15)}${this.formatSize(compressed)} ${fmText(261)} ZIP</span>
                    </div>
                </div>
                <div class="panel-file-manager__archive-columns" aria-hidden="true"><span>${fmText(262)}</span><span>${fmText(51)}</span><span>${fmText(256)}</span><span>${fmText(263)}</span></div>
                <div class="panel-file-manager__archive-scroll">
                    <div class="panel-file-manager__archive-list" data-archive-list role="tree"></div>
                    <div class="panel-file-manager__archive-empty hidden" data-archive-empty><span>${fileManagerIconMarkup('search', 24)}</span><strong>${fmText(264)}</strong><p>${fmText(265)}</p></div>
                    <div class="panel-file-manager__structured-loader hidden" data-structured-loader><span></span>${fmText(266)}</div>
                </div>
            </div>`;
            const search = this.elements.previewStage.querySelector('[data-archive-search]');
            search?.addEventListener('input', () => this.filterArchiveEntries(search.value));
            search?.addEventListener('keydown', (event) => event.stopPropagation());
        }
        const list = this.elements.previewStage.querySelector('[data-archive-list]');
        list?.insertAdjacentHTML('beforeend', (data.entries || []).map((entry) => this.archiveEntryHtml(entry)).join(''));
        this.previewStructuredState = { kind: 'archive', item, nextOffset: data.next_offset, loading: false };
        this.filterArchiveEntries(this.elements.previewStage.querySelector('[data-archive-search]')?.value || '');
        const scroll = this.elements.previewStage.querySelector('.panel-file-manager__archive-scroll');
        if (scroll && !scroll.dataset.bound) {
            scroll.dataset.bound = 'true';
            scroll.addEventListener('scroll', () => this.loadMoreStructuredPreview(scroll));
        }
    }

    archiveEntryHtml(entry) {
        const extension = String(entry.extension || '').toLowerCase();
        const path = String(entry.path || '');
        const cleanPath = path.replace(/[\\/]+$/, '');
        const segments = cleanPath.split(/[\\/]/).filter(Boolean);
        const parentPath = segments.slice(0, -1).join(' / ');
        const size = Number(entry.size || 0);
        const compressed = Number(entry.compressed_size || 0);
        const ratio = size > 0 ? Math.max(0, Math.min(99, Math.round((1 - (compressed / size)) * 100))) : 0;
        const searchValue = `${entry.name || path} ${path}`.toLocaleLowerCase('fa-IR');
        const type = this.archiveTypeLabel(extension, Boolean(entry.directory));
        return `<article class="panel-file-manager__archive-entry ${entry.directory ? 'is-folder' : `is-${this.escape(type.group)}`}" style="--entry-depth:${Math.min(12, Number(entry.depth || 0))}" role="treeitem" aria-level="${Math.min(13, Number(entry.depth || 0) + 1)}" data-archive-entry data-archive-search-value="${this.escape(searchValue)}">
            <div class="panel-file-manager__archive-name-cell">
                <span class="panel-file-manager__archive-entry-icon">${this.archiveGlyph(extension, Boolean(entry.directory))}${!entry.directory ? `<b>${this.escape(extension || 'FILE')}</b>` : ''}</span>
                <div><strong dir="auto" title="${this.escape(entry.name || path)}">${this.escape(entry.name || path)}</strong><small dir="ltr" title="${this.escape(path)}">${parentPath ? this.escape(parentPath) : 'Root /'}</small></div>
                ${entry.encrypted ? `<em title="${fmText(267)}">${fileManagerIconMarkup('lock', 14)}</em>` : ''}
            </div>
            <span class="panel-file-manager__archive-type"><i></i><b>${this.escape(type.label)}</b></span>
            <span class="panel-file-manager__archive-size">${entry.directory ? '—' : this.formatSize(size)}</span>
            <span class="panel-file-manager__archive-compressed">${entry.directory ? '—' : `<b>${this.formatSize(compressed)}</b><small>${ratio.toLocaleString(document.documentElement.lang || 'fa')}${fmText(268)}</small>`}</span>
        </article>`;
    }

    filterArchiveEntries(query = '') {
        const normalized = String(query).trim().toLocaleLowerCase('fa-IR');
        const entries = [...this.elements.previewStage.querySelectorAll('[data-archive-entry]')];
        let visible = 0;
        entries.forEach((entry) => {
            const matches = !normalized || String(entry.dataset.archiveSearchValue || '').includes(normalized);
            entry.classList.toggle('hidden', !matches);
            if (matches) visible += 1;
        });
        const counter = this.elements.previewStage.querySelector('[data-archive-visible-count]');
        if (counter) counter.textContent = visible.toLocaleString(document.documentElement.lang || 'fa');
        this.elements.previewStage.querySelector('[data-archive-empty]')?.classList.toggle('hidden', visible > 0);
    }

    archiveTypeLabel(extension, directory = false) {
        if (directory) return { group: 'folder', label: `${fmText(11)}` };
        if (['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg', 'avif'].includes(extension)) return { group: 'image', label: `${fmText(15)}` };
        if (['mp4', 'webm', 'mov', 'avi', 'mkv'].includes(extension)) return { group: 'video', label: `${fmText(17)}` };
        if (['mp3', 'wav', 'ogg', 'm4a', 'flac'].includes(extension)) return { group: 'audio', label: `${fmText(18)}` };
        if (['xls', 'xlsx', 'csv'].includes(extension)) return { group: 'sheet', label: `${fmText(269)}` };
        if (extension === 'pdf') return { group: 'pdf', label: 'PDF' };
        if (['zip', 'rar', '7z', 'tar', 'gz'].includes(extension)) return { group: 'archive', label: `${fmText(270)}` };
        if (['doc', 'docx', 'txt', 'rtf', 'ppt', 'pptx'].includes(extension)) return { group: 'document', label: `${fmText(16)}` };
        return { group: 'file', label: extension ? extension.toUpperCase() : `${fmText(20)}` };
    }

    archiveGlyph(extension, directory = false) {
        if (directory) return iconMarkup('folder', 24);
        const group = this.archiveTypeLabel(extension, false).group;
        const icons = { image: 'file-image', video: 'file-video', audio: 'file-audio', sheet: 'file-spreadsheet', pdf: 'file-text', archive: 'file-archive', document: 'file-text', file: 'file' };
        return iconMarkup(icons[group] || 'file', 24);
    }

    archiveIcon(extension) {
        if (['jpg', 'jpeg', 'png', 'webp', 'gif', 'svg'].includes(extension)) return 'media';
        if (['xls', 'xlsx', 'csv'].includes(extension)) return 'file-spreadsheet';
        if (['mp4', 'webm', 'mov'].includes(extension)) return 'video';
        if (['mp3', 'wav', 'ogg', 'm4a'].includes(extension)) return 'headphones';
        if (extension === 'pdf') return 'file-text';
        return 'file-text';
    }

    async loadMoreStructuredPreview(scroll) {
        const state = this.previewStructuredState;
        if (!state || state.loading || state.nextOffset === null || scroll.scrollHeight - scroll.scrollTop - scroll.clientHeight > 160) return;
        state.loading = true;
        this.elements.previewStage.querySelector('[data-structured-loader]')?.classList.remove('hidden');
        await this.loadStructuredPreview(state.item, { sheet: state.sheet || 0, offset: state.nextOffset });
        this.elements.previewStage.querySelector('[data-structured-loader]')?.classList.add('hidden');
    }

    closePreview() {
        if (!this.elements.previewModal) return;
        this.destroyPreviewSelects();
        this.previewGeneration = (this.previewGeneration || 0) + 1;
        this.previewDispose?.(); this.previewDispose = null;
        this.destroyAudioPreview?.();
        this.destroyVideoPreview?.();
        this.destroyPdfPreview?.();
        this.elements.previewModal.querySelectorAll('video,audio').forEach((media) => media.pause());
        this.elements.previewModal.classList.add('hidden');
        this.elements.previewStage.innerHTML = '';
        this.elements.previewTabs.innerHTML = '';
        this.elements.previewTabs.classList.add('hidden');
        this.elements.zoomTools.classList.add('hidden');
        this.previewItem = null;
        this.previewStructuredState = null;
    }

    async showUsages(item) {
        const token = Symbol();
        this.usageRequest = token;
        this.elements.details.querySelector('[data-fm-usage-list]')?.remove();
        const box = document.createElement('div');
        box.dataset.fmUsageList = 'true';
        box.className = 'panel-file-manager__usage-list';
        box.setAttribute('aria-busy', 'true');
        box.textContent = fmText(59) + '…';
        this.elements.details.appendChild(box);
        try {
        const response = await fetch(`${this.config.itemsUrl}/${item.id}/usages`, { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error();
        const payload = await response.json();
        if (this.usageRequest !== token || !box.isConnected) return;
        const usages = payload.data || [];
        box.innerHTML = usages.length
            ? `<strong>${fmText(59)}</strong>${usages.map((usage) => usage.url ? `<a href="${this.escape(usage.url)}"><span>${this.escape(usage.label)}</span><small>${this.escape(usage.collection)}</small></a>` : `<div><span>${this.escape(usage.label)}</span><small>${this.escape(usage.collection)}</small></div>`).join('')}`
            : `<p>${fmText(271)}</p>`;
        } catch {
            if (box.isConnected) box.textContent = fmText(59) + ' —';
        } finally {
            box.removeAttribute('aria-busy');
        }
    }

    openFolder(id, remember = true) {
        if (id === this.folderId) return;
        if (remember) this.history.push(this.folderId);
        this.folderId = id;
        this.page = 1;
        // A result created by archive/upload operations can be revealed through
        // a temporary search in its parent. That query belongs to the parent and
        // must never leak into the folder the user is opening.
        if (this.elements.search) this.elements.search.value = '';
        if (this.elements.scope) this.elements.scope.value = 'folder';
        if (this.mode === 'picker') {
            this.selectedItems = new Map([...this.selectedItems].filter(([, item]) => item?.type === 'file'));
            this.selected = new Set(this.selectedItems.keys());
            this.renderSelection();
            this.resetDetails();
        } else {
            this.clearSelection();
        }
        this.hideContextMenu();
        this.load();
    }

    goBack() {
        if (this.folderId === this.rootId) return;
        const previous = this.history.pop() || this.breadcrumbs.at(-2)?.id || this.rootId;
        this.openFolder(Number(previous), false);
    }

    async createFolder() {
        if (!this.permissions.manage) return;
        const name = await this.askName({ title: `${fmText(272)}`, text: `${fmText(273)}`, icon: 'folder-plus' });
        if (!name) return;
        const response = await this.request(this.config.folderStoreUrl, { method: 'POST', body: { name, parent_id: this.folderId } });
        if (response.ok) { this.toast(`${fmText(274)}`); this.load(); }
    }

    async rename() {
        if (!this.permissions.manage || this.selected.size !== 1) { if (this.selected.size !== 1) this.toast(`${fmText(275)}`, 'error'); return; }
        const item = this.selectedItems.get([...this.selected][0]) || this.item([...this.selected][0]);
        if (!item) return;
        const name = await this.askName({ title: `${fmText(276)}`, text: `${fmText(277)}${item.name}» ${fmText(278)}`, value: item.name, icon: 'edit' });
        if (!name || name === item.name) return;
        const response = await this.request(`${this.config.itemsUrl}/${item.id}`, { method: 'PATCH', body: { name } });
        if (response.ok) { this.toast(`${fmText(279)}`); this.load(); }
    }

    suggestedArchiveName() {
        const items = this.selectedEntries();
        if (items.length === 1) {
            const item = items[0];
            const base = item.type === 'file'
                ? String(item.name || 'archive').replace(/\.[^.]+$/, '')
                : String(item.name || 'archive');
            return `${base || 'archive'}.zip`;
        }

        const date = new Date();
        const pad = (value) => String(value).padStart(2, '0');
        return `archive-${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}-${pad(date.getHours())}${pad(date.getMinutes())}.zip`;
    }

    async compressSelected() {
        if (!this.permissions.manage || !this.selected.size || this.archiveBusy) return;
        const name = await this.askName({
            title: `${fmText(280)} ZIP`,
            text: `${this.selected.size.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(281)} ZIP ${fmText(282)}`,
            value: this.suggestedArchiveName(),
            icon: 'archive',
            confirmLabel: `${fmText(280)} ZIP`,
        });
        if (!name) return;

        this.setArchiveBusy(true);
        let response;
        try {
            response = await this.request(this.config.compressUrl, {
                method: 'POST',
                body: {
                    item_ids: [...this.selected],
                    destination_id: this.folderId,
                    name: /\.zip$/i.test(name) ? name : `${name}.zip`,
                },
            });
        } catch {
            this.toast(`${fmText(283)} ZIP ${fmText(284)}`, 'error');
            return;
        } finally {
            this.setArchiveBusy(false);
        }

        if (response?.status === 409) {
            await this.resolveConflict(response.payload, true);
            return;
        }

        if (response?.ok) {
            const archive = response.payload.data?.item;
            this.clearSelection();
            await this.revealBrowseResult(archive);
            if (archive?.id) {
                this.select(archive.id);
                this.elements.items.querySelector(`[data-fm-item="${archive.id}"]`)?.scrollIntoView({ block: 'nearest' });
            }
            this.toast(response.payload.message || `${fmText(20)} ZIP ${fmText(285)}`);
        }
    }

    async extractArchive(item, destination) {
        if (!this.permissions.manage || this.archiveBusy || !this.isZipItem(item)) return;
        const endpoint = String(this.config.extractUrl || `${this.config.itemsUrl}/__ID__/extract`).replace('__ID__', String(item.id));
        this.setArchiveBusy(true);
        let response;
        try {
            response = await this.request(endpoint, { method: 'POST', body: { destination } });
        } catch {
            this.toast(`${fmText(286)} ZIP ${fmText(284)}`, 'error');
            return;
        } finally {
            this.setArchiveBusy(false);
        }

        if (response?.status === 409) {
            await this.resolveConflict(response.payload, true);
            return;
        }

        if (response?.ok) {
            const result = response.payload.data || {};
            await this.revealBrowseResult(destination === 'named_folder' ? result.destination : null);
            if (destination === 'named_folder' && result.destination?.id) {
                this.select(result.destination.id);
                this.elements.items.querySelector(`[data-fm-item="${result.destination.id}"]`)?.scrollIntoView({ block: 'nearest' });
            }
            const extracted = Number(result.extracted_count || 0).toLocaleString(document.documentElement.lang || 'fa');
            const skipped = Number(result.skipped_count || 0);
            this.toast(skipped
                ? `${extracted} ${fmText(287)} ${skipped.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(288)}`
                : `${extracted} ${fmText(289)}`);
        }
    }

    isZipItem(item) {
        return item?.type === 'file' && String(item.extension || '').toLowerCase() === 'zip';
    }

    async revealBrowseResult(item = null) {
        if (this.elements.group) this.elements.group.value = '';
        if (this.elements.scope) this.elements.scope.value = 'folder';
        if (this.elements.search) this.elements.search.value = '';
        this.page = 1;
        await this.load();

        if (!item?.id || this.item(Number(item.id))) return;

        // The result may fall outside the first page with name-based sorting.
        // Keep the complete current-folder view and pin only the new result to
        // the top instead of filtering every sibling out through the search box.
        this.items.unshift(item);
        this.renderItems({ append: false, incoming: this.items });
        this.applyPermissions();
    }

    setArchiveBusy(busy) {
        this.archiveBusy = Boolean(busy);
        this.root.classList.toggle('is-archive-busy', this.archiveBusy);
        this.root.querySelectorAll('[data-fm-compress]').forEach((button) => { button.disabled = this.archiveBusy || !this.selected.size; });
        this.renderSelection();
    }

    askName({ title, text, value = '', icon = 'folder-plus', confirmLabel = `${fmText(290)}` }) {
        return new Promise((resolve) => {
            const modal = this.elements.prompt;
            const form = modal.querySelector('[data-fm-prompt-form]');
            const input = modal.querySelector('[data-fm-prompt-input]');
            modal.querySelector('[data-fm-prompt-title]').textContent = title;
            modal.querySelector('[data-fm-prompt-text]').textContent = text;
            modal.querySelector('.panel-file-manager__prompt-icon').innerHTML = fileManagerIconMarkup(icon, 22, 'panel-file-manager__prompt-svg');
            modal.querySelector('[data-fm-prompt-confirm]').textContent = confirmLabel;
            input.value = value;
            this.promptResolve = resolve;
            form.onsubmit = (event) => {
                event.preventDefault();
                const result = input.value.trim();
                if (!result) { input.focus(); return; }
                this.closePrompt(result);
            };
            modal.querySelector('[data-fm-prompt-cancel]').onclick = () => this.closePrompt(null);
            modal.classList.remove('hidden');
            setTimeout(() => { input.focus(); input.select(); }, 20);
        });
    }

    closePrompt(result) {
        if (!this.promptResolve) return;
        this.elements.prompt.classList.add('hidden');
        const resolve = this.promptResolve;
        this.promptResolve = null;
        resolve(result);
    }

    showContextMenu(event, item = null) {
        event.preventDefault();
        if (!this.elements.context) return;
        const actions = item ? this.itemContextActions(item) : this.backgroundContextActions();
        this.elements.context.innerHTML = actions.map((action) => this.contextActionHtml(action)).join('');
        const executableActions = actions.flatMap((action) => action.children || [action]);
        const handlers = Object.fromEntries(executableActions.filter((action) => action.key && action.run).map((action) => [action.key, action.run]));
        this.elements.context.querySelectorAll('[data-context-action]').forEach((button) => button.addEventListener('click', () => {
            this.hideContextMenu();
            handlers[button.dataset.contextAction]?.();
        }));
        this.elements.context.querySelectorAll('[data-context-submenu-trigger]').forEach((button) => {
            const group = button.closest('[data-context-submenu-group]');
            const submenu = group?.querySelector('[data-context-submenu]');
            const open = () => {
                if (button.disabled || !submenu || !submenu.classList.contains('hidden')) return;
                this.closeContextSubmenus();
                submenu?.classList.remove('hidden');
                button.classList.add('is-open');
                button.setAttribute('aria-expanded', 'true');
                requestAnimationFrame(() => this.positionContextSubmenu(button, submenu));
            };
            button.addEventListener('pointerenter', open);
            button.addEventListener('click', (clickEvent) => {
                clickEvent.stopPropagation();
                open();
            });
        });
        this.elements.context.classList.remove('hidden');
        this.contextPoint = { x: event.clientX, y: event.clientY };
        this.fitContextMenu();
    }

    contextActionHtml(action) {
        if (action.separator) return '<span class="panel-file-manager__context-separator"></span>';
        if (action.children?.length) {
            return `<div class="panel-file-manager__context-group" data-context-submenu-group>
                <button type="button" class="panel-file-manager__context-submenu-trigger" data-context-submenu-trigger aria-haspopup="menu" aria-expanded="false" ${action.disabled ? 'disabled' : ''}>
                    ${this.contextIcon(action.icon)}<span>${this.escape(action.label)}</span><span class="panel-file-manager__context-chevron" aria-hidden="true">${iconMarkup('chevron-down', 13)}</span>
                </button>
                <div class="panel-file-manager__context-submenu hidden" data-context-submenu>
                    ${action.children.map((child) => `<button type="button" data-context-action="${child.key}" ${child.disabled ? 'disabled' : ''}>${this.contextIcon(child.icon)}<span class="panel-file-manager__context-copy"><b>${this.escape(child.label)}</b>${child.description ? `<small>${this.escape(child.description)}</small>` : ''}</span></button>`).join('')}
                </div>
            </div>`;
        }

        return `<button type="button" class="${action.danger ? 'is-danger' : ''}" data-context-action="${action.key}" ${action.disabled ? 'disabled' : ''}>${this.contextIcon(action.icon)}<span>${this.escape(action.label)}</span>${action.shortcut ? `<small>${this.escape(action.shortcut)}</small>` : ''}</button>`;
    }

    fitContextMenu() {
        if (!this.elements.context || !this.contextPoint) return;
        const rect = this.elements.context.getBoundingClientRect();
        const left = Math.min(this.contextPoint.x, window.innerWidth - rect.width - 8);
        const top = Math.min(this.contextPoint.y, window.innerHeight - rect.height - 8);
        this.elements.context.style.left = `${Math.max(8, left)}px`;
        this.elements.context.style.top = `${Math.max(8, top)}px`;
    }

    positionContextSubmenu(trigger, submenu) {
        if (!trigger || !submenu || submenu.classList.contains('hidden')) return;
        const gap = 10;
        const edge = 8;
        const triggerRect = trigger.getBoundingClientRect();
        const submenuRect = submenu.getBoundingClientRect();
        const roomRight = window.innerWidth - triggerRect.right - gap - edge;
        const roomLeft = triggerRect.left - gap - edge;
        const openRight = roomRight >= submenuRect.width || roomRight >= roomLeft;
        const left = openRight
            ? triggerRect.right + gap
            : triggerRect.left - submenuRect.width - gap;
        const top = Math.min(
            Math.max(edge, triggerRect.top - 6),
            window.innerHeight - submenuRect.height - edge,
        );

        submenu.style.left = `${Math.max(edge, Math.min(left, window.innerWidth - submenuRect.width - edge))}px`;
        submenu.style.top = `${Math.max(edge, top)}px`;
        submenu.dataset.side = openRight ? 'right' : 'left';
    }

    closeContextSubmenus() {
        this.elements.context?.querySelectorAll('[data-context-submenu]').forEach((element) => {
            element.classList.add('hidden');
            element.style.removeProperty('left');
            element.style.removeProperty('top');
            delete element.dataset.side;
        });
        this.elements.context?.querySelectorAll('[data-context-submenu-trigger]').forEach((element) => {
            element.classList.remove('is-open');
            element.setAttribute('aria-expanded', 'false');
        });
    }

    contextIcon(icon) {
        return `<span class="panel-file-manager__context-icon" data-context-icon="${this.escape(icon)}" aria-hidden="true">${fileManagerIconMarkup(icon, 18)}</span>`;
    }

    backgroundContextActions() {
        return [
            { key: 'select-all', label: `${fmText(291)}`, icon: 'check-circle', shortcut: 'Ctrl+A', run: () => this.selectVisible(true) },
            { separator: true },
            { key: 'back', label: `${fmText(292)}`, icon: 'arrow-right', disabled: this.folderId === this.rootId, run: () => this.goBack() },
            { key: 'refresh', label: `${fmText(293)}`, icon: 'refresh', run: () => this.load() },
            { separator: true },
            { key: 'folder', label: `${fmText(294)}`, icon: 'folder-plus', disabled: !this.permissions.manage, run: () => this.createFolder() },
            { key: 'upload', label: `${fmText(295)}`, icon: 'upload', disabled: !this.permissions.manage, run: () => this.elements.fileInput?.click() },
            { key: 'paste', label: `${fmText(296)}`, icon: 'clipboard', shortcut: 'Ctrl+V', disabled: !this.permissions.manage || !this.clipboard, run: () => this.paste() },
        ];
    }

    itemContextActions(item) {
        const file = item.type === 'file';
        const zip = this.isZipItem(item);
        const archiveFolderName = String(item.name || 'archive').replace(/\.zip$/i, '') || 'archive';
        return [
            { key: 'select', label: `${fmText(13)}`, icon: 'cursor', run: () => this.select(item.id, true) },
            {
                key: 'open',
                label: file ? `${fmText(57)}` : `${fmText(56)}`,
                icon: file ? 'eye' : 'folder',
                // In picker mode activateItem() confirms the file and closes the
                // picker. The context-menu preview must never change selection or
                // confirm the picker; it only opens the nested preview dialog.
                run: () => file ? this.openPreview(item) : this.openFolder(item.id),
            },
            ...(file && item.url ? [{ key: 'download', label: `${fmText(58)}`, icon: 'download', run: () => this.download(item) }] : []),
            ...(zip ? [{
                key: 'extract',
                label: `${fmText(297)} ZIP`,
                icon: 'folder-download',
                disabled: !this.permissions.manage || this.archiveBusy,
                children: [
                    { key: 'extract-current', label: `${fmText(298)}`, description: `${fmText(299)}`, icon: 'folder-download', run: () => this.extractArchive(item, 'current') },
                    { key: 'extract-folder', label: `${fmText(300)}${archiveFolderName}»`, description: `${fmText(301)}`, icon: 'folder-plus', run: () => this.extractArchive(item, 'named_folder') },
                ],
            }] : []),
            { separator: true },
            { key: 'copy', label: `${fmText(302)}`, icon: 'copy', shortcut: 'Ctrl+C', disabled: !this.permissions.manage, run: () => this.setClipboard('copy') },
            { key: 'cut', label: `${fmText(303)}`, icon: 'arrows-left-right', shortcut: 'Ctrl+X', disabled: !this.permissions.manage, run: () => this.setClipboard('move') },
            { key: 'paste', label: file ? `${fmText(304)}` : `${fmText(305)}`, icon: 'clipboard', shortcut: 'Ctrl+V', disabled: !this.permissions.manage || !this.clipboard?.ids?.length, run: () => this.pasteClipboardTo(file ? this.folderId : item.id) },
            { key: 'compress', label: `${fmText(306)} ZIP`, icon: 'archive', disabled: !this.permissions.manage || !this.selected.size || this.archiveBusy, run: () => this.compressSelected() },
            { key: 'rename', label: `${fmText(276)}`, icon: 'edit', shortcut: 'F2', disabled: !this.permissions.manage, run: () => this.rename() },
            { separator: true },
            { key: 'delete', label: `${fmText(307)}`, icon: 'trash', shortcut: 'Del', danger: true, disabled: !this.permissions.delete, run: () => this.destroySelected() },
        ];
    }

    hideContextMenu() {
        this.closeContextSubmenus();
        this.elements.context?.classList.add('hidden');
    }

    download(item) {
        const link = document.createElement('a');
        link.href = item.url;
        link.download = item.name;
        link.rel = 'noopener';
        link.click();
    }

    setClipboard(mode) {
        if (!this.permissions.manage || !this.selected.size) return;
        this.clipboard = { mode, ids: [...this.selected] };
        this.renderSelection();
        this.toast(`${this.clipboard.ids.length.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(308)} ${mode === 'copy' ? `${fmText(302)}` : `${fmText(28)}`} ${fmText(309)}`);
    }

    async paste(resolution = null, confirmUsedReplace = false) {
        return this.pasteClipboardTo(this.folderId, resolution, confirmUsedReplace);
    }

    async pasteClipboardTo(destinationId, resolution = null, confirmUsedReplace = false) {
        if (!this.permissions.manage || !this.clipboard?.ids?.length) { this.toast(`${fmText(310)}`, 'error'); return; }
        const response = await this.request(this.config.pasteUrl, { method: 'POST', body: { item_ids: this.clipboard.ids, destination_id: Number(destinationId), mode: this.clipboard.mode, resolution, confirm_used_replace: confirmUsedReplace } });
        if (response.status === 409) {
            const choice = await this.resolveConflict(response.payload);
            if (choice === 'cancel') return;
            return this.pasteClipboardTo(destinationId, choice, response.payload.code === 'replace_used_confirmation');
        }
        if (response.ok) {
            if (this.clipboard.mode === 'move') this.clipboard = null;
            this.clearSelection();
            this.toast(`${fmText(311)}`);
            await this.load();
        }
    }

    async moveItemsToFolder(ids, destinationId, resolution = null, confirmUsedReplace = false) {
        const itemIds = [...new Set((ids || []).map(Number).filter(Number.isInteger))];
        if (!this.permissions.manage || !itemIds.length) return;
        if (itemIds.includes(Number(destinationId))) {
            this.toast(`${fmText(10)}`, 'warning');
            return;
        }

        const response = await this.request(this.config.pasteUrl, {
            method: 'POST',
            body: {
                item_ids: itemIds,
                destination_id: Number(destinationId),
                mode: 'move',
                resolution,
                confirm_used_replace: confirmUsedReplace,
            },
        });
        if (response.status === 409) {
            const choice = await this.resolveConflict(response.payload);
            if (choice === 'cancel') return;
            return this.moveItemsToFolder(
                itemIds,
                destinationId,
                choice,
                response.payload.code === 'replace_used_confirmation',
            );
        }
        if (!response.ok) return;

        const processed = Number(response.payload.data?.processed?.length || 0);
        const skipped = Number(response.payload.data?.skipped?.length || 0);
        this.clearSelection();
        this.toast(skipped
            ? `${processed.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(312)} ${skipped.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(313)}`
            : `${processed.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(314)}`);
        await this.load();
    }

    async uploadFiles(files) {
        if (!this.permissions.manage || !files.length) return;
        this.uploadBatches = (this.uploadBatches || 0) + 1;
        const results = [];
        try {
            for (const file of files) results.push(await this.uploadOne(file));
        } finally {
            this.uploadBatches--;
            this.updateUploadCount();
        }
        await this.load();
        const reveal = [...results].reverse().find((result) => result?.reveal)?.reveal;
        if (reveal) await this.revealItem(reveal);
    }

    async uploadOne(file, options = {}) {
        const task = options.task || this.showUpload(file);
        const clientError = this.validateUploadCandidate(file);
        if (clientError) {
            task.setProgress(0, 0, file.size);
            task.setState(`${fmText(315)}`, 'is-error', clientError, false);
            this.toast(clientError, 'error');
            return;
        }
        const formData = new FormData();
        formData.append('file', file);
        formData.append('parent_id', String(this.folderId));
        if (options.allowDuplicate) formData.append('allow_duplicate', '1');
        if (options.resolution) formData.append('resolution', options.resolution);
        if (options.confirmUsedReplace) formData.append('confirm_used_replace', '1');
        const response = await this.uploadRequest(formData, task);
        const payload = response.payload;
        if (response.cancelled) return;
        if (response.status === 409) {
            const choice = await this.resolveConflict(payload);
            if (choice === 'show_existing') {
                task.setProgress(100);
                task.setState(`${fmText(316)}`, 'is-warning', `${fmText(317)}`);
                setTimeout(() => { task.row.remove(); this.updateUploadCount(); }, 3000);
                return { reveal: payload.data?.existing || null };
            }
            if (choice === 'continue_duplicate') return this.uploadOne(file, { ...options, task, allowDuplicate: true });
            if (['replace', 'keep_both', 'skip'].includes(choice)) return this.uploadOne(file, { ...options, task, resolution: choice, allowDuplicate: true, confirmUsedReplace: payload.code === 'replace_used_confirmation' });
            task.setState(
                payload.code === 'duplicate_same_folder' ? `${fmText(316)}` : `${fmText(318)}`,
                payload.code === 'duplicate_same_folder' ? 'is-warning' : 'is-cancelled',
                payload.code === 'duplicate_same_folder' ? (payload.message || `${fmText(319)}`) : '',
            );
            if (payload.code === 'duplicate_same_folder') {
                task.setProgress(100);
                setTimeout(() => { task.row.remove(); this.updateUploadCount(); }, 3000);
            }
            return { reveal: payload.code === 'duplicate_same_folder' ? payload.data?.existing || null : null };
        }
        if (!response.ok) {
            const message = this.uploadErrorMessage(file, response);
            this.toast(message, 'error');
            task.setState(`${fmText(315)}`, 'is-error', message, true);
            task.retry.onclick = async () => {
                const result = await this.uploadOne(file, { ...options, task });
                await this.load();
                if (result?.reveal) await this.revealItem(result.reveal);
            };
        } else {
            this.toast(`${file.name} ${fmText(320)}`);
            task.setProgress(100, file.size, file.size);
            task.setState(`${fmText(321)}`, 'is-done');
            if (this.mode === 'picker' && payload.data?.id && !this.isPickerItemIncompatible(payload.data)) {
                this.selected.add(Number(payload.data.id));
                this.selectedItems.set(Number(payload.data.id), payload.data);
            }
            setTimeout(() => { task.row.remove(); this.updateUploadCount(); }, 2200);
            return { reveal: payload.data || null };
        }
    }

    async revealItem(item) {
        if (!item?.id) return;
        let element = this.elements.items.querySelector(`[data-fm-item="${Number(item.id)}"]`);
        if (!element) {
            if (this.elements.search) this.elements.search.value = item.name || '';
            if (this.elements.scope) this.elements.scope.value = 'folder';
            if (this.elements.group) this.elements.group.value = '';
            await this.load();
            element = this.elements.items.querySelector(`[data-fm-item="${Number(item.id)}"]`);
        }
        if (!element) return;
        this.focusItem(item.id);
        this.renderDetails(this.item(item.id) || item);
        element.classList.add('is-upload-revealed');
        setTimeout(() => element.classList.remove('is-upload-revealed'), 2200);
    }

    showUpload(file) {
        clearTimeout(this.uploadCollapseTimer);
        this.elements.uploads.classList.remove('hidden', 'is-minimized');
        const meta = this.uploadFileMeta(file);
        const row = document.createElement('article');
        row.className = 'panel-file-manager__upload-task';
        row.innerHTML = `
            <div class="panel-file-manager__upload-file">
                ${this.uploadFileVisual(meta)}
                <div class="panel-file-manager__upload-copy">
                    <div><strong dir="auto">${this.escape(file.name)}</strong></div>
                    <small data-upload-size>${fmText(191)} B ${fmText(32)} ${this.formatSize(file.size)}</small>
                </div>
                <span class="panel-file-manager__upload-state" data-upload-state>${fmText(322)}</span>
            </div>
            <div class="panel-file-manager__upload-meter"><div class="panel-file-manager__upload-progress"><span></span></div><output data-upload-percent>${fmText(143)}</output></div>
            <div class="panel-file-manager__upload-error hidden" data-upload-error role="alert">
                <span aria-hidden="true">${iconMarkup('info', 17)}</span>
                <div><strong data-upload-error-title>${fmText(323)}</strong><p data-upload-error-message></p></div>
            </div>
            <div class="panel-file-manager__upload-actions">
                <button type="button" data-upload-error-toggle class="panel-file-manager__upload-action is-error-details hidden" aria-expanded="false">${iconMarkup('info', 15)}<span data-upload-error-toggle-label>${fmText(324)}</span>${iconMarkup('chevron-down', 13, 'panel-file-manager__upload-action-chevron')}</button>
                <span class="panel-file-manager__upload-actions-spacer"></span>
                <button type="button" data-upload-retry class="panel-file-manager__upload-action is-retry hidden" aria-label="${fmText(325)}" title="${fmText(325)}">${iconMarkup('refresh', 15)}<span>${fmText(325)}</span></button>
                <button type="button" data-upload-cancel class="panel-file-manager__upload-action is-remove" aria-label="${fmText(326)}" title="${fmText(327)}">${iconMarkup('trash', 15)}</button>
            </div>`;
        this.elements.uploadList.prepend(row);
        this.updateUploadCount();
        const progress = row.querySelector('.panel-file-manager__upload-progress span');
        const percent = row.querySelector('[data-upload-percent]');
        const size = row.querySelector('[data-upload-size]');
        const state = row.querySelector('[data-upload-state]');
        const error = row.querySelector('[data-upload-error]');
        const errorTitle = row.querySelector('[data-upload-error-title]');
        const errorMessage = row.querySelector('[data-upload-error-message]');
        const errorToggle = row.querySelector('[data-upload-error-toggle]');
        const errorToggleLabel = row.querySelector('[data-upload-error-toggle-label]');
        const retry = row.querySelector('[data-upload-retry]');
        const cancel = row.querySelector('[data-upload-cancel]');
        const task = {
            row, retry, cancel, xhr: null,
            setProgress: (value, loaded = null, total = file.size) => {
                const normalized = Math.max(0, Math.min(100, value));
                progress.style.width = `${normalized}%`;
                percent.textContent = `${Math.round(normalized).toLocaleString(document.documentElement.lang || 'fa')}${fmText(92)}`;
                if (loaded !== null) size.textContent = `${this.formatUploadSize(loaded)} ${fmText(32)} ${this.formatUploadSize(total)}`;
            },
            setState: (label, className = '', message = '', retryable = false) => {
                state.textContent = label;
                row.classList.remove('is-error', 'is-warning', 'is-done', 'is-cancelled');
                if (className) row.classList.add(className);
                errorMessage.textContent = message;
                errorTitle.textContent = className === 'is-warning' ? `${fmText(328)}` : `${fmText(323)}`;
                error.classList.add('hidden');
                errorToggle.classList.toggle('hidden', !message);
                errorToggle.setAttribute('aria-expanded', 'false');
                errorToggleLabel.textContent = className === 'is-warning' ? `${fmText(329)}` : `${fmText(324)}`;
                retry.classList.toggle('hidden', !retryable);
                this.updateUploadCount();
            },
        };
        errorToggle.addEventListener('click', () => {
            const expanded = errorToggle.getAttribute('aria-expanded') === 'true';
            errorToggle.setAttribute('aria-expanded', String(!expanded));
            error.classList.toggle('hidden', expanded);
            errorToggleLabel.textContent = expanded
                ? (row.classList.contains('is-warning') ? `${fmText(329)}` : `${fmText(324)}`)
                : `${fmText(330)}`;
        });
        cancel.addEventListener('click', () => {
            if (task.xhr && task.xhr.readyState !== XMLHttpRequest.DONE) task.xhr.abort();
            row.remove();
            this.updateUploadCount();
        });
        return task;
    }

    updateUploadCount() {
        const count = this.elements.uploadList?.querySelectorAll('.panel-file-manager__upload-task').length || 0;
        const failures = this.elements.uploadList?.querySelectorAll('.is-error').length || 0;
        clearTimeout(this.uploadCollapseTimer);
        const pending = this.elements.uploadList?.querySelector('.panel-file-manager__upload-task:not(.is-done):not(.is-warning):not(.is-cancelled):not(.is-error)');
        if (!this.uploadBatches && !pending && !failures && !this.elements.uploads?.classList.contains('hidden')) {
            this.uploadCollapseTimer = setTimeout(() => {
                this.elements.uploads.classList.add('hidden');
                const toggle = this.root.querySelector('[data-fm-uploads-toggle]');
                toggle?.classList.remove('is-complete-pulse');
                if (toggle) {
                    void toggle.offsetWidth; toggle.classList.add('is-complete-pulse');
                    toggle.addEventListener('animationend', () => toggle.classList.remove('is-complete-pulse'), {once:true});
                }
            }, 1400);
        }
        this.root.querySelector('[data-fm-uploads-empty]')?.classList.toggle('hidden', count > 0);
        this.elements.uploadCount.textContent = count.toLocaleString(document.documentElement.lang || 'fa');
        this.root.querySelector('[data-fm-uploads-toggle]')?.classList.toggle('has-items', count > 0);
        const summary = this.root.querySelector('[data-fm-upload-summary]');
        if (summary) summary.textContent = failures
            ? `${failures.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(331)} ${count.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(6)}`
            : (count ? `${count.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(332)}` : `${fmText(333)}`);
    }

    uploadFileMeta(file) {
        const extension = String(file.name.split('.').pop() || '').toLowerCase();
        const groups = this.config.uploadGroups || {};
        const entry = Object.entries(groups).find(([, definition]) => (definition.extensions || []).includes(extension));
        const group = entry?.[0] || 'unknown';
        return { extension, group, definition: entry?.[1] || null };
    }

    uploadFileVisual(meta) {
        const icons = { image: 'file-image', document: 'file-text', audio: 'file-audio', video: 'file-video', archive: 'file-archive', unknown: 'file-question' };
        const extension = (meta.extension || 'FILE').slice(0, 4).toUpperCase();
        return `<span class="panel-file-manager__upload-visual is-${this.escape(meta.group)}" aria-hidden="true">${iconMarkup(icons[meta.group] || icons.unknown, 56)}<b>${this.escape(extension)}</b></span>`;
    }

    validateUploadCandidate(file) {
        const meta = this.uploadFileMeta(file);
        if (!meta.definition) {
            const extensions = Object.values(this.config.uploadGroups || {}).flatMap((group) => group.extensions || []).map((item) => item.toUpperCase());
            return `${fmText(334)}${meta.extension || `${fmText(335)}`}» ${fmText(336)}${extensions.length ? ` ${fmText(337)} ${extensions.join(`${fmText(338)} `)}` : ''}`;
        }
        const groupLabels = { image: `${fmText(15)}`, document: `${fmText(16)}`, audio: `${fmText(339)}`, video: `${fmText(17)}`, archive: `${fmText(20)} ZIP` };
        const groupLimit = Number(meta.definition.maxBytes || 0);
        if (groupLimit && file.size > groupLimit) {
            return `${fmText(340)}${file.name}» ${this.formatUploadSize(file.size)} ${fmText(341)} ${groupLabels[meta.group]} ${this.formatUploadSize(groupLimit)} ${fmText(342)}`;
        }
        const serverLimit = this.serverUploadLimit();
        if (serverLimit && file.size > serverLimit) {
            return `${fmText(340)}${file.name}» ${this.formatUploadSize(file.size)} ${fmText(343)} ${this.formatUploadSize(serverLimit)} ${fmText(342)}`;
        }
        return null;
    }

    serverUploadLimit() {
        const limits = this.config.serverUploadLimits || {};
        const values = [limits.uploadMax, limits.postMax].map((value) => this.parseByteSize(value)).filter((value) => value > 0);
        return values.length ? Math.min(...values) : 0;
    }

    parseByteSize(value) {
        const match = String(value || '').trim().match(/^(\d+(?:\.\d+)?)\s*([kmgt]?)b?$/i);
        if (!match) return 0;
        const powers = { '': 0, k: 1, m: 2, g: 3, t: 4 };
        return Math.round(Number(match[1]) * (1024 ** powers[match[2].toLowerCase()]));
    }

    formatUploadSize(bytes) {
        if (!Number.isFinite(Number(bytes)) || Number(bytes) <= 0) return `${fmText(191)} B`;
        return this.formatSize(Number(bytes));
    }

    uploadErrorMessage(file, response) {
        const payload = response.payload || {};
        const validationMessage = Object.values(payload.errors || {}).flat(Infinity).find((message) => typeof message === 'string');
        if (validationMessage) return validationMessage;
        if (payload.message && payload.message !== 'The given data was invalid.') return payload.message;
        if (response.status === 413) {
            const limit = this.serverUploadLimit();
            return `${fmText(344)}${file.name}» ${fmText(345)}${limit ? ` ${fmText(346)} ${this.formatUploadSize(limit)} ${fmText(342)}` : ` ${fmText(347)} upload_max_filesize ${fmText(348)}`}`;
        }
        if ([401, 419].includes(response.status)) return `${fmText(349)}`;
        if (response.status === 403) return `${fmText(350)}`;
        if (response.status === 422) return `${fmText(344)}${file.name}» ${fmText(351)}`;
        if (response.status === 0) return `${fmText(352)}${file.name}» ${fmText(353)}`;
        if (response.status >= 500) return `${fmText(354)}${file.name}» ${fmText(355)} ${response.status.toLocaleString(document.documentElement.lang || 'fa')}). ${fmText(356)}`;
        return `${fmText(357)}${file.name}» ${fmText(358)} ${Number(response.status || 0).toLocaleString(document.documentElement.lang || 'fa')}).`;
    }

    uploadRequest(formData, task) {
        return new Promise((resolve) => {
            const xhr = new XMLHttpRequest();
            task.xhr = xhr;
            task.setState(`${fmText(359)}`);
            xhr.open('POST', this.config.uploadUrl);
            Object.entries(this.headers(false)).forEach(([key, value]) => xhr.setRequestHeader(key, value));
            xhr.upload.addEventListener('progress', (event) => { if (event.lengthComputable) task.setProgress((event.loaded / event.total) * 100, event.loaded, event.total); });
            xhr.addEventListener('load', () => {
                const payload = (() => { try { return JSON.parse(xhr.responseText || '{}'); } catch { return {}; } })();
                resolve({ ok: xhr.status >= 200 && xhr.status < 300, status: xhr.status, payload });
            });
            xhr.addEventListener('error', () => resolve({ ok: false, status: 0, payload: {} }));
            xhr.addEventListener('abort', () => { task.setState(`${fmText(318)}`, 'is-cancelled'); resolve({ ok: false, status: 0, cancelled: true, payload: {} }); });
            xhr.send(formData);
        });
    }

    async destroySelected() {
        if (!this.selected.size || !this.permissions.delete) return;
        const confirmed = await new Promise(resolve => {
            const modal = this.elements.conflict;
            modal.classList.remove('is-duplicate');
            modal.classList.add('is-delete-confirmation');
            modal.querySelector('[data-fm-conflict-title]').textContent = `${fmText(360)}`;
            modal.querySelector('[data-fm-conflict-message]').textContent = `${fmText(361)}`;
            modal.querySelector('[data-fm-conflict-compare]').innerHTML = `<ul class="panel-file-manager__delete-list">${this.selectedEntries().map(item => `<li>${this.fileIcon(item)}<span>${this.escape(item.name)}</span></li>`).join('')}</ul>`;
            modal.querySelector('[data-fm-conflict-usages]').classList.add('hidden');
            const actions = modal.querySelector('[data-fm-conflict-actions]');
            actions.innerHTML = `<button type="button" data-answer="no">${fmText(362)}</button><button type="button" class="is-danger" data-answer="yes">${fmText(307)}</button>`;
            actions.querySelectorAll('button').forEach(button => button.addEventListener('click', () => { modal.classList.add('hidden'); resolve(button.dataset.answer === 'yes'); }, {once:true}));
            modal.classList.remove('hidden');
            actions.querySelector('[data-answer="no"]')?.focus();
        });
        if (!confirmed) return;
        for (const id of [...this.selected]) {
            const response = await this.request(`${this.config.itemsUrl}/${id}`, { method: 'DELETE' });
            if (response.status === 409) await this.resolveConflict(response.payload, true);
        }
        this.clearSelection();
        this.load();
    }

    resolveConflict(payload, infoOnly = false) {
        return new Promise((resolve) => {
            const modal = this.elements.conflict;
            modal.classList.remove('is-delete-confirmation');
            const data = payload.data || {};
            const duplicateHere = payload.code === 'duplicate_same_folder';
            modal.classList.toggle('is-duplicate', duplicateHere);
            modal.querySelector('[data-fm-conflict-title]').textContent = duplicateHere
                ? `${fmText(363)}`
                : (payload.code === 'replace_used_confirmation' ? `${fmText(364)}` : `${fmText(365)}`);
            modal.querySelector('[data-fm-conflict-message]').textContent = duplicateHere
                ? `${fmText(366)}${data.existing?.name || `${fmText(367)}`}» ${fmText(368)}`
                : (payload.message || '');
            const cards = duplicateHere
                ? [{ item: data.existing, label: `${fmText(369)}` }]
                : [
                    ...(data.source ? [{ item: data.source, label: `${fmText(370)}` }] : []),
                    ...(data.destination ? [{ item: data.destination, label: `${fmText(371)}` }] : []),
                    ...(!data.destination && data.existing ? [{ item: data.existing, label: `${fmText(369)}` }] : []),
                    ...(Array.isArray(data.duplicates) ? data.duplicates.slice(0, 2).map((item) => ({ item, label: `${fmText(372)}` })) : []),
                ];
            const compare = modal.querySelector('[data-fm-conflict-compare]');
            compare.classList.toggle('is-single', cards.length === 1);
            compare.innerHTML = cards.filter(({ item }) => item).map(({ item, label }) => {
                const visual = item.thumbnail_url
                    ? `<img src="${this.escape(item.thumbnail_url)}" alt="${fmText(57)} ${this.escape(item.name)}">`
                    : `<div class="panel-file-manager__compare-file-icon">${item.type === 'folder' ? '<i class="panel-file-manager__folder-icon" aria-hidden="true"></i>' : this.fileIcon(item)}</div>`;
                return `<article class="panel-file-manager__compare-card"><span>${this.escape(label)}</span>${visual}<div><strong>${this.escape(item.name)}</strong><small>${this.escape(this.fileTypeLabel(item))} · ${this.formatSize(item.size)}</small>${item.mime_type ? `<small dir="ltr">${this.escape(item.mime_type)}</small>` : ''}</div></article>`;
            }).join('');
            const usages = data.usages || [];
            const usageBox = modal.querySelector('[data-fm-conflict-usages]');
            usageBox.classList.toggle('hidden', !usages.length);
            usageBox.innerHTML = usages.length ? `<strong>${fmText(373)} ${usages.length.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(374)}</strong><br>${usages.slice(0, 5).map((usage) => usage.url ? `<a href="${this.escape(usage.url)}">${this.escape(`${usage.label} — ${usage.collection}`)}</a>` : this.escape(`${usage.label} — ${usage.collection}`)).join('<br>')}` : '';
            let actions;
            if (duplicateHere) actions = [['cancel', `${fmText(375)}`, ''], ['show_existing', `${fmText(376)}`, 'is-primary']];
            else if (infoOnly || payload.code === 'item_in_use') actions = [['cancel', `${fmText(50)}`, '']];
            else if (payload.code === 'duplicate_other_folders') actions = [['cancel', `${fmText(362)}`, ''], ['continue_duplicate', `${fmText(377)}`, 'is-primary']];
            else actions = [['cancel', `${fmText(362)}`, ''], ['skip', `${fmText(378)}`, ''], ['keep_both', `${fmText(379)}`, ''], ['replace', usages.length ? `${fmText(380)}` : `${fmText(381)}`, usages.length ? 'is-danger' : 'is-primary']];
            const footer = modal.querySelector('[data-fm-conflict-actions]');
            footer.innerHTML = actions.map(([value, label, cls]) => `<button type="button" class="${cls}" data-choice="${value}">${label}</button>`).join('');
            footer.querySelectorAll('[data-choice]').forEach((button) => button.addEventListener('click', () => { modal.classList.add('hidden'); resolve(button.dataset.choice); }, { once: true }));
            modal.classList.remove('hidden');
        });
    }

    openPicker(options = {}) {
        PanelFileManager.activeInstance = this;
        this.pickerOptions = { multiple: Boolean(options.multiple), max: Number(options.max || 1), group: options.group || null, selected: options.selected || [] };
        this.pickerOptions.groups = options.groups || (options.group ? [options.group] : []);
        this.selected = new Set(this.pickerOptions.selected.map((item) => Number(item.id)));
        this.selectedItems = new Map(this.pickerOptions.selected.map((item) => [Number(item.id), item]));
        this.onPickerConfirm = options.onConfirm;
        this.folderId = this.rootId;
        this.history = [];
        this.page = 1;
        if (this.elements.search) this.elements.search.value = '';
        this.load();
        setTimeout(() => this.root.focus({ preventScroll: true }), 20);
    }

    confirmPicker() {
        const issue = this.pickerSelectionIssue();
        if (issue) {
            this.toast(issue, 'warning');
            return;
        }
        this.onPickerConfirm?.(this.selectedFiles());
        this.closePickerModal();
    }

    cancelPicker() { this.closePickerModal(); }

    closePickerModal() {
        this.hideContextMenu();
        this.root.closest('dialog')?.close();
        this.closePreview();
        document.body.classList.remove('overflow-hidden');
        if (PanelFileManager.activeInstance === this) PanelFileManager.activeInstance = null;
    }

    applyPermissions() {
        this.root.querySelectorAll('[data-fm-new-folder],[data-fm-upload],[data-fm-rename],[data-fm-copy],[data-fm-cut],[data-fm-paste],[data-fm-compress]').forEach((element) => element.classList.toggle('hidden', !this.permissions.manage));
        this.root.querySelectorAll('[data-fm-delete],[data-fm-delete-toolbar]').forEach((element) => element.classList.toggle('hidden', !this.permissions.delete));
        this.elements.actionsMenu?.classList.toggle('hidden', !this.permissions.manage && !this.permissions.delete);
    }

    setView(view) {
        this.view = view === 'list' ? 'list' : 'grid';
        this.root.classList.toggle('is-list', this.view === 'list');
        localStorage.setItem('panel-file-manager-view', this.view);
        this.root.querySelectorAll('[data-fm-view]').forEach((button) => {
            const active = button.dataset.fmView === this.view;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-pressed', active ? 'true' : 'false');
        });
    }

    toggleLoading(loading) {
        this.elements.loading.classList.toggle('hidden', !loading);
        this.elements.items.classList.toggle('hidden', loading);
    }

    setLoadMoreState(loading) {
        if (!this.elements.loadMore) return;
        const hasMore = this.page < this.lastPage;
        this.elements.loadMore.classList.toggle('hidden', !loading && !hasMore);
        this.elements.loadMore.classList.toggle('is-loading', loading);
        const label = this.elements.loadMore.querySelector('p');
        if (label) label.textContent = loading ? `${fmText(382)}` : `${fmText(383)}`;
    }

    item(id) { return this.items.find((item) => item.id === Number(id)); }

    formatSize(bytes) {
        if (!bytes) return '—';
        const units = ['B', 'KB', 'MB', 'GB'];
        const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), 3);
        return `${(bytes / (1024 ** index)).toFixed(index ? 1 : 0)} ${units[index]}`;
    }

    headers(json = true) {
        const headers = { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content || this.config.csrfToken, 'X-Requested-With': 'XMLHttpRequest' };
        if (json) headers['Content-Type'] = 'application/json';
        return headers;
    }

    async request(url, options = {}) {
        let response;
        try {
            response = await fetch(url, { method: options.method || 'GET', headers: this.headers(), body: options.body ? JSON.stringify(options.body) : undefined });
        } catch {
            this.toast(`${fmText(384)}`, 'error');
            return {ok:false,status:0,payload:{}};
        }
        const payload = await response.json().catch(() => ({}));
        if (!response.ok && response.status !== 409) {
            const message = Object.values(payload.errors || {}).flat()[0] || payload.message || `${fmText(384)}`;
            this.toast(message, 'error');
        }
        return { ok: response.ok, status: response.status, payload };
    }

    toast(message, type = 'success') {
        if (window.PanelToast?.[type]) window.PanelToast[type](message, { container: this.elements.toastStack });
        else if (window.toastr?.[type]) window.toastr[type](message);
    }

    escape(value) {
        return String(value ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }
}
