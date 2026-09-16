<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Installation\Installation;
use Illuminate\Console\Command;
use Throwable;

/**
 * Provide release installation application behavior.
 */
class ReleaseInstallation extends Command
{
    protected $signature = 'installation:release';

    protected $description = 'Release the installer session after losing access to its browser';

    /**
     * Release installation ownership for local recovery.
     *
     * @param Installation $installation
     *
     * @return int
     * @throws Throwable
     */
    public function handle(Installation $installation): int
    {
        $result = $installation->release();
        $this->line($result->message);

        return $result->ok ? self::SUCCESS : self::FAILURE;
    }
}
