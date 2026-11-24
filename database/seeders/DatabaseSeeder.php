<?php

namespace Database\Seeders;

use Database\Seeders\Vuero\AdminUserSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(WebsiteInstallationSeeder::class);
        $this->call(AdminUserSeeder::class);
    }
}
