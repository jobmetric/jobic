<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Extensions\Module\JobicSlideshow\JobicSlideshow;
use App\Extensions\Module\JobicStarterArticles\JobicStarterArticles;
use App\Extensions\Theme\JobicStarterTheme\JobicStarterTheme;
use App\Support\Layouts\SiteThemes;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use JobMetric\Extension\Facades\Extension as ExtensionService;
use JobMetric\Extension\Models\Extension;
use JobMetric\Layout\Models\Layout;
use JobMetric\Layout\Models\LayoutPage;
use RuntimeException;

class WebsiteInstallationSeeder extends Seeder
{
    public function run(SiteThemes $themes): void
    {
        $this->call(InstallationSeeder::class);

        $theme = $this->install(JobicStarterTheme::class);
        $modules = [
            'top' => $this->install(JobicSlideshow::class),
            'content' => $this->install(JobicStarterArticles::class),
        ];
        $key = $themes->key($theme);

        if ($themes->activeKey() === null) {
            $themes->select($key);
        }

        DB::transaction(function () use ($key, $modules): void {
            if (LayoutPage::query()->where('application', $key)->where('page', 'home')->exists()) {
                return;
            }

            $layout = Layout::query()->create(['name' => __('site.starter_layout'), 'status' => true]);
            $layout->layoutPages()->create(['application' => $key, 'page' => 'home']);
            foreach ($modules as $position => $module) {
                $instance = $module->plugins()->firstOrCreate([], [
                    'name' => trans($module->info['title']),
                    'fields' => [],
                    'status' => true,
                ]);
                $layout->layoutPlugins()->create([
                    'plugin_id' => $instance->id,
                    'position' => $position,
                    'ordering' => 0,
                ]);
            }
        });
    }

    private function install(string $namespace): Extension
    {
        $extension = Extension::whereNamespace($namespace)->first();
        if ($extension !== null) {
            return $extension;
        }

        if (! ExtensionService::install($namespace)->ok) {
            throw new RuntimeException('Could not install bundled website extension: '.$namespace);
        }

        return Extension::whereNamespace($namespace)->firstOrFail();
    }
}
