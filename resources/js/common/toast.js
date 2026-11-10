let timer;
export function announce(message, error = false) {
    if (window.parent !== window && document.querySelector('[data-kanban-editor]')) {
        try {
            if (window.parent.document.querySelector('[data-workflow-toast]')) {
                window.parent.dispatchEvent(new CustomEvent('jobic:workflow:toast', {detail:{message:String(message ?? ''),error:Boolean(error)}}));
                return;
            }
        } catch { /* Use the local toast when a parent is not accessible. */ }
    }
    const toast = document.querySelector('[data-ui-toast]');
    if (!toast || !message) return;
    clearTimeout(timer);
    toast.querySelector('[data-ui-toast-message]').textContent = message;
    toast.classList.toggle('is-error', error);
    toast.hidden = false;
    toast.classList.add('is-visible');
    if (toast.hasAttribute('popover') && typeof toast.showPopover === 'function' && !toast.matches(':popover-open')) toast.showPopover();
    const close = () => {
        if (toast.hasAttribute('popover') && typeof toast.hidePopover === 'function' && toast.matches(':popover-open')) toast.hidePopover();
        toast.hidden = true; toast.classList.remove('is-visible'); clearTimeout(timer);
    };
    toast.querySelector('[data-toast-dismiss]').onclick = close;
    timer = setTimeout(close, error ? 8000 : 4500);
}
