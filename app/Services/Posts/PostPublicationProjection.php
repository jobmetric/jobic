<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Services\Editor\EditorDocuments;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Models\PostScheduledAction;
use JobMetric\Post\Services\PostLifecycle;
use JobMetric\Post\Support\PostTypeRegistry;
use JsonException;

/** Project Jobic-owned fields and editor documents into immutable package revisions. */
final readonly class PostPublicationProjection
{
    public function __construct(private EditorDocuments $documents, private PostLifecycle $lifecycle)
    {
    }

    /**
     * Capture changed languages and shared public fields inside the owner's transaction.
     *
     * @param Post $post
     * @param list<string> $locales
     * @param Authenticatable|null $actor
     * @param string|null $legacyStatus
     * @param bool $autoPublish
     *
     * @return void
     * @throws JsonException
     */
    public function capture(Post $post, array $locales, ?Authenticatable $actor, ?string $legacyStatus = null, bool $autoPublish = true): void
    {
        $snapshot = $post->snapshot();

        $fieldsByLocale = [];
        foreach ($snapshot['translation'] as $row) {
            $fieldsByLocale[$row['locale']][$row['field']] = $row['value'];
        }

        $languages = [];
        foreach ($fieldsByLocale as $language => $fields) {
            $content = $this->documents->read($post, $language);
            $fields['content'] = $content ? json_decode($content, true, 512, JSON_THROW_ON_ERROR) : ['blocks' => []];
            $languages[$language] = $fields;
        }

        $primary = $languages[array_key_first($languages)] ?? [];
        $aggregate = [
            'languages' => $languages,
            'shared'    => $this->sharedFields($post, $snapshot),
            'title'     => $primary['title'] ?? '',
            'content'   => $primary['content'] ?? ['blocks' => []],
        ];

        $shared = $this->lifecycle->saveDraft($post, PostPublicationStream::SHARED, $aggregate, $actor);
        if ($this->hasAdvancedWorkflow($post) && $post->status !== $shared->content_status) {
            $post->status = $shared->content_status;
            $post->save();
        }
        $simple = $autoPublish && ! $this->hasAdvancedWorkflow($post);
        $status = $legacyStatus ?? (string) $post->status;

        if ($simple && $shared->draft_revision_id && $status === 'publish' && $shared->live_revision_id !== $shared->draft_revision_id) {
            $this->lifecycle->publish($post, PostPublicationStream::SHARED, (int) $shared->draft_revision_id, $actor);
        }

        if ($simple && $shared->draft_revision_id && $status === 'future' && $post->published_at?->isFuture() && ! PostScheduledAction::where('stream_id', $shared->id)
                ->whereIn('state', [
                    'pending',
                    'paused',
                ])
                ->exists()) {
            $this->lifecycle->schedule($post, PostPublicationStream::SHARED, (int) $shared->draft_revision_id, $post->published_at, $actor);
        }
    }

    /**
     * Select only fields whose live value is shared across languages.
     *
     * @param Post $post
     * @param array<string, mixed> $snapshot
     *
     * @return array<string, mixed>
     */
    public function sharedFields(Post $post, array $snapshot): array
    {
        $builder = app(PostTypeRegistry::class)->for((string) $post->type);

        $shared = [];
        foreach (
            [
                'slug',
                'comment_status',
            ] as $field) {
            if ($builder->getFieldOwner($field) === 'shared') {
                $shared[$field] = $snapshot['attributes'][$field] ?? null;
            }
        }

        foreach ((array) ($snapshot['metadata'] ?? []) as $key => $value) {
            if ($builder->getFieldOwner('metadata.' . $key) === 'shared') {
                $shared['metadata'][$key] = $value;
            }
        }

        foreach (
            [
                'files' => 'media',
                'taxonomies' => 'taxonomies',
            ] as $source => $target) {
            foreach ((array) ($snapshot[$source] ?? []) as $item) {
                $collection = (string) ($item['collection'] ?? 'base');
                if ($builder->getFieldOwner($target . '.' . $collection) === 'shared') {
                    $shared[$target][] = $item;
                }
            }
        }

        return $shared;
    }

    /**
     * Withdraw every live language and cancel unfinished actions before owner deletion.
     *
     * @param Post $post
     * @param Authenticatable|null $actor
     *
     * @return void
     */
    public function withdrawAll(Post $post, ?Authenticatable $actor): void
    {
        $streams = PostPublicationStream::query()
            ->where('post_id', $post->id)
            ->orderBy('id')
            ->get();

        foreach ($streams as $stream) {
            if ($stream->live_revision_id || PostScheduledAction::where('stream_id', $stream->id)
                    ->whereIn('state', [
                        'pending',
                        'paused',
                    ])
                    ->exists()) {
                $this->lifecycle->withdraw($post, $stream->locale, (int) $stream->version, $actor);
            }
        }
    }

    /**
     * Check whether this type has an explicitly activated advanced definition.
     *
     * @param Post $post
     *
     * @return bool
     */
    private function hasAdvancedWorkflow(Post $post): bool
    {
        if (! app(PostTypeRegistry::class)->getOption((string) $post->type, 'workflow-enabled', true)) {
            return false;
        }

        return DB::table(config('post.tables.workflow', 'post_workflows') . ' as workflow')
            ->join(config('post.tables.workflow_definition', 'post_workflow_definitions') . ' as definition', 'definition.id', '=', 'workflow.active_definition_id')
            ->where('workflow.type', $post->type)
            ->where('definition.enabled', true)
            ->exists();
    }

    /**
     * Make the simple editorial path publishable without bypassing advanced review.
     *
     * @param Post $post
     * @param PostPublicationStream $stream
     * @param Authenticatable|null $actor
     *
     * @return void
     */
    private function approveCurrent(Post $post, PostPublicationStream $stream, ?Authenticatable $actor): void
    {
        if ($stream->draft_revision_id && $stream->approved_revision_id !== $stream->draft_revision_id) {
            $this->lifecycle->approve($post, $stream->locale, (int) $stream->draft_revision_id, $actor);
        }
    }
}
