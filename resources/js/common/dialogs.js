// Dismiss native modal backdrops without treating clicks inside content as dismissal.
let backdropStart = null;
function outside(dialog, event) {
    const rect = dialog.getBoundingClientRect();
    return event.clientX < rect.left || event.clientX > rect.right || event.clientY < rect.top || event.clientY > rect.bottom;
}
document.addEventListener('pointerdown', event => {
    backdropStart = event.target instanceof HTMLDialogElement && outside(event.target, event) ? event.target : null;
});
document.addEventListener('click', event => {
    if (event.target === backdropStart && backdropStart?.open && outside(backdropStart, event)) backdropStart.close();
    backdropStart = null;
});
