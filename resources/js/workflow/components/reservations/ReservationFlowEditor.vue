<script setup>
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';

import { useReservationFlowConfirmInject } from '../../composables/useReservationFlowConfirmInject.js';

import ReservationFlowCanvasToolbar from './flow/ReservationFlowCanvasToolbar.vue';
import ReservationFlowInspectorEmpty from './flow/ReservationFlowInspectorEmpty.vue';
import ReservationFlowMiniMap from './flow/ReservationFlowMiniMap.vue';
import ReservationFlowStateContextMenu from './flow/ReservationFlowStateContextMenu.vue';
import ReservationFlowStateInspector from './flow/ReservationFlowStateInspector.vue';
import ReservationFlowTransitionInspector from './flow/ReservationFlowTransitionInspector.vue';
import ReservationFlowZoomControls from './flow/ReservationFlowZoomControls.vue';
import {
    buildOrthogonalEdgePath,
    buildReservationFlowDiagram,
    buildSelfLoopEdgePath,
    findPortTarget,
} from '../../utils/reservationFlowDiagramLayout.js';
import {
    computeGraphBounds,
    fitViewportToGraph,
    graphViewportFromPanZoom,
    panToGraphPoint,
} from '../../utils/reservationFlowDraft.js';
import { reservationFlowNodeTheme } from '../../utils/reservationFlowNodeTheme.js';
import { edgePanStep, nodePositionAtPointer } from '../../utils/workflowNodeDrag.js';

const props = defineProps({
    copy: { type: Object, required: true },
    states: { type: Array, default: () => [] },
    transitions: { type: Array, default: () => [] },
    availableStatuses: { type: Array, default: () => [] },
    availableTaskDrivers: { type: Array, default: () => [] },
    editable: { type: Boolean, default: false },
    saving: { type: Boolean, default: false },
    showAllTransitionLabels: { type: Boolean, default: false },
    startPosition: { type: Object, default: null },
    currentStatus: { type: String, default: '' },
    reservationView: { type: Boolean, default: false },
    embedded: { type: Boolean, default: false },
    hideInspector: { type: Boolean, default: false },
    hideMiniMap: { type: Boolean, default: false },
});

function isCurrentReservationNode(node) {
    if (node.kind !== 'state' || !props.currentStatus) return false;
    return String(node.status || '').toLowerCase() === String(props.currentStatus).toLowerCase();
}

const emit = defineEmits([
    'customize',
    'update-state',
    'save-graph',
    'delete-state',
    'duplicate-state',
    'move-state',
    'move-start',
    'update-transition',
    'reconnect-transition',
    'delete-transition',
    'add-task',
    'update-task',
    'delete-task',
    'add-state',
    'add-transition',
    'state-menu-edit',
    'state-menu-add-transition',
]);

const requestConfirm = useReservationFlowConfirmInject({ optional: !props.editable });

const selected = ref(null);
const inspectorMode = ref('idle');
const dragState = ref(null);
const panDrag = ref(null);
const dragPositions = ref({});
const dragStartPosition = ref(null);
const canvasWrapRef = ref(null);
const canvasRef = ref(null);
const reconnectDrag = ref(null);
const reconnectCursor = ref(null);
const reconnectArmedEnd = ref(null);
const creationDrag = ref(null);
const creationCursor = ref(null);
const hoveredStateId = ref(null);
const hoveredEdgeId = ref(null);
const shiftHeld = ref(false);
const zoomLevel = ref(1);
const panX = ref(0);
const panY = ref(0);
const canvasTool = ref('select');
const didInitialFit = ref(false);
let edgePanFrame = null;

const ZOOM_MIN = 0.55;
const ZOOM_MAX = 1.6;
const ZOOM_STEP = 0.1;
const RECONNECT_NODE_PROXIMITY = 80;

const editStateForm = ref({ name: '', content_status: 'editable', is_terminal: false, allows_approval: false });
const stateInspectorTab = ref('details');
const stateMenu = ref({ open: false, anchor: null, node: null });
const canvasMenu = ref(null);
const editTransitionForm = ref({ label: '' });
const anchorCache = reactive({});

const positionOverrides = computed(() => {
    const map = {};

    props.states.forEach((state) => {
        if (dragPositions.value[state.id]) {
            map[state.id] = dragPositions.value[state.id];
            return;
        }

        if (state.position?.x != null && state.position?.y != null) {
            map[state.id] = state.position;
        }
    });

    return map;
});

const layout = computed(() => buildReservationFlowDiagram(
    props.states,
    props.transitions,
    {
        positions: positionOverrides.value,
        startPosition: dragStartPosition.value ?? props.startPosition,
        startLabel: props.copy.reservation_flow_from_any ?? '',
        anchorCache,
    },
));

const stageStyle = computed(() => ({
    width: `${layout.value.width}px`,
    height: `${layout.value.height}px`,
    transform: `translate(${panX.value}px, ${panY.value}px) scale(${zoomLevel.value})`,
    transformOrigin: '0 0',
}));

const graphBounds = computed(() => computeGraphBounds(
    [...layout.value.nodes, ...layout.value.edges.filter((edge) => edge.bounds).map((edge) => edge.bounds)].map((node) => ({
        x: node.x,
        y: node.y,
        width: node.width,
        height: node.height,
    })),
    80,
));

const minimapViewport = computed(() => {
    const wrap = canvasWrapRef.value;

    if (!wrap || zoomLevel.value <= 0) {
        return null;
    }

    return graphViewportFromPanZoom({
        panX: panX.value,
        panY: panY.value,
        zoom: zoomLevel.value,
        viewportWidth: wrap.clientWidth,
        viewportHeight: wrap.clientHeight,
    });
});

const reconnectHint = computed(() => {
    if (reconnectDrag.value) {
        return props.copy.reservation_flow_editor_reconnect_drop;
    }

    if (reconnectArmedEnd.value && selectedTransition.value) {
        return props.copy.reservation_flow_editor_reconnect_pick_port;
    }

    return '';
});

const selectedState = computed(() => (
    selected.value?.kind === 'state'
        ? props.states.find((row) => row.id === selected.value.id) ?? null
        : null
));

const selectedTransition = computed(() => (
    selected.value?.kind === 'transition'
        ? props.transitions.find((row) => row.id === selected.value.id) ?? null
        : null
));

const incomingTransitions = computed(() => {
    if (!selectedState.value) {
        return [];
    }

    return props.transitions.filter((row) => row.to === selectedState.value.id);
});

const outgoingTransitions = computed(() => {
    if (!selectedState.value) {
        return [];
    }

    return props.transitions.filter((row) => (
        row.from === selectedState.value.id && !row.is_start_edge
    ));
});

const hasInspectorSelection = computed(() => selected.value != null);

function stateName(stateId) {
    return props.states.find((row) => row.id === stateId)?.name ?? '';
}

function stateInspectorForm(state) {
    return {
        name: state?.name ?? '',
        content_status: state?.content_status ?? (props.availableStatuses.some((choice) => choice.value === state?.status) ? state.status : 'editable'),
        is_terminal: Boolean(state?.is_terminal),
        allows_approval: Boolean(state?.allows_approval),
    };
}

function resetInspectorForms() {
    editStateForm.value = { name: '', content_status: 'editable', is_terminal: false, allows_approval: false };
    editTransitionForm.value = { label: '' };
}

function cancelReconnect() {
    reconnectDrag.value = null;
    reconnectCursor.value = null;
    reconnectArmedEnd.value = null;
}

function cancelCreation() {
    creationDrag.value = null;
    creationCursor.value = null;
}

function clearSelection() {
    selected.value = null;
    inspectorMode.value = 'idle';
    resetInspectorForms();
    cancelReconnect();
    cancelCreation();
}

function selectNode(node) {
    if (node.kind === 'start') {
        selected.value = { kind: 'start' };
        inspectorMode.value = 'start';
        resetInspectorForms();
        return;
    }

    selected.value = { kind: 'state', id: node.stateId };
    inspectorMode.value = 'state';
    const state = props.states.find((row) => row.id === node.stateId);
    editStateForm.value = stateInspectorForm(state);
}

function selectTransition(edge) {
    if (!reconnectDrag.value) {
        reconnectArmedEnd.value = null;
    }

    selected.value = { kind: 'transition', id: edge.id };
    inspectorMode.value = 'transition';
    editTransitionForm.value = {
        label: edge.label ?? '',
    };
}

function selectStateById(stateId) {
    const node = layout.value.nodes.find((row) => row.kind === 'state' && row.stateId === stateId);

    if (node) {
        selectNode(node);
    }
}

function isNodeSelected(node) {
    if (node.kind === 'start') {
        return selected.value?.kind === 'start';
    }

    return selected.value?.kind === 'state' && selected.value.id === node.stateId;
}

function isTransitionSelected(edge) {
    return selected.value?.kind === 'transition' && selected.value.id === edge.id;
}

function shouldShowEdgeLabel(edge) {
    if (props.showAllTransitionLabels || hoveredEdgeId.value === edge.id) {
        return true;
    }

    if (selected.value?.kind === 'transition') {
        return selected.value.id === edge.id;
    }

    if (selected.value?.kind === 'start') {
        return Boolean(edge.is_start_edge);
    }

    if (selected.value?.kind === 'state') {
        return edge.from === selected.value.id && !edge.is_start_edge;
    }

    return false;
}

function isReconnectingEdge(edge) {
    return reconnectDrag.value?.transitionId === edge.id;
}

function inferPortSideFromPoint(node, point) {
    if (!node) {
        return 'right';
    }

    const dx = point.x - node.centerX;
    const dy = point.y - node.centerY;

    if (Math.abs(dx) >= Math.abs(dy)) {
        return dx >= 0 ? 'right' : 'left';
    }

    return dy >= 0 ? 'bottom' : 'top';
}

function findNearestNodeToPoint(point) {
    if (!point) {
        return null;
    }

    let best = null;
    let bestDistance = RECONNECT_NODE_PROXIMITY;

    layout.value.nodes.forEach((node) => {
        const dx = Math.max(node.x - point.x, 0, point.x - (node.x + node.width));
        const dy = Math.max(node.y - point.y, 0, point.y - (node.y + node.height));
        const distance = Math.hypot(dx, dy);

        if (distance > bestDistance) {
            return;
        }

        bestDistance = distance;
        best = node;
    });

    return best;
}

function reconnectPreviewPath() {
    if (!reconnectDrag.value || !reconnectCursor.value) {
        return '';
    }

    const edge = layout.value.edges.find((row) => row.id === reconnectDrag.value.transitionId);
    if (!edge) {
        return '';
    }

    const cursor = reconnectCursor.value;
    const cursorPort = { x: cursor.x, y: cursor.y };

    if (reconnectDrag.value.end === 'target') {
        const nearestNode = findNearestNodeToPoint(cursor);
        const targetNode = nearestNode?.kind === 'state'
            ? nearestNode
            : layout.value.nodes.find((row) => row.stateId === edge.to);
        const toSide = targetNode ? inferPortSideFromPoint(targetNode, cursor) : edge.toSide;

        return buildOrthogonalEdgePath(edge.start, cursorPort, edge.fromSide, toSide).path;
    }

    const sourceNode = edge.is_start_edge
        ? layout.value.nodes.find((row) => row.kind === 'start')
        : layout.value.nodes.find((row) => row.stateId === edge.from);
    const nearestNode = findNearestNodeToPoint(cursor);
    const fromNode = nearestNode && (
        nearestNode.kind === 'start'
        || nearestNode.stateId === edge.from
        || (edge.is_start_edge && nearestNode.kind === 'start')
    ) ? nearestNode : sourceNode;
    const fromSide = fromNode ? inferPortSideFromPoint(fromNode, cursor) : edge.fromSide;

    return buildOrthogonalEdgePath(cursorPort, edge.end, fromSide, edge.toSide).path;
}

function reconnectThreadPath() {
    if (!reconnectDrag.value || !reconnectCursor.value) {
        return '';
    }

    const edge = layout.value.edges.find((row) => row.id === reconnectDrag.value.transitionId);
    if (!edge) {
        return '';
    }

    const anchor = reconnectDrag.value.end === 'target' ? edge.end : edge.start;
    const cursor = reconnectCursor.value;

    return `M ${anchor.x} ${anchor.y} L ${cursor.x} ${cursor.y}`;
}

function creationPreviewPath() {
    if (!creationDrag.value || !creationCursor.value) return '';
    const start = creationDrag.value.port;
    const end = creationCursor.value;
    if (Math.hypot(end.x - start.x, end.y - start.y) < 4) return '';
    const nearby = findPortTarget(layout.value.nodes, end, 24);
    if (nearby?.kind === 'state' && nearby.stateId === creationDrag.value.from) {
        const node = layout.value.nodes.find((row) => row.kind === 'state'
            && row.stateId === creationDrag.value.from);
        if (node) {
            return buildSelfLoopEdgePath(node, node.ports[creationDrag.value.fromSide],
                node.ports[creationDrag.value.fromSide], creationDrag.value.fromSide,
                creationDrag.value.fromSide).path;
        }
    }
    const toSide = nearby?.kind === 'state' ? nearby.side
        : inferPortSideFromPoint(findNearestNodeToPoint(end), end);

    return buildOrthogonalEdgePath(start, end, creationDrag.value.fromSide, toSide).path;
}

function nodeTheme(node) {
    return reservationFlowNodeTheme(node);
}

function nodeCardStyle(node) {
    const theme = nodeTheme(node);

    return {
        '--rf-node-border': theme.border,
        '--rf-node-badge-bg': theme.badgeBg,
        '--rf-node-badge-text': theme.badgeText,
        '--rf-node-surface': theme.iconBg,
    };
}

function canShowStateMenu(node) {
    if (!props.editable) {
        return false;
    }

    return node.kind === 'state' || node.kind === 'start';
}

function openStateMenu(event, node) {
    event.preventDefault();
    event.stopPropagation();

    if (!canShowStateMenu(node)) {
        return;
    }

    selectNode(node);
    canvasMenu.value = null;
    stateMenu.value = {
        open: true,
        anchor: event.type === 'contextmenu'
            ? { getBoundingClientRect: () => ({ top: event.clientY, bottom: event.clientY, left: event.clientX, right: event.clientX }), contains: () => false }
            : event.currentTarget,
        node,
    };
}

function onTransitionContextMenu(event, edge) {
    if (!props.editable) return;
    selectTransition(edge);
    closeStateMenu();
    canvasMenu.value = { kind: 'transition', x: Math.min(event.clientX, window.innerWidth - 215), y: Math.min(event.clientY, window.innerHeight - 90), isStart: Boolean(edge.is_start_edge) };
}

function onCanvasContextMenu(event) {
    if (!props.editable) return;
    closeStateMenu();
    canvasMenu.value = { kind: 'canvas', x: Math.min(event.clientX, window.innerWidth - 215), y: Math.min(event.clientY, window.innerHeight - 150) };
}

function onCanvasMenuAction(action) {
    canvasMenu.value = null;
    if (action === 'add-state') emit('add-state');
    if (action === 'add-transition') emit('add-transition');
    if (action === 'fit') fitView();
    if (action === 'delete-transition') submitDeleteTransition();
}

function closeStateMenu() {
    stateMenu.value = { open: false, anchor: null, node: null };
}

function stateMenuMode() {
    return stateMenu.value.node?.kind === 'start' ? 'start' : 'state';
}

function connectedTransitionCount(stateId) {
    return connectedTransitions(stateId).length;
}

function connectedTransitions(stateId) {
    return props.transitions.filter((row) => (
        String(row.from) === String(stateId) || String(row.to) === String(stateId)
    ));
}

function connectedTaskCount(transitions) {
    return transitions.reduce((total, transition) => total + (transition.tasks?.length ?? 0), 0);
}

function transitionRouteLabel(transition) {
    const from = transition.is_start_edge || transition.from == null
        ? props.copy.reservation_flow_from_any
        : stateName(transition.from);
    const to = stateName(transition.to);

    return `${from} -> ${to}`;
}

function replaceDeleteStateTokens(template, state, transitionCount, taskCount) {
    return String(template ?? '')
        .replaceAll(':state', state?.name ?? '')
        .replaceAll(':count', String(transitionCount))
        .replaceAll(':transitionCount', String(transitionCount))
        .replaceAll(':taskCount', String(taskCount));
}

function deleteStateConfirmOptions(state) {
    const transitions = connectedTransitions(state.id);
    const transitionCount = transitions.length;
    const taskCount = connectedTaskCount(transitions);
    const messageTemplate = transitionCount > 0 || taskCount > 0
        ? props.copy.reservation_flow_delete_state_impact
            ?? props.copy.reservation_flow_delete_state_with_transitions
            ?? props.copy.reservation_flow_delete_state_confirm
        : props.copy.reservation_flow_delete_state_confirm;
    const transitionItems = transitions
        .slice(0, 6)
        .map((transition) => ({
            title: transition.label ?? transition.name ?? transition.slug ?? props.copy.reservation_flow_delete_transition,
            meta: transitionRouteLabel(transition),
        }));
    const extraCount = transitions.length - transitionItems.length;
    const taskTemplate = props.copy.reservation_flow_delete_state_tasks_label ?? 'Tasks attached to these transitions: :taskCount';

    return {
        title: props.copy.reservation_flow_delete_state,
        message: replaceDeleteStateTokens(messageTemplate, state, transitionCount, taskCount),
        impactStats: transitionCount > 0 || taskCount > 0
            ? [
                { value: transitionCount, label: props.copy.reservation_flow_delete_state_transition_count ?? 'Transitions' },
                { value: taskCount, label: props.copy.reservation_flow_delete_state_task_count ?? 'Tasks' },
            ]
            : [],
        impactTitle: props.copy.reservation_flow_delete_state_transitions_label ?? 'Transitions to remove',
        impactItems: [
            ...transitionItems,
            ...(extraCount > 0 ? [{
                title: `+${extraCount}`,
                meta: props.copy.reservation_flow_delete_state_more_transitions ?? 'More connected transitions',
            }] : []),
        ],
        impactFooter: taskCount > 0
            ? taskTemplate.replaceAll(':taskCount', String(taskCount))
            : '',
        confirmLabel: props.copy.reservation_flow_delete_state,
        variant: 'danger',
        focus: 'cancel',
    };
}

function onStateMenuEdit() {
    const node = stateMenu.value.node;

    if (!node || node.kind !== 'state') {
        return;
    }

    emit('state-menu-edit', { stateId: node.stateId });
    stateInspectorTab.value = 'details';
}

function onStateMenuAddTransition() {
    const node = stateMenu.value.node;

    if (!node) {
        return;
    }

    emit('state-menu-add-transition', {
        stateId: node.stateId ?? null,
        kind: node.kind,
    });
}

function onStateMenuDuplicate() {
    const node = stateMenu.value.node;

    if (!node || node.kind !== 'state') {
        return;
    }

    emit('duplicate-state', { stateId: node.stateId });
}

async function onStateMenuDelete() {
    const node = stateMenu.value.node;

    if (!node || node.kind !== 'state') {
        return;
    }

    closeStateMenu();

    const state = props.states.find((row) => String(row.id) === String(node.stateId));
    const ok = await requestConfirm(deleteStateConfirmOptions(state ?? { id: node.stateId, name: node.name ?? '' }));

    if (!ok) {
        return;
    }

    emit('delete-state', { stateId: node.stateId });
    clearSelection();
}

function focusStateDetails() {
    stateInspectorTab.value = 'details';
}

function isEdgeRelatedToSelection(edge) {
    if (selected.value?.kind === 'transition') {
        return false;
    }

    if (selected.value?.kind === 'state') {
        return edge.from === selected.value.id && !edge.is_start_edge;
    }

    if (selected.value?.kind === 'start') {
        return Boolean(edge.is_start_edge);
    }

    return false;
}

function isNodeDragging(node) {
    if (!dragState.value) {
        return false;
    }

    if (node.kind === 'start') {
        return dragState.value.kind === 'start';
    }

    return dragState.value.kind === 'state' && dragState.value.stateId === node.stateId;
}

function findLayoutNode(node) {
    if (node.kind === 'start') {
        return layout.value.nodes.find((row) => row.kind === 'start') ?? null;
    }

    return layout.value.nodes.find((row) => row.stateId === node.stateId) ?? null;
}

function revertStatePosition(stateId) {
    if (!stateId || !dragPositions.value[stateId]) {
        return;
    }

    const next = { ...dragPositions.value };
    delete next[stateId];
    dragPositions.value = next;
}

function fitView() {
    const wrap = canvasWrapRef.value;

    if (!wrap) {
        return;
    }

    const fitted = fitViewportToGraph({
        graphBounds: graphBounds.value,
        viewportWidth: wrap.clientWidth,
        viewportHeight: wrap.clientHeight,
        padding: 72,
        zoomMin: ZOOM_MIN,
        zoomMax: ZOOM_MAX,
    });

    panX.value = fitted.panX;
    panY.value = fitted.panY;
    zoomLevel.value = fitted.zoom;
}

function getViewportCenter() {
    const wrap = canvasWrapRef.value;

    if (!wrap) {
        return null;
    }

    const centerX = (wrap.clientWidth / 2 - panX.value) / zoomLevel.value;
    const centerY = (wrap.clientHeight / 2 - panY.value) / zoomLevel.value;

    return { centerX, centerY };
}

function ensureNodeVisible(stateId) {
    const node = layout.value.nodes.find((row) => row.kind === 'state' && row.stateId === stateId);
    const wrap = canvasWrapRef.value;

    if (!node || !wrap) {
        return;
    }

    const viewport = graphViewportFromPanZoom({
        panX: panX.value,
        panY: panY.value,
        zoom: zoomLevel.value,
        viewportWidth: wrap.clientWidth,
        viewportHeight: wrap.clientHeight,
    });
    const pad = 48;
    const outside = (
        node.x + node.width + pad < viewport.x
        || node.x - pad > viewport.x + viewport.w
        || node.y + node.height + pad < viewport.y
        || node.y - pad > viewport.y + viewport.h
    );

    if (!outside) {
        return;
    }

    const next = panToGraphPoint({
        panX: panX.value,
        panY: panY.value,
        zoom: zoomLevel.value,
        viewportWidth: wrap.clientWidth,
        viewportHeight: wrap.clientHeight,
        graphX: node.x + (node.width / 2),
        graphY: node.y + (node.height / 2),
    });

    panX.value = next.panX;
    panY.value = next.panY;
}

function onMinimapNavigate(point) {
    const wrap = canvasWrapRef.value;

    if (!wrap || !point) {
        return;
    }

    const next = panToGraphPoint({
        panX: panX.value,
        panY: panY.value,
        zoom: zoomLevel.value,
        viewportWidth: wrap.clientWidth,
        viewportHeight: wrap.clientHeight,
        graphX: point.x,
        graphY: point.y,
    });

    panX.value = next.panX;
    panY.value = next.panY;
}

function onMinimapPan({ dx, dy }) {
    panX.value += dx * zoomLevel.value;
    panY.value += dy * zoomLevel.value;
}

function selectTransitionById(transitionId) {
    const edge = layout.value.edges.find((row) => row.id === transitionId);

    if (edge) {
        selectTransition(edge);
    }
}

function onSelectTool(tool) {
    canvasTool.value = tool;
}

function applyAnchorCache(transitionId, anchors = {}) {
    if (transitionId == null) {
        return;
    }

    anchorCache[transitionId] = {
        ...(anchorCache[transitionId] ?? {}),
        ...anchors,
    };
}

function syncAnchorsFromTransitions(rows = []) {
    rows.forEach((row) => {
        if (row.anchors?.from_side && row.anchors?.to_side) {
            applyAnchorCache(row.id, {
                from_side: row.anchors.from_side,
                to_side: row.anchors.to_side,
            });
        }
    });
}

function showPorts(node) {
    if (!props.editable) {
        return false;
    }

    if (reconnectDrag.value) {
        return isNodeNearReconnectCursor(node);
    }

    if (reconnectArmedEnd.value) {
        return shiftHeld.value && isNodeNearReconnectCursor(node);
    }

    if (creationDrag.value) {
        return node.kind === 'state' && (node.stateId === creationDrag.value.from
            || isNodeNearCreationCursor(node));
    }

    return shiftHeld.value && node.kind === 'state' && !node.is_terminal
        && node.stateId === hoveredStateId.value;
}

function isNodeNearReconnectCursor(node) {
    const point = reconnectCursor.value;
    if (!point) {
        return false;
    }

    const pad = RECONNECT_NODE_PROXIMITY;

    return (
        point.x >= node.x - pad
        && point.x <= node.x + node.width + pad
        && point.y >= node.y - pad
        && point.y <= node.y + node.height + pad
    );
}

function isNodeNearCreationCursor(node) {
    const point = creationCursor.value;
    if (!point) return false;
    const pad = RECONNECT_NODE_PROXIMITY;

    return point.x >= node.x - pad && point.x <= node.x + node.width + pad
        && point.y >= node.y - pad && point.y <= node.bottomY + pad;
}

function isNodePortInteractive(node) {
    if (reconnectDrag.value) return isNodeNearReconnectCursor(node);
    if (reconnectArmedEnd.value) return shiftHeld.value && isNodeNearReconnectCursor(node);
    if (creationDrag.value) return node.kind === 'state' && (node.stateId === creationDrag.value.from
        || isNodeNearCreationCursor(node));

    return shiftHeld.value && node.kind === 'state' && !node.is_terminal
        && node.stateId === hoveredStateId.value;
}

function shouldShowEdgeEndpointDots(edge) {
    return isTransitionSelected(edge) || isReconnectingEdge(edge);
}

function isPortDropTarget(node, side) {
    if (!reconnectDrag.value && !creationDrag.value) {
        return false;
    }

    const point = reconnectDrag.value ? reconnectCursor.value : creationCursor.value;
    if (!point) {
        return false;
    }

    const port = node.ports?.[side];
    if (!port) {
        return false;
    }

    return Math.hypot(port.x - point.x, port.y - point.y) <= 18;
}

function zoomAt(nextZoom, clientX, clientY) {
    const wrap = canvasWrapRef.value;

    if (!wrap) {
        return;
    }

    const rect = wrap.getBoundingClientRect();
    const cx = clientX ?? rect.left + (rect.width / 2);
    const cy = clientY ?? rect.top + (rect.height / 2);
    const graphX = (cx - rect.left - panX.value) / zoomLevel.value;
    const graphY = (cy - rect.top - panY.value) / zoomLevel.value;

    zoomLevel.value = nextZoom;
    panX.value = cx - rect.left - (graphX * zoomLevel.value);
    panY.value = cy - rect.top - (graphY * zoomLevel.value);
}

function zoomIn() {
    zoomAt(Math.min(ZOOM_MAX, Number((zoomLevel.value + ZOOM_STEP).toFixed(2))));
}

function zoomOut() {
    zoomAt(Math.max(ZOOM_MIN, Number((zoomLevel.value - ZOOM_STEP).toFixed(2))));
}

function onCanvasWheel(event) {
    if (dragState.value || panDrag.value || creationDrag.value || reconnectDrag.value) return;
    // Normalize line/page wheel events while retaining smooth trackpad zoom.
    const unit = event.deltaMode === 1 ? 16 : event.deltaMode === 2
        ? canvasWrapRef.value?.clientHeight ?? 800 : 1;
    const delta = event.deltaY * unit;
    if (!Number.isFinite(delta) || delta === 0) return;

    const nextZoom = Math.max(ZOOM_MIN, Math.min(ZOOM_MAX,
        zoomLevel.value * Math.exp(-delta * 0.0015)));
    if (nextZoom !== zoomLevel.value) zoomAt(nextZoom, event.clientX, event.clientY);
}

function clientToCanvas(clientX, clientY) {
    const wrap = canvasWrapRef.value;

    if (!wrap) {
        return null;
    }

    const rect = wrap.getBoundingClientRect();

    return {
        x: (clientX - rect.left - panX.value) / zoomLevel.value,
        y: (clientY - rect.top - panY.value) / zoomLevel.value,
    };
}

function transitionAnchorSide(transition, end) {
    const cached = anchorCache[transition.id] ?? {};

    if (end === 'target') {
        return transition.anchors?.to_side ?? cached.to_side ?? null;
    }

    return transition.anchors?.from_side ?? cached.from_side ?? null;
}

function onEndpointPointerDown(event, edge, end) {
    if (!props.editable) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    if (event.currentTarget?.setPointerCapture) {
        event.currentTarget.setPointerCapture(event.pointerId);
    }

    reconnectArmedEnd.value = {
        transitionId: edge.id,
        end,
    };
    reconnectDrag.value = {
        transitionId: edge.id,
        end,
        isStartEdge: Boolean(edge.is_start_edge),
    };
    selectTransition(edge);
    updateReconnectCursor(event.clientX, event.clientY);
}

function onPortPointerDown(event, node, side) {
    if (!props.editable || event.button !== 0 || !isNodePortInteractive(node)) {
        return;
    }

    event.preventDefault();
    event.stopPropagation();

    const target = {
        kind: node.kind,
        stateId: node.stateId ?? null,
        side,
    };

    if (reconnectDrag.value) {
        completeReconnect(target);
        return;
    }

    if (!reconnectArmedEnd.value && !creationDrag.value && node.kind === 'state' && !node.is_terminal) {
        creationDrag.value = {from: node.stateId, fromSide: side, port: node.ports[side],
            pointerId: event.pointerId, moved: false};
        creationCursor.value = {x: node.ports[side].x, y: node.ports[side].y};
        return;
    }

    const armed = reconnectArmedEnd.value;
    if (!armed || selectedTransition.value?.id !== armed.transitionId) {
        return;
    }

    reconnectDrag.value = {
        transitionId: armed.transitionId,
        end: armed.end,
        isStartEdge: Boolean(selectedTransition.value?.is_start_edge),
    };
    completeReconnect(target);
}

function onPortPointerUp(event, node, side) {
    if (creationDrag.value && event.pointerId === creationDrag.value.pointerId) {
        event.stopPropagation();
        completeCreation({kind: node.kind, stateId: node.stateId ?? null, side});
        return;
    }

    if (!reconnectDrag.value || !isNodePortInteractive(node)) {
        return;
    }

    event.stopPropagation();
    completeReconnect({
        kind: node.kind,
        stateId: node.stateId ?? null,
        side,
    });
}

function completeCreation(target) {
    const source = creationDrag.value;
    cancelCreation();
    if (!source || target?.kind !== 'state' || !target.stateId) return;
    if (target.stateId === source.from && target.side === source.fromSide && !source.moved) return;

    const existing = props.transitions.find((row) => !row.is_start_edge
        && String(row.from) === String(source.from) && String(row.to) === String(target.stateId));
    if (existing) {
        const rendered = layout.value.edges.find((row) => String(row.id) === String(existing.id));
        if (rendered) selectTransition(rendered);
        return;
    }

    emit('add-transition', {from: source.from, to: target.stateId,
        anchors: {from_side: source.fromSide,
            to_side: target.stateId === source.from ? source.fromSide : target.side}});
}

function onCanvasBackdropPointerDown(event) {
    event.stopPropagation();
    cancelReconnect();

    panDrag.value = {
        startX: event.clientX,
        startY: event.clientY,
        originPanX: panX.value,
        originPanY: panY.value,
        moved: false,
    };

    if (event.currentTarget?.setPointerCapture) {
        event.currentTarget.setPointerCapture(event.pointerId);
    }
}

function updateReconnectCursor(clientX, clientY) {
    reconnectCursor.value = clientToCanvas(clientX, clientY);
}

function completeReconnect(target) {
    if (!reconnectDrag.value || !target) {
        cancelReconnect();
        return;
    }

    const transition = props.transitions.find((row) => row.id === reconnectDrag.value.transitionId);
    if (!transition) {
        cancelReconnect();
        return;
    }

    const payload = {
        transitionId: transition.id,
        anchors: {},
    };

    if (reconnectDrag.value.end === 'target') {
        if (target.kind === 'start' || target.stateId == null) {
            cancelReconnect();
            return;
        }
        if (transition.is_start_edge && props.states.find((state) => state.id === target.stateId)?.is_terminal) {
            cancelReconnect();
            return;
        }

        const currentToSide = transitionAnchorSide(transition, 'target');
        const isLoop = !transition.is_start_edge && transition.from === target.stateId;
        const loopSide = transition.from === transition.to ? target.side
            : (transitionAnchorSide(transition, 'source')
                ?? layout.value.edges.find((edge) => edge.id === transition.id)?.fromSide
                ?? target.side);
        const sameTarget = transition.to === target.stateId
            && currentToSide === (isLoop ? loopSide : target.side);

        if (sameTarget) {
            cancelReconnect();
            return;
        }

        if (transition.to !== target.stateId) {
            payload.to = target.stateId;
        }

        if (isLoop) {
            payload.anchors.from_side = loopSide;
            payload.anchors.to_side = loopSide;
        } else {
            payload.anchors.to_side = target.side;
        }
    } else if (transition.is_start_edge) {
        if (target.kind !== 'start') {
            cancelReconnect();
            return;
        }

        const currentFromSide = transitionAnchorSide(transition, 'source');
        if (currentFromSide === target.side) {
            cancelReconnect();
            return;
        }

        if (transition.to === target.stateId) {
            payload.anchors.from_side = target.side;
            payload.anchors.to_side = target.side;
        } else {
            payload.anchors.from_side = target.side;
        }
    } else if (target.kind === 'start' || target.stateId == null) {
        cancelReconnect();
        return;
    } else {
        const currentFromSide = transitionAnchorSide(transition, 'source');
        const sameSource = transition.from === target.stateId && currentFromSide === target.side;

        if (sameSource) {
            cancelReconnect();
            return;
        }

        if (transition.from !== target.stateId) {
            payload.from = target.stateId;
        }

        payload.anchors.from_side = target.side;
    }

    applyAnchorCache(transition.id, payload.anchors);
    emit('reconnect-transition', payload);
    cancelReconnect();
}

function onNodePointerDown(event, node) {
    if (!props.editable || reconnectDrag.value || event.button !== 0) {
        return;
    }

    if (node.kind !== 'state' && node.kind !== 'start') {
        return;
    }

    const current = findLayoutNode(node);
    const pointer = clientToCanvas(event.clientX, event.clientY);
    if (!current || !pointer) {
        return;
    }

    dragState.value = {
        kind: node.kind,
        stateId: node.stateId ?? null,
        pointerId: event.pointerId,
        startX: event.clientX,
        startY: event.clientY,
        grabOffsetX: pointer.x - current.x,
        grabOffsetY: pointer.y - current.y,
        pointerX: event.clientX,
        pointerY: event.clientY,
        moved: false,
    };

    event.currentTarget?.setPointerCapture?.(event.pointerId);

    selectNode(node);
}

function onNodeKeydown(event, node) {
    if (!props.editable || event.target !== event.currentTarget || !['state', 'start'].includes(node.kind)) {
        return;
    }

    const directions = {
        ArrowLeft: [-1, 0],
        ArrowRight: [1, 0],
        ArrowUp: [0, -1],
        ArrowDown: [0, 1],
    };
    const direction = directions[event.key];
    if (!direction) {
        return;
    }

    const current = findLayoutNode(node);
    if (!current) {
        return;
    }

    event.preventDefault();
    const distance = event.shiftKey ? 40 : 10;
    const position = {
        x: current.x + direction[0] * distance,
        y: current.y + direction[1] * distance,
    };
    if (node.kind === 'start') {
        emit('move-start', { position });
    } else {
        emit('move-state', { stateId: node.stateId, position });
    }
}

function onWindowPointerMove(event) {
    shiftHeld.value = event.shiftKey;
    if (creationDrag.value) {
        if (event.pointerId === creationDrag.value.pointerId) {
            creationCursor.value = clientToCanvas(event.clientX, event.clientY);
            if (creationCursor.value && Math.hypot(creationCursor.value.x - creationDrag.value.port.x,
                creationCursor.value.y - creationDrag.value.port.y) > 24) {
                creationDrag.value.moved = true;
            }
        }
        return;
    }

    if (!reconnectDrag.value && !dragState.value && !panDrag.value && props.editable) {
        const rect = canvasWrapRef.value?.getBoundingClientRect();
        const inside = rect && event.clientX >= rect.left && event.clientX <= rect.right
            && event.clientY >= rect.top && event.clientY <= rect.bottom;
        const point = inside ? clientToCanvas(event.clientX, event.clientY) : null;
        const threshold = 28 / zoomLevel.value;
        const nearby = point ? layout.value.nodes.find((node) => {
            if (node.kind !== 'state' || node.is_terminal) return false;
            if (point.x < node.x - threshold || point.x > node.rightX + threshold
                || point.y < node.y - threshold || point.y > node.bottomY + threshold) return false;
            return Math.min(Math.abs(point.x - node.x), Math.abs(point.x - node.rightX),
                Math.abs(point.y - node.y), Math.abs(point.y - node.bottomY)) <= threshold;
        }) : null;
        hoveredStateId.value = nearby?.stateId ?? null;
    }

    if (reconnectDrag.value) {
        updateReconnectCursor(event.clientX, event.clientY);
    }

    if (panDrag.value) {
        const dx = event.clientX - panDrag.value.startX;
        const dy = event.clientY - panDrag.value.startY;

        if (!panDrag.value.moved && Math.hypot(dx, dy) < 4) {
            return;
        }

        panDrag.value.moved = true;
        panX.value = panDrag.value.originPanX + dx;
        panY.value = panDrag.value.originPanY + dy;
    }

    if (!dragState.value) {
        return;
    }

    if (event.pointerId !== dragState.value.pointerId) return;

    const dx = event.clientX - dragState.value.startX;
    const dy = event.clientY - dragState.value.startY;

    if (!dragState.value.moved && Math.hypot(dx, dy) < 4) {
        return;
    }

    dragState.value.moved = true;
    dragState.value.pointerX = event.clientX;
    dragState.value.pointerY = event.clientY;
    updateDraggedPosition();
    if (edgePanFrame === null) edgePanFrame = requestAnimationFrame(panWhileDragging);
}

function updateDraggedPosition() {
    if (!dragState.value) return;
    const rect = canvasWrapRef.value?.getBoundingClientRect();
    if (!rect) return;
    const nextPosition = nodePositionAtPointer(
        {x: dragState.value.pointerX, y: dragState.value.pointerY},
        {left: rect.left, top: rect.top, panX: panX.value, panY: panY.value, zoom: zoomLevel.value},
        {x: dragState.value.grabOffsetX, y: dragState.value.grabOffsetY},
    );

    if (dragState.value.kind === 'start') {
        dragStartPosition.value = nextPosition;
        return;
    }

    dragPositions.value = {
        ...dragPositions.value,
        [dragState.value.stateId]: nextPosition,
    };
}

function panWhileDragging() {
    edgePanFrame = null;
    const drag = dragState.value;
    const wrap = canvasWrapRef.value;
    if (!drag?.moved || !wrap) return;
    const rect = wrap.getBoundingClientRect();
    const step = edgePanStep({x: drag.pointerX, y: drag.pointerY}, rect);
    if (step.x || step.y) {
        panX.value += step.x;
        panY.value += step.y;
        updateDraggedPosition();
        edgePanFrame = requestAnimationFrame(panWhileDragging);
    }
}

function cancelNodeDrag() {
    if (edgePanFrame !== null) cancelAnimationFrame(edgePanFrame);
    edgePanFrame = null;
    if (dragState.value?.kind === 'state') {
        const positions = {...dragPositions.value};
        delete positions[dragState.value.stateId];
        dragPositions.value = positions;
    }
    dragStartPosition.value = null;
    dragState.value = null;
}

function onDocumentPointerDown(event) {
    if (!event.target.closest?.('.rf-editor-context')) canvasMenu.value = null;
}
function onDocumentKeydown(event) {
    if (event.key === 'Shift') {
        shiftHeld.value = true;
        return;
    }
    if (event.key === 'Escape') {
        canvasMenu.value = null;
        cancelCreation();
        return;
    }
    if (!props.editable || document.querySelector('[role="dialog"][aria-modal="true"]')
        || !['Delete', 'Backspace'].includes(event.key)
        || event.repeat || event.target?.closest?.('input, textarea, select, [contenteditable="true"], [role="dialog"]')) return;
    if (selected.value?.kind === 'state' && selectedState.value && !selectedState.value.is_start) {
        event.preventDefault();
        submitDeleteState();
    } else if (selected.value?.kind === 'transition' && selectedTransition.value && !selectedTransition.value.is_start_edge) {
        event.preventDefault();
        submitDeleteTransition();
    }
}

function onDocumentKeyup(event) {
    if (event.key === 'Shift') shiftHeld.value = false;
}

function onWindowBlur() {
    shiftHeld.value = false;
    hoveredStateId.value = null;
    hoveredEdgeId.value = null;
    cancelCreation();
    cancelNodeDrag();
    panDrag.value = null;
}

function onWindowPointerUp(event) {
    if (dragState.value && event.pointerId !== dragState.value.pointerId) return;
    if (edgePanFrame !== null) cancelAnimationFrame(edgePanFrame);
    edgePanFrame = null;
    if (creationDrag.value && event.pointerId === creationDrag.value.pointerId) {
        const point = clientToCanvas(event.clientX, event.clientY);
        const target = point ? findPortTarget(layout.value.nodes, point, 24) : null;
        completeCreation(target);
        return;
    }
    if (reconnectDrag.value) {
        const point = clientToCanvas(event.clientX, event.clientY);
        const target = point ? findPortTarget(layout.value.nodes, point, 24) : null;

        if (target) {
            completeReconnect(target);
        } else if (reconnectDrag.value) {
            reconnectArmedEnd.value = {
                transitionId: reconnectDrag.value.transitionId,
                end: reconnectDrag.value.end,
            };
            reconnectDrag.value = null;
            reconnectCursor.value = null;
        }
    }

    if (panDrag.value && !panDrag.value.moved) {
        selected.value = null;
        inspectorMode.value = 'idle';
        resetInspectorForms();
    }

    panDrag.value = null;

    if (!dragState.value) {
        return;
    }

    const moved = dragState.value.moved;
    const kind = dragState.value.kind;
    const stateId = dragState.value.stateId;
    const position = kind === 'start'
        ? dragStartPosition.value
        : dragPositions.value[stateId];
    dragState.value = null;

    if (!moved || !position) {
        dragStartPosition.value = null;
        return;
    }

    if (kind === 'start') {
        emit('move-start', { position });
        dragStartPosition.value = null;
        return;
    }

    emit('move-state', { stateId, position });
}

function onWindowPointerCancel(event) {
    if (creationDrag.value?.pointerId === event.pointerId) cancelCreation();
    if (dragState.value?.pointerId === event.pointerId) cancelNodeDrag();
}

function submitEditState() {
    if (!selectedState.value) {
        return;
    }

    emit('update-state', {
        stateId: selectedState.value.id,
        name: editStateForm.value.name.trim(),
        status: selectedState.value.status,
        content_status: editStateForm.value.content_status,
        is_terminal: editStateForm.value.is_terminal,
        allows_approval: editStateForm.value.allows_approval,
    });
    emit('save-graph');
}

async function submitDeleteState() {
    if (!selectedState.value) {
        return;
    }

    const ok = await requestConfirm(deleteStateConfirmOptions(selectedState.value));

    if (!ok) {
        return;
    }

    emit('delete-state', { stateId: selectedState.value.id });
    clearSelection();
}

function submitEditTransition() {
    if (!selectedTransition.value) {
        return;
    }

    emit('update-transition', {
        transitionId: selectedTransition.value.id,
        label: editTransitionForm.value.label.trim(),
        slug: selectedTransition.value.slug,
    });
    emit('save-graph');
}

async function submitDeleteTransition() {
    if (!selectedTransition.value) {
        return;
    }

    const ok = await requestConfirm({
        title: props.copy.reservation_flow_delete_transition,
        message: props.copy.reservation_flow_delete_transition_confirm,
        confirmLabel: props.copy.reservation_flow_delete_transition,
        variant: 'danger',
    });

    if (!ok) {
        return;
    }

    emit('delete-transition', { transitionId: selectedTransition.value.id });
    clearSelection();
}

watch(() => props.transitions, (rows) => {
    syncAnchorsFromTransitions(rows);
}, { immediate: true, deep: true });

watch(() => selectedState.value?.id, (id) => {
    const state = props.states.find((row) => row.id === id);
    if (!state) return;
    editStateForm.value = stateInspectorForm(state);
}, { flush: 'sync' });

watch(selectedTransition, (transition) => {
    if (!transition) return;

    editTransitionForm.value = {
        label: transition.label ?? transition.name ?? '',
    };
});

watch([() => props.states, () => props.transitions], () => {
    dragPositions.value = {};
    dragStartPosition.value = null;

    if (!selected.value) {
        return;
    }

    if (selected.value.kind === 'state' && !props.states.some((row) => String(row.id) === String(selected.value.id))) {
        clearSelection();
    }

    if (selected.value.kind === 'transition' && !props.transitions.some((row) => String(row.id) === String(selected.value.id))) {
        clearSelection();
    }
}, { deep: true });

let canvasResizeObserver = null;

if (typeof window !== 'undefined') {
    window.addEventListener('pointermove', onWindowPointerMove);
    window.addEventListener('pointerup', onWindowPointerUp);
    window.addEventListener('pointercancel', onWindowPointerCancel);
}

onMounted(() => {
    document.addEventListener('pointerdown', onDocumentPointerDown, true);
    document.addEventListener('keydown', onDocumentKeydown);
    document.addEventListener('keyup', onDocumentKeyup);
    window.addEventListener('blur', onWindowBlur);
    const wrap = canvasWrapRef.value;

    if (!wrap || typeof ResizeObserver === 'undefined') {
        requestAnimationFrame(() => {
            if (!didInitialFit.value && props.states.length) {
                fitView();
                didInitialFit.value = true;
            }
        });

        return;
    }

    canvasResizeObserver = new ResizeObserver(() => {
        if (didInitialFit.value || wrap.clientWidth <= 0 || wrap.clientHeight <= 0 || !props.states.length) {
            return;
        }

        requestAnimationFrame(() => {
            fitView();
            didInitialFit.value = true;
        });
    });
    canvasResizeObserver.observe(wrap);
});

onBeforeUnmount(() => {
    if (edgePanFrame !== null) cancelAnimationFrame(edgePanFrame);
    document.removeEventListener('pointerdown', onDocumentPointerDown, true);
    document.removeEventListener('keydown', onDocumentKeydown);
    document.removeEventListener('keyup', onDocumentKeyup);
    window.removeEventListener('blur', onWindowBlur);
    canvasResizeObserver?.disconnect();
    canvasResizeObserver = null;

    if (typeof window !== 'undefined') {
        window.removeEventListener('pointermove', onWindowPointerMove);
        window.removeEventListener('pointerup', onWindowPointerUp);
        window.removeEventListener('pointercancel', onWindowPointerCancel);
    }
});

defineExpose({
    clearSelection,
    revertStatePosition,
    fitView,
    selectStateById,
    selectTransitionById,
    getViewportCenter,
    ensureNodeVisible,
    focusStateDetails,
});
</script>

<template>
    <div class="rf-editor" :class="{ 'is-embedded': embedded, 'is-inspector-hidden': hideInspector }">
        <p v-if="reconnectHint" class="rf-editor__hint">
            {{ reconnectHint }}
        </p>

        <div class="rf-editor__workspace">
            <div
                ref="canvasWrapRef"
                class="rf-editor__canvas-wrap"
                @pointerdown.self="onCanvasBackdropPointerDown"
                @wheel.prevent="onCanvasWheel"
                @contextmenu.prevent.self="onCanvasContextMenu"
            >
                <ReservationFlowCanvasToolbar
                    :copy="copy"
                    :editable="editable"
                    :active-tool="canvasTool"
                    @select-tool="onSelectTool"
                    @add-state="emit('add-state')"
                    @add-transition="emit('add-transition')"
                    @fit-view="fitView"
                />

                <ReservationFlowZoomControls
                    :copy="copy"
                    :zoom-level="zoomLevel"
                    :zoom-min="ZOOM_MIN"
                    :zoom-max="ZOOM_MAX"
                    @zoom-in="zoomIn"
                    @zoom-out="zoomOut"
                    @fit-view="fitView"
                />

                <ReservationFlowMiniMap
                    v-if="!hideMiniMap"
                    :nodes="layout.nodes"
                    :graph-bounds="graphBounds"
                    :viewport="minimapViewport"
                    @navigate="onMinimapNavigate"
                    @pan="onMinimapPan"
                />

                <div class="rf-editor__canvas-stage" :style="stageStyle" @contextmenu.prevent.self="onCanvasContextMenu">
                    <div
                        ref="canvasRef"
                        class="rf-editor__canvas"
                        :class="{ 'is-readonly': !editable, 'is-reconnecting': reconnectDrag, 'is-panning': panDrag?.moved }"
                        @pointerdown.self="onCanvasBackdropPointerDown"
                        @contextmenu.prevent.self="onCanvasContextMenu"
                    >
                        <svg class="rf-editor__edges" :width="layout.width" :height="layout.height" aria-hidden="true">
                            <rect
                                class="rf-editor__canvas-backdrop"
                                :width="layout.width"
                                :height="layout.height"
                                fill="transparent"
                                @pointerdown.stop="onCanvasBackdropPointerDown"
                                @contextmenu.prevent.stop="onCanvasContextMenu"
                            />
                            <g v-for="edge in layout.edges" :key="`edge-${edge.id}`">
                                <path :d="edge.hitPath" class="rf-editor__edge-hit"
                                    @pointerenter="hoveredEdgeId = edge.id"
                                    @pointerleave="hoveredEdgeId = null"
                                    @click.stop="selectTransition(edge)"
                                    @contextmenu.prevent.stop="onTransitionContextMenu($event, edge)" />
                                <path
                                    :d="isReconnectingEdge(edge) ? reconnectPreviewPath() : edge.path"
                                    class="rf-editor__edge"
                                    :class="{
                                        'is-selected': isTransitionSelected(edge),
                                        'is-hovered': hoveredEdgeId === edge.id,
                                        'is-related': !isTransitionSelected(edge) && isEdgeRelatedToSelection(edge),
                                        'is-reconnecting-preview': isReconnectingEdge(edge),
                                        'is-faded': reconnectDrag && !isReconnectingEdge(edge),
                                        'is-hidden-during-reconnect': isReconnectingEdge(edge),
                                    }"
                                />
                                <polygon
                                    v-if="edge.arrowPoints && !isReconnectingEdge(edge)"
                                    :points="edge.arrowPoints"
                                    class="rf-editor__arrow-head"
                                    :class="{
                                        'is-selected': isTransitionSelected(edge),
                                        'is-related': !isTransitionSelected(edge) && isEdgeRelatedToSelection(edge),
                                        'is-faded': reconnectDrag,
                                    }"
                                />
                                <circle
                                    v-if="shouldShowEdgeEndpointDots(edge)"
                                    :cx="edge.start.x"
                                    :cy="edge.start.y"
                                    r="4.5"
                                    class="rf-editor__port-dot is-source is-endpoint"
                                    :class="{ 'is-faded': reconnectDrag && !isReconnectingEdge(edge) }"
                                />
                                <circle
                                    v-if="shouldShowEdgeEndpointDots(edge)"
                                    :cx="edge.end.x"
                                    :cy="edge.end.y"
                                    r="4.5"
                                    class="rf-editor__port-dot is-target is-endpoint"
                                    :class="{ 'is-faded': reconnectDrag && !isReconnectingEdge(edge) }"
                                />
                            </g>
                        </svg>

                        <button
                            v-for="edge in layout.edges"
                            :key="`handle-source-${edge.id}`"
                            v-show="editable && isTransitionSelected(edge) && !edge.is_start_edge"
                            type="button"
                            class="rf-editor__endpoint-handle-btn is-source"
                            :style="{ left: `${edge.start.x}px`, top: `${edge.start.y}px` }"
                            :aria-label="copy.reservation_flow_editor_reconnect_source"
                            @pointerdown.stop="onEndpointPointerDown($event, edge, 'source')"
                        />
                        <button
                            v-for="edge in layout.edges"
                            :key="`handle-target-${edge.id}`"
                            v-show="editable && isTransitionSelected(edge)"
                            type="button"
                            class="rf-editor__endpoint-handle-btn is-target"
                            :style="{ left: `${edge.end.x}px`, top: `${edge.end.y}px` }"
                            :aria-label="copy.reservation_flow_editor_reconnect_target"
                            @pointerdown.stop="onEndpointPointerDown($event, edge, 'target')"
                        />

                        <button
                            v-for="edge in layout.edges"
                            :key="`label-${edge.id}`"
                            v-show="shouldShowEdgeLabel(edge)"
                            type="button"
                            class="rf-editor__edge-label"
                            :class="{ 'is-selected': isTransitionSelected(edge) }"
                            :style="{ left: `${edge.labelX}px`, top: `${edge.labelY}px` }"
                            :title="edge.label"
                            @click.stop="selectTransition(edge)"
                            @contextmenu.prevent.stop="onTransitionContextMenu($event, edge)"
                        >
                            {{ edge.label }}
                        </button>

                        <svg
                            v-if="(reconnectDrag && reconnectCursor) || (creationDrag && creationCursor)"
                            class="rf-editor__reconnect-overlay"
                            :width="layout.width"
                            :height="layout.height"
                            aria-hidden="true"
                        >
                            <path
                                v-if="reconnectPreviewPath()"
                                :d="reconnectPreviewPath()"
                                class="rf-editor__reconnect-preview"
                            />
                            <path
                                v-if="reconnectThreadPath()"
                                :d="reconnectThreadPath()"
                                class="rf-editor__reconnect-thread"
                            />
                            <path
                                v-if="creationPreviewPath()"
                                :d="creationPreviewPath()"
                                class="rf-editor__reconnect-preview"
                            />
                            <circle
                                :cx="(reconnectCursor || creationCursor).x"
                                :cy="(reconnectCursor || creationCursor).y"
                                r="7"
                                class="rf-editor__reconnect-cursor"
                            />
                        </svg>

                        <button
                            v-for="node in layout.nodes"
                            :key="node.key"
                            type="button"
                            class="rf-editor__node"
                            :class="{
                                'is-start': node.kind === 'start',
                                'is-terminal': node.is_terminal,
                                'is-menu-less-state': node.kind === 'state' && !node.is_terminal && !canShowStateMenu(node),
                                'is-selected': isNodeSelected(node),
                                'is-reconnect-hover': (isNodeNearReconnectCursor(node) && (reconnectDrag || reconnectArmedEnd))
                                    || (creationDrag && node.kind === 'state' && isNodeNearCreationCursor(node)),
                                'is-draggable': editable && (node.kind === 'state' || node.kind === 'start'),
                                'is-dragging': isNodeDragging(node),
                                'is-current-reservation': isCurrentReservationNode(node),
                            }"
                            :style="{
                                left: `${node.x}px`,
                                top: `${node.y}px`,
                                width: `${node.width}px`,
                                height: `${node.height}px`,
                                ...nodeCardStyle(node),
                            }"
                            :title="node.kind === 'state' ? node.name : copy.reservation_flow_start_node"
                            @click.stop="selectNode(node)"
                            @contextmenu.prevent.stop="openStateMenu($event, node)"
                            @pointerdown="onNodePointerDown($event, node)"
                            @keydown="onNodeKeydown($event, node)"
                        >
                            <template v-if="showPorts(node) && node.ports">
                                <button
                                    v-for="(port, side) in node.ports"
                                    :key="`${node.key}-${side}`"
                                    type="button"
                                    class="rf-editor__port"
                                    :class="[`is-${side}`, { 'is-interactive': isNodePortInteractive(node), 'is-drop-target': isPortDropTarget(node, side) }]"
                                    :style="{ left: `${port.x - node.x}px`, top: `${port.y - node.y}px` }"
                                    :aria-label="`${copy.reservation_flow_port_label} ${copy[`reservation_flow_port_${side}`]}`"
                                    @pointerdown.stop="onPortPointerDown($event, node, side)"
                                    @pointerup.stop="onPortPointerUp($event, node, side)"
                                />
                            </template>

                            <div class="rf-editor__node-inner">
                                <span class="rf-editor__node-content">
                                    <span class="rf-editor__node-title">
                                        {{ node.kind === 'start' ? copy.reservation_flow_start_node : node.name }}
                                    </span>
                                </span>
                                <button
                                    v-if="node.kind !== 'start' && !node.is_terminal && canShowStateMenu(node)"
                                    type="button"
                                    class="rf-editor__node-menu"
                                    :class="{ 'is-visible': isNodeSelected(node) }"
                                    :aria-label="copy.reservation_flow_state_menu"
                                    @pointerdown.stop
                                    @click.stop="openStateMenu($event, node)"
                                >
                                    <i class="ki-filled ki-dots-vertical" aria-hidden="true" />
                                </button>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <ReservationFlowStateContextMenu
                :copy="copy"
                :open="stateMenu.open"
                :anchor="stateMenu.anchor"
                :mode="stateMenuMode()"
                :connected-count="stateMenu.node?.stateId ? connectedTransitionCount(stateMenu.node.stateId) : 0"
                @close="closeStateMenu"
                @edit="onStateMenuEdit"
                @add-transition="onStateMenuAddTransition"
                @duplicate="onStateMenuDuplicate"
                @delete="onStateMenuDelete"
            />
            <Teleport to="body"><div v-if="canvasMenu" class="rf-editor-context" :style="{ left: `${canvasMenu.x}px`, top: `${canvasMenu.y}px` }" role="menu">
                <template v-if="canvasMenu.kind === 'canvas'"><button type="button" role="menuitem" @click="onCanvasMenuAction('add-state')"><i class="ki-filled ki-plus" aria-hidden="true" />{{ copy.reservation_flow_add_state }}</button><button type="button" role="menuitem" @click="onCanvasMenuAction('add-transition')"><i class="ki-filled ki-arrow-right-left" aria-hidden="true" />{{ copy.reservation_flow_add_transition }}</button><button type="button" role="menuitem" @click="onCanvasMenuAction('fit')"><i class="ki-filled ki-maximize" aria-hidden="true" />{{ copy.reservation_flow_context_fit }}</button></template>
                <template v-else><button v-if="!canvasMenu.isStart" type="button" role="menuitem" @click="onCanvasMenuAction('delete-transition')"><i class="ki-filled ki-trash" aria-hidden="true" />{{ copy.reservation_flow_delete_transition }}</button><span v-else class="rf-editor-context__hint">{{ copy.reservation_flow_start_edge_hint }}</span></template>
            </div></Teleport>

            <aside
                class="rf-editor__inspector"
                :class="{ 'is-open': hasInspectorSelection }"
            >
                <div v-if="!editable && !selected" class="rf-editor__inspector-scroll">
                    <div class="rf-inspector-readonly">
                        <h3>{{ reservationView ? copy.workflow_position : copy.reservation_flow_editor_readonly_title }}</h3>
                        <p>{{ reservationView ? copy.workflow_position_hint : copy.reservation_flow_editor_readonly_body }}</p>
                        <div v-if="reservationView" class="rf-inspector-current"><i /> {{ copy.workflow_current_state }}</div>
                        <button v-else type="button" class="rf-btn rf-btn--primary rf-btn--block" :disabled="saving" @click="emit('customize')">
                            {{ copy.reservation_flow_action_customize }}
                        </button>
                    </div>
                </div>

                <ReservationFlowTransitionInspector
                    v-else-if="inspectorMode === 'transition' && selectedTransition"
                    :copy="copy"
                    :transition="selectedTransition"
                    :form="editTransitionForm"
                    :available-task-drivers="availableTaskDrivers"
                    :editable="editable"
                    :saving="saving"
                    :state-name="stateName"
                    @save="submitEditTransition"
                    @delete="submitDeleteTransition"
                    @deselect="clearSelection"
                    @add-task="emit('add-task', { transitionId: selectedTransition.id, ...$event })"
                    @update-task="emit('update-task', { transitionId: selectedTransition.id, ...$event })"
                    @delete-task="emit('delete-task', { transitionId: selectedTransition.id, ...$event })"
                />

                <ReservationFlowStateInspector
                    v-else-if="inspectorMode === 'state' && selectedState"
                    :key="selectedState.id"
                    :copy="copy"
                    :state="selectedState"
                    :incoming="incomingTransitions"
                    :outgoing="outgoingTransitions"
                    :state-name="stateName"
                    :form="editStateForm"
                    :available-statuses="availableStatuses"
                    :editable="editable"
                    :saving="saving"
                    :active-tab="stateInspectorTab"
                    @save="submitEditState"
                    @delete="submitDeleteState"
                    @deselect="clearSelection"
                    @select-transition="selectTransitionById"
                    @add-transition="emit('add-transition', $event)"
                    @update:active-tab="stateInspectorTab = $event"
                />

                <div v-else-if="inspectorMode === 'start'" class="rf-editor__inspector-scroll">
                    <div class="rf-inspector-form">
                        <h3>{{ copy.reservation_flow_from_any }}</h3>
                        <p>{{ copy.reservation_flow_editor_start_hint }}</p>
                    </div>
                </div>

                <div v-else class="rf-editor__inspector-scroll">
                    <ReservationFlowInspectorEmpty :copy="copy" />
                </div>
            </aside>
        </div>
    </div>
</template>

<style scoped>
.rf-editor {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    overflow: hidden;
}

.rf-editor.is-embedded {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    min-width: 0;
    min-height: 0;
}

.rf-editor.is-embedded .rf-editor__workspace {
    width: 100%;
    height: 100%;
    flex: 1 1 0;
}

.rf-editor.is-embedded .rf-editor__canvas-wrap {
    height: 100%;
}

.rf-editor.is-inspector-hidden .rf-editor__workspace {
    grid-template-columns: minmax(0, 1fr);
}

.rf-editor.is-inspector-hidden .rf-editor__inspector {
    display: none;
}

.rf-editor__hint {
    margin: 0 1.25rem 0.5rem;
    font-size: 0.75rem;
    font-weight: 600;
    color: #146EF5;
}

.rf-editor__hint--mode {
    color: #4F46E5;
}

.rf-editor__workspace {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 380px;
    flex: 1 1 auto;
    min-height: 0;
    height: auto;
    overflow: hidden;
    border-top: 1px solid #E7EAF0;
}

.rf-editor__canvas-wrap {
    position: relative;
    overflow: hidden;
    min-height: 0;
    height: 100%;
    background-color: #FCFDFE;
    background-image: radial-gradient(circle, rgba(100, 116, 139, 0.18) 1px, transparent 1px);
    background-size: 20px 20px;
    user-select: none;
}

.rf-editor__canvas-stage {
    position: absolute;
    top: 0;
    left: 0;
    transform-origin: 0 0;
    will-change: transform;
}

.rf-editor__canvas {
    position: relative;
    width: 100%;
    height: 100%;
}

.rf-editor__edges {
    position: absolute;
    inset: 0;
    overflow: visible;
}
.rf-editor-context { position:fixed; z-index:470; width:200px; padding:5px; border:1px solid var(--border,#e4e8ee); border-radius:11px; background:var(--surface,#fff); color:var(--text,#1f2d48); box-shadow:0 12px 32px rgb(0 0 0 / 18%); }
.rf-editor-context button { display:flex; align-items:center; gap:9px; width:100%; padding:9px 10px; border:0; border-radius:7px; background:transparent; color:inherit; text-align:start; cursor:pointer; font-size:12px; }
.rf-editor-context button:hover { background:var(--surface-soft,#f2f8f6); color:var(--primary,#20a58e); }.rf-editor-context__hint { display:block; padding:9px; color:var(--text-soft,#72809a); font-size:12px; line-height:1.7; }

.rf-editor__canvas-backdrop {
    pointer-events: all;
    cursor: grab;
}

.rf-editor__canvas.is-panning .rf-editor__canvas-backdrop {
    cursor: grabbing;
}

.rf-editor__edge-hit {
    fill: none;
    stroke: transparent;
    stroke-width: 16;
    pointer-events: stroke;
    cursor: pointer;
}

.rf-editor__edge {
    fill: none;
    stroke: #7D879A;
    stroke-width: 1.7;
    pointer-events: none;
    transition: stroke 0.15s ease, stroke-width 0.15s ease;
}

.rf-editor__edge.is-hovered,
.rf-editor__edge.is-related {
    stroke: #6674E8;
    stroke-width: 2;
}

.rf-editor__edge.is-selected {
    stroke: #4F5EF7;
    stroke-width: 2.3;
}

.rf-editor__edge.is-faded {
    stroke: rgba(125, 135, 154, 0.28);
    stroke-width: 1.4;
}

.rf-editor__edge.is-reconnecting-preview {
    stroke: #4F5EF7;
    stroke-width: 3.2;
    filter: drop-shadow(0 0 4px rgba(79, 94, 247, 0.35));
}

.rf-editor__edge.is-hidden-during-reconnect {
    opacity: 0;
}

.rf-editor__reconnect-overlay {
    position: absolute;
    inset: 0;
    z-index: 3;
    pointer-events: none;
    overflow: visible;
}

.rf-editor__reconnect-preview {
    fill: none;
    stroke: #4F5EF7;
    stroke-width: 3.2;
    stroke-linecap: round;
    stroke-linejoin: round;
}

.rf-editor__reconnect-thread {
    fill: none;
    stroke: #4F5EF7;
    stroke-width: 2.4;
    stroke-dasharray: 7 5;
    stroke-linecap: round;
    opacity: 0.85;
}

.rf-editor__reconnect-cursor {
    fill: #4F5EF7;
    stroke: #FFFFFF;
    stroke-width: 2.5;
}

.rf-editor__arrow-head {
    fill: #7D879A;
    stroke: none;
    pointer-events: none;
}

.rf-editor__arrow-head.is-related {
    fill: #6674E8;
}

.rf-editor__arrow-head.is-selected {
    fill: #4F5EF7;
}

.rf-editor__arrow-head.is-faded {
    fill: rgba(125, 135, 154, 0.35);
}

.rf-editor__port-dot {
    fill: #4F5EF7;
    stroke: #FFFFFF;
    stroke-width: 1.5;
    pointer-events: none;
}

.rf-editor__port-dot.is-faded {
    fill: rgba(125, 135, 154, 0.45);
}

.rf-editor__endpoint-handle-btn {
    position: absolute;
    z-index: 4;
    width: 16px;
    height: 16px;
    margin: -8px 0 0 -8px;
    border-radius: 9999px;
    border: 2px solid #4F5EF7;
    background: #FFFFFF;
    box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.9);
    cursor: grab;
    padding: 0;
    touch-action: none;
}

.rf-editor__endpoint-handle-btn.is-target {
    background: #4F5EF7;
    border-color: #FFFFFF;
}

.rf-editor__endpoint-handle-btn:active {
    cursor: grabbing;
}

.rf-editor__edge-label {
    position: absolute;
    z-index: 3;
    max-width: 8.5rem;
    transform: translate(-50%, -50%);
    padding: 3px 8px;
    border-radius: 9999px;
    border: 1px solid #D6DCE6;
    background: #FFFFFF;
    box-shadow: 0 2px 7px rgba(15, 23, 42, 0.05);
    font-size: 0.6875rem;
    font-weight: 600;
    line-height: 1.2;
    color: #475569;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    cursor: pointer;
    pointer-events: none;
}

.rf-editor__edge-label.is-selected {
    border-color: #4F5EF7;
    color: #312E81;
    pointer-events: auto;
}

.rf-editor__node {
    position: absolute;
    z-index: 2;
    padding: 0;
    box-sizing: border-box;
    border: 1.5px solid var(--rf-node-border, #CBD5E1);
    border-radius: 12px;
    background: color-mix(in srgb, var(--rf-node-surface, #FFFFFF) 22%, #FFFFFF);
    box-shadow: 0 4px 14px rgba(15, 23, 42, 0.06);
    text-align: start;
    overflow: visible;
    transition: box-shadow 0.15s ease, border-color 0.15s ease;
    touch-action: none;
}
.rf-editor__node.is-start { border:3px solid #e68a26; border-radius:50%; background:#fff7ed; text-align:center; box-shadow:0 5px 18px rgb(230 138 38 / 18%); }
.rf-editor__node.is-start.is-selected { box-shadow:0 0 0 4px rgb(230 138 38 / 20%),0 5px 18px rgb(230 138 38 / 18%); }
.rf-editor__node.is-start .rf-editor__node-inner { justify-content:center; padding:7px; }
.rf-editor__node.is-start .rf-editor__node-content { flex:0 0 auto; align-items:center; width:100%; }
.rf-editor__node.is-start .rf-editor__node-title { white-space:normal; text-align:center; line-height:1.2; font-size:12px; color:#a8540c; }
.rf-editor__node.is-terminal {
    border: 2.5px solid #2EAE98;
    border-radius: 999px;
    background: #F0FAF7;
    box-shadow: 0 4px 14px rgba(46, 174, 152, 0.12);
    text-align: center;
}
.rf-editor__node.is-terminal .rf-editor__node-inner { justify-content: center; padding: 8px 16px; }
.rf-editor__node.is-terminal .rf-editor__node-content { align-items: center; text-align: center; }
.rf-editor__node.is-terminal .rf-editor__node-title { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
html[data-theme='dark'] .rf-editor__node.is-start { background:#3b2619; border-color:#f3a647; }
html[data-theme='dark'] .rf-editor__node.is-start .rf-editor__node-title { color:#ffd394; }

.rf-editor__node-inner {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    width: 100%;
    height: 100%;
    min-height: inherit;
    padding: 8px 10px;
    box-sizing: border-box;
    overflow: hidden;
}

.rf-editor__node-menu {
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 1.5rem;
    height: 1.5rem;
    margin-inline-start: auto;
    border: 0;
    border-radius: 6px;
    background: transparent;
    color: #64748B;
    opacity: 0.55;
    cursor: pointer;
}

.rf-editor__node-menu.is-visible,
.rf-editor__node:hover .rf-editor__node-menu,
.rf-editor__node:focus-within .rf-editor__node-menu {
    opacity: 1;
}

.rf-editor__node-menu:hover,
.rf-editor__node-menu:focus-visible {
    background: rgba(15, 23, 42, 0.06);
    outline: none;
}

.rf-editor__node-content {
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    justify-content: center;
    gap: 0.15rem;
    min-width: 0;
    flex: 1;
}

.rf-editor__node.is-menu-less-state .rf-editor__node-inner {
    justify-content: center;
}

.rf-editor__node.is-menu-less-state .rf-editor__node-content {
    align-items: center;
    text-align: center;
}

.rf-editor__node-title {
    width: 100%;
    font-size: 0.8125rem;
    font-weight: 700;
    line-height: 1.2;
    color: #172033;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.rf-editor__node.is-draggable {
    cursor: grab;
}

.rf-editor__node.is-dragging {
    cursor: grabbing;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.14);
}

.rf-editor__node.is-selected {
    border-color: var(--rf-node-border, #635BFF);
    border-width: 2px;
    box-shadow:
        0 0 0 3px rgba(99, 91, 255, 0.14),
        0 9px 24px rgba(15, 23, 42, 0.10);
}

.rf-editor__node.is-current-reservation {
    z-index: 3;
    border-color: #F59E0B;
    border-width: 2.5px;
    box-shadow: 0 0 0 5px rgba(245, 158, 11, 0.2), 0 14px 30px rgba(180, 83, 9, 0.14);
}

.rf-inspector-current { display:flex; align-items:center; gap:8px; margin-top:16px; padding:10px; border:1px solid #BFDBFE; border-radius:10px; background:#EFF6FF; color:#1D4ED8; font-size:.75rem; font-weight:750; }
.rf-inspector-current i { width:9px; height:9px; border-radius:50%; background:#2563EB; box-shadow:0 0 0 4px #DBEAFE; }

.rf-editor__node.is-transition-source {
    box-shadow: 0 0 0 3px rgba(20, 110, 245, 0.14);
}

.rf-editor__node.is-reconnect-hover {
    box-shadow: 0 0 0 3px rgba(79, 94, 247, 0.18);
}

.rf-editor__node.is-terminal.is-selected { border-width: 2.5px; }

.rf-editor__port {
    position: absolute;
    width: 9px;
    height: 9px;
    margin: -4.5px 0 0 -4.5px;
    border-radius: 9999px;
    border: 2px solid var(--rf-node-border, #635BFF);
    background: #FFFFFF;
    box-shadow: 0 1px 4px rgba(15, 23, 42, 0.12);
    pointer-events: none;
    padding: 0;
}

.rf-editor__port.is-interactive {
    width: 22px;
    height: 22px;
    margin: -11px 0 0 -11px;
    pointer-events: all;
    cursor: crosshair;
    z-index: 5;
}

.rf-editor__port.is-drop-target {
    background: #4F5EF7;
    border-color: #FFFFFF;
    box-shadow: 0 0 0 3px rgba(79, 94, 247, 0.25);
}

.rf-editor__inspector {
    display: flex;
    flex-direction: column;
    min-height: 0;
    height: 100%;
    overflow: hidden;
    border-inline-start: 1px solid #E7EAF0;
    background: #F8FAFB;
}

.rf-editor__inspector :deep(.rf-inspector-head) {
    align-items: center;
    gap: 0.85rem;
    padding: 1.2rem;
    background: #FFFFFF;
}

.rf-editor__inspector :deep(.rf-inspector-head__icon) {
    width: 2.75rem;
    height: 2.75rem;
    border-radius: 12px;
    font-size: 1.15rem;
}

.rf-editor__inspector :deep(.rf-inspector-head__title) {
    font-size: 1.05rem;
}

.rf-editor__inspector :deep(.rf-inspector-tabs) {
    gap: 0.35rem;
    padding: 0.65rem;
    border-bottom: 1px solid #E7EAF0;
    background: #FFFFFF;
}

.rf-editor__inspector :deep(.rf-inspector-tabs__btn) {
    flex: 1;
    justify-content: center;
    min-height: 2.4rem;
    border: 0;
    border-radius: 9px;
}

.rf-editor__inspector :deep(.rf-inspector-tabs__btn.is-active) {
    color: #168A76;
    background: #E8F7F3;
}

.rf-editor__inspector :deep(.rf-inspector-panel) {
    margin: 1rem;
    padding: 1.15rem;
    border: 1px solid #E7EAF0;
    border-radius: 14px;
    background: #FFFFFF;
    box-shadow: 0 4px 18px rgba(15, 23, 42, 0.035);
}

.rf-editor__inspector :deep(.rf-inspector-foot) {
    background: #FFFFFF;
}

html[data-theme='dark'] .rf-editor__inspector { background: #1A2433; border-color: #384559; }
html[data-theme='dark'] .rf-editor__inspector :deep(.rf-inspector-head),
html[data-theme='dark'] .rf-editor__inspector :deep(.rf-inspector-tabs),
html[data-theme='dark'] .rf-editor__inspector :deep(.rf-inspector-panel),
html[data-theme='dark'] .rf-editor__inspector :deep(.rf-inspector-foot) {
    background: #222E40;
    border-color: #384559;
}
html[data-theme='dark'] .rf-editor__inspector :deep(.rf-inspector-head__title),
html[data-theme='dark'] .rf-editor__inspector :deep(.rf-section-head__title) { color: #EDF3FA; }
html[data-theme='dark'] .rf-editor__inspector :deep(.rf-inspector-tabs__btn.is-active) {
    background: #193E3B;
    color: #73D8C2;
}

.rf-editor__inspector :deep(.rf-state-inspector),
.rf-editor__inspector :deep(.rf-transition-inspector) {
    flex: 1;
    min-height: 0;
}

.rf-editor__inspector-scroll {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
}

.rf-inspector-readonly,
.rf-inspector-form {
    padding: 1rem;
}

.rf-inspector-readonly h3,
.rf-inspector-form h3 {
    margin: 0;
    font-size: 0.9375rem;
    font-weight: 700;
    color: #0F172A;
}

.rf-inspector-readonly p,
.rf-inspector-form p {
    margin: 0.55rem 0 0;
    font-size: 0.8125rem;
    color: #64748B;
    line-height: 1.5;
}

.rf-inspector-form__route {
    font-weight: 600;
    color: #334155 !important;
}

.rf-inspector-form__actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 1rem;
}

.rf-field {
    display: block;
    margin-top: 0.85rem;
    font-size: 0.8125rem;
}

.rf-field span {
    display: block;
    margin-bottom: 0.35rem;
    color: #64748B;
}

.rf-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    height: 2.35rem;
    padding: 0 0.85rem;
    border-radius: 9px;
    border: 1px solid transparent;
    font-size: 0.8125rem;
    font-weight: 600;
    cursor: pointer;
}

.rf-btn--block {
    width: 100%;
    margin-top: 0.85rem;
}

.rf-btn--primary {
    background: #146EF5;
    color: #FFFFFF;
}

.rf-btn--secondary {
    background: #FFFFFF;
    border-color: #DDE3EC;
    color: #263247;
}

.rf-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}

.rf-editor__canvas.is-reconnecting .rf-editor__node {
    cursor: crosshair;
}

@media (max-width: 1024px) {
    .rf-editor__workspace {
        grid-template-columns: 1fr;
        grid-template-rows: minmax(420px, 1fr);
        height: auto;
    }

    .rf-editor__inspector {
        position: fixed;
        inset-inline: 0;
        bottom: 0;
        z-index: 120;
        max-height: 72vh;
        border-inline-start: 0;
        border-top: 1px solid #E7EAF0;
        border-radius: 16px 16px 0 0;
        box-shadow: 0 -12px 40px rgba(15, 23, 42, 0.12);
        transform: translateY(100%);
        visibility: hidden;
        pointer-events: none;
        transition: transform 0.22s ease, visibility 0.22s ease;
    }

    .rf-editor__inspector.is-open {
        transform: translateY(0);
        visibility: visible;
        pointer-events: auto;
    }
}

@media (max-width: 768px) {
    .rf-editor__workspace {
        min-height: 520px;
    }
}
</style>
