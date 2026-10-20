const NODE_WIDTH = 200;
const NODE_MIN_WIDTH = 200;
const NODE_MAX_WIDTH = 260;
const NODE_HEIGHT = 52;
const H_GAP = 132;
const V_GAP = 28;
const PADDING = 28;
const START_DIAMETER = 68;
const TERMINAL_WIDTH = 168;
const PORT_SPREAD = 18;
const EDGE_STUB = 20;
const CORNER_RADIUS = 10;
const ARROW_SIZE = 8;
const ROUTE_ALIGN_EPS = 2;
const SAME_SIDE_LOOP_OFFSET = 42;
const EDGE_LANE_SPACING = 18;
const LANE_GROUP_EPS = 8;

export const FLOW_NODE_WIDTH = NODE_WIDTH;
export const FLOW_NODE_HEIGHT = NODE_HEIGHT;
export const FLOW_ARROW_SIZE = ARROW_SIZE;

export const FLOW_NODE_PORTS = ['top', 'right', 'bottom', 'left'];

/**
 * @param {Record<string, unknown>} config
 */
function readPosition(config) {
    if (!config || typeof config !== 'object') {
        return null;
    }

    const position = config.position ?? config;

    if (
        position
        && typeof position === 'object'
        && Number.isFinite(Number(position.x))
        && Number.isFinite(Number(position.y))
    ) {
        return {
            x: Number(position.x),
            y: Number(position.y),
        };
    }

    return null;
}

/**
 * @param {number|null|undefined} from
 * @param {number} to
 */
export function flowTransitionPathKey(from, to) {
    return `${from ?? 'start'}:${to}`;
}

/**
 * @param {{ centerX: number, centerY: number, leftX: number, rightX: number, topY: number, bottomY: number }} node
 * @param {'top'|'right'|'bottom'|'left'} side
 * @param {number} slotIndex
 * @param {number} slotTotal
 */
export function portPoint(node, side, slotIndex = 0, slotTotal = 1) {
    const offset = slotTotal <= 1 ? 0 : (slotIndex - (slotTotal - 1) / 2) * PORT_SPREAD;

    switch (side) {
        case 'right':
            return { x: node.rightX, y: node.centerY + offset, side };
        case 'left':
            return { x: node.leftX, y: node.centerY + offset, side };
        case 'top':
            return { x: node.centerX + offset, y: node.topY, side };
        case 'bottom':
            return { x: node.centerX + offset, y: node.bottomY, side };
        default:
            return { x: node.centerX, y: node.centerY, side: 'right' };
    }
}

/**
 * @param {{ centerX: number, centerY: number, leftX: number, rightX: number, topY: number, bottomY: number }} source
 * @param {{ centerX: number, centerY: number, leftX: number, rightX: number, topY: number, bottomY: number }} target
 */
export function pickPortSides(source, target) {
    const normals = {
        top: { x: 0, y: -1 },
        right: { x: 1, y: 0 },
        bottom: { x: 0, y: 1 },
        left: { x: -1, y: 0 },
    };
    const candidates = [];

    for (const from of FLOW_NODE_PORTS) {
        const start = portPoint(source, from);
        for (const to of FLOW_NODE_PORTS) {
            const end = portPoint(target, to);
            const dx = end.x - start.x;
            const dy = end.y - start.y;
            const pointsOutward = dx * normals[from].x + dy * normals[from].y >= 0
                && dx * normals[to].x + dy * normals[to].y <= 0;
            candidates.push({ from, to, distance: dx * dx + dy * dy, pointsOutward });
        }
    }

    // Prefer the shortest connection whose ends face each other. Manual anchors
    // remain authoritative and bypass this choice entirely.
    const facing = candidates.filter((candidate) => candidate.pointsOutward);
    const pool = facing.length ? facing : candidates;
    pool.sort((a, b) => a.distance - b.distance);

    return { from: pool[0].from, to: pool[0].to };
}

/**
 * @param {'top'|'right'|'bottom'|'left'} side
 */
function isHorizontalSide(side) {
    return side === 'left' || side === 'right';
}

/**
 * @param {{ x: number, y: number }} point
 * @param {'top'|'right'|'bottom'|'left'} side
 * @param {number} distance
 */
function extendFromSide(point, side, distance) {
    switch (side) {
        case 'right':
            return { x: point.x + distance, y: point.y };
        case 'left':
            return { x: point.x - distance, y: point.y };
        case 'bottom':
            return { x: point.x, y: point.y + distance };
        case 'top':
            return { x: point.x, y: point.y - distance };
        default:
            return point;
    }
}

/**
 * @param {Array<{x:number,y:number}>} points
 * @param {number} ratio
 */
export function pointAlongPolyline(points, ratio) {
    if (!points.length) {
        return { x: 0, y: 0 };
    }

    if (points.length === 1) {
        return { ...points[0] };
    }

    const segments = [];
    let total = 0;

    for (let index = 1; index < points.length; index += 1) {
        const from = points[index - 1];
        const to = points[index];
        const length = Math.hypot(to.x - from.x, to.y - from.y);
        segments.push({ from, to, length });
        total += length;
    }

    if (total <= 0.001) {
        return { ...points[0] };
    }

    let remaining = total * Math.min(1, Math.max(0, ratio));

    for (const segment of segments) {
        if (remaining <= segment.length) {
            const t = segment.length <= 0.001 ? 0 : remaining / segment.length;

            return {
                x: segment.from.x + (segment.to.x - segment.from.x) * t,
                y: segment.from.y + (segment.to.y - segment.from.y) * t,
            };
        }

        remaining -= segment.length;
    }

    const last = points[points.length - 1];

    return { x: last.x, y: last.y };
}

/**
 * @param {Array<{x:number,y:number}>} points
 * @param {number} radius
 */
export function roundedPolylinePath(points, radius = CORNER_RADIUS) {
    if (points.length < 2) {
        return '';
    }

    let path = `M ${points[0].x} ${points[0].y}`;

    for (let index = 1; index < points.length - 1; index += 1) {
        const previous = points[index - 1];
        const corner = points[index];
        const next = points[index + 1];

        const dx1 = corner.x - previous.x;
        const dy1 = corner.y - previous.y;
        const dx2 = next.x - corner.x;
        const dy2 = next.y - corner.y;

        const len1 = Math.hypot(dx1, dy1);
        const len2 = Math.hypot(dx2, dy2);

        if (len1 < 0.001 || len2 < 0.001) {
            continue;
        }

        const cornerRadius = Math.min(radius, len1 / 2, len2 / 2);
        const beforeX = corner.x - (dx1 / len1) * cornerRadius;
        const beforeY = corner.y - (dy1 / len1) * cornerRadius;
        const afterX = corner.x + (dx2 / len2) * cornerRadius;
        const afterY = corner.y + (dy2 / len2) * cornerRadius;

        path += ` L ${beforeX} ${beforeY} Q ${corner.x} ${corner.y} ${afterX} ${afterY}`;
    }

    const last = points[points.length - 1];
    path += ` L ${last.x} ${last.y}`;

    return path;
}

/**
 * @param {{ x: number, y: number }} tip
 * @param {'top'|'right'|'bottom'|'left'} toSide
 * @param {number} [size=ARROW_SIZE]
 */
export function buildArrowHeadPoints(tip, toSide, size = ARROW_SIZE) {
    const half = size * 0.42;
    const base = extendFromSide(tip, toSide, size);

    switch (toSide) {
        case 'top':
            return [tip, { x: base.x - half, y: base.y }, { x: base.x + half, y: base.y }];
        case 'bottom':
            return [tip, { x: base.x - half, y: base.y }, { x: base.x + half, y: base.y }];
        case 'left':
            return [tip, { x: base.x, y: base.y - half }, { x: base.x, y: base.y + half }];
        case 'right':
            return [tip, { x: base.x, y: base.y - half }, { x: base.x, y: base.y + half }];
        default:
            return [tip, base, { x: base.x, y: base.y + half }];
    }
}

/**
 * @param {Array<{x:number,y:number}>} points
 */
export function polygonPointsAttr(points) {
    return points.map((point) => `${point.x},${point.y}`).join(' ');
}

/**
 * @param {{ x: number, y: number }} exit
 * @param {{ x: number, y: number }} entry
 * @param {'top'|'right'|'bottom'|'left'} fromSide
 * @param {'top'|'right'|'bottom'|'left'} toSide
 */
/**
 * @param {{ x?: number, y?: number }|null} [lane]
 */
function buildRoutePoints(exit, entry, fromSide, toSide, lane = null) {
    const points = [];
    const dx = entry.x - exit.x;
    const dy = entry.y - exit.y;
    const alignedX = Math.abs(dx) <= ROUTE_ALIGN_EPS;
    const alignedY = Math.abs(dy) <= ROUTE_ALIGN_EPS;
    const loop = SAME_SIDE_LOOP_OFFSET;
    const laneX = lane?.x ?? 0;
    const laneY = lane?.y ?? 0;
    const shiftX = (value) => value + laneX;
    const shiftY = (value) => value + laneY;

    const oppositeVertical = (
        (fromSide === 'bottom' && toSide === 'top')
        || (fromSide === 'top' && toSide === 'bottom')
    );
    const oppositeHorizontal = (
        (fromSide === 'right' && toSide === 'left')
        || (fromSide === 'left' && toSide === 'right')
    );

    if (oppositeVertical && alignedX) {
        points.push(entry);
        return points;
    }

    if (oppositeHorizontal && alignedY) {
        points.push(entry);
        return points;
    }

    if (fromSide === toSide) {
        if (isHorizontalSide(fromSide)) {
            const outward = fromSide === 'right' ? 1 : -1;
            points.push(
                { x: shiftX(exit.x + outward * loop), y: exit.y },
                { x: shiftX(exit.x + outward * loop), y: entry.y },
            );
        } else {
            const outward = fromSide === 'bottom' ? 1 : -1;
            points.push(
                { x: exit.x, y: shiftY(exit.y + outward * loop) },
                { x: entry.x, y: shiftY(exit.y + outward * loop) },
            );
        }

        points.push(entry);
        return points;
    }

    if (isHorizontalSide(fromSide) && isHorizontalSide(toSide)) {
        if (Math.abs(exit.y - entry.y) < NODE_HEIGHT) {
            const detourY = shiftY(Math.max(exit.y, entry.y) + loop);
            points.push(
                { x: exit.x, y: detourY },
                { x: entry.x, y: detourY },
            );
        } else {
            const midX = shiftX((exit.x + entry.x) / 2);
            points.push(
                { x: midX, y: exit.y },
                { x: midX, y: entry.y },
            );
        }

        points.push(entry);
        return points;
    }

    if (!isHorizontalSide(fromSide) && !isHorizontalSide(toSide)) {
        const midY = shiftY((exit.y + entry.y) / 2);
        points.push(
            { x: exit.x, y: midY },
            { x: entry.x, y: midY },
        );
        points.push(entry);
        return points;
    }

    if (isHorizontalSide(fromSide)) {
        if (Math.abs(exit.y - entry.y) < NODE_HEIGHT) {
            const detourY = shiftY(exit.y + (exit.y <= entry.y ? -loop : loop));
            points.push(
                { x: exit.x, y: detourY },
                { x: entry.x, y: detourY },
            );
        } else {
            const midX = shiftX((exit.x + entry.x) / 2);
            points.push(
                { x: midX, y: exit.y },
                { x: midX, y: entry.y },
            );
        }
    } else if (Math.abs(exit.x - entry.x) < NODE_WIDTH) {
        const detourX = shiftX(exit.x + (exit.x <= entry.x ? -loop : loop));
        points.push(
            { x: detourX, y: exit.y },
            { x: detourX, y: entry.y },
        );
    } else {
        const midY = shiftY((exit.y + entry.y) / 2);
        points.push(
            { x: exit.x, y: midY },
            { x: entry.x, y: midY },
        );
    }

    points.push(entry);

    return points;
}

/**
 * @param {Array<{x:number,y:number}>} points
 */
function ensureOrthogonalPath(points) {
    if (points.length < 2) {
        return points;
    }

    const result = [{ ...points[0] }];

    for (let index = 1; index < points.length; index += 1) {
        const previous = result[result.length - 1];
        const current = points[index];
        const dx = Math.abs(current.x - previous.x);
        const dy = Math.abs(current.y - previous.y);

        if (dx > ROUTE_ALIGN_EPS && dy > ROUTE_ALIGN_EPS) {
            result.push({ x: current.x, y: previous.y });
        }

        result.push({ ...current });
    }

    return result;
}

/**
 * @param {{ x: number, y: number }} start
 * @param {{ x: number, y: number }} end
 * @param {'top'|'right'|'bottom'|'left'} fromSide
 * @param {'top'|'right'|'bottom'|'left'} toSide
 */
export function buildOrthogonalEdgePath(start, end, fromSide, toSide, lane = null) {
    const exit = extendFromSide(start, fromSide, EDGE_STUB);
    const entry = extendFromSide(end, toSide, EDGE_STUB);
    const arrowBase = extendFromSide(end, toSide, ARROW_SIZE);
    const route = buildRoutePoints(exit, entry, fromSide, toSide, lane);
    const drawable = ensureOrthogonalPath([start, exit, ...route.slice(0, -1), entry, arrowBase]);
    const hitPoints = ensureOrthogonalPath([start, exit, ...route, end]);
    const labelPoint = pointAlongPolyline([exit, ...route], 0.5);

    return {
        path: roundedPolylinePath(drawable),
        hitPath: roundedPolylinePath(hitPoints),
        arrowPoints: polygonPointsAttr(buildArrowHeadPoints(end, toSide)),
        labelX: labelPoint.x,
        labelY: labelPoint.y,
        points: drawable,
        start,
        end,
    };
}

/** Keep a reflexive transition outside its state as a soft, oval-shaped loop. */
export function buildSelfLoopEdgePath(node, sourcePort, targetPort, fromSide, toSide, index = 0) {
    const start = {...sourcePort};
    const end = {...targetPort};
    const depth = 104 + index * 32;
    const spread = 14;

    if (fromSide === toSide) {
        if (isHorizontalSide(fromSide)) {
            start.y = node.centerY - spread - index * 3;
            end.y = node.centerY + spread + index * 3;
        } else {
            start.x = node.centerX - spread - index * 3;
            end.x = node.centerX + spread + index * 3;
        }
    }

    const arrowBase = extendFromSide(end, toSide, ARROW_SIZE);
    const outwardStart = extendFromSide(start, fromSide, depth);
    const outwardEnd = extendFromSide(end, toSide, depth);
    const opposite = (fromSide === 'right' && toSide === 'left')
        || (fromSide === 'left' && toSide === 'right')
        || (fromSide === 'top' && toSide === 'bottom')
        || (fromSide === 'bottom' && toSide === 'top');
    let path;
    let label;
    let controls;

    if (opposite) {
        const horizontal = isHorizontalSide(fromSide);
        const via = horizontal
            ? {x: node.centerX, y: node.topY - depth}
            : {x: node.rightX + depth, y: node.centerY};
        const first = horizontal ? {x: outwardStart.x, y: start.y - depth / 2}
            : {x: start.x + depth / 2, y: outwardStart.y};
        const second = horizontal ? {x: via.x + depth / 2, y: via.y}
            : {x: via.x, y: via.y + depth / 2};
        const third = horizontal ? {x: via.x - depth / 2, y: via.y}
            : {x: via.x, y: via.y - depth / 2};
        const fourth = horizontal ? {x: outwardEnd.x, y: end.y - depth / 2}
            : {x: end.x + depth / 2, y: outwardEnd.y};
        path = `M ${start.x} ${start.y} C ${first.x} ${first.y} ${second.x} ${second.y} ${via.x} ${via.y} C ${third.x} ${third.y} ${fourth.x} ${fourth.y} ${arrowBase.x} ${arrowBase.y}`;
        label = via;
        controls = [first, second, via, third, fourth];
    } else {
        const first = fromSide === toSide && isHorizontalSide(fromSide)
            ? {x: outwardStart.x, y: start.y - depth * 0.65}
            : fromSide === toSide ? {x: start.x - depth * 0.65, y: outwardStart.y}
                : outwardStart;
        const second = fromSide === toSide && isHorizontalSide(toSide)
            ? {x: outwardEnd.x, y: end.y + depth * 0.65}
            : fromSide === toSide ? {x: end.x + depth * 0.65, y: outwardEnd.y}
                : outwardEnd;
        path = `M ${start.x} ${start.y} C ${first.x} ${first.y} ${second.x} ${second.y} ${arrowBase.x} ${arrowBase.y}`;
        label = {x: (start.x + 3 * first.x + 3 * second.x + arrowBase.x) / 8,
            y: (start.y + 3 * first.y + 3 * second.y + arrowBase.y) / 8};
        if (fromSide === toSide) {
            if (isHorizontalSide(fromSide)) {
                label = {x: node.centerX + (fromSide === 'right' ? 1 : -1) * (node.width / 2 + depth * 0.7),
                    y: node.topY - 22 - index * 12};
            } else {
                label = {x: node.rightX + 28 + index * 12,
                    y: node.centerY + (fromSide === 'bottom' ? 1 : -1) * (node.height / 2 + depth * 0.7)};
            }
        }
        controls = [first, second];
    }

    const coordinates = [start, end, arrowBase, label, ...controls];
    const xs = coordinates.map((point) => point.x);
    const ys = coordinates.map((point) => point.y);

    return {
        path,
        hitPath: path,
        arrowPoints: polygonPointsAttr(buildArrowHeadPoints(end, toSide)),
        labelX: label.x,
        labelY: label.y,
        start,
        end,
        bounds: {x: Math.min(...xs), y: Math.min(...ys),
            width: Math.max(...xs) - Math.min(...xs), height: Math.max(...ys) - Math.min(...ys)},
    };
}

/**
 * @param {object} transition
 * @param {object} source
 * @param {object} target
 * @param {Record<number, {from_side?: string, to_side?: string}>} [anchorCache]
 */
function resolveTransitionSides(transition, source, target, anchorCache) {
    const storedFrom = transition.anchors?.from_side;
    const storedTo = transition.anchors?.to_side;

    if (FLOW_NODE_PORTS.includes(storedFrom) && FLOW_NODE_PORTS.includes(storedTo)) {
        return { from: storedFrom, to: storedTo };
    }

    if (FLOW_NODE_PORTS.includes(storedFrom)) {
        const cachedTo = anchorCache?.[transition.id]?.to_side;
        if (FLOW_NODE_PORTS.includes(cachedTo)) {
            return { from: storedFrom, to: cachedTo };
        }
    }

    if (FLOW_NODE_PORTS.includes(storedTo)) {
        const cachedFrom = anchorCache?.[transition.id]?.from_side;
        if (FLOW_NODE_PORTS.includes(cachedFrom)) {
            return { from: cachedFrom, to: storedTo };
        }
    }

    const cached = anchorCache?.[transition.id];
    if (FLOW_NODE_PORTS.includes(cached?.from_side) && FLOW_NODE_PORTS.includes(cached?.to_side)) {
        return { from: cached.from_side, to: cached.to_side };
    }

    const autoSides = transition.is_start_edge
        ? {from: 'bottom', to: 'top'}
        : pickPortSides(source, target);

    return autoSides;
}

/**
 * @param {FlowStateNode[]} states
 * @param {FlowTransitionEdge[]} transitions
 * @param {{ positions?: Record<number, {x:number,y:number}>, startPosition?: {x:number,y:number}|null, startLabel?: string, anchorCache?: Record<number, {from_side?: string, to_side?: string}> }} [options]
 */
export function buildReservationFlowDiagram(states, transitions, options = {}) {
    if (!Array.isArray(states) || states.length === 0) {
        return {
            width: 320,
            height: 160,
            nodes: [],
            edges: [],
            hasStart: false,
        };
    }

    const explicitPositions = { ...(options.positions ?? {}) };
    const anchorCache = options.anchorCache ?? null;

    states.forEach((state) => {
        if (explicitPositions[state.id]) {
            return;
        }

        const fromState = readPosition(state.position) ?? readPosition(state.config);
        if (fromState) {
            explicitPositions[state.id] = fromState;
        }
    });

    const layers = new Map();
    const startEdges = transitions.filter((row) => row.is_start_edge && row.to);
    const hasStart = startEdges.length > 0;

    const queue = [];
    const visited = new Set();

    startEdges.forEach((edge) => {
        queue.push({ id: edge.to, layer: 1 });
    });

    if (queue.length === 0) {
        const fallback = states.find((state) => state.is_start) ?? states[0];
        if (fallback) {
            queue.push({ id: fallback.id, layer: 1 });
        }
    }

    while (queue.length > 0) {
        const current = queue.shift();
        if (!current?.id || visited.has(current.id)) {
            continue;
        }

        visited.add(current.id);
        layers.set(current.id, Math.max(layers.get(current.id) ?? 0, current.layer));

        transitions
            .filter((row) => row.from === current.id && row.to)
            .forEach((row) => {
                queue.push({ id: row.to, layer: current.layer + 1 });
            });
    }

    let maxLayer = 1;

    states.forEach((state) => {
        if (layers.has(state.id)) {
            maxLayer = Math.max(maxLayer, layers.get(state.id));
            return;
        }

        const layer = state.is_terminal ? maxLayer + 1 : 1;
        layers.set(state.id, layer);
        maxLayer = Math.max(maxLayer, layer);
    });

    const grouped = new Map();

    states.forEach((state) => {
        const layer = layers.get(state.id) ?? 1;
        if (!grouped.has(layer)) {
            grouped.set(layer, []);
        }
        grouped.get(layer).push(state);
    });

    grouped.forEach((layerStates) => {
        layerStates.sort((a, b) => {
            if (Boolean(a.is_terminal) !== Boolean(b.is_terminal)) {
                return a.is_terminal ? 1 : -1;
            }

            return String(a.name).localeCompare(String(b.name));
        });
    });

    const nodes = [];
    const nodeMeta = new Map();
    let maxRows = 1;

    grouped.forEach((layerStates, layer) => {
        maxRows = Math.max(maxRows, layerStates.length);

        let nextAutoY = PADDING;
        layerStates.forEach((state) => {
            const columnIndex = hasStart ? layer : Math.max(0, layer - 1);
            const autoX = PADDING + columnIndex * (NODE_WIDTH + H_GAP);
            const autoY = nextAutoY;
            const saved = explicitPositions[state.id];
            const x = saved?.x ?? autoX;
            const y = saved?.y ?? autoY;
            const node = createStateNode(state, x, y);

            nodes.push(node);
            nodeMeta.set(state.id, node);
            nextAutoY += node.height + V_GAP;
        });
    });

    let startNode = null;

    if (hasStart) {
        const firstNode = nodeMeta.get(startEdges[0].to);
        const startX = options.startPosition?.x ?? (firstNode ? firstNode.centerX - START_DIAMETER / 2 : PADDING);
        const startY = options.startPosition?.y ?? (firstNode ? firstNode.y - START_DIAMETER - 100 : PADDING);
        startNode = createStartNode(startX, startY, options.startLabel ?? '');
        nodes.unshift(startNode);
    }

    const edges = buildTransitionEdges(transitions, nodeMeta, startNode, anchorCache);
    const bounds = computeBounds(nodes, edges);

    return {
        width: Math.max(360, bounds.width),
        height: Math.max(220, bounds.height),
        nodes,
        edges,
        hasStart,
    };
}

function measureStateNodeWidth(name) {
    const text = String(name ?? '').trim();

    if (!text) {
        return NODE_MIN_WIDTH;
    }

    // Reserve space for icon, padding, menu, and gaps inside the card.
    const reserved = 96;
    const estimated = Math.ceil(reserved + (text.length * 8.4));

    return Math.min(NODE_MAX_WIDTH, Math.max(NODE_MIN_WIDTH, estimated));
}

function measureStartNodeWidth(label) {
    return START_DIAMETER;
}

/**
 * @param {FlowStateNode} state
 */
function createStateNode(state, x, y) {
    const width = state.is_terminal ? TERMINAL_WIDTH : measureStateNodeWidth(state.name);
    const height = NODE_HEIGHT;
    const bounds = {
        centerX: x + width / 2,
        centerY: y + height / 2,
        leftX: x,
        rightX: x + width,
        topY: y,
        bottomY: y + height,
    };

    return {
        key: `state-${state.id}`,
        kind: 'state',
        stateId: state.id,
        name: state.name,
        status: state.status,
        is_start: Boolean(state.is_start),
        is_terminal: Boolean(state.is_terminal),
        x,
        y,
        width,
        height,
        ...bounds,
        ports: {
            top: portPoint(bounds, 'top'),
            right: portPoint(bounds, 'right'),
            bottom: portPoint(bounds, 'bottom'),
            left: portPoint(bounds, 'left'),
        },
    };
}

function createStartNode(x, y, label = '') {
    const width = measureStartNodeWidth(label);
    const height = START_DIAMETER;

    const node = {
        key: 'start',
        kind: 'start',
        name: label || 'start',
        x,
        y,
        width,
        height,
        centerX: x + width / 2,
        centerY: y + height / 2,
        rightX: x + width,
        leftX: x,
        bottomY: y + height,
        topY: y,
    };

    return {
        ...node,
        ports: {
            right: portPoint(node, 'right'),
            bottom: portPoint(node, 'bottom'),
            top: portPoint(node, 'top'),
            left: portPoint(node, 'left'),
        },
    };
}

function detectCorridor(exit, entry, fromSide, toSide, route) {
    if (route.length === 1) {
        if (Math.abs(exit.x - entry.x) <= ROUTE_ALIGN_EPS) {
            return {
                axis: 'x',
                key: `straight-v:${Math.round(((exit.x + entry.x) / 2) / LANE_GROUP_EPS)}`,
                sort: (exit.y + entry.y) / 2,
            };
        }

        return {
            axis: 'y',
            key: `straight-h:${Math.round(((exit.y + entry.y) / 2) / LANE_GROUP_EPS)}`,
            sort: (exit.x + entry.x) / 2,
        };
    }

    const first = route[0];
    const second = route.length > 1 ? route[1] : first;

    if (Math.abs(first.x - second.x) <= ROUTE_ALIGN_EPS) {
        return {
            axis: 'x',
            key: `midX:${Math.round(first.x / LANE_GROUP_EPS)}`,
            sort: Math.min(first.y, second.y, exit.y, entry.y),
        };
    }

    if (Math.abs(first.y - second.y) <= ROUTE_ALIGN_EPS) {
        return {
            axis: 'y',
            key: `midY:${Math.round(first.y / LANE_GROUP_EPS)}`,
            sort: Math.min(first.x, second.x, exit.x, entry.x),
        };
    }

    return {
        axis: 'x',
        key: `edge:${Math.round(exit.x / LANE_GROUP_EPS)}:${Math.round(entry.x / LANE_GROUP_EPS)}:${fromSide}:${toSide}`,
        sort: exit.y,
    };
}

function mergeLaneOffsets(existing, axis, offset) {
    if (!offset) {
        return existing ?? null;
    }

    const next = {
        x: existing?.x ?? 0,
        y: existing?.y ?? 0,
    };

    if (axis === 'x') {
        if (next.y !== 0) {
            return Math.abs(offset) >= Math.abs(next.x)
                ? { x: offset, y: 0 }
                : next;
        }

        next.x += offset;
    } else if (next.x !== 0) {
        return Math.abs(offset) >= Math.abs(next.y)
            ? { x: 0, y: offset }
            : next;
    } else {
        next.y += offset;
    }

    if (next.x === 0 && next.y === 0) {
        return null;
    }

    return next;
}

function assignEdgeLanes(planned) {
    const corridorGroups = new Map();
    const sourceGroups = new Map();

    planned.forEach((row) => {
        row.lane = null;

        if (row.source === row.target) return;

        const sourceKey = row.source.kind === 'start' ? 'start' : `state-${row.source.stateId}`;
        const fromTotal = row.fromTotal ?? 1;
        const toTotal = row.toTotal ?? 1;
        const start = portPoint(row.source, row.sides.from, row.fromSlot ?? 0, fromTotal);
        const end = portPoint(row.target, row.sides.to, row.toSlot ?? 0, toTotal);
        const exit = extendFromSide(start, row.sides.from, EDGE_STUB);
        const entry = extendFromSide(end, row.sides.to, EDGE_STUB);
        const route = buildRoutePoints(exit, entry, row.sides.from, row.sides.to);
        const corridor = detectCorridor(exit, entry, row.sides.from, row.sides.to, route);

        row._corridor = corridor;

        const corridorKey = `${corridor.key}:${sourceKey}:${row.sides.from}`;
        if (!corridorGroups.has(corridorKey)) {
            corridorGroups.set(corridorKey, []);
        }
        corridorGroups.get(corridorKey).push(row);

        const sourceKeyBus = `${sourceKey}:${row.sides.from}`;
        if (!sourceGroups.has(sourceKeyBus)) {
            sourceGroups.set(sourceKeyBus, []);
        }
        sourceGroups.get(sourceKeyBus).push(row);
    });

    corridorGroups.forEach((rows) => {
        if (rows.length <= 1) {
            return;
        }

        rows.sort((left, right) => {
            if (left._corridor.sort !== right._corridor.sort) {
                return left._corridor.sort - right._corridor.sort;
            }

            return left.transition.id - right.transition.id;
        });

        rows.forEach((row, index) => {
            const offset = (index - (rows.length - 1) / 2) * EDGE_LANE_SPACING;
            row.lane = mergeLaneOffsets(row.lane, row._corridor.axis, offset);
        });
    });

    sourceGroups.forEach((rows) => {
        if (rows.length <= 1) {
            return;
        }

        rows.sort((left, right) => {
            const dy = left.target.centerY - right.target.centerY;
            if (Math.abs(dy) > 0.001) {
                return dy;
            }

            return left.target.centerX - right.target.centerX;
        });

        const departureAxis = isHorizontalSide(rows[0].sides.from) ? 'y' : 'x';

        rows.forEach((row, index) => {
            const offset = (index - (rows.length - 1) / 2) * EDGE_LANE_SPACING;
            row.lane = mergeLaneOffsets(row.lane, departureAxis, offset);
        });
    });
}

/**
 * @param {Map<number, object>} nodeMeta
 */
function buildTransitionEdges(transitions, nodeMeta, startNode, anchorCache) {
    const portUsage = new Map();
    const loopUsage = new Map();
    const edges = [];

    const planned = transitions
        .map((transition) => {
            const target = nodeMeta.get(transition.to);
            if (!target) {
                return null;
            }

            let source = null;

            if (transition.is_start_edge) {
                source = startNode;
            } else if (transition.from) {
                source = nodeMeta.get(transition.from);
            }

            if (!source) {
                return null;
            }

            const sides = resolveTransitionSides(transition, source, target, anchorCache);
            if (source === target && !transition.anchors?.from_side && !transition.anchors?.to_side
                && !anchorCache?.[transition.id]?.from_side && !anchorCache?.[transition.id]?.to_side) {
                sides.from = 'right';
            }
            // Render older malformed drafts safely: both ends of a self-loop
            // always belong to the same side of the state.
            if (source === target) sides.to = sides.from;

            return { transition, source, target, sides };
        })
        .filter(Boolean);

    planned.forEach((row) => {
        if (row.source === row.target) {
            row.loopIndex = loopUsage.get(row.source.stateId) ?? 0;
            loopUsage.set(row.source.stateId, row.loopIndex + 1);
        }
        const sourceKey = row.source.kind === 'start' ? 'start' : `state-${row.source.stateId}`;
        const targetKey = `state-${row.target.stateId}`;
        const fromKey = `${sourceKey}:${row.sides.from}`;
        const toKey = `${targetKey}:${row.sides.to}`;

        const fromCount = (portUsage.get(fromKey) ?? 0) + 1;
        const toCount = (portUsage.get(toKey) ?? 0) + 1;
        portUsage.set(fromKey, fromCount);
        portUsage.set(toKey, toCount);

        row.fromSlot = fromCount - 1;
        row.toSlot = toCount - 1;
        row.fromTotal = fromCount;
        row.toTotal = toCount;
    });

    const fromTotals = new Map();
    const toTotals = new Map();

    planned.forEach((row) => {
        const sourceKey = row.source.kind === 'start' ? 'start' : `state-${row.source.stateId}`;
        const targetKey = `state-${row.target.stateId}`;
        fromTotals.set(`${sourceKey}:${row.sides.from}`, Math.max(fromTotals.get(`${sourceKey}:${row.sides.from}`) ?? 0, row.fromTotal));
        toTotals.set(`${targetKey}:${row.sides.to}`, Math.max(toTotals.get(`${targetKey}:${row.sides.to}`) ?? 0, row.toTotal));
    });

    planned.forEach((row) => {
        const sourceKey = row.source.kind === 'start' ? 'start' : `state-${row.source.stateId}`;
        const targetKey = `state-${row.target.stateId}`;
        fromTotals.set(`${sourceKey}:${row.sides.from}`, Math.max(fromTotals.get(`${sourceKey}:${row.sides.from}`) ?? 0, row.fromTotal));
        toTotals.set(`${targetKey}:${row.sides.to}`, Math.max(toTotals.get(`${targetKey}:${row.sides.to}`) ?? 0, row.toTotal));
    });

    assignEdgeLanes(planned);

    planned.forEach((row) => {
        const sourceKey = row.source.kind === 'start' ? 'start' : `state-${row.source.stateId}`;
        const targetKey = `state-${row.target.stateId}`;
        const fromTotal = fromTotals.get(`${sourceKey}:${row.sides.from}`) ?? 1;
        const toTotal = toTotals.get(`${targetKey}:${row.sides.to}`) ?? 1;

        const start = portPoint(row.source, row.sides.from, row.fromSlot, fromTotal);
        const end = portPoint(row.target, row.sides.to, row.toSlot, toTotal);
        const geometry = row.source === row.target
            ? buildSelfLoopEdgePath(row.source, start, end, row.sides.from, row.sides.to, row.loopIndex)
            : buildOrthogonalEdgePath(start, end, row.sides.from, row.sides.to, row.lane ?? null);

        edges.push({
            id: row.transition.id,
            slug: row.transition.slug,
            label: row.transition.label,
            from: row.transition.from,
            to: row.transition.to,
            is_start_edge: Boolean(row.transition.is_start_edge),
            fromSide: row.sides.from,
            toSide: row.sides.to,
            path: geometry.path,
            hitPath: geometry.hitPath,
            arrowPoints: geometry.arrowPoints,
            labelX: geometry.labelX,
            labelY: geometry.labelY,
            start: geometry.start,
            end: geometry.end,
            bounds: geometry.bounds ?? null,
        });
    });

    return edges;
}

/**
 * @param {Array<{x:number,y:number,width:number,height:number}>} nodes
 */
function computeBounds(nodes, edges = []) {
    let maxX = 360;
    let maxY = 220;

    nodes.forEach((node) => {
        maxX = Math.max(maxX, node.x + node.width + PADDING);
        maxY = Math.max(maxY, node.y + node.height + PADDING);
    });

    edges.forEach((edge) => {
        if (!edge.bounds) return;
        maxX = Math.max(maxX, edge.bounds.x + edge.bounds.width + PADDING);
        maxY = Math.max(maxY, edge.bounds.y + edge.bounds.height + PADDING);
    });

    return { width: maxX, height: maxY };
}

/**
 * @param {Array<{ kind: string, stateId?: number, ports?: Record<string, {x:number,y:number,side:string}> }>} nodes
 * @param {{ x: number, y: number }} point
 * @param {number} [radius=18]
 */
export function findPortTarget(nodes, point, radius = 18) {
    let best = null;
    let bestDistance = radius;

    nodes.forEach((node) => {
        if (!node.ports) {
            return;
        }

        Object.entries(node.ports).forEach(([side, port]) => {
            const distance = Math.hypot(port.x - point.x, port.y - point.y);
            if (distance > bestDistance) {
                return;
            }

            bestDistance = distance;
            best = {
                nodeKey: node.key,
                kind: node.kind,
                stateId: node.stateId ?? null,
                side,
                port,
            };
        });
    });

    return best;
}
