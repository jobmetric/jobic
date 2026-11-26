<?php

declare(strict_types=1);

namespace App\Services\Posts;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Validation\ValidationException;
use JobMetric\Post\Exceptions\PostStatusTransitionNotAllowedException;
use JobMetric\Post\Enums\PostStatusEnum;
use JobMetric\Post\Facades\PostTypeRegistry;
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
        return array_keys($this->availableStatusOptions($post, app()->getLocale()));
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
        return $this->statusOptions($post, $this->workflow->availableStatusOptions($post, $locale), $locale);
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
        return $this->statusOptions($post, $this->workflow->allStatusOptions($post, $locale), $locale);
    }

    /**
     * Restrict status options to the configured allowlist while preserving its order.
     *
     * @param string $type
     * @param array<string, string> $options
     *
     * @return array<string, string>
     */
    public function constrain(string $type, array $options): array
    {
        $allowed = PostTypeRegistry::for($type)->getAllowedStatuses();

        return $allowed === []
            ? $options
            : array_replace(array_intersect_key(array_fill_keys($allowed, ''), $options), array_intersect_key($options, array_flip($allowed)));
    }

    /**
     * Reject a status excluded by the post type before a persistence operation.
     *
     * @param string $type
     * @param string $status
     *
     * @return void
     * @throws ValidationException
     */
    public function validateStatus(string $type, string $status): void
    {
        if ($this->constrain($type, [$status => $status]) === []) {
            throw ValidationException::withMessages(['status' => [trans('validation.in', ['attribute' => trans('posts.status')])]]);
        }
    }

    /**
     * Fall back to standard editorial statuses when no Flow statuses are available.
     *
     * @param Post $post
     * @param array<string, string> $options
     * @param string $locale
     *
     * @return array<string, string>
     */
    private function statusOptions(Post $post, array $options, string $locale): array
    {
        if ($options === []) {
            foreach (PostStatusEnum::cases() as $status) {
                $options[$status->value] = trans('posts.statuses.'.$status->value, [], $locale);
            }
        }

        return $this->constrain((string) $post->type, $this->localize($options));
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
        $this->validateStatus((string) $post->type, $target);
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
