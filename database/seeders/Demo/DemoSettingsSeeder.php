<?php

declare(strict_types=1);

namespace Database\Seeders\Demo;

use Illuminate\Database\Seeder;
use JobMetric\Setting\Models\Setting;

/** Populate only safe SEO examples; never seed mail, secrets, or social identities. */
final class DemoSettingsSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([
            'jobic_seo_meta_title' => 'Jobic Demo',
            'jobic_seo_meta_description' => 'A sample CMS site. Replace this description before launch.',
            'jobic_seo_robots_index' => '0',
            'jobic_seo_robots_follow' => '0',
        ] as $key => $value) {
            Setting::query()->firstOrCreate(['form' => 'jobic_seo', 'key' => $key], [
                'value' => $value,
                'is_json' => false,
            ]);
        }
    }
}
