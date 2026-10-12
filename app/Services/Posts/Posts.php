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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Post\Exceptions\PostStatusTransitionNotAllowedException;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
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

            Hooks::action('jobic.posts.saving', [
                'type'    => $type,
                'post_id' => $post?->id,
                'user_id' => $actor?->getAuthIdentifier(),
                'fields'  => array_keys($data),
            ]);

            $historyBefore = $this->historySnapshot($post);
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
            } elseif (($data['password'] ?? null) === null || $data['password'] === '') {
                unset($data['password']);
            }

            try {
                $service = app(\JobMetric\Post\Post::class);
                $result = $isNew ? $service->store($type, $data, $actor) : $service->update($type, (int) $post->id, $data, $actor);
                $post = $result->data['post'];
            } catch (PostStatusTransitionNotAllowedException) {
                throw ValidationException::withMessages(['status' => [trans('posts.workflow_status')]]);
            }

            foreach ($contentChanges as $locale => $content) {
                $documents->save($post, $locale, $content);
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
