<?php

declare(strict_types=1);

namespace App\Services\Posts;

use App\Facades\Hooks;
use App\Rules\EditorContentRule;
use App\Services\Editor\EditorDocuments;
use App\Services\History\RecordHistory;
use App\Services\Taxonomies\TaxonomyEditLocks;
use App\Support\EditorDriverRegistry;
use App\Support\RecordVersion;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Post\Exceptions\PostStatusTransitionNotAllowedException;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostLocaleLock;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Services\PostStreamWorkflow;
use JobMetric\Post\Services\PostWorkflowDefinitions;
use Throwable;

/**
 * Apply CMS edit policies, editor validation and audit hooks around package persistence.
 */
final class Posts
{
    /**
     * Save validated CMS content through the reusable post package.
     *
     * @param string $type
     * @param array<string, mixed> $data
     * @param Post|null $post
     * @param Authenticatable|null $actor
     *
     * @return Response
     * @throws Throwable
     */
    public function save(string $type, array $data, ?Post $post, ?Authenticatable $actor): Response
    {
        $post = DB::transaction(function () use ($data, $type, $post, $actor): Post {
            app(PostWorkflowDefinitions::class)->lock($type);
            if ($post) {
                $post = Post::query()
                    ->where('type', $type)
                    ->lockForUpdate()
                    ->findOrFail($post->id);

                if (PostTypeRegistry::for($type)
                    ->hasOptimisticLocking()) {
                    abort_unless(app(TaxonomyEditLocks::class)->canSave('posts:' . $type, (int) $post->id, (int) $actor?->getAuthIdentifier(), (string) ($data['_edit_lock'] ?? '')), 423, trans('posts.edit_lock_lost'));
                    abort_unless(hash_equals(RecordVersion::token($post, 'posts:' . $type), (string) ($data['_version'] ?? '')), 409, trans('posts.conflict'));
                }
            }

            Hooks::lifecycle('jobic.posts.saving', [
                'type'    => $type,
                'post_id' => $post?->id,
                'user_id' => $actor?->getAuthIdentifier(),
                'fields'  => array_keys($data),
            ]);

            $historyBefore = $this->historySnapshot($post);
            if ($post && app(PostLifecycleView::class)->activeDefinition($type)) {
                if ($post->status !== 'editable') {
                    throw ValidationException::withMessages(['status' => [__('post::lifecycle.not_editable')]]);
                }
                foreach (array_keys((array) ($data['translation'] ?? [])) as $language) {
                    if (PostLocaleLock::where('post_id', $post->id)
                        ->where('locale', $language)
                        ->where('locked', true)
                        ->exists()) {
                        throw ValidationException::withMessages(['translation.' . $language => [__('post::lifecycle.language_locked')]]);
                    }
                }
            }
            $documents = app(EditorDocuments::class);

            $documentsBefore = $post ? $documents->snapshot($post) : [];
            $postBefore = $post?->snapshot();
            $lastRevision = (int) $post?->revisions()
                ->max('id');

            $contentChanges = [];
            $isNew = $post === null;
            $translations = (array) ($data['translation'] ?? []);
            foreach ($translations as $locale => $fields) {
                foreach ($fields as $field => $value) {
                    if ($field !== 'content' || ($value !== '' && $value !== null && ! is_array($value) && ! is_string($value))) {
                        continue;
                    }

                    $content = is_string($value) ? json_decode($value, true) : $value;
                    if (is_array($content)) {
                        $content = Hooks::filter('jobic.editor.content', $content, [
                            'type'    => $type,
                            'locale'  => $locale,
                            'post_id' => $post?->id,
                        ]);

                        $validator = Validator::make([
                            'content' => $content,
                        ], [
                            'content' => [new EditorContentRule(app(EditorDriverRegistry::class), $documents->read($post, $locale))],
                        ]);

                        if ($validator->fails()) {
                            throw ValidationException::withMessages($validator->errors()
                                ->toArray());
                        }
                    }

                    $contentChanges[$locale] = $documents->normalize($value === '' || $value === null ? null : $content, $documents->read($post, $locale));
                    unset($translations[$locale]['content']);
                }
            }

            $data['translation'] = $translations;
            $clearPassword = (bool) ($data['clear_password'] ?? false);
            unset($data['clear_password']);
            if ($clearPassword) {
                $data['password'] = null;
            } else {
                if (($data['password'] ?? null) === null || $data['password'] === '') {
                    unset($data['password']);
                }
            }

            try {
                $service = app(\JobMetric\Post\Post::class);
                $result = $isNew ? $service->store($type, $data, $actor) : $service->update($type, (int) $post->id, $data, $actor);
                $post = $result->data['post'];
            } catch (PostStatusTransitionNotAllowedException) {
                throw ValidationException::withMessages([
                    'status' => [trans('posts.workflow_status')],
                ]);
            }

            foreach ($contentChanges as $locale => $content) {
                $documents->save($post, $locale, $content);
            }

            app(PostPublicationProjection::class)->capture($post, array_values(array_unique([
                ...array_keys($translations),
                ...array_keys($contentChanges),
            ])), $actor, (string) ($data['status'] ?? $post->status));
            if (isset($data['workflow_transition'])) {
                $stream = PostPublicationStream::where('post_id', $post->id)
                    ->where('locale', PostPublicationStream::SHARED)
                    ->firstOrFail();
                try {
                    app(PostStreamWorkflow::class)->transition($post, PostPublicationStream::SHARED, (string) $data['workflow_transition'], (int) $stream->draft_revision_id, (int) $stream->version, $actor, isset($data['workflow_due_at']) ? Carbon::parse($data['workflow_due_at']) : null);
                } catch (\JobMetric\Flow\Exceptions\TaskRestrictionException $error) {
                    abort_if(in_array($error->getMessage(), [trans('post::lifecycle.role_denied'), trans('post::lifecycle.permission_denied')], true), 403, $error->getMessage());
                    throw ValidationException::withMessages(['workflow_transition' => [$error->getMessage()]]);
                } catch (PostStatusTransitionNotAllowedException) {
                    throw ValidationException::withMessages(['workflow_transition' => [trans('posts.workflow_status')]]);
                }
            }

            if (! $isNew) {
                $revision = $post->revisions()
                    ->where('id', '>', $lastRevision)
                    ->latest('id')
                    ->first();
                if (! $revision && $documentsBefore !== $documents->snapshot($post)) {
                    $revision = $post->revisions()
                        ->create(['snapshot' => $postBefore]);
                }

                if ($revision) {
                    $documents->capture($revision, $documentsBefore);
                }
            }

            app(RecordHistory::class)->record('posts:' . $type, (int) $post->id, (int) $actor?->getAuthIdentifier(), $isNew ? 'create' : 'update', $historyBefore, $this->historySnapshot($post));

            return $post;
        });

        Hooks::notify('jobic.posts.saved', [
            'type'    => $type,
            'post_id' => $post->id,
            'user_id' => $actor?->getAuthIdentifier(),
            'fields'  => array_keys($data),
        ]);

        return Response::make(true, trans('posts.saved'), [
            'post' => $post,
        ]);
    }

    /**
     * Refresh media references on the post's independent editor documents.
     *
     * @param Post $post
     *
     * @return void
     * @throws Throwable
     */
    public function syncEditorMedia(Post $post): void
    {
        $documents = app(EditorDocuments::class);

        foreach (
            $documents->query($post)
                ->get() as $document) {
            $documents->syncMedia($document);
        }
    }

    /**
     * Select public aggregate state for the shared CMS change history.
     *
     * @param Post|null $post
     *
     * @return array<string, mixed>
     */
    public function historySnapshot(?Post $post): array
    {
        if (! $post) {
            return [];
        }

        $snapshot = $post->snapshot();
        $attributes = $snapshot['attributes'] ?? [];

        return [
            'translation'      => $snapshot['translation'] ?? [],
            'editor_documents' => collect(app(EditorDocuments::class)->snapshot($post))
                ->mapWithKeys(static fn (array $document): array => [$document['field'] . ':' . $document['locale'] => hash('sha256', json_encode($document['content'], JSON_THROW_ON_ERROR))])
                ->all(),
            'metadata'         => $snapshot['metadata'] ?? [],
            'taxonomies'       => $snapshot['taxonomies'] ?? [],
            'media'            => $snapshot['files'] ?? [],
            'status'           => $attributes['status'] ?? null,
            'published_at'     => $attributes['published_at'] ?? null,
            'comment_status'   => $attributes['comment_status'] ?? null,
            'slug'             => $attributes['slug'] ?? null,
        ];
    }
}
