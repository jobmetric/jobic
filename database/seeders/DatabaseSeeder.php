<?php

namespace Database\Seeders;

use Database\Seeders\Vuero\AdminUserSeeder;
use Illuminate\Database\Seeder;
use JobMetric\Setting\Models\Setting;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(AdminUserSeeder::class);

        Setting::factory(10)->create();
    }
}
