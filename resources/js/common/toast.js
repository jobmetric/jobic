let timer;
export function announce(message, error = false) {
    const toast = document.querySelector('[data-ui-toast]');
    if (!toast || !message) return;
    clearTimeout(timer);
    toast.querySelector('[data-ui-toast-message]').textContent = message;
    toast.classList.toggle('is-error', error);
    toast.hidden = false;
    toast.classList.add('is-visible');
    const close = () => { toast.hidden = true; toast.classList.remove('is-visible'); clearTimeout(timer); };
    toast.querySelector('[data-toast-dismiss]').onclick = close;
    timer = setTimeout(close, error ? 8000 : 4500);
}
