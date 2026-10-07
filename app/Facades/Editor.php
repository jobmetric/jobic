<?php

declare(strict_types=1);

namespace App\Facades;

use App\Support\EditorDriverBuilder;
use App\Support\EditorDriverRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * @method static \App\Support\EditorDriverBuilder register(string $key)
 * @method static bool has(string $key)
 * @method static \App\Support\EditorDriverDefinition get(string $key)
 * @method static array all()
 * @method static array clientTools()
 *
 * @see \App\Support\EditorDriverRegistry
 */
class Editor extends Facade
{
    protected static function getFacadeAccessor(): string { return EditorDriverRegistry::class; }
}
