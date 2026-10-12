<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Retain document snapshots belonging to one owner revision without copying shared media. */
class EditorDocumentRevision extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['documents' => 'array'];
}
