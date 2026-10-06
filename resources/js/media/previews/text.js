export async function mount(manager, item) {
    if (Number(item.size) > 2 * 1024 * 1024) throw new Error(manager.previewLabels().too_large);
    const generation = manager.previewGeneration;
    const response = await fetch(item.url, {headers:{Accept:'text/plain'}});
    if (!response.ok) throw new Error(manager.previewLabels().failed);
    const content = await response.text();
    if (manager.previewGeneration !== generation) return;
    const stage = manager.elements.previewStage;
    stage.classList.add('is-text');
    const pre = document.createElement('pre');
    pre.className = 'fm-text-document'; pre.dir = 'auto'; pre.textContent = content;
    stage.replaceChildren(pre);
}
