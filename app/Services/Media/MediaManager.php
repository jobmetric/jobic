<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\Facades\Hooks;
use App\Http\Requests\Media\MediaManagerRequest;
use App\Support\AdminAccess;
use App\Support\LocalizedDate;
use App\Support\TaxonomyPermissions;
use App\Support\TaxonomyPresentation;
use Illuminate\Validation\ValidationException;
use JobMetric\Media\Exceptions\FileManagerConflict;
use JobMetric\Media\FileManager;
use JobMetric\Media\Http\Resources\MediaResource;
use JobMetric\Media\Models\Media;
use JobMetric\Media\Models\MediaPath;
use JobMetric\Media\Models\MediaRelation;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry;
use JobMetric\Taxonomy\Models\Taxonomy;
use Throwable;

/** Adapt the package media tree to the reusable administration file manager. */
class MediaManager
{
    /** Execute a validated command and expose safe lifecycle hooks.
     *
     * @param string $operation
     * @param array<string, mixed> $data
     * @param Media|null $item
     *
     * @return Response
     * @throws Throwable
     */
    public function execute(string $operation, array $data, ?Media $item = null): Response
    {
        $data = dto($data, MediaManagerRequest::class);
        $files = app(FileManager::class);
        $context = [
            'operation' => $operation,
            'id'        => $item?->id,
            'user_id'   => auth()->id(),
        ];
        try {
            $required = match ($operation) {
                'upload' => [
                    'file',
                ],
                'folder', 'rename' => [
                    'name',
                ],
                'paste' => [
                    'item_ids',
                    'mode',
                ],
                'compress' => [
                    'item_ids',
                    'name',
                ],
                'extract' => [
                    'destination',
                ],
                default => [],
            };
            foreach ($required as $field) {
                if (! isset($data[$field])) {
                    throw ValidationException::withMessages([$field => __('validation.required', ['attribute' => $field])]);
                }
            }

            Hooks::action('jobic.media.saving', $context);

            $parent = (int) ($data['parent_id'] ?? 0) ?: null;
            $destination = (int) ($data['destination_id'] ?? 0) ?: null;

            $result = match ($operation) {
                'upload' => $files->upload($data['file'], $parent, $data['resolution'] ?? null, (bool) ($data['allow_duplicate'] ?? false), (bool) ($data['confirm_used_replace'] ?? false)),
                'folder' => $files->createFolder($data['name'], $parent),
                'rename' => $files->rename($item, $data['name']),
                'paste' => $files->paste($data['item_ids'], $destination, $data['mode'], $data['resolution'] ?? null, (bool) ($data['confirm_used_replace'] ?? false)),
                'compress' => ['item' => $files->compress($data['item_ids'], $data['name'], $destination)],
                'extract' => $files->extract($item, $data['destination']),
                'destroy' => $this->delete($files, $item ? [$item->id] : ($data['ids'] ?? [])),
                default => throw new \InvalidArgumentException('Unknown media command.'),
            };

            Hooks::notify('jobic.media.saved', $context);

            return Response::make(true, __('media-manager.saved'), $this->serialize($result), in_array($operation, [
                'upload',
                'folder',
            ], true) ? 201 : 200);
        } catch (FileManagerConflict $conflict) {
            $details = $conflict->details;
            $used = $details['destination'] ?? $details['existing'] ?? null;

            if ($used instanceof Media) {
                $details['usages'] = $this->usages($used);
            }

            return Response::make(false, __('media-manager.conflicts.' . $conflict->reason), [
                    'conflict_code' => $conflict->reason,
                ] + $this->serialize($details), 409);
        }
    }

    /** Delete only an explicit nonempty selection through package checks.
     *
     * @param FileManager $files
     * @param array<int, int> $ids
     *
     * @return array<string, mixed>
     * @throws Throwable
     */
    private function delete(FileManager $files, array $ids): array
    {
        if (! $ids) {
            throw ValidationException::withMessages([
                'ids' => __('validation.required', ['attribute' => 'ids']),
            ]);
        }

        $files->delete($ids);

        return [];
    }

    /** Convert package models to the UI's common file contract.
     *
     * @param mixed $value
     *
     * @return mixed
     */
    private function serialize(mixed $value): mixed
    {
        if ($value instanceof Media) {
            return $this->transform($value);
        }

        if (is_array($value)) {
            return array_map(fn ($item) => $this->serialize($item), $value);
        }

        return $value;
    }

    /** Build a bounded folder page and ordered breadcrumbs.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    public function browse(array $data): array
    {
        $parent = (int) ($data['parent_id'] ?? 0) ?: null;

        app(FileManager::class)->folder($parent);

        $query = Media::query()
            ->withCount([
                'mediaRelations',
                'children',
            ]);

        if (($data['scope'] ?? 'folder') !== 'global' || blank($data['search'] ?? null)) {
            $query->where('parent_id', $parent);
        }

        if (filled($data['search'] ?? null)) {
            $query->where('name', 'like', '%' . trim($data['search']) . '%');
        }

        if (filled($data['group'] ?? null)) {
            $group = $data['group'];
            $query->where(function ($query) use ($group): void {
                $query->where('type', 'c')
                    ->orWhere(function ($query) use ($group): void {
                        $query->where('type', 'f');
                        if (in_array($group, [
                            'image',
                            'video',
                            'audio',
                        ], true)) {
                            $query->where('mime_type', 'like', $group . '/%');
                        } else {
                            if ($group === 'archive') {
                                $query->where('extension', 'zip');
                            } else {
                                $query->where('mime_type', 'not like', 'image/%')
                                    ->where('mime_type', 'not like', 'video/%')
                                    ->where('mime_type', 'not like', 'audio/%')
                                    ->where('extension', '!=', 'zip');
                            }
                        }
                    });
            });
        }

        $page = $query->orderByRaw("CASE WHEN type = 'c' THEN 0 ELSE 1 END")
            ->orderBy($data['sort'] ?? 'name', $data['direction'] ?? 'asc')
            ->orderBy('id')
            ->paginate($data['per_page'] ?? 48);

        $crumbs = [
            [
                'id'   => 0,
                'name' => __('taxonomies.media_manager_title'),
            ],
        ];

        if ($parent) {
            foreach (
                MediaPath::query()
                    ->with('path')
                    ->where('media_id', $parent)
                    ->orderBy('level')
                    ->get() as $path) {
                if ($path->path) {
                    $crumbs[] = [
                        'id'   => $path->path_id,
                        'name' => $path->path->name,
                    ];
                }
            }
        }

        return [
            'ok'          => true,
            'data'        => array_map(fn ($item) => $this->transform($item), $page->items()),
            'folder'      => [
                'id' => $parent,
            ],
            'breadcrumbs' => $crumbs,
            'permissions' => [
                'manage' => true,
                'delete' => true,
            ],
            'meta'        => [
                'current_page' => $page->currentPage(),
                'last_page'    => $page->lastPage(),
                'total'        => $page->total(),
            ],
        ];
    }

    /**
     * Present safe file fields and language-aware timestamps.
     *
     * @param Media $item
     *
     * @return array<string, mixed>
     */
    public function transform(Media $item): array
    {
        $result = (new MediaResource($item))->resolve();

        $folder = $item->type === 'c';
        $result['type'] = $folder ? 'folder' : 'file';
        $result['name'] = app(FileManager::class)->displayName($item);

        return $result + [
                'is_folder'          => $folder,
                'url'                => $folder ? null : route('admin.media-manager.content', $item->id),
                'thumbnail_url'      => ! $folder && str_starts_with((string) $item->mime_type, 'image/') ? ($result['src'] ?? null) : null,
                'group'              => $item->extension === 'zip' ? 'archive' : (in_array(strtok((string) $item->mime_type, '/'), [
                    'image',
                    'audio',
                    'video',
                ], true) ? strtok($item->mime_type, '/') : 'document'),
                'usages_count'       => $item->media_relations_count ?? $item->mediaRelations()
                        ->count(),
                'children_count'     => $folder ? ($item->children_count ?? $item->children()
                    ->count()) : 0,
                'created_at_display' => LocalizedDate::format($item->created_at, false),
                'updated_at_display' => LocalizedDate::format($item->updated_at, false),
            ];
    }

    /** Return relation labels without leaking unrelated model attributes.
     *
     * @param Media $item
     *
     * @return array<int, array<string, mixed>>
     */
    public function usages(Media $item): array
    {
        $ids = MediaPath::query()
            ->where('path_id', $item->id)
            ->pluck('media_id')
            ->push($item->id);

        return MediaRelation::query()
            ->whereIn('media_id', $ids)
            ->get()
            ->map(function ($relation): ?array {
                $taxonomyClass = Taxonomy::class;
                $isTaxonomy = in_array($relation->mediaable_type, [
                    $taxonomyClass,
                    (new $taxonomyClass)->getMorphClass(),
                ], true);

                if ($isTaxonomy) {
                    $record = $taxonomyClass::find($relation->mediaable_id);
                    if (! $record) {
                        return null;
                    }

                    $registry = TaxonomyTypeRegistry::getFacadeRoot();

                    $typeLabel = $registry->has($record->type) ? $registry->for($record->type)
                        ->getLabel() : __('media-manager.content');

                    $label = TaxonomyPresentation::typeLabel($record->type, $typeLabel) . ' — ' . TaxonomyPresentation::name($record);

                    $url = null;
                    if ($registry->has($record->type)) {
                        $permissions = app(TaxonomyPermissions::class)->for($record->type, $registry->for($record->type));
                        if (app(AdminAccess::class)->allows(auth()->user(), $permissions['manage'])) {
                            $url = route('admin.taxonomies.edit', [
                                $record->type,
                                $record->id,
                            ]);
                        }
                    }
                } else if ($relation->mediaable_type === (new \App\Models\EditorDocument)->getMorphClass()) {
                    $document = \App\Models\EditorDocument::find($relation->mediaable_id);
                    $owner = $document?->owner;
                    if (! $owner) {
                        return null;
                    }
                    $label = __('editor.document_usage', [
                        'title'  => $owner instanceof \JobMetric\Post\Models\Post ? ($owner->getTranslation('title', $document->locale) ?: '#' . $owner->id) : '#' . $owner->getKey(),
                        'locale' => $document->locale,
                    ]);
                    $url = null;
                    if ($owner instanceof \JobMetric\Post\Models\Post && \JobMetric\Post\Facades\PostTypeRegistry::has($owner->type)) {
                        $permission = \JobMetric\Post\Facades\PostTypeRegistry::for($owner->type)
                            ->getManagePermission();
                        if ($permission && app(AdminAccess::class)->allows(auth()->user(), $permission)) {
                            $url = route('admin.posts.edit', [
                                $owner->type,
                                $owner->id,
                            ]);
                        }
                    }
                } else {
                    $label = __('media-manager.content') . ' #' . $relation->mediaable_id;
                    $url = null;
                }

                $role = match ($relation->collection) {
                    'editor' => __('editor.content_usage'),
                    'base' => __('taxonomies.media_base'),
                    'gallery', 'photos' => __('taxonomies.media_gallery'),
                    'seo_image' => __('taxonomies.media_seo_image'),
                    default => __('media-manager.attachment'),
                };

                return [
                    'label'      => $label,
                    'collection' => $role,
                    'url'        => $url,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }
}
