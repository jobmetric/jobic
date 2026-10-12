<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use JobMetric\Media\Models\MediaRelation;

/** Store one editor field and language for an exclusively owning model. */
class EditorDocument extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['content' => 'array'];

    /**
     * Resolve the model owning this document.
     *
     * @return MorphTo
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Remove usage references when the document is deleted, retaining media files.
     *
     * @return void
     */
    protected static function booted(): void
    {
        static::deleting(static function (self $document): void {
            MediaRelation::query()
                ->where('mediaable_type', $document->getMorphClass())
                ->where('mediaable_id', $document->id)
                ->delete();
        });
    }
}
