<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Installation\Installation;
use Illuminate\Console\Command;

class ReleaseInstallation extends Command
{
    protected $signature = 'installation:release';

    protected $description = 'Release the installer session after losing access to its browser';

    public function handle(Installation $installation): int
    {
        $result = $installation->release();
        $this->line($result->message);

        return $result->ok ? self::SUCCESS : self::FAILURE;
    }
}
