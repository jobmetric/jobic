import { computed, ref } from 'vue';

import {
    cloneDeep,
    commitWorkflowDraft,
    createTempId,
    defaultStatePlacement,
    extractFlowEntities,
    isTempId,
    slugifyLabel,
    workflowsEqual,
} from '../utils/reservationFlowDraft.js';

export function useReservationFlowDraft() {
    const originalStates = ref([]);
    const originalTransitions = ref([]);
    const draftStates = ref([]);
    const draftTransitions = ref([]);
    const isSaving = ref(false);
    const saveError = ref('');

    const isDirty = computed(() => !workflowsEqual(
        originalStates.value,
        originalTransitions.value,
        draftStates.value,
        draftTransitions.value,
    ));

    function syncFromPayload(payload) {
        const { states, transitions } = extractFlowEntities(payload);
        originalStates.value = cloneDeep(states);
        originalTransitions.value = cloneDeep(transitions);
        draftStates.value = cloneDeep(states);
        draftTransitions.value = cloneDeep(transitions);
        saveError.value = '';
    }

    function discardDraft() {
        draftStates.value = cloneDeep(originalStates.value);
        draftTransitions.value = cloneDeep(originalTransitions.value);
        saveError.value = '';
    }

    function addState({ name, status, is_terminal = false, position = null, viewport = null }) {
        const id = createTempId('tmp-state-');
        draftStates.value = [
            ...draftStates.value,
            {
                id,
                name: name.trim(),
                status,
                is_terminal: Boolean(is_terminal),
                is_start: false,
                position: position ?? defaultStatePlacement(draftStates.value, viewport),
            },
        ];

        return id;
    }

    function updateState({ stateId, name, is_terminal, status }) {
        draftStates.value = draftStates.value.map((row) => (
            String(row.id) === String(stateId)
                ? {
                    ...row,
                    ...(name !== undefined ? { name: name.trim() } : {}),
                    ...(is_terminal !== undefined ? { is_terminal: Boolean(is_terminal) } : {}),
                    ...(status !== undefined ? { status } : {}),
                }
                : row
        ));
    }

    function duplicateState({ stateId, name }) {
        const source = draftStates.value.find((row) => String(row.id) === String(stateId));

        if (!source) {
            return null;
        }

        const id = createTempId('tmp-state-');
        const offset = 40;
        const position = source.position ?? { x: 0, y: 0 };

        draftStates.value = [
            ...draftStates.value,
            {
                ...cloneDeep(source),
                id,
                name: name?.trim() || source.name,
                is_start: false,
                position: {
                    x: Number(position.x ?? 0) + offset,
                    y: Number(position.y ?? 0) + offset,
                },
            },
        ];

        return id;
    }

    function moveState({ stateId, position }) {
        draftStates.value = draftStates.value.map((row) => (
            String(row.id) === String(stateId)
                ? { ...row, position: { ...position } }
                : row
        ));
    }

    function deleteState({ stateId }) {
        const key = String(stateId);
        draftStates.value = draftStates.value.filter((row) => String(row.id) !== key);
        draftTransitions.value = draftTransitions.value.filter((row) => (
            String(row.from) !== key && String(row.to) !== key
        ));
    }

    function addTransition({ from, to, name, slug, anchors = {} }) {
        const id = createTempId('tmp-transition-');
        const fromId = from === 'start' || from == null ? null : (isTempId(from) ? from : (Number.isFinite(Number(from)) ? Number(from) : from));
        const toId = to == null ? null : (isTempId(to) ? to : (Number.isFinite(Number(to)) ? Number(to) : to));

        draftTransitions.value = [
            ...draftTransitions.value,
            {
                id,
                from: fromId,
                to: toId,
                name: name.trim(),
                label: name.trim(),
                slug: slug?.trim() || slugifyLabel(name),
                anchors,
                is_start_edge: fromId == null,
                tasks: [],
            },
        ];

        return id;
    }

    function updateTransition({ transitionId, name, slug }) {
        draftTransitions.value = draftTransitions.value.map((row) => (
            String(row.id) === String(transitionId)
                ? {
                    ...row,
                    ...(name !== undefined ? { name: name.trim(), label: name.trim() } : {}),
                    ...(slug !== undefined ? { slug: slug.trim() } : {}),
                }
                : row
        ));
    }

    function reconnectTransition({ transitionId, from, to, anchors }) {
        draftTransitions.value = draftTransitions.value.map((row) => {
            if (String(row.id) !== String(transitionId)) {
                return row;
            }

            const next = { ...row };

            if (from !== undefined) {
                next.from = from;
                next.is_start_edge = from == null;
            }

            if (to !== undefined) {
                next.to = to;
            }

            if (anchors) {
                next.anchors = {
                    ...(row.anchors ?? {}),
                    ...anchors,
                };
            }

            return next;
        });
    }

    function deleteTransition({ transitionId }) {
        draftTransitions.value = draftTransitions.value.filter((row) => String(row.id) !== String(transitionId));
    }

    function addTask({ transitionId, driver, config, status = true, type = 'action', title = '', description = '', driver_key = '' }) {
        draftTransitions.value = draftTransitions.value.map((row) => {
            if (String(row.id) !== String(transitionId)) {
                return row;
            }

            const tasks = [...(row.tasks ?? []), {
                id: createTempId('tmp-task-'),
                driver,
                driver_key: driver_key || undefined,
                config: config ?? {},
                status: Boolean(status),
                ordering: (row.tasks?.length ?? 0) + 1,
                type,
                title,
                description,
            }];

            return { ...row, tasks };
        });
    }

    function updateTask({ transitionId, taskId, config, status, ordering }) {
        draftTransitions.value = draftTransitions.value.map((row) => {
            if (String(row.id) !== String(transitionId)) {
                return row;
            }

            const tasks = (row.tasks ?? []).map((task) => (
                String(task.id) === String(taskId)
                    ? {
                        ...task,
                        ...(config !== undefined ? { config } : {}),
                        ...(status !== undefined ? { status } : {}),
                        ...(ordering !== undefined ? { ordering } : {}),
                    }
                    : task
            ));

            return { ...row, tasks };
        });
    }

    function deleteTask({ transitionId, taskId }) {
        draftTransitions.value = draftTransitions.value.map((row) => {
            if (String(row.id) !== String(transitionId)) {
                return row;
            }

            return {
                ...row,
                tasks: (row.tasks ?? []).filter((task) => String(task.id) !== String(taskId)),
            };
        });
    }

    async function commitDraft(placeId, api) {
        if (!placeId || !isDirty.value) {
            return null;
        }

        isSaving.value = true;
        saveError.value = '';

        try {
            const result = await commitWorkflowDraft({
                placeId,
                originalStates: originalStates.value,
                originalTransitions: originalTransitions.value,
                draftStates: draftStates.value,
                draftTransitions: draftTransitions.value,
                api,
            });

            if (result) {
                syncFromPayload(result);
            }

            return result;
        } catch (error) {
            saveError.value = error?.message ?? 'Save failed';
            throw error;
        } finally {
            isSaving.value = false;
        }
    }

    return {
        originalStates,
        originalTransitions,
        draftStates,
        draftTransitions,
        isDirty,
        isSaving,
        saveError,
        syncFromPayload,
        discardDraft,
        addState,
        updateState,
        duplicateState,
        moveState,
        deleteState,
        addTransition,
        updateTransition,
        reconnectTransition,
        deleteTransition,
        addTask,
        updateTask,
        deleteTask,
        commitDraft,
    };
}
