<?php

declare(strict_types=1);

namespace App\Services\History;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use JobMetric\PackageCore\Output\Response;
use Throwable;

/**
 * Store transactional record changes with an explicit per-resource field allowlist.
 */
class RecordHistory
{
    private const array FIELDS = [
        'languages' => [
            'name',
            'locale',
            'flag',
            'direction',
            'calendar',
            'first_day_of_week',
            'status',
        ],
        'users'     => [
            'name',
            'email',
            'status',
            'email_verified_at',
            'role_ids',
        ],
        'roles'     => [
            'name',
            'description',
            'parent_id',
            'allow',
            'deny',
            'is_default',
            'is_super',
            'ordering',
            'rules',
        ],
    ];

    /**
     * Record changed public fields inside the caller's persistence transaction.
     *
     * @param string $resource
     * @param int $recordId
     * @param int $userId
     * @param string $operation
     * @param array<string, mixed> $before
     * @param array<string, mixed> $after
     *
     * @return void
     * @throws Throwable
     */
    public function record(
        string $resource,
        int $recordId,
        int $userId,
        string $operation,
        array $before,
        array $after
    ): void {
        $changes = [];
        foreach (self::FIELDS[$resource] ?? [] as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;
            if ($old !== $new) {
                $changes[$field] = [
                    'before' => $old,
                    'after'  => $new,
                ];
            }
        }

        if ($changes === []) {
            return;
        }

        DB::table('record_changes')->insert([
            'resource'   => $resource,
            'record_id'  => $recordId,
            'user_id'    => $userId,
            'operation'  => $operation,
            'changes'    => json_encode($changes, JSON_THROW_ON_ERROR),
            'created_at' => now(),
        ]);
    }

    /**
     * Read a bounded history page without exposing account credentials or email addresses.
     *
     * @param string $resource
     * @param int $recordId
     * @param int $page
     *
     * @return Response
     * @throws Throwable
     */
    public function page(string $resource, int $recordId, int $page): Response
    {
        $entries = DB::table('record_changes as changes')
            ->leftJoin('users', 'users.id', '=', 'changes.user_id')
            ->where('changes.resource', $resource)
            ->where('changes.record_id', $recordId)
            ->orderByDesc('changes.id')
            ->paginate(20, [
                'changes.id',
                'changes.operation',
                'changes.changes',
                'changes.created_at',
                'users.name as actor',
            ], 'page', $page);

        $entries->getCollection()->transform(function ($entry) {
            $entry->created_at = Carbon::parse($entry->created_at)->toIso8601String();
            $entry->changes = json_decode($entry->changes, true, 512, JSON_THROW_ON_ERROR);

            return $entry;
        });

        return Response::make(true, '', [
            'entries' => $entries->items(),
            'page'    => $entries->currentPage(),
            'pages'   => $entries->lastPage(),
        ]);
    }
}
