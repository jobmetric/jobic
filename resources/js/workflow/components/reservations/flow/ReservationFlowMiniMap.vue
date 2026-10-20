<script setup>
import { computed, onBeforeUnmount, ref } from 'vue';

import { reservationFlowNodeTheme } from '../../../utils/reservationFlowNodeTheme.js';

const props = defineProps({
    nodes: { type: Array, default: () => [] },
    graphBounds: { type: Object, required: true },
    viewport: { type: Object, default: null },
});

const emit = defineEmits(['navigate', 'pan']);

const MINIMAP_INNER_WIDTH = 180;
const MINIMAP_INNER_HEIGHT = 110;
const PADDING = 10;

const minimapRef = ref(null);
const viewportDrag = ref(null);
const displayBounds = computed(() => {
    const graph = props.graphBounds;
    const viewport = props.viewport;
    const minX = Math.min(graph.minX, viewport?.x ?? graph.minX);
    const minY = Math.min(graph.minY, viewport?.y ?? graph.minY);
    const maxX = Math.max(graph.minX + graph.width, viewport ? viewport.x + viewport.w : graph.minX + graph.width);
    const maxY = Math.max(graph.minY + graph.height, viewport ? viewport.y + viewport.h : graph.minY + graph.height);

    return { minX, minY, width: Math.max(1, maxX - minX), height: Math.max(1, maxY - minY) };
});

const minimapScale = computed(() => {
    const bounds = displayBounds.value;

    if (!bounds?.width || !bounds?.height) {
        return 1;
    }

    return Math.min(
        MINIMAP_INNER_WIDTH / bounds.width,
        MINIMAP_INNER_HEIGHT / bounds.height,
    );
});

const minimapOffset = computed(() => {
    const bounds = displayBounds.value;
    const scale = minimapScale.value;

    return {
        x: PADDING + ((MINIMAP_INNER_WIDTH - bounds.width * scale) / 2),
        y: PADDING + ((MINIMAP_INNER_HEIGHT - bounds.height * scale) / 2),
    };
});

const viewportRect = computed(() => {
    if (!props.viewport) {
        return null;
    }

    const scale = minimapScale.value;
    const offset = minimapOffset.value;
    const bounds = displayBounds.value;

    const w = Math.min(MINIMAP_INNER_WIDTH, Math.max(8, props.viewport.w * scale));
    const h = Math.min(MINIMAP_INNER_HEIGHT, Math.max(8, props.viewport.h * scale));
    const x = Math.max(PADDING, Math.min(PADDING + MINIMAP_INNER_WIDTH - w,
        (props.viewport.x - bounds.minX) * scale + offset.x));
    const y = Math.max(PADDING, Math.min(PADDING + MINIMAP_INNER_HEIGHT - h,
        (props.viewport.y - bounds.minY) * scale + offset.y));

    return { x, y, w, h };
});

function nodeRect(node) {
    const scale = minimapScale.value;
    const offset = minimapOffset.value;
    const bounds = displayBounds.value;

    return {
        x: (node.x - bounds.minX) * scale + offset.x,
        y: (node.y - bounds.minY) * scale + offset.y,
        w: Math.max(4, node.width * scale),
        h: Math.max(3, node.height * scale),
    };
}

function nodeFill(node) {
    return reservationFlowNodeTheme(node).border;
}

function graphPointFromMinimap(clientX, clientY) {
    const element = minimapRef.value;
    if (!element) {
        return null;
    }

    const rect = element.getBoundingClientRect();
    const localX = clientX - rect.left - minimapOffset.value.x;
    const localY = clientY - rect.top - minimapOffset.value.y;
    const scale = minimapScale.value;

    if (scale <= 0) {
        return null;
    }

    return {
        x: (localX / scale) + displayBounds.value.minX,
        y: (localY / scale) + displayBounds.value.minY,
    };
}

function onMinimapPointerDown(event) {
    if (event.target?.classList?.contains('rf-minimap__viewport')) {
        return;
    }

    event.preventDefault();

    const point = graphPointFromMinimap(event.clientX, event.clientY);

    if (point) {
        emit('navigate', point);
    }
}

function navigateFromClientPoint(clientX, clientY) {
    const point = graphPointFromMinimap(clientX, clientY);

    if (point) {
        emit('navigate', point);
    }
}

function onViewportPointerDown(event) {
    event.preventDefault();
    event.stopPropagation();

    if (event.currentTarget?.setPointerCapture) {
        event.currentTarget.setPointerCapture(event.pointerId);
    }

    viewportDrag.value = {
        lastX: event.clientX,
        lastY: event.clientY,
        startX: event.clientX,
        startY: event.clientY,
        moved: false,
    };
}

function onViewportPointerMove(event) {
    if (!viewportDrag.value) {
        return;
    }

    const scale = minimapScale.value;

    if (scale <= 0) {
        return;
    }

    const totalDx = event.clientX - viewportDrag.value.startX;
    const totalDy = event.clientY - viewportDrag.value.startY;

    if (Math.abs(totalDx) > 3 || Math.abs(totalDy) > 3) {
        viewportDrag.value.moved = true;
    }

    const dx = (event.clientX - viewportDrag.value.lastX) / scale;
    const dy = (event.clientY - viewportDrag.value.lastY) / scale;

    viewportDrag.value.lastX = event.clientX;
    viewportDrag.value.lastY = event.clientY;

    if (!viewportDrag.value.moved) {
        return;
    }

    emit('pan', { dx: -dx, dy: -dy });
}

function onViewportPointerUp(event) {
    if (!viewportDrag.value) {
        return;
    }

    if (!viewportDrag.value.moved) {
        navigateFromClientPoint(viewportDrag.value.startX, viewportDrag.value.startY);
    }

    viewportDrag.value = null;
}

if (typeof window !== 'undefined') {
    window.addEventListener('pointermove', onViewportPointerMove);
    window.addEventListener('pointerup', onViewportPointerUp);
}

onBeforeUnmount(() => {
    if (typeof window !== 'undefined') {
        window.removeEventListener('pointermove', onViewportPointerMove);
        window.removeEventListener('pointerup', onViewportPointerUp);
    }
});
</script>

<template>
    <div
        ref="minimapRef"
        class="rf-minimap"
        @pointerdown="onMinimapPointerDown"
    >
        <svg
            :width="MINIMAP_INNER_WIDTH + PADDING * 2"
            :height="MINIMAP_INNER_HEIGHT + PADDING * 2"
            aria-hidden="true"
        >
            <rect
                class="rf-minimap__background"
                :x="PADDING"
                :y="PADDING"
                :width="MINIMAP_INNER_WIDTH"
                :height="MINIMAP_INNER_HEIGHT"
                fill="var(--rf-minimap-surface, #f4f8f7)"
                rx="4"
            />
            <template v-for="node in nodes" :key="node.key">
                <circle
                    v-if="node.kind === 'start'"
                    class="rf-minimap__node"
                    :cx="nodeRect(node).x + nodeRect(node).w / 2"
                    :cy="nodeRect(node).y + nodeRect(node).h / 2"
                    :r="Math.min(nodeRect(node).w, nodeRect(node).h) / 2"
                    fill="#e68a26"
                    :opacity="0.65"
                    @pointerdown.stop="emit('navigate', { x: node.x + node.width / 2, y: node.y + node.height / 2 })"
                />
                <rect
                    v-else-if="node.is_terminal"
                    class="rf-minimap__node"
                    :x="nodeRect(node).x"
                    :y="nodeRect(node).y"
                    :width="nodeRect(node).w"
                    :height="nodeRect(node).h"
                    :fill="nodeFill(node)"
                    :opacity="0.88"
                    :rx="nodeRect(node).h / 2"
                    @pointerdown.stop="emit('navigate', { x: node.x + node.width / 2, y: node.y + node.height / 2 })"
                />
                <rect
                    v-else
                    class="rf-minimap__node"
                    :x="nodeRect(node).x"
                    :y="nodeRect(node).y"
                    :width="nodeRect(node).w"
                    :height="nodeRect(node).h"
                    :fill="nodeFill(node)"
                    :opacity="0.88"
                    rx="2"
                    @pointerdown.stop="emit('navigate', { x: node.x + node.width / 2, y: node.y + node.height / 2 })"
                />
            </template>
            <rect
                v-if="viewportRect"
                class="rf-minimap__viewport"
                :x="viewportRect.x"
                :y="viewportRect.y"
                :width="viewportRect.w"
                :height="viewportRect.h"
                fill="rgba(46, 174, 152, 0.12)"
                stroke="#2eae98"
                stroke-width="2"
                rx="6"
                @pointerdown.stop="onViewportPointerDown"
            />
        </svg>
    </div>
</template>

<style scoped>
.rf-minimap {
    position: absolute;
    bottom: 16px;
    inset-inline-end: 16px;
    z-index: 7;
    padding: 0;
    --rf-minimap-surface: #f4f8f7;
    border: 1px solid var(--rf-border, #dce9e5);
    border-radius: 13px;
    background: var(--rf-panel, #ffffff);
    box-shadow: 0 6px 20px rgba(15, 23, 42, 0.10);
    overflow: hidden;
    cursor: crosshair;
    touch-action: none;
}

.rf-minimap__viewport {
    cursor: grab;
}

:root[data-theme='dark'] .rf-minimap { --rf-minimap-surface: var(--surface-soft); }
:root[data-theme='dark'] .rf-minimap__viewport { fill: color-mix(in srgb, var(--primary) 18%, transparent); stroke: var(--primary); }
:root[data-theme='dark'] .rf-minimap__node { opacity: 0.65; }

.rf-minimap__viewport:active {
    cursor: grabbing;
}

@media (max-width: 1024px) {
    .rf-minimap {
        display: none;
    }
}
</style>
