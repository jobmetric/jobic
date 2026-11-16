<?php

declare(strict_types=1);

namespace App\Support\Extensions;

use JobMetric\Extension\Models\Plugin;
use JobMetric\Extension\Services\Plugin as PackagePlugins;

/**
 * Apply CMS activation policy to direct package plugin execution.
 */
class Plugins extends PackagePlugins
{
    /**
     * Run an enabled plugin only while its owning extension is active.
     *
     * @param  int  $plugin_id
     *
     * @return string|null
     * @throws \Throwable
     */
    public function run(int $plugin_id): ?string
    {
        $plugin = Plugin::with('extension')->find($plugin_id);
        if ($plugin && ! app(Runtime::class)->active((string) $plugin->extension?->namespace)) {
            return null;
        }

        return parent::run($plugin_id);
    }
}
