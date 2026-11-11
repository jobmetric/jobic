<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Store a named, type-scoped view of the shared content workflow. */
class ContentBoard extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['roles' => 'array', 'users' => 'array', 'states' => 'array', 'filters' => 'array', 'version' => 'integer'];
}
