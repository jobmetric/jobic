export async function mount(manager, item) {
    const generation = manager.previewGeneration;
    const response = await manager.request(`${manager.config.itemsUrl}/${item.id}/preview`);
    if (!response.ok) throw new Error(response.payload?.message || manager.previewLabels().failed);
    if (generation !== manager.previewGeneration) return;
    const stage = manager.elements.previewStage;
    stage.className = 'panel-file-manager__preview-stage is-office';
    stage.replaceChildren();
    for (const [index, page] of (response.payload.data?.pages || []).entries()) {
        const article = document.createElement('article'); article.className = 'fm-office-page';
        if (item.extension === 'pptx') article.classList.add('is-slide');
        const number = document.createElement('small'); number.textContent = String(index + 1); article.append(number);
        for (const text of page.paragraphs) {const p=document.createElement('p');p.dir='auto';p.textContent=text;article.append(p);}
        stage.append(article);
    }
}
