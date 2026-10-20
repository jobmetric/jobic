<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use JobMetric\Post\Models\PostScheduledAction;
use JobMetric\Post\Services\PostLifecycle;
use Throwable;

/** Process due, approved publication actions without reusing mutable drafts. */
final class RunPostSchedules extends Command
{
    protected $signature = 'posts:run-schedules {--limit=100 : Maximum due actions for one run}';

    protected $description = 'Run due post publication actions';

    /**
     * Process each action independently; package locking makes retries idempotent.
     *
     * @param PostLifecycle $lifecycle
     *
     * @return int
     */
    public function handle(PostLifecycle $lifecycle): int
    {
        $limit = max(1, min(1000, (int) $this->option('limit')));

        $ids = PostScheduledAction::query()
            ->where('state', 'pending')
            ->where('due_at', '<=', now())
            ->orderBy('due_at')
            ->limit($limit)
            ->pluck('id');

        $completed = 0;
        $failed = 0;
        foreach ($ids as $id) {
            try {
                $completed += (int) $lifecycle->execute((int) $id);
            } catch (Throwable $exception) {
                report($exception);
                $this->error("Scheduled action {$id} failed: {$exception->getMessage()}");
                $failed++;
            }
        }

        $this->info("Completed {$completed}; failed {$failed}.");

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
