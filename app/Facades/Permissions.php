<?php

declare(strict_types=1);

namespace App\Facades;

use App\Support\Permissions\PermissionRegistry;
use Illuminate\Support\Facades\Facade;

/**
 * Provide a concise extension API for the CMS permission registry.
 *
 * @method static PermissionRegistry addPermission(string $key, string $label, ?string $route = null, array<string, scalar> $parameters = [], int $order = 100)
 * @method static bool has(string $key)
 * @method static array<string, string> labels()
 * @method static list<string> beneath(string $prefix)
 *
 * @see PermissionRegistry
 */
class Permissions extends Facade
{
    /**
     * Resolve the permission registry from Laravel's service container.
     *
     * @return class-string<PermissionRegistry>
     */
    protected static function getFacadeAccessor(): string
    {
        return PermissionRegistry::class;
    }
}
