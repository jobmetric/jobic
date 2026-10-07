<?php

declare(strict_types=1);

namespace App\Facades;

use App\Support\AdminMenuRegistry;
use App\Support\AdminMenuSectionBuilder;
use Illuminate\Support\Facades\Facade;

/**
 * Provide a concise extension API for the CMS admin menu registry.
 *
 * @method static AdminMenuSectionBuilder register(string $key)
 *
 * @see AdminMenuRegistry
 */
class AdminMenu extends Facade
{
    /**
     * Resolve the admin menu registry from Laravel's service container.
     *
     * @return class-string<AdminMenuRegistry>
     */
    protected static function getFacadeAccessor(): string
    {
        return AdminMenuRegistry::class;
    }
}
