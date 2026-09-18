import { pageData } from './page';

export function enhanceSelects(root = document) {
    const $ = window.jQuery;
    if (!$?.fn.select2) return;
    root.querySelectorAll('select').forEach(select => {
        // The installation studio already owns its Select2 lifecycle and layout.
        if (select.closest('[data-installation]') || $(select).hasClass('select2-hidden-accessible')) return;
        const dialog = select.closest('dialog');
        const renderOption = item => {
            const flag = item.element?.dataset.flagUrl;
            if (!flag) return item.text;
            const row = document.createElement('span'); row.className = 'cms-select-flag';
            const image = document.createElement('img'); image.src = flag; image.alt = ''; image.width = 24; image.height = 18;
            const label = document.createElement('span'); label.textContent = item.text;
            row.append(image, label);
            return $(row);
        };
        $(select).select2({
            templateResult: renderOption, templateSelection: renderOption,
            width: '100%', dir: document.documentElement.dir,
            dropdownParent: dialog ? $(dialog) : $(document.body),
            minimumResultsForSearch: select.options.length < 8 ? Infinity : 0,
            language: {noResults: () => pageData().selects?.no_results || '', searching: () => pageData().selects?.searching || ''},
        });
        $(select).on('select2:select.cms select2:unselect.cms', () => {
            select.dispatchEvent(new Event('change', {bubbles: true}));
        });
    });
}
function initialize() {
    enhanceSelects();
    new MutationObserver(records => {
        records.forEach(record => record.addedNodes.forEach(node => {
            if (node.nodeType !== Node.ELEMENT_NODE || node.closest('.select2-container')) return;
            if (node.matches('select')) enhanceSelects(node.parentElement);
            else if (node.querySelector('select')) enhanceSelects(node);
        }));
    }).observe(document.body, {childList: true, subtree: true});
    new MutationObserver(() => {
        const $ = window.jQuery;
        if (!$?.fn.select2) return;
        document.querySelectorAll('select.select2-hidden-accessible').forEach(select => {
            if (select.closest('[data-installation]')) return;
            $(select).off('.cms').select2('destroy');
        });
        enhanceSelects();
    }).observe(document.documentElement, {attributes: true, attributeFilter: ['lang', 'dir']});
}
if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize);
else initialize();
