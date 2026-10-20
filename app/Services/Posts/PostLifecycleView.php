<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Support\AdminAccess;
use App\Support\PostWorkflowPermissions;
use Illuminate\Support\Facades\DB;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostLocaleLock;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Models\PostScheduledAction;
use JobMetric\Post\Models\PostWorkflowDefinition;

/** Assemble each active language's immutable versions for the editing form. */
final readonly class PostLifecycleView
{
    /**
     * Read the active definition, independent language streams and pending actions.
     *
     * @param Post $post
     * @param string $locale
     *
     * @return array<string, mixed>|null
     */
    public function for(Post $post, string $locale): ?array
    {
        $definition = $this->activeDefinition((string) $post->type);
        if (! $definition) {
            return null;
        }

        $stream = PostPublicationStream::query()
            ->where('post_id', $post->id)
            ->where('locale', PostPublicationStream::SHARED)
            ->first();

        $schedules = $stream ? PostScheduledAction::query()
            ->where('stream_id', $stream->id)
            ->whereIn('state', [
                'pending',
                'paused',
            ])
            ->orderBy('due_at')
            ->get() : collect();

        $states = collect($definition->graph['states'])->keyBy('id');
        $permissions = app(PostWorkflowPermissions::class)->for((string) $post->type);
        $state = $states->firstWhere('status', $stream?->status);
        $transitions = $stream ? collect($definition->graph['transitions'])
            ->filter(fn (array $transition): bool => ($states->get($transition['from'])['status'] ?? null) === $stream->status)
            ->values()
            ->all() : [];

        return [
            'definition' => $definition,
            'editable' => app(AdminAccess::class)->allows(request()->user(), $permissions['manage']),
            'stream' => $stream,
            'state' => $state,
            'transitions' => $transitions,
            'schedules' => $schedules,
            'locks' => PostLocaleLock::where('post_id', $post->id)
                ->where('locked', true)
                ->pluck('locale')
                ->all(),
        ];
    }

    /**
     * Read only the currently activated definition of one post type.
     *
     * @param string $type
     *
     * @return PostWorkflowDefinition|null
     */
    public function activeDefinition(string $type): ?PostWorkflowDefinition
    {
        $workflow = DB::table(config('post.tables.workflow', 'post_workflows'))
            ->where('type', $type)
            ->first();
        $definition = $workflow?->active_definition_id ? PostWorkflowDefinition::find($workflow->active_definition_id) : null;

        return $definition?->enabled ? $definition : null;
    }
}
