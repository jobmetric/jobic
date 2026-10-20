import assert from 'node:assert/strict';
import test from 'node:test';

import { edgePanStep, nodePositionAtPointer } from '../../resources/js/workflow/utils/workflowNodeDrag.js';
import { mergeTransitionChange } from '../../resources/js/workflow/utils/reservationFlowDraft.js';

test('the grabbed point stays under the pointer through zoom and pan changes', () => {
    const viewport = {left: 80, top: 30, panX: 120, panY: -40, zoom: 0.6};
    const grabOffset = {x: 45, y: 18};
    const pointer = {x: 260, y: 110};
    const first = nodePositionAtPointer(pointer, viewport, grabOffset);

    assert.deepEqual(first, {x: 55, y: 182});
    assert.equal(viewport.left + viewport.panX + (first.x + grabOffset.x) * viewport.zoom, pointer.x);
    assert.equal(viewport.top + viewport.panY + (first.y + grabOffset.y) * viewport.zoom, pointer.y);

    const panned = {...viewport, panX: viewport.panX + 3};
    const next = nodePositionAtPointer(pointer, panned, grabOffset);
    assert.equal(panned.left + panned.panX + (next.x + grabOffset.x) * panned.zoom, pointer.x);
});

test('edge panning begins gently, is bounded, and stops outside the canvas', () => {
    const bounds = {left: 0, top: 0, right: 800, bottom: 600};

    assert.deepEqual(edgePanStep({x: 400, y: 300}, bounds), {x: 0, y: 0});
    assert.equal(edgePanStep({x: 35, y: 300}, bounds).x > 0, true);
    assert.equal(edgePanStep({x: 35, y: 300}, bounds).x < 0.01, true);
    assert.equal(edgePanStep({x: 0, y: 300}, bounds).x, 4);
    assert.equal(edgePanStep({x: 800, y: 300}, bounds).x, -4);
    assert.deepEqual(edgePanStep({x: -1, y: 300}, bounds), {x: 0, y: 0});
});

test('reconnecting either transition endpoint preserves the opposite port', () => {
    const original = {id: 'edge-1', from: 'draft', to: 'review',
        anchors: {from_side: 'right', to_side: 'left'}};
    const movedTarget = mergeTransitionChange(original, {to: 'published', anchors: {to_side: 'top'}});
    assert.deepEqual(movedTarget.anchors, {from_side: 'right', to_side: 'top'});
    assert.equal(movedTarget.to, 'published');

    const movedSource = mergeTransitionChange(movedTarget, {from: 'editing', anchors: {from_side: 'bottom'}});
    assert.deepEqual(movedSource.anchors, {from_side: 'bottom', to_side: 'top'});
    assert.equal(movedSource.from, 'editing');
});

test('a self-loop moves both ends together when its exit side changes', () => {
    const loop = {id: 'loop', from: 'draft', to: 'draft',
        anchors: {from_side: 'right', to_side: 'right'}};

    for (const side of ['top', 'right', 'bottom', 'left']) {
        assert.deepEqual(mergeTransitionChange(loop, {anchors: {from_side: side}}).anchors,
            {from_side: side, to_side: side});
        assert.deepEqual(mergeTransitionChange(loop, {anchors: {to_side: side}}).anchors,
            {from_side: side, to_side: side});
    }
});

test('joining a transition back to its source keeps the exit side', () => {
    const edge = {id: 'edge', from: 'draft', to: 'review',
        anchors: {from_side: 'bottom', to_side: 'left'}};
    const loop = mergeTransitionChange(edge, {to: 'draft', anchors: {to_side: 'top'}});

    assert.deepEqual(loop.anchors, {from_side: 'bottom', to_side: 'bottom'});
});
