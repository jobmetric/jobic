<?php

declare(strict_types=1);

namespace App\Extensions\Theme\JobicThemeExample\Services;

use App\Extensions\Theme\JobicThemeExample\JobicThemeExample;
use App\Support\LocalizedDate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use JobMetric\Extension\Facades\Plugin;
use JobMetric\Extension\Models\Extension;
use JobMetric\PackageCore\Output\Response;

/**
 * Read only this example's public instances and private audit aggregate.
 */
class Example
{
    /**
     * Return at most twenty-five instance previews without exposing stored credentials.
     *
     * @return Response
     * @throws \Throwable
     */
    public function page(): Response
    {
        $extension = Extension::whereNamespace(JobicThemeExample::class)->firstOrFail();
        $cards = $extension->plugins()->orderBy('id')->limit(25)->get()->map(static fn ($plugin): array => ['name' => $plugin->name, 'active' => $plugin->status, 'html' => Plugin::run($plugin->id)])->all();

        return Response::make(true, '', ['upgradeApplied' => Schema::hasTable('jobic_example_theme_upgrade'), 'cards' => $cards, 'renderedAt' => LocalizedDate::format(now()), 'auditCount' => DB::table('jobic_example_theme_audit')->count(), 'extensionId' => $extension->id]);
    }
}
