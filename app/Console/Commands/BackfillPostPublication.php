<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Editor\EditorDocuments;
use App\Services\Posts\PostPublicationProjection;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostPublicationStream;
use JobMetric\Post\Services\PostWorkflowDefinitions;
use RuntimeException;
use Throwable;

/** Backfill existing CMS posts only after a complete private backup is written. */
final class BackfillPostPublication extends Command
{
    protected $signature = 'posts:backfill-publication {--apply : Write streams after backing up every target}';

    protected $description = 'Report or backfill whole-post publication streams from existing posts';

    /**
     * Audit candidates, archive their current aggregate, then backfill idempotently.
     *
     * @param PostPublicationProjection $projection
     * @param EditorDocuments $documents
     *
     * @return int
     */
    public function handle(PostPublicationProjection $projection, EditorDocuments $documents): int
    {
        $ids = [];
        $byStatus = [];
        foreach (
            Post::query()
                ->orderBy('id')
                ->cursor() as $post) {
            if (PostPublicationStream::query()
                ->where('post_id', $post->id)
                ->where('locale', PostPublicationStream::SHARED)
                ->exists()) {
                continue;
            }

            $ids[] = (int) $post->id;
            $byStatus[$post->status] = ($byStatus[$post->status] ?? 0) + 1;
        }

        $count = count($ids);
        $this->info("Posts with missing whole-post publication streams: {$count}");

        foreach ($byStatus as $status => $statusCount) {
            $this->line("  {$status}: {$statusCount}");
        }

        if (! $this->option('apply') || $count === 0) {
            $this->line('Dry run: no data changed. Use --apply after reviewing the report.');

            return self::SUCCESS;
        }

        $backup = 'backups/post-publication-' . now()->format('Ymd-His') . '-' . bin2hex(random_bytes(4));
        $disk = Storage::disk('local');

        try {
            foreach ($ids as $id) {
                $post = Post::query()
                    ->findOrFail($id);

                $archive = [
                    'snapshot'            => $post->snapshot(),
                    'editor_documents'    => $documents->snapshot($post),
                    'legacy_status'       => $post->status,
                    'legacy_published_at' => $post->published_at?->toIso8601String(),
                ];

                if (! $disk->put("{$backup}/{$id}.json", json_encode($archive, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE))) {
                    throw new RuntimeException("Cannot back up post {$id}.");
                }
            }

            if (! $disk->put("{$backup}/manifest.json", json_encode([
                'post_ids'   => $ids,
                'created_at' => now()->toIso8601String(),
                'purpose'    => 'post-publication-backfill',
            ], JSON_THROW_ON_ERROR))) {
                throw new RuntimeException('Cannot write backup manifest.');
            }
            $this->info("Private backup: storage/app/private/{$backup}");

            foreach ($ids as $id) {
                DB::transaction(function () use ($id, $projection): void {
                    $type = (string) Post::query()
                        ->findOrFail($id)->type;

                    app(PostWorkflowDefinitions::class)->lock($type);

                    $post = Post::query()
                        ->lockForUpdate()
                        ->findOrFail($id);

                    if (PostPublicationStream::query()
                        ->where('post_id', $post->id)
                        ->where('locale', PostPublicationStream::SHARED)
                        ->exists()) {
                        return;
                    }

                    $projection->capture($post, array_keys($post->getTranslations()), null);
                });
            }
        } catch (Throwable $exception) {
            $this->error("Backfill stopped. Keep the backup at {$backup} for recovery.");

            throw $exception;
        }

        $this->info("Backfilled {$count} posts. Legacy columns and documents were not deleted.");

        return self::SUCCESS;
    }
}
