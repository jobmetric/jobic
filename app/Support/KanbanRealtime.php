<?php

declare(strict_types=1);

namespace App\Support;

use App\Events\Kanban\KanbanInvalidated;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Publish content-free, after-commit workspace invalidations through Laravel Reverb.
 */
class KanbanRealtime
{
    /** @var array<string, bool> */
    private array $pending = [];

    /**
     * Expose only public connection parameters to the Domi page registry.
     *
     * @param string $type
     *
     * @return array<string, mixed>
     */
    public function client(string $type): array
    {
        $connection = config('broadcasting.connections.reverb', []);
        return ['enabled' => config('broadcasting.default') === 'reverb' && !empty($connection['key']),'key' => $connection['key'] ?? '',
            'host' => config('broadcasting.public.host', $connection['options']['host'] ?? ''),'port' => (int)config('broadcasting.public.port', $connection['options']['port'] ?? 8080),
            'scheme' => config('broadcasting.public.scheme', $connection['options']['scheme'] ?? 'http'),'authUrl' => url('/broadcasting/auth'),'channel' => 'jobic.kanban.'.$type];
    }
    /**
     * Coalesce each type until commit, release rolled-back markers and isolate transport failures.
     *
     * @param string $type
     *
     * @return void
     */
    public function changed(string $type): void
    {
        if (config('broadcasting.default') !== 'reverb') {
            return;
        }
        if (isset($this->pending[$type])) {
            return;
        }
        $this->pending[$type] = true;
        if (DB::transactionLevel() > 0) {
            DB::afterRollBack(function () use ($type): void {
                unset($this->pending[$type]);
            });
        }
        DB::afterCommit(function () use ($type): void {
            unset($this->pending[$type]);
            try {
                event(new KanbanInvalidated($type));
            } catch (Throwable $error) {
                Log::warning('Kanban live notification failed.', ['type' => $type,'exception' => get_class($error)]);
            }
        });
    }
}
