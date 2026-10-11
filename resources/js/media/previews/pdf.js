import { pageData } from '../../common/page';
import { iconMarkup } from '../../common/icons';
const fmText = key => pageData().fileManager?.texts?.[key] ?? '';
export default {
    async renderPdfPreview(item) {
        const stage = this.elements.previewStage;
        stage.className = 'panel-file-manager__preview-stage is-pdf';
        stage.innerHTML = `<div class="panel-file-manager__pdf-viewer" data-pdf-viewer tabindex="0">
            <header class="panel-file-manager__pdf-toolbar">
                <div class="panel-file-manager__pdf-page-tools">
                    <button type="button" data-pdf-previous aria-label="${fmText(73)}" title="${fmText(73)}">${iconMarkup('chevron-left', 16)}</button>
                    <label><span>${fmText(74)}</span><input type="number" min="1" value="1" data-pdf-page aria-label="${fmText(75)}"><i>/</i><b data-pdf-pages>—</b></label>
                    <button type="button" data-pdf-next aria-label="${fmText(76)}" title="${fmText(76)}">${iconMarkup('chevron-right', 16)}</button>
                </div>
                <div class="panel-file-manager__pdf-document"><span>${iconMarkup('file-text', 17)}</span><div><strong dir="auto">${this.escape(item.name)}</strong><small data-pdf-status>${fmText(77)}</small></div></div>
                <div class="panel-file-manager__pdf-zoom-tools">
                    <button type="button" data-pdf-zoom-out aria-label="${fmText(78)}" title="${fmText(78)}">${iconMarkup('minus', 15)}</button>
                    <output data-pdf-zoom-value>${fmText(79)}</output>
                    <button type="button" data-pdf-zoom-in aria-label="${fmText(80)}" title="${fmText(80)}">${iconMarkup('plus', 15)}</button>
                    <button type="button" data-pdf-fit aria-label="${fmText(81)}" title="${fmText(81)}">${iconMarkup('maximize', 15)}</button>
                    <button type="button" data-pdf-rotate aria-label="${fmText(82)}" title="${fmText(82)}">${iconMarkup('rotate', 15)}</button>
                </div>
            </header>
            <div class="panel-file-manager__pdf-stage" data-pdf-stage>
                <div class="panel-file-manager__pdf-paper"><canvas data-pdf-canvas aria-label="${fmText(74)} PDF"></canvas></div>
                <div class="panel-file-manager__pdf-loading" data-pdf-loading><span></span><strong>${fmText(83)}</strong><small>${fmText(84)}</small></div>
                <div class="panel-file-manager__pdf-error hidden" data-pdf-error>${iconMarkup('info', 19)}<strong>${fmText(85)} PDF ${fmText(86)}</strong><p></p><a href="${this.escape(item.url)}" download="${this.escape(item.name)}">${fmText(87)}</a></div>
            </div>
        </div>`;

        const viewer = stage.querySelector('[data-pdf-viewer]');
        const state = {
            item,
            viewer,
            document: null,
            loadingTask: null,
            renderTask: null,
            page: 1,
            zoom: 1,
            fitScale: 1,
            zoomMode: 'fit',
            rotation: 0,
            resizeTimer: null,
            resizeHandler: null,
        };
        this.pdfPreviewState = state;
        const scroll = viewer.querySelector('[data-pdf-stage]');
        scroll.addEventListener('wheel', event => {
            if (event.ctrlKey || !state.document || state.renderTask || Date.now() < (state.wheelUntil || 0)) return;
            const bottom = scroll.scrollTop + scroll.clientHeight >= scroll.scrollHeight - 2;
            if ((event.deltaY > 0 && bottom && state.page < state.document.numPages) || (event.deltaY < 0 && scroll.scrollTop <= 0 && state.page > 1)) {
                event.preventDefault(); state.wheelUntil = Date.now() + 450;
                this.changePdfPage(state, event.deltaY > 0 ? 1 : -1);
            }
        }, {passive:false});

        viewer.querySelector('[data-pdf-previous]').addEventListener('click', () => this.changePdfPage(state, -1));
        viewer.querySelector('[data-pdf-next]').addEventListener('click', () => this.changePdfPage(state, 1));
        viewer.querySelector('[data-pdf-page]').addEventListener('change', (event) => {
            if (!state.document) return;
            state.page = Math.max(1, Math.min(state.document.numPages, Number(event.target.value) || 1));
            this.renderPdfPage(state);
        });
        viewer.querySelector('[data-pdf-zoom-in]').addEventListener('click', () => this.changePdfZoom(state, 1.2));
        viewer.querySelector('[data-pdf-zoom-out]').addEventListener('click', () => this.changePdfZoom(state, 1 / 1.2));
        viewer.querySelector('[data-pdf-fit]').addEventListener('click', () => {
            state.zoomMode = 'fit';
            this.renderPdfPage(state);
        });
        viewer.querySelector('[data-pdf-rotate]').addEventListener('click', () => {
            state.rotation = (state.rotation + 90) % 360;
            this.renderPdfPage(state);
        });
        viewer.addEventListener('keydown', (event) => {
            if (event.target.tagName === 'INPUT') return;
            if (!['ArrowLeft', 'ArrowRight', 'PageUp', 'PageDown', 'Home', 'End', '+', '-', '0'].includes(event.key)) return;
            event.preventDefault();
            if (event.key === 'ArrowLeft' || event.key === 'PageDown') this.changePdfPage(state, 1);
            if (event.key === 'ArrowRight' || event.key === 'PageUp') this.changePdfPage(state, -1);
            if (event.key === 'Home') { state.page = 1; this.renderPdfPage(state); }
            if (event.key === 'End' && state.document) { state.page = state.document.numPages; this.renderPdfPage(state); }
            if (event.key === '+') this.changePdfZoom(state, 1.2);
            if (event.key === '-') this.changePdfZoom(state, 1 / 1.2);
            if (event.key === '0') { state.zoomMode = 'fit'; this.renderPdfPage(state); }
        });
        state.resizeHandler = () => {
            if (state.zoomMode !== 'fit' || this.pdfPreviewState !== state) return;
            clearTimeout(state.resizeTimer);
            state.resizeTimer = setTimeout(() => this.renderPdfPage(state), 120);
        };
        window.addEventListener('resize', state.resizeHandler);

        try {
            const pdfModule = '/vendors/pdfjs/build/pdf.min.js?v=6.2.108-2';
            this.pdfJsPromise ||= import(/* @vite-ignore */ pdfModule)
                .then((pdfjs) => {
                    pdfjs.GlobalWorkerOptions.workerSrc = '/vendors/pdfjs/build/pdf.worker.min.js?v=6.2.108-2';
                    return pdfjs;
                })
                .catch((error) => {
                    // A rejected dynamic import remains rejected forever. Reset it so a
                    // temporary network/cache failure can be retried without reopening the app.
                    this.pdfJsPromise = null;
                    throw error;
                });
            const pdfjs = await this.pdfJsPromise;
            if (this.pdfPreviewState !== state) return;
            state.loadingTask = pdfjs.getDocument({
                url: item.url,
                cMapUrl: '/vendors/pdfjs/cmaps/',
                cMapPacked: true,
                standardFontDataUrl: '/vendors/pdfjs/standard_fonts/',
                wasmUrl: '/vendors/pdfjs/wasm/',
            });
            state.document = await state.loadingTask.promise;
            if (this.pdfPreviewState !== state) return;
            viewer.querySelector('[data-pdf-pages]').textContent = state.document.numPages.toLocaleString(document.documentElement.lang || 'fa');
            viewer.querySelector('[data-pdf-page]').max = String(state.document.numPages);
            await this.renderPdfPage(state);
            viewer.focus();
        } catch (error) {
            if (this.pdfPreviewState !== state || error?.name === 'AbortException') return;
            viewer.querySelector('[data-pdf-loading]').classList.add('hidden');
            const technicalMessage = error?.message || '';
            const moduleLoadFailed = /dynamically imported module|module script|pdf\.min\.mjs/i.test(technicalMessage);
            const message = moduleLoadFailed
                ? `${fmText(88)} PDF ${fmText(89)}`
                : (technicalMessage || `${fmText(90)}`);
            const errorBox = viewer.querySelector('[data-pdf-error]');
            errorBox.querySelector('p').textContent = message;
            errorBox.classList.remove('hidden');
            this.toast(`${fmText(57)} PDF ${fmText(91)}`, 'error');
        }
    },

    async renderPdfPage(state) {
        if (!state?.document || this.pdfPreviewState !== state) return;
        const revision = state.renderRevision = (state.renderRevision || 0) + 1;
        const viewer = state.viewer;
        const loading = viewer.querySelector('[data-pdf-loading]');
        const canvas = viewer.querySelector('[data-pdf-canvas]');
        const paper = canvas.closest('.panel-file-manager__pdf-paper');
        loading.classList.remove('hidden');
        paper.classList.add('is-rendering');
        const previousTask = state.renderTask;
        previousTask?.cancel();
        if (previousTask) await previousTask.promise.catch(() => {});
        if (revision !== state.renderRevision || this.pdfPreviewState !== state) return;

        try {
            const page = await state.document.getPage(state.page);
            if (this.pdfPreviewState !== state || revision !== state.renderRevision) return;
            const stage = viewer.querySelector('[data-pdf-stage]');
            const baseViewport = page.getViewport({ scale: 1, rotation: state.rotation });
            state.fitScale = Math.max(.2, Math.min(3, (stage.clientWidth - 64) / baseViewport.width, (stage.clientHeight - 54) / baseViewport.height));
            if (state.zoomMode === 'fit') state.zoom = state.fitScale;
            const viewport = page.getViewport({ scale: state.zoom, rotation: state.rotation });
            const ratio = Math.min(2, window.devicePixelRatio || 1);
            canvas.width = Math.floor(viewport.width * ratio);
            canvas.height = Math.floor(viewport.height * ratio);
            canvas.style.width = `${Math.floor(viewport.width)}px`;
            canvas.style.height = `${Math.floor(viewport.height)}px`;
            const context = canvas.getContext('2d', { alpha: false });
            state.renderTask = page.render({
                canvasContext: context,
                viewport,
                transform: ratio === 1 ? null : [ratio, 0, 0, ratio, 0, 0],
            });
            await state.renderTask.promise;
            viewer.querySelector('[data-pdf-page]').value = String(state.page);
            viewer.querySelector('[data-pdf-previous]').disabled = state.page <= 1;
            viewer.querySelector('[data-pdf-next]').disabled = state.page >= state.document.numPages;
            viewer.querySelector('[data-pdf-zoom-value]').textContent = state.zoomMode === 'fit' ? `${fmText(79)}` : `${Math.round(state.zoom * 100).toLocaleString(document.documentElement.lang || 'fa')}${fmText(92)}`;
            viewer.querySelector('[data-pdf-status]').textContent = `${fmText(74)} ${state.page.toLocaleString(document.documentElement.lang || 'fa')} ${fmText(32)} ${state.document.numPages.toLocaleString(document.documentElement.lang || 'fa')}`;
        } catch (error) {
            if (error?.name !== 'RenderingCancelledException') throw error;
        } finally {
            if (revision === state.renderRevision) state.renderTask = null;
            if (this.pdfPreviewState === state && revision === state.renderRevision) {
                loading.classList.add('hidden');
                paper.classList.remove('is-rendering');
            }
        }
    },

    changePdfPage(state, direction) {
        if (!state?.document) return;
        const next = Math.max(1, Math.min(state.document.numPages, state.page + direction));
        if (next === state.page) return;
        state.page = next;
        this.renderPdfPage(state);
    },

    changePdfZoom(state, multiplier) {
        if (!state?.document) return;
        state.zoomMode = 'custom';
        state.zoom = Math.max(.2, Math.min(4, state.zoom * multiplier));
        this.renderPdfPage(state);
    },

    destroyPdfPreview() {
        const state = this.pdfPreviewState;
        if (!state) return;
        clearTimeout(state.resizeTimer);
        if (state.resizeHandler) window.removeEventListener('resize', state.resizeHandler);
        try { state.renderTask?.cancel(); } catch {}
        try { state.loadingTask?.destroy(); } catch {}
        try { state.document?.destroy(); } catch {}
        this.pdfPreviewState = null;
    },

};
