<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\EditorDocumentRevision;
use App\Services\Editor\EditorDocuments;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use JobMetric\Media\Models\MediaRelation;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostRevision;
use JobMetric\Translation\Models\Translation;
use RuntimeException;

/** Back up and remove only legacy post editor content and its media usage. */
class ClearLegacyEditorContent extends Command
{
    protected $signature = 'editor:clear-legacy {--execute : Write a backup and apply the reported cleanup}';
    protected $description = 'Report legacy post editor content; use --execute to back up and remove it.';

    /**
     * Inspect exact legacy targets, back them up, then clean them transactionally.
     *
     * @return int
     * @throws \Throwable
     */
    public function handle(): int
    {
        return DB::transaction(function (): int {
            $types = array_unique([Post::class, (new Post)->getMorphClass()]);
            $translations = Translation::withTrashed()
                ->whereIn('translatable_type', $types)
                ->where('field', 'content')
                ->lockForUpdate()
                ->get();

            $relations = MediaRelation::query()
                ->whereIn('mediaable_type', $types)
                ->where('collection', 'editor')
                ->lockForUpdate()
                ->get();

            $revisions = PostRevision::query()
                ->lockForUpdate()
                ->get()
                ->filter(static fn ($revision
                ): bool => collect($revision->snapshot['translation'] ?? [])->contains('field', 'content') || collect($revision->snapshot['files'] ?? [])->contains('collection', 'editor'));

            $this->table([
                'Target',
                'Count',
            ], [
                ['Legacy translations', $translations->count()],
                ['Legacy media usages', $relations->count()],
                ['Revision snapshots to sanitize', $revisions->count()],
            ]);

            if (! $this->option('execute') || ($translations->isEmpty() && $relations->isEmpty() && $revisions->isEmpty())) {
                return self::SUCCESS;
            }

            $directory = storage_path('app/private/editor-backups');
            File::ensureDirectoryExists($directory, 0700);
            $path = $directory . '/' . now()->format('Ymd-His') . '-' . bin2hex(random_bytes(6)) . '.json';
            $backup = json_encode([
                'version'      => 1,
                'tables'       => [
                    'translations' => (new Translation)->getTable(),
                    'relations'    => (new MediaRelation)->getTable(),
                    'revisions'    => (new PostRevision)->getTable(),
                ],
                'translations' => $translations->map->getAttributes()
                    ->values()
                    ->all(),
                'relations'    => $relations->map->getAttributes()
                    ->values()
                    ->all(),
                'revisions'    => $revisions->map->getAttributes()
                    ->values()
                    ->all(),
            ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

            if (File::put($path, $backup, true) !== strlen($backup) || ! hash_equals(hash('sha256', $backup), hash_file('sha256', $path))) {
                throw new RuntimeException('Legacy editor backup verification failed. No data was removed.');
            }

            foreach ($revisions as $revision) {
                $snapshot = $revision->snapshot;
                $snapshot['translation'] = array_values(array_filter($snapshot['translation'] ?? [], static fn (array $row): bool => ($row['field'] ?? '') !== 'content'));
                $snapshot['files'] = array_values(array_filter($snapshot['files'] ?? [], static fn (array $row): bool => ($row['collection'] ?? '') !== 'editor'));
                $revision->update(['snapshot' => $snapshot]);
                if (! EditorDocumentRevision::query()
                    ->where('revision_type', $revision->getMorphClass())
                    ->where('revision_id', $revision->id)
                    ->exists()) {
                    app(EditorDocuments::class)->capture($revision, []);
                }
            }

            Translation::withTrashed()
                ->whereIn('id', $translations->modelKeys())
                ->forceDelete();

            foreach ($relations as $relation) {
                MediaRelation::query()
                    ->where($relation->only([
                        'media_id',
                        'mediaable_type',
                        'mediaable_id',
                        'collection',
                    ]))
                    ->delete();
            }

            $this->info('Backup: ' . $path);
            $this->info('Legacy editor content removed; posts and media files retained.');

            return self::SUCCESS;
        });
    }
}
