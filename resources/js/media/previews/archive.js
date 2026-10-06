export async function mount(manager, item) {
    await manager.loadStructuredPreview(item, {offset:0});
}
