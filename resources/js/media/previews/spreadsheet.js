export async function mount(manager, item) {
    await manager.loadStructuredPreview(item, {sheet:0, offset:0});
}
