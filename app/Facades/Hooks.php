<?php

declare(strict_types=1);

namespace App\Facades;

use App\Support\Hooks as HooksRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * Provide a concise extension API for CMS actions, filters, and view slots.
 *
 * @method static void addAction(string $name, callable $callback, int $priority = 10)
 * @method static void addFilter(string $name, callable $callback, int $priority = 10)
 * @method static void lifecycle(string $name, array $context = [])
 * @method static void action(string $name, array $context = [])
 * @method static void notify(string $name, array $context = [])
 * @method static void kanbanOutcome(string $name, array $context = [])
 * @method static mixed filter(string $name, mixed $value, array $context = [])
 * @method static void addView(string $name, string $view, int $priority = 10)
 * @method static \Illuminate\Support\HtmlString render(string $name, array $context = [])
 *
 * @see HooksRegistry
 */
class Hooks extends Facade
{
    /**
     * Resolve the hooks registry from Laravel's service container.
     *
     * @return class-string<HooksRegistry>
     */
    protected static function getFacadeAccessor(): string
    {
        return HooksRegistry::class;
    }
}
