<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Fingerprint persisted state and its latest CMS history entry for optimistic locking.
 */
class RecordVersion
{
    /**
     * Produce an opaque version that changes with record state or recorded mutations.
     *
     * @param Model $record
     * @param string $resource
     *
     * @return string
     * @throws Throwable
     */
    public static function token(Model $record, string $resource): string
    {
        $attributes = $record->getAttributes();
        ksort($attributes);
        $revision = DB::table('record_changes')
            ->where('resource', $resource)
            ->where('record_id', $record->getKey())
            ->max('id');

        return hash('sha256', json_encode([
            $attributes,
            $revision,
        ], JSON_THROW_ON_ERROR));
    }
}
