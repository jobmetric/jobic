<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Facades\Hooks;
use App\Http\Requests\Posts\WorkflowRequest;
use App\Support\LocaleCatalog;
use App\Support\Permissions\PermissionRegistry;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use JobMetric\Flow\Support\FlowTaskRegistry;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Post\Contracts\WorkflowTaskConfiguration;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Models\PostWorkflowDefinition;
use JobMetric\Post\Services\PostWorkflowDefinitions;
use JobMetric\Post\Support\WorkflowGraph;
use JobMetric\Rolix\Models\Role;
use Throwable;

/** Adapt the package workflow aggregate to Jobic hooks and designer presentation. */
class WorkflowDesigner
{
    /**
     * Read the latest saved draft and registered task configuration fields.
     *
     * @param string $type
     *
     * @return array<string, mixed>
     */
    public function read(string $type): array
    {
        $workflow = DB::table(config('post.tables.workflow', 'post_workflows'))
            ->where('type', $type)
            ->first();

        $definition = $workflow ? PostWorkflowDefinition::where('workflow_id', $workflow->id)
            ->latest('id')
            ->first() : null;

        $tasks = [];
        $locales = collect(LocaleCatalog::all())
            ->mapWithKeys(static fn (array $locale, string $code): array => [
                $code => $locale['name'] ?? strtoupper($code),
            ])
            ->all();

        $permissions = collect(app(PermissionRegistry::class)->labels())
            ->mapWithKeys(static fn (string $label, string $key): array => [$key => __($label)])
            ->all();

        /** @var FlowTaskRegistry $registry */
        $registry = app('FlowTaskRegistry');
        foreach ($registry->forSubject(PostPublicationStream::class) as $category => $drivers) {
            foreach ($drivers as $class => $driver) {
                if (! $driver instanceof WorkflowTaskConfiguration) {
                    continue;
                }

                $metadata = $driver::definition();
                $fields = $driver->configurationFields();

                foreach ($fields as &$field) {
                    $field['label'] = __($field['label']);
                }

                unset($field);

                if (isset($fields['locale'])) {
                    $fields['locale']['type'] = 'select';
                    $fields['locale']['options'] = $locales;
                }

                if (isset($fields['permission'])) {
                    $fields['permission']['type'] = 'select';
                    $fields['permission']['options'] = $permissions;
                }

                $tasks[] = [
                    'class'       => $class,
                    'type'        => $category,
                    'title'       => __($metadata->title),
                    'description' => __($metadata->description ?? ''),
                    'icon'        => $metadata->icon,
                    'fields'      => $fields,
                ];
            }
        }

        return [
            'version'       => (int) ($workflow->version ?? 0),
            'definition_id' => $definition?->id,
            'state'         => $definition?->state,
            'enabled'       => $definition?->enabled ?? false,
            'graph'         => $definition?->graph ?? [
                    'states'      => [
                        [
                            'id'              => 'draft',
                            'status'          => 'draft',
                            'content_status'  => 'editable',
                            'name'            => __('posts.statuses.editable'),
                            'is_start'        => true,
                            'is_terminal'     => false,
                            'allows_approval' => false,
                        ],
                    ],
                    'transitions' => [],
                ],
            'drivers'       => [['tasks' => $tasks]],
            'permissions'   => $permissions,
        ];
    }

    /**
     * Execute a validated draft or activation command with success-only notifications.
     *
     * @param string $type
     * @param array<string, mixed> $data
     * @param Authenticatable $actor
     *
     * @return Response
     * @throws Throwable
     */
    public function save(string $type, array $data, Authenticatable $actor): Response
    {
        $data = dto($data, WorkflowRequest::class);
        if ($data['operation'] === 'simulate') {
            $data['graph'] = app(WorkflowGraph::class)->validate($data['graph'], false);
            $this->validateStateRoles($data['graph']);
            return Response::make(true, '', app(WorkflowSimulation::class)->inspect($data['graph'], $data['scenario'], $actor));
        }

        $definitions = app(PostWorkflowDefinitions::class);
        if ($data['operation'] === 'preview') {
            return Response::make(true, '', $definitions->preview($type, (int) $data['definition_id']));
        }

        if ($data['operation'] === 'draft') {
            $data['graph'] = app(WorkflowGraph::class)->validate($data['graph'], false);
            $this->validateStateRoles($data['graph']);
        } elseif ($data['operation'] === 'activate') {
            $definition = PostWorkflowDefinition::query()->findOrFail((int) $data['definition_id']);
            $this->validateStateRoles($definition->graph);
        }

        $context = [
            'type'      => $type,
            'operation' => $data['operation'],
            'user_id'   => $actor->getAuthIdentifier(),
        ];

        DB::transaction(function () use ($definitions, $type, $data, $actor, $context): void {
            Hooks::lifecycle('jobic.posts.workflow.saving', $context);

            if ($data['operation'] === 'draft') {
                $definitions->saveDraft($type, $data['graph'], (bool) $data['enabled'], (int) $data['version'], $actor);
            } else {
                $definitions->activate($type, (int) $data['definition_id'], $data['fingerprint'], $data['mapping'] ?? [], $actor);
            }

            DB::afterCommit(static fn () => Hooks::notify('jobic.posts.workflow.saved', $context));
        });

        return Response::make(true, __('workflow.saved'), $this->read($type));
    }

    /**
     * Reject state role IDs that do not identify system roles.
     *
     * @param array<string, mixed> $graph
     *
     * @return void
     * @throws ValidationException
     */
    private function validateStateRoles(array $graph): void
    {
        foreach ($graph['states'] ?? [] as $index => $state) {
            $roleIds = $state['view_roles'] ?? [];
            if ($roleIds === []) {
                continue;
            }
            $roleIds = array_values(array_unique(array_map('intval', $roleIds)));
            $validCount = Role::query()->where('type', 'system')->whereIn('id', $roleIds)->count();
            if ($validCount !== count($roleIds)) {
                throw ValidationException::withMessages(['graph.states.'.$index.'.view_roles' => [__('workflow.invalid_state_roles')]]);
            }
        }
    }
}
