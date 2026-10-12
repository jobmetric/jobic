<?php

declare(strict_types=1);

namespace App\Facades;

use App\Support\EditorDriverBuilder;
use App\Support\EditorDriverDefinition;
use App\Support\EditorDriverRegistry;
use App\Support\EditorTemplateRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * Provide the public extension API for content drivers and templates.
 *
 * @method static EditorDriverBuilder register(string $key)
 * @method static bool has(string $key)
 * @method static EditorDriverDefinition get(string $key)
 * @method static array<string, EditorDriverDefinition> all()
 * @method static array<string, array<string, mixed>> clientTools()
 * @method static string render(array|string|null $document)
 * @method static EditorDriverRegistry addPath(string $path)
 * @method static EditorTemplateRegistry templates()
 *
 * @see EditorDriverRegistry
 */
class Editor extends Facade
{
    /**
     * Resolve the shared editor registry from the application container.
     *
     * @return class-string<EditorDriverRegistry>
     */
    protected static function getFacadeAccessor(): string
    {
        return EditorDriverRegistry::class;
    }
}
