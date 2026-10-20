import assert from 'node:assert/strict';
import test from 'node:test';

import { inspectionEntry, transitionEntry } from '../../resources/js/workflow/utils/workflowSimulationReport.js';

test('each inspection keeps the role and checks from its own step even after later changes', () => {
    const scenario = {title: 'Sample', approved: false};
    const result = {state: {name: 'Draft'}, role: 'Editor', transitions: [{
        id: 'send', label: 'Send', to_name: 'Review', status: 'blocked',
        checks: [{title: 'Approval', status: 'blocked', message: 'Approval required'}],
    }]};
    const first = inspectionEntry({sequence: 1, step: 1, scenario, result, locale: 'فارسی'});
    scenario.title = 'Changed';
    result.role = 'Manager';
    result.transitions[0].checks[0].message = 'Changed';

    assert.equal(first.role, 'Editor');
    assert.equal(first.title, 'Sample');
    assert.equal(first.transitions[0].checks[0].message, 'Approval required');
    assert.equal(first.transitions[0].status, 'blocked');
});

test('the chosen transition records the actor before the next step changes roles', () => {
    const current = {state: {name: 'Review'}, role: 'Editor'};
    const edge = {label: 'Approve', to_name: 'Published'};
    const moved = transitionEntry({sequence: 3, step: 2, current, transition: edge, contentStatus: 'Published'});
    current.role = 'Publisher';

    assert.deepEqual(moved, {sequence: 3, kind: 'transition', step: 2,
        from: 'Review', to: 'Published', label: 'Approve', role: 'Editor', contentStatus: 'Published'});
});
