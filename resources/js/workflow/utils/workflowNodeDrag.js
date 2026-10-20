/** Keep the exact point grabbed on a node underneath the pointer at any zoom. */
export function nodePositionAtPointer(pointer, viewport, grabOffset) {
    return {
        x: (pointer.x - viewport.left - viewport.panX) / viewport.zoom - grabOffset.x,
        y: (pointer.y - viewport.top - viewport.panY) / viewport.zoom - grabOffset.y,
    };
}

/** Ease canvas panning into the last few pixels of its viewport. */
export function edgePanStep(pointer, bounds, margin = 36, maxSpeed = 4) {
    if (pointer.x < bounds.left || pointer.x > bounds.right
        || pointer.y < bounds.top || pointer.y > bounds.bottom) {
        return {x: 0, y: 0};
    }

    const axisStep = (position, low, high) => {
        const lowDepth = Math.max(0, (low + margin - position) / margin);
        const highDepth = Math.max(0, (position - (high - margin)) / margin);
        return maxSpeed * (lowDepth * lowDepth - highDepth * highDepth);
    };

    return {
        x: axisStep(pointer.x, bounds.left, bounds.right),
        y: axisStep(pointer.y, bounds.top, bounds.bottom),
    };
}
