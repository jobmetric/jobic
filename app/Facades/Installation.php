<?php

declare(strict_types=1);

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/** @see \App\Services\Installation\Installation */
class Installation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \App\Services\Installation\Installation::class;
    }
}
