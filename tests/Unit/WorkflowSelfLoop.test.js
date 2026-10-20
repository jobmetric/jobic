import assert from 'node:assert/strict';
import test from 'node:test';

import { buildReservationFlowDiagram } from '../../resources/js/workflow/utils/reservationFlowDiagramLayout.js';

const states = [{id: 'review', name: 'Review', status: 'review', is_start: true,
    position: {x: 100, y: 100}}];

test('a same-state transition is an oval loop outside the node with a visible arrow', () => {
    const diagram = buildReservationFlowDiagram(states, [
        {id: 'repeat', from: 'review', to: 'review', label: 'Review again'},
    ]);
    const node = diagram.nodes[0];
    const loop = diagram.edges[0];

    assert.equal(loop.fromSide, 'right');
    assert.equal(loop.toSide, 'right');
    assert.match(loop.path, / C /);
    assert.ok(loop.start.y < loop.end.y);
    assert.ok(loop.labelX > node.rightX);
    assert.ok(loop.bounds.x + loop.bounds.width > node.rightX + 40);
    assert.ok(diagram.width > loop.bounds.x + loop.bounds.width);
    assert.ok(loop.arrowPoints);
});

test('a mismatched legacy loop is rendered entirely on its exit side', () => {
    const diagram = buildReservationFlowDiagram(states, [
        {id: 'repeat', from: 'review', to: 'review', label: 'Review again',
            anchors: {from_side: 'bottom', to_side: 'right'}},
    ]);
    const loop = diagram.edges[0];
    const node = diagram.nodes[0];

    assert.equal(loop.fromSide, 'bottom');
    assert.equal(loop.toSide, 'bottom');
    assert.equal(loop.start.y, node.bottomY);
    assert.equal(loop.end.y, node.bottomY);
    assert.match(loop.path, / C /);
});

test('each of the four exit sides draws both loop ends on that side', () => {
    for (const side of ['top', 'right', 'bottom', 'left']) {
        const diagram = buildReservationFlowDiagram(states, [
            {id: `loop-${side}`, from: 'review', to: 'review', label: 'Again',
                anchors: {from_side: side, to_side: side}},
        ]);
        const node = diagram.nodes[0];
        const loop = diagram.edges[0];
        const coordinate = side === 'top' ? ['y', node.topY]
            : side === 'bottom' ? ['y', node.bottomY]
                : side === 'left' ? ['x', node.leftX] : ['x', node.rightX];
        assert.equal(loop.fromSide, side);
        assert.equal(loop.toSide, side);
        assert.equal(loop.start[coordinate[0]], coordinate[1]);
        assert.equal(loop.end[coordinate[0]], coordinate[1]);
        assert.match(loop.path, / C /);
    }
});

test('ordinary transitions still use the existing orthogonal route', () => {
    const diagram = buildReservationFlowDiagram([...states,
        {id: 'done', name: 'Done', status: 'done', position: {x: 450, y: 100}}], [
        {id: 'finish', from: 'review', to: 'done', label: 'Finish'},
    ]);

    assert.doesNotMatch(diagram.edges[0].path, / C /);
});
