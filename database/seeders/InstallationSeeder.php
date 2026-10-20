<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InstallationSeeder extends Seeder
{
    /** Seed deterministic system defaults for a fresh installation. */
    public function run(): void
    {
        foreach (config('localization.bootstrap', []) as $language) {
            DB::table(config('language.tables.language', 'languages'))->insertOrIgnore($language + [
                'calendar' => $language['locale'] === 'fa' ? 'jalali' : 'gregorian',
                'first_day_of_week' => $language['locale'] === 'fa' ? 0 : 1,
                'status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
