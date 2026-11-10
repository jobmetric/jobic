<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Models\User;
use App\Support\LocaleCatalog;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JobMetric\Flow\DTO\TransitionResult;
use JobMetric\Flow\Support\FlowTaskContext;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Support\WorkflowGraph;
use JobMetric\Post\Tasks\RecordTransition;
use JobMetric\Post\Tasks\RequireApprovedRevision;
use JobMetric\Post\Tasks\RequireContent;
use JobMetric\Post\Tasks\RequireExcerpt;
use JobMetric\Post\Tasks\RequirePermission;
use JobMetric\Rolix\Models\Role;

/** Inspect a draft workflow without running transitions, actions, or database writes. */
final class WorkflowSimulation
{
    /**
     * @param array<string, mixed> $graph
     * @param array<string, mixed> $scenario
     *
     * @return array<string, mixed>
     */
    public function inspect(array $graph, array $scenario, User $actor): array
    {
        $graph = app(WorkflowGraph::class)->validate($graph, false);
        if (! in_array($scenario['locale'] ?? null, LocaleCatalog::codes(), true)) {
            throw ValidationException::withMessages(['scenario.locale' => [__('workflow.simulation_invalid_locale')]]);
        }

        $states = collect($graph['states'])->keyBy('id');

        $stateId = (string) $scenario['state_id'];
        if (! $states->has($stateId)) {
            throw ValidationException::withMessages(['scenario.state_id' => [__('workflow.simulation_invalid_state')]]);
        }

        $role = isset($scenario['role_id']) ? Role::query()
            ->where('type', 'system')
            ->find($scenario['role_id']) : null;

        if (isset($scenario['role_id']) && ! $role) {
            throw ValidationException::withMessages(['scenario.role_id' => [__('workflow.simulation_invalid_role')]]);
        }

        $transitions = collect($graph['transitions'])
            ->where('from', $stateId)
            ->map(function (array $edge) use ($states, $scenario, $actor, $role): array {
                $checks = [];
                $roleIds = $role ? collect([$role])->merge($role->ancestors())->pluck('id')->map(fn ($id) => (int) $id)->all()
                    : app(\JobMetric\Post\Services\WorkflowTransitionAccess::class)->roleIds($actor);
                if (! empty($edge['roles'])) {
                    $allowed = array_intersect(array_map('intval', $edge['roles']), $roleIds) !== [];
                    $checks[] = ['title' => __('kanban.transition_roles'), 'status' => $allowed ? 'passed' : 'blocked',
                        'message' => $allowed ? __('workflow.simulation_check_passed') : trans('post::lifecycle.role_denied')];
                }
                foreach ($edge['tasks'] as $task) {
                    if (! ($task['status'] ?? true)) {
                        continue;
                    }
                    $checks[] = $this->check($task, $scenario, $actor, $role);
                }

                $statuses = array_column($checks, 'status');
                $status = in_array('blocked', $statuses, true) ? 'blocked' : (in_array('unknown', $statuses, true) ? 'unknown' : 'ready');
                $target = $states->get($edge['to']);

                return [
                    'id'             => $edge['id'],
                    'label'          => $edge['label'],
                    'to'             => $edge['to'],
                    'to_name'        => $target['translations'][$scenario['locale']]['name'] ?? $target['name'],
                    'content_status' => $target['content_status'] ?? $target['status'],
                    'terminal'       => (bool) $target['is_terminal'],
                    'status'         => $status,
                    'checks'         => $checks,
                ];
            })
            ->values()
            ->all();

        return [
            'state'       => [
                'id'       => $stateId,
                'name'     => $states[$stateId]['translations'][$scenario['locale']]['name'] ?? $states[$stateId]['name'],
                'terminal' => (bool) $states[$stateId]['is_terminal'],
            ],
            'role'        => $role?->name ?? __('workflow.simulation_current_user'),
            'transitions' => $transitions,
        ];
    }

    /** @param array<string, mixed> $task @param array<string, mixed> $scenario */
    private function check(array $task, array $scenario, User $actor, ?Role $role): array
    {
        $driver = $task['driver'];
        $config = $task['config'] ?? [];
        $title = __($driver::definition()->title);

        if ($driver === RecordTransition::class) {
            return compact('title') + [
                    'status'  => 'action',
                    'message' => __('workflow.simulation_action_skipped'),
                ];
        }

        if ($driver === RequireApprovedRevision::class) {
            $approved = (bool) ($scenario['approved'] ?? false);

            return compact('title') + [
                    'status'  => $approved ? 'passed' : 'blocked',
                    'message' => $approved ? __('workflow.simulation_check_passed') : trans('post::lifecycle.approval_required'),
                ];
        }

        if ($driver === RequirePermission::class) {
            $roleIds = is_array($config['roles'] ?? null) ? array_map('intval', $config['roles']) : [];
            if ($roleIds !== []) {
                $actorRoleIds = $role ? collect([$role])->merge($role->ancestors())->pluck('id')->map(static fn ($id): int => (int) $id)->all()
                    : app(PostStateAccess::class)->effectiveSystemRoleIdsFor($actor);
                $allowed = array_intersect($roleIds, $actorRoleIds) !== [];
                $message = trans('post::lifecycle.role_denied');
            } else {
                $permission = $config['permission'] ?? null;
                $allowed = is_string($permission) && $permission !== ''
                    && Gate::forUser($actor)->allows($permission, new PostPublicationStream);
                $message = trans('post::lifecycle.permission_denied');
            }

            return compact('title') + [
                    'status'  => $allowed ? 'passed' : 'blocked',
                    'message' => $allowed ? __('workflow.simulation_check_passed') : $message,
                ];
        }

        if ($driver === RequireContent::class || $driver === RequireExcerpt::class) {
            $context = (new FlowTaskContext(new PostPublicationStream, new TransitionResult, [], $actor))->replaceConfig($config);
            $content = [
                'title'   => (string) ($scenario['title'] ?? ''),
                'excerpt' => (string) ($scenario['excerpt'] ?? ''),
                'content' => (string) ($scenario['body'] ?? ''),
            ];
            $errors = Validator::make(['content' => $content], (new $driver)->rules($context))
                ->errors();

            return compact('title') + [
                    'status'  => $errors->isEmpty() ? 'passed' : 'blocked',
                    'message' => $errors->isEmpty() ? __('workflow.simulation_check_passed') : $errors->first(),
                ];
        }

        // External drivers are never executed during a dry run. Their result remains unknown.
        return compact('title') + [
                'status'  => 'unknown',
                'message' => __('workflow.simulation_unknown_driver'),
            ];
    }

}
