<?php

declare(strict_types=1);

namespace App\Services\Posts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use JobMetric\Post\Exceptions\PostStatusTransitionNotAllowedException;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Services\PostWorkflow as PackagePostWorkflow;

/** Adapt reusable post workflow operations to Jobic translations and form errors. */
final class PostWorkflow
{
    /**
     * Create the application workflow adapter.
     *
     * @param PackagePostWorkflow $workflow
     *
     * @return void
     */
    public function __construct(private readonly PackagePostWorkflow $workflow)
    {
    }

    /**
     * Return statuses reachable from the current state or the start state.
     *
     * @param Post $post
     *
     * @return array<int, string>
     */
    public function availableStatuses(Post $post): array
    {
        return $this->workflow->availableStatuses($post);
    }

    /**
     * Label reachable states with workflow translations and Jobic fallbacks.
     *
     * @param Post $post
     * @param string $locale
     *
     * @return array<string, string>
     */
    public function availableStatusOptions(Post $post, string $locale): array
    {
        return $this->localize($this->workflow->availableStatusOptions($post, $locale));
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
        return $this->localize($this->workflow->allStatusOptions($post, $locale));
    }

    /**
     * Execute a configured transition and convert package errors to form errors.
     *
     * @param Post $post
     * @param string $target
     * @param Authenticatable|null $actor
     * @param bool $isNew
     *
     * @return void
     * @throws ValidationException
     */
    public function transition(Post $post, string $target, ?Authenticatable $actor, bool $isNew = false): void
    {
        try {
            $this->workflow->transition($post, $target, $actor, $isNew);
        } catch (PostStatusTransitionNotAllowedException) {
            throw ValidationException::withMessages(['status' => [trans('posts.workflow_status')]]);
        }
    }

    /**
     * Replace untranslated workflow status keys with Jobic's localized labels.
     *
     * @param array<string, string> $options
     *
     * @return array<string, string>
     */
    private function localize(array $options): array
    {
        foreach ($options as $status => $label) {
            if ($label === $status) $options[$status] = trans('posts.statuses.'.$status);
        }

        return $options;
    }
}
