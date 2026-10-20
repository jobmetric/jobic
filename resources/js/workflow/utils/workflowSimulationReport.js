/** Keep a plain, immutable account of each dry-run check and chosen transition. */
export function inspectionEntry({sequence, step, scenario, result, locale}) {
    return {
        sequence,
        kind: 'inspection',
        step,
        state: result.state.name,
        role: result.role,
        locale,
        approved: Boolean(scenario.approved),
        title: String(scenario.title ?? '').trim(),
        transitions: result.transitions.map((transition) => ({
            id: transition.id,
            label: transition.label,
            to: transition.to_name,
            status: transition.status,
            checks: transition.checks.map((check) => ({
                title: check.title,
                status: check.status,
                message: check.message,
            })),
        })),
    };
}

export function transitionEntry({sequence, step, current, transition, contentStatus}) {
    return {
        sequence,
        kind: 'transition',
        step,
        from: current.state.name,
        to: transition.to_name,
        label: transition.label,
        role: current.role,
        contentStatus,
    };
}
