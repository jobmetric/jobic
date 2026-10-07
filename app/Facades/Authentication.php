<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Provide a concise API for authenticating users and managing password recovery.
 *
 * @method static \JobMetric\PackageCore\Output\Response login(array $data, array $context = [])
 * @method static \JobMetric\PackageCore\Output\Response logout(array $context = [])
 * @method static \JobMetric\PackageCore\Output\Response sendResetLink(array $data, array $context = [])
 * @method static \JobMetric\PackageCore\Output\Response resetPassword(array $data)
 * @method static \JobMetric\PackageCore\Output\Response language(array $data)
 *
 * @see \App\Services\Auth\Authentication
 */
class Authentication extends Facade
{
    /**
     * Resolve the authentication service from Laravel's service container.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'authentication';
    }
}
