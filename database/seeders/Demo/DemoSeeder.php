<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Database\Seeders\InstallationSeeder;
use Database\Seeders\Vuero\AdminUserSeeder;
use Illuminate\Database\Seeder;

/** Seed an explicit, rerunnable tour of the CMS without running on ordinary installs. */
final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(InstallationSeeder::class);
        $this->call(AdminUserSeeder::class);
        $this->call(DemoRolesSeeder::class);
        $this->call(DemoSettingsSeeder::class);
        $this->call(DemoMediaSeeder::class);
        $this->call(DemoTaxonomiesSeeder::class);
        $this->call(DemoPostsSeeder::class);
        $this->call(DemoMenusSeeder::class);
        $this->call(DemoWorkflowSeeder::class);
    }
}
