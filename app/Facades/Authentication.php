<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
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
     * Get the container binding for the facade.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'authentication';
    }
}
