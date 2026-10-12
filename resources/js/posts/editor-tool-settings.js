const instances = new Map();

/** Keep trusted tool settings available without depending on Editor.js private properties. */
export function withToolSettings(Tool) {
    return class extends Tool {
        constructor(options) {
            super(options);
            this.workbenchBlockId = options.block?.id;
            if (this.workbenchBlockId) instances.set(this.workbenchBlockId, this);
        }
        destroy() {
            if (instances.get(this.workbenchBlockId) === this) instances.delete(this.workbenchBlockId);
            return super.destroy?.();
        }
    };
}

export function toolSettings(block) {
    return instances.get(block.id)?.renderSettings?.() || [];
}
