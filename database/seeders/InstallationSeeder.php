<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class InstallationSeeder extends Seeder
{
    /** Production-only seed entry point. Add deterministic system defaults here. */
    public function run(): void
    {
        // Current core has no required records; the installer creates its administrator.
    }
}
