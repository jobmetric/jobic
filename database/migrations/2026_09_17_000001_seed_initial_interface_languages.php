<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('localization.bootstrap') as $language) {
            DB::table(config('language.tables.language', 'languages'))->insertOrIgnore($language + [
                'calendar' => $language['locale'] === 'fa' ? 'jalali' : 'gregorian',
                'first_day_of_week' => $language['locale'] === 'fa' ? 0 : 1,
                'status' => true, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Language records may have been edited or referenced; never remove user data on rollback.
    }
};
