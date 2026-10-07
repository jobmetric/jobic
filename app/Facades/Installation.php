<?php

declare(strict_types=1);

namespace App\Facades;

use App\Services\Installation\Installation as InstallationService;
use Illuminate\Support\Facades\Facade;

/**
 * Provide a concise API for the resumable installation workflow.
 *
 * @see InstallationService
 */
class Installation extends Facade
{
    /**
     * Resolve the installation service from Laravel's service container.
     *
     * @return class-string<InstallationService>
     */
    protected static function getFacadeAccessor(): string
    {
        return InstallationService::class;
    }
}
