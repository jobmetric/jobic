<?php

declare(strict_types=1);

namespace App\Services\Editor;

use App\Facades\Hooks;
use App\Models\EditorDocument;
use App\Models\EditorDocumentRevision;
use App\Rules\EditorContentRule;
use App\Support\EditorDriverRegistry;
use Illuminate\Container\EntryNotFoundException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JobMetric\Media\Http\Resources\MediaResource;
use JobMetric\Media\Models\Media;
use JobMetric\Media\Models\MediaRelation;
use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Throwable;

/** Persist owner-scoped editor documents and their canonical media usage atomically. */
final class EditorDocuments
{
    /**
     * Scope documents to the supplied owner, never a client-provided document identifier.
     *
     * @param Model $owner
     *
     * @return Builder<EditorDocument>
     */
    public function query(Model $owner): Builder
    {
        return EditorDocument::query()
            ->where('owner_type', $owner->getMorphClass())
            ->where('owner_id', $owner->getKey());
    }

    /**
     * Read the stored JSON string expected by existing form controls.
     *
     * @param Model|null $owner
     * @param string $locale
     * @param string $field
     *
     * @return string|null
     * @throws JsonException
     */
    public function read(?Model $owner, string $locale, string $field = 'content'): ?string
    {
        $document = $owner ? $this->query($owner)
            ->where(compact('locale', 'field'))
            ->first() : null;

        return $document ? json_encode($document->content, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    }

    /**
     * Prepare stored content for authorized editing without changing persisted media URLs.
     *
     * @param Model|null $owner
     * @param string $locale
     * @param string $field
     *
     * @return string|null
     * @throws JsonException
     */
    public function readForEditor(?Model $owner, string $locale, string $field = 'content'): ?string
    {
        $stored = $this->read($owner, $locale, $field);
        if ($stored === null) {
            return null;
        }

        $document = json_decode($stored, true, 512, JSON_THROW_ON_ERROR);
        $this->prepareEditorMedia($document['blocks']);

        return json_encode($document, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Resolve registered media references to the authorized inline endpoint recursively.
     *
     * @param list<array<string, mixed>> $blocks
     *
     * @return void
     * @throws EntryNotFoundException
     * @throws CircularDependencyException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function prepareEditorMedia(array &$blocks): void
    {
        $registry = app(EditorDriverRegistry::class);

        foreach ($blocks as &$block) {
            if (! $registry->has($block['type'])) {
                continue;
            }

            $driver = $registry->get($block['type']);
            foreach ($driver->presentation['mediaPaths'] ?? [] as $path) {
                $multiple = str_ends_with($path, '.*');
                $files = data_get($block['data'], $path, []);
                $files = $multiple ? $files : [$files];
                foreach ($files as &$file) {
                    if (! empty($file['id'])) {
                        $file['url'] = route('admin.media-manager.content', ['media' => $file['id']]);
                    }
                }

                unset($file);
                data_set($block['data'], $multiple ? substr($path, 0, -2) : $path, $multiple ? $files : ($files[0] ?? []));
            }

            if ($driver->clientConfig['config']['nested'] ?? false) {
                foreach ($block['data']['sections'] as &$section) {
                    $this->prepareEditorMedia($section['blocks']);
                }

                unset($section);
            }
        }

        unset($block);
    }

    /**
     * Validate a draft and replace media addresses with file-manager-owned references.
     *
     * @param mixed $content
     * @param mixed $original
     *
     * @return array<string, mixed>
     * @throws JsonException
     */
    public function normalize(mixed $content, mixed $original = null): array
    {
        if ($content === null || $content === '') {
            $content = ['blocks' => []];
        }

        Validator::make(['content' => $content], ['content' => [new EditorContentRule(app(EditorDriverRegistry::class), $original)]])
            ->validate();

        $document = is_string($content) ? json_decode($content, true, 512, JSON_THROW_ON_ERROR) : $content;
        $this->normalizeBlocks($document['blocks']);

        return $document;
    }

    /**
     * Validate media IDs and recursively normalize declared media fields.
     *
     * @param array<int, array<string, mixed>> $blocks
     *
     * @return void
     * @throws CircularDependencyException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws NotFoundExceptionInterface
     */
    private function normalizeBlocks(array &$blocks): void
    {
        $registry = app(EditorDriverRegistry::class);
        foreach ($blocks as &$block) {
            if (! $registry->has($block['type'])) {
                continue;
            }

            $definition = $registry->get($block['type']);
            $group = $definition->clientConfig['config']['media'] ?? ($block['type'] === 'image' ? 'image' : 'file');

            foreach ($definition->presentation['mediaPaths'] ?? [] as $path) {
                $files = data_get($block['data'], $path, []);
                $multiple = str_ends_with($path, '.*');
                $files = $multiple ? $files : [$files];
                $normalized = [];
                foreach ($files as $file) {
                    if ($file === []) {
                        continue;
                    }

                    $id = $file['id'] ?? null;
                    $media = filter_var($id, FILTER_VALIDATE_INT) ? Media::query()
                        ->where('type', 'f')
                        ->find($id) : null;

                    if (! $media || ($group !== 'file' && getMimeGroup($media->mime_type) !== $group)) {
                        throw ValidationException::withMessages(['content' => trans('editor.invalid_media')]);
                    }

                    $resource = (new MediaResource($media))->resolve();
                    $normalized[] = [
                        'id'   => (int) $media->id,
                        'url'  => $resource['src'],
                        'name' => $media->name,
                    ];
                }

                data_set($block['data'], $multiple ? substr($path, 0, -2) : $path, $multiple ? $normalized : ($normalized[0] ?? []));
            }

            if ($definition->clientConfig['config']['nested'] ?? false) {
                foreach ($block['data']['sections'] as &$section) {
                    $this->normalizeBlocks($section['blocks']);
                }

                unset($section);
            }
        }

        unset($block);
    }

    /**
     * Save one language under an already authorized owner inside its transaction.
     *
     * @param Model $owner
     * @param string $locale
     * @param mixed $content
     * @param string $field
     *
     * @return EditorDocument
     * @throws Throwable
     */
    public function save(Model $owner, string $locale, mixed $content, string $field = 'content'): EditorDocument
    {
        if (! $owner->exists) {
            throw new \LogicException('The editor owner must be persisted first.');
        }

        return DB::transaction(function () use ($owner, $locale, $content, $field): EditorDocument {
            $document = $this->query($owner)
                ->firstOrNew(compact('locale', 'field'));

            $document->owner_type = $owner->getMorphClass();
            $document->owner_id = $owner->getKey();
            $document->content = $this->normalize($content, $document->content);

            $context = [
                'owner_type' => $owner->getMorphClass(),
                'owner_id'   => $owner->getKey(),
                'field'      => $field,
                'locale'     => $locale,
                'user_id'    => auth()->id(),
            ];

            Hooks::action('jobic.editor.document.saving', $context);

            $document->save();

            $this->syncMedia($document);

            DB::afterCommit(static fn () => Hooks::notify('jobic.editor.document.saved', $context));

            return $document;
        });
    }

    /**
     * Synchronize only this document's media references without deleting files.
     *
     * @param EditorDocument $document
     *
     * @return void
     * @throws CircularDependencyException
     * @throws ContainerExceptionInterface
     * @throws EntryNotFoundException
     * @throws NotFoundExceptionInterface
     */
    public function syncMedia(EditorDocument $document): void
    {
        $ids = [];
        $unavailable = false;
        $pending = $document->content['blocks'] ?? [];

        $registry = app(EditorDriverRegistry::class);

        while ($pending !== []) {
            $block = array_pop($pending);
            $unavailable = $unavailable || ! $registry->has($block['type']);
            $paths = $registry->has($block['type']) ? ($registry->get($block['type'])->presentation['mediaPaths'] ?? []) : [];

            foreach ($paths as $path) {
                $files = data_get($block['data'], $path, []);

                foreach (str_contains($path, '*') ? $files : [$files] as $file) {
                    if (isset($file['id'])) {
                        $ids[] = (int) $file['id'];
                    }
                }
            }

            foreach ($block['data']['sections'] ?? [] as $section) {
                array_push($pending, ...$section['blocks']);
            }
        }

        $scope = [
            'mediaable_type' => $document->getMorphClass(),
            'mediaable_id'   => $document->id,
            'collection'     => 'editor',
        ];

        if ($unavailable) {
            $ids = array_merge($ids, MediaRelation::query()
                ->where($scope)
                ->pluck('media_id')
                ->all());
        }

        MediaRelation::query()
            ->where($scope)
            ->whereNotIn('media_id', $ids)
            ->delete();

        foreach (array_unique($ids) as $id) {
            MediaRelation::query()
                ->firstOrCreate($scope + ['media_id' => $id]);
        }
    }

    /**
     * Capture owner documents for history or an owner revision.
     *
     * @param Model $owner
     *
     * @return list<array<string, mixed>>
     */
    public function snapshot(Model $owner): array
    {
        return $this->query($owner)
            ->orderBy('field')
            ->orderBy('locale')
            ->get([
                'field',
                'locale',
                'content',
            ])
            ->toArray();
    }

    /**
     * Attach the previous document state to an owner revision.
     *
     * @param Model $revision
     * @param array<int, array<string, mixed>> $documents
     *
     * @return void
     */
    public function capture(Model $revision, array $documents): void
    {
        EditorDocumentRevision::query()
            ->updateOrCreate([
                'revision_type' => $revision->getMorphClass(),
                'revision_id'   => $revision->getKey(),
            ], compact('documents'));
    }

    /**
     * Restore the documents belonging to an owner revision.
     *
     * @param Model $owner
     * @param Model $revision
     *
     * @return void
     * @throws Throwable
     */
    public function restore(Model $owner, Model $revision): void
    {
        $snapshot = EditorDocumentRevision::query()
            ->where('revision_type', $revision->getMorphClass())
            ->where('revision_id', $revision->getKey())
            ->firstOrFail();

        $this->delete($owner);

        foreach ($snapshot->documents as $document) {
            $this->save($owner, $document['locale'], $document['content'], $document['field']);
        }
    }

    /**
     * Remove an owner's documents and their usage references.
     *
     * @param Model $owner
     *
     * @return void
     */
    public function delete(Model $owner): void
    {
        $this->query($owner)
            ->get()->each->delete();
    }
}
