<?php

declare(strict_types=1);

namespace App\Services\Posts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use JobMetric\Flow\Facades\FlowTransition;
use JobMetric\Flow\Models\Flow;
use JobMetric\Flow\Models\FlowState;
use JobMetric\Post\Models\Post;

/**
 * Resolve and execute status changes through the Flow bound to a post.
 */
final class PostWorkflow
{
    /**
     * Return statuses reachable from the current state or the start state.
     *
     * @param Post $post
     *
     * @return array<int, string>
     */
    public function availableStatuses(Post $post): array
    {
        $flow = $post->exists ? $post->boundFlow() : $post->pickFlow();
        if (! $flow) return [];

        $current = $this->state($flow, (string) $post->status)
            ?? $flow->states()->start()->first();
        if (! $current) return [];

        $ids = $flow->transitions()->where('from', $current->id)->whereNotNull('to')->pluck('to');

        return $flow->states()->whereIn('id', $ids->push($current->id))->whereNotNull('status')
            ->pluck('status')->unique()->values()->all();
    }

    /**
     * Label reachable states with their own Flow translations.
     *
     * @param Post $post
     * @param string $locale
     *
     * @return array<string, string>
     */
    public function availableStatusOptions(Post $post, string $locale): array
    {
        $flow = $post->exists ? $post->boundFlow() : $post->pickFlow();
        if (! $flow) return [];
        $statuses = $this->availableStatuses($post);

        return $flow->states()->whereIn('status', $statuses)->get()->mapWithKeys(
            static fn (FlowState $state): array => [(string) $state->status => $state->getTranslation('name', $locale) ?: trans('posts.statuses.'.$state->status)]
        )->all();
    }

    /**
     * Label every state of the selected post type for list filters and badges.
     *
     * @param Post $post
     * @param string $locale
     *
     * @return array<string, string>
     */
    public function allStatusOptions(Post $post, string $locale): array
    {
        $flow = $post->exists ? $post->boundFlow() : $post->pickFlow();
        if (! $flow) return [];

        return $flow->states()->whereNotNull('status')->get()->mapWithKeys(
            static fn (FlowState $state): array => [(string) $state->status => $state->getTranslation('name', $locale) ?: trans('posts.statuses.'.$state->status)]
        )->all();
    }

    /**
     * Start a new post's flow or move an existing post to a reachable state.
     *
     * @param Post $post
     * @param string $target
     * @param Authenticatable|null $actor
     * @param bool $isNew
     *
     * @return void
     */
    public function transition(Post $post, string $target, ?Authenticatable $actor, bool $isNew = false): void
    {
        $flow = $post->boundFlow();
        if (! $flow) {
            if (Post::typeRegistry()->getOption((string) $post->type, 'workflow') !== null) $this->invalidStatus();
            if ((string) $post->status !== $target) {
                $post->status = $target;
                $post->save();
            }
            return;
        }

        if ($isNew) {
            $start = $flow->states()->start()->first();
            if (! $start) $this->invalidStatus();
            $startTransition = $flow->transitions()->whereNull('from')->where('to', $start->id)->first();
            if (! $startTransition) $this->invalidStatus();
            FlowTransition::runner((int) $startTransition->id, $post, [], $actor);
        }

        if ((string) $post->status === $target) return;

        $from = $this->state($flow, (string) $post->status);
        $to = $this->state($flow, $target);
        if (! $from || ! $to) $this->invalidStatus();
        $transition = $flow->transitions()->where('from', $from->id)->where('to', $to->id)->first();
        if (! $transition) $this->invalidStatus();

        FlowTransition::runner((int) $transition->id, $post, [], $actor);
    }

    /**
     * Find a state by its domain status in one flow.
     *
     * @param Flow $flow
     * @param string $status
     *
     * @return FlowState|null
     */
    private function state(Flow $flow, string $status): ?FlowState
    {
        return $flow->states()->where('status', $status)->first();
    }

    /**
     * Report a transition that the configured flow does not allow.
     *
     * @return never
     */
    private function invalidStatus(): never
    {
        throw ValidationException::withMessages(['status' => [trans('posts.workflow_status')]]);
    }
}
