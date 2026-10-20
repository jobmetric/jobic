/**
 * Draft workflow utilities for reservation flow editor.
 */

const TEMP_PREFIXES = ['tmp-state-', 'tmp-transition-', 'tmp-task-'];

export function isTempId(id) {
    const key = String(id ?? '');

    return TEMP_PREFIXES.some((prefix) => key.startsWith(prefix));
}

let tempCounter = 0;

export function createTempId(prefix) {
    tempCounter += 1;

    return `${prefix}${Date.now()}-${tempCounter}`;
}

export function cloneDeep(value) {
    return JSON.parse(JSON.stringify(value));
}

/** Updating one endpoint must keep the other endpoint's saved port. */
export function mergeTransitionChange(transition, change) {
    const merged = {
        ...transition,
        ...change,
        ...(change.anchors ? {anchors: {...(transition.anchors ?? {}), ...change.anchors}} : {}),
    };

    if (merged.from != null && merged.from === merged.to) {
        // A reflexive transition has one movable side, even when an older draft
        // or an external caller supplies only one of its two anchors.
        const side = change.anchors?.from_side ?? (transition.from === transition.to
            ? change.anchors?.to_side : null) ?? merged.anchors?.from_side
            ?? merged.anchors?.to_side ?? 'right';
        merged.anchors = {from_side: side, to_side: side};
    }

    return merged;
}

function normalizePosition(position) {
    if (
        position == null
        || typeof position !== 'object'
        || !Number.isFinite(Number(position.x))
        || !Number.isFinite(Number(position.y))
    ) {
        return null;
    }

    return {
        x: Math.round(Number(position.x)),
        y: Math.round(Number(position.y)),
    };
}

function normalizeTask(task) {
    return {
        id: String(task.id),
        driver: String(task.driver ?? ''),
        config: cloneDeep(task.config ?? {}),
        status: Boolean(task.status),
        ordering: Number(task.ordering ?? 0),
        type: String(task.type ?? ''),
    };
}

function normalizeState(state) {
    return {
        id: String(state.id),
        name: String(state.name ?? '').trim(),
        status: String(state.status ?? ''),
        is_terminal: Boolean(state.is_terminal),
        is_start: Boolean(state.is_start),
        position: normalizePosition(state.position),
    };
}

function normalizeTransition(transition) {
    const tasks = (transition.tasks ?? [])
        .map(normalizeTask)
        .sort((left, right) => left.id.localeCompare(right.id));

    return {
        id: String(transition.id),
        from: transition.from == null || transition.from === 'start' ? null : String(transition.from),
        to: String(transition.to),
        name: String(transition.name ?? transition.label ?? '').trim(),
        slug: String(transition.slug ?? '').trim(),
        anchors: transition.anchors?.from_side && transition.anchors?.to_side
            ? {
                from_side: transition.anchors.from_side,
                to_side: transition.anchors.to_side,
            }
            : null,
        is_start_edge: Boolean(transition.is_start_edge),
        tasks,
    };
}

export function normalizeWorkflow(states = [], transitions = []) {
    return {
        states: states.map(normalizeState).sort((left, right) => left.id.localeCompare(right.id)),
        transitions: transitions.map(normalizeTransition).sort((left, right) => left.id.localeCompare(right.id)),
    };
}

export function workflowsEqual(leftStates, leftTransitions, rightStates, rightTransitions) {
    const left = JSON.stringify(normalizeWorkflow(leftStates, leftTransitions));
    const right = JSON.stringify(normalizeWorkflow(rightStates, rightTransitions));

    return left === right;
}

export function extractFlowEntities(payload) {
    const flow = payload?.flow ?? {};

    return {
        states: cloneDeep(flow.states ?? []),
        transitions: cloneDeep(flow.transitions ?? []),
    };
}

export function slugifyLabel(label) {
    return String(label ?? '')
        .trim()
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

export function computeGraphBounds(nodes = [], padding = 80) {
    if (!nodes.length) {
        return {
            minX: 0,
            minY: 0,
            maxX: 360,
            maxY: 220,
            width: 360,
            height: 220,
        };
    }

    let minX = Infinity;
    let minY = Infinity;
    let maxX = -Infinity;
    let maxY = -Infinity;

    nodes.forEach((node) => {
        minX = Math.min(minX, node.x);
        minY = Math.min(minY, node.y);
        maxX = Math.max(maxX, node.x + node.width);
        maxY = Math.max(maxY, node.y + node.height);
    });

    minX -= padding;
    minY -= padding;
    maxX += padding;
    maxY += padding;

    return {
        minX,
        minY,
        maxX,
        maxY,
        width: Math.max(360, maxX - minX),
        height: Math.max(220, maxY - minY),
    };
}

function mapStateId(id, stateIdMap) {
    if (id == null) {
        return null;
    }

    const key = String(id);

    return stateIdMap.get(key) ?? (isTempId(key) ? null : Number(key));
}

function mapTransitionId(id, transitionIdMap) {
    const key = String(id);

    return transitionIdMap.get(key) ?? (isTempId(key) ? null : Number(key));
}

/**
 * Commit draft changes using granular APIs in dependency-safe order.
 */
export async function commitWorkflowDraft({
    placeId,
    originalStates,
    originalTransitions,
    draftStates,
    draftTransitions,
    api,
}) {
    const original = normalizeWorkflow(originalStates, originalTransitions);
    const draft = normalizeWorkflow(draftStates, draftTransitions);
    const stateIdMap = new Map();
    const transitionIdMap = new Map();
    let latestPayload = null;

    async function apply(result) {
        if (result && typeof result === 'object') {
            latestPayload = result;
        }
    }

    const originalStateIds = new Set(original.states.map((row) => row.id));
    const draftStateIds = new Set(draft.states.map((row) => row.id));
    const originalTransitionIds = new Set(original.transitions.map((row) => row.id));
    const draftTransitionIds = new Set(draft.transitions.map((row) => row.id));

    for (const transitionId of originalTransitionIds) {
        if (!draftTransitionIds.has(transitionId) && !isTempId(transitionId)) {
            await apply(await api.destroyTransition(placeId, Number(transitionId)));
        }
    }

    for (const stateId of originalStateIds) {
        if (!draftStateIds.has(stateId) && !isTempId(stateId)) {
            await apply(await api.destroyState(placeId, Number(stateId)));
        }
    }

    for (const state of draft.states) {
        if (!isTempId(state.id)) {
            stateIdMap.set(state.id, Number(state.id));
        }
    }

    for (const state of draft.states) {
        if (!isTempId(state.id)) {
            continue;
        }

        const created = await api.storeState(placeId, {
            name: state.name,
            status: state.status,
            is_terminal: state.is_terminal,
            position: state.position,
        });
        await apply(created);

        const createdState = created?.flow?.states?.find((row) => row.name === state.name && row.status === state.status)
            ?? created?.flow?.states?.slice(-1)[0];

        if (!createdState?.id) {
            throw new Error('Failed to create state');
        }

        stateIdMap.set(state.id, createdState.id);
    }

    for (const state of draft.states) {
        if (isTempId(state.id)) {
            continue;
        }

        const mappedId = stateIdMap.get(state.id);

        if (!mappedId) {
            continue;
        }

        const originalState = original.states.find((row) => row.id === state.id);
        const payload = {};

        if (!originalState || originalState.name !== state.name) {
            payload.name = state.name;
        }

        if (!originalState || originalState.is_terminal !== state.is_terminal) {
            payload.is_terminal = state.is_terminal;
        }

        if (!originalState || JSON.stringify(originalState.position) !== JSON.stringify(state.position)) {
            if (state.position != null) {
                payload.position = state.position;
            }
        }

        if (Object.keys(payload).length > 0) {
            await apply(await api.updateState(placeId, mappedId, payload));
        }
    }

    for (const transition of draft.transitions) {
        if (!isTempId(transition.id)) {
            transitionIdMap.set(transition.id, Number(transition.id));
        }
    }

    for (const transition of draft.transitions) {
        if (!isTempId(transition.id)) {
            continue;
        }

        const from = mapStateId(transition.from, stateIdMap);
        const to = mapStateId(transition.to, stateIdMap);

        if (to == null) {
            throw new Error('Invalid transition destination');
        }

        const created = await api.storeTransition(placeId, {
            name: transition.name,
            slug: transition.slug || undefined,
            from,
            to,
            anchors: transition.anchors ?? undefined,
        });
        await apply(created);

        const createdTransition = created?.flow?.transitions?.find((row) => (
            String(row.from ?? null) === String(from ?? null)
            && String(row.to) === String(to)
        )) ?? created?.flow?.transitions?.slice(-1)[0];

        if (!createdTransition?.id) {
            throw new Error('Failed to create transition');
        }

        transitionIdMap.set(transition.id, createdTransition.id);
    }

    for (const transition of draft.transitions) {
        if (isTempId(transition.id)) {
            continue;
        }

        const mappedId = transitionIdMap.get(transition.id);

        if (!mappedId) {
            continue;
        }

        const originalTransition = original.transitions.find((row) => row.id === transition.id);
        const body = {};

        const mappedFrom = mapStateId(transition.from, stateIdMap);
        const mappedTo = mapStateId(transition.to, stateIdMap);

        if (!originalTransition || String(originalTransition.from ?? null) !== String(transition.from ?? null)) {
            body.from = mappedFrom;
        }

        if (!originalTransition || originalTransition.to !== transition.to) {
            body.to = mappedTo;
        }

        if (transition.name !== (originalTransition?.name ?? '')) {
            body.name = transition.name;
        }

        if (transition.slug !== (originalTransition?.slug ?? '')) {
            body.slug = transition.slug;
        }

        if (JSON.stringify(transition.anchors) !== JSON.stringify(originalTransition?.anchors ?? null)) {
            body.anchors = transition.anchors;
        }

        if (Object.keys(body).length > 0) {
            await apply(await api.updateTransition(placeId, mappedId, body));
        }
    }

    for (const transition of draft.transitions) {
        const mappedTransitionId = transitionIdMap.get(transition.id);

        if (!mappedTransitionId) {
            continue;
        }

        const originalTransition = original.transitions.find((row) => row.id === transition.id);
        const originalTasks = new Map((originalTransition?.tasks ?? []).map((task) => [String(task.id), task]));
        const draftTasks = new Map((transition.tasks ?? []).map((task) => [String(task.id), task]));

        for (const [taskId] of originalTasks) {
            if (!draftTasks.has(taskId) && !isTempId(taskId)) {
                await apply(await api.destroyTask(placeId, mappedTransitionId, Number(taskId)));
            }
        }

        for (const [taskId, task] of draftTasks) {
            if (isTempId(taskId)) {
                await apply(await api.storeTask(placeId, mappedTransitionId, {
                    driver: task.driver,
                    config: task.config ?? {},
                    status: task.status ?? true,
                }));
            }
        }

        for (const [taskId, task] of draftTasks) {
            if (isTempId(taskId)) {
                continue;
            }

            const originalTask = originalTasks.get(taskId);
            const payload = {};

            if (JSON.stringify(task.config ?? {}) !== JSON.stringify(originalTask?.config ?? {})) {
                payload.config = task.config ?? {};
            }

            if (Boolean(task.status) !== Boolean(originalTask?.status)) {
                payload.status = task.status;
            }

            if (Number(task.ordering ?? 0) !== Number(originalTask?.ordering ?? 0)) {
                payload.ordering = task.ordering;
            }

            if (Object.keys(payload).length > 0) {
                await apply(await api.updateTask(placeId, mappedTransitionId, Number(taskId), payload));
            }
        }
    }

    return latestPayload;
}

export function defaultStatePlacement(states = [], viewport = null) {
    if (viewport) {
        return {
            x: Math.max(0, Math.round(viewport.centerX - 100)),
            y: Math.max(0, Math.round(viewport.centerY - 34)),
        };
    }

    const maxX = states.reduce((acc, row) => Math.max(acc, Number(row.position?.x ?? 0)), 0);

    return {
        x: Math.max(28, maxX + 220),
        y: 28 + (states.length % 4) * 96,
    };
}

export function fitViewportToGraph({
    graphBounds,
    viewportWidth,
    viewportHeight,
    padding = 72,
    zoomMin = 0.55,
    zoomMax = 1.6,
}) {
    const innerWidth = Math.max(1, viewportWidth - padding * 2);
    const innerHeight = Math.max(1, viewportHeight - padding * 2);
    const zoom = Math.min(
        zoomMax,
        Math.max(zoomMin, Math.min(innerWidth / graphBounds.width, innerHeight / graphBounds.height)),
    );
    const panX = ((viewportWidth - graphBounds.width * zoom) / 2) - (graphBounds.minX * zoom);
    const panY = ((viewportHeight - graphBounds.height * zoom) / 2) - (graphBounds.minY * zoom);

    return { panX, panY, zoom };
}

export function graphViewportFromPanZoom({ panX, panY, zoom, viewportWidth, viewportHeight }) {
    const safeZoom = Math.max(zoom, 0.01);

    return {
        x: -panX / safeZoom,
        y: -panY / safeZoom,
        w: viewportWidth / safeZoom,
        h: viewportHeight / safeZoom,
    };
}

export function panToGraphPoint({ panX, panY, zoom, viewportWidth, viewportHeight, graphX, graphY }) {
    const safeZoom = Math.max(zoom, 0.01);

    return {
        panX: (viewportWidth / 2) - (graphX * safeZoom),
        panY: (viewportHeight / 2) - (graphY * safeZoom),
    };
}
