<?php

declare(strict_types=1);

namespace App\Support\Layouts;

use Illuminate\Support\Facades\Log;
use JobMetric\Extension\Facades\Plugin;
use JobMetric\Layout\Models\LayoutPage;
use Throwable;

/**
 * Resolve and render ordered Module instances for one public Theme position.
 */
class SiteLayoutRenderer
{
    /**
     * Create the site layout renderer.
     *
     * @param SiteThemes $themes
     * @param ThemeManifest $manifest
     * @param PageTypeRegistry $pageTypes
     *
     * @return void
     */
    public function __construct(
        private readonly SiteThemes $themes,
        private readonly ThemeManifest $manifest,
        private readonly PageTypeRegistry $pageTypes
    ) {
    }

    /**
     * Render one position for the current Theme and explicit page type.
     *
     * @param string $position
     * @param string $pageType
     *
     * @return string
     */
    public function render(string $position, string $pageType): string
    {
        $theme = $this->themes->active();
        if ($theme === null || ! $this->pageTypes->has($pageType)
            || ! array_key_exists($position, $this->manifest->positions($theme->namespace))) {
            return '';
        }
        $layoutPage = LayoutPage::query()->where('application', $this->themes->key($theme))
            ->where('page', $pageType)->whereHas('layout', fn ($query) => $query->where('status', true))
            ->with(['layout.layoutPlugins' => fn ($query) => $query->where('position', $position)->orderBy('ordering')])
            ->first();
        if ($layoutPage?->layout === null) {
            return '';
        }
        $html = '';
        foreach ($layoutPage->layout->layoutPlugins as $placement) {
            try {
                $html .= Plugin::run((int) $placement->plugin_id) ?? '';
            } catch (Throwable $exception) {
                Log::warning('A site layout module failed to render.', [
                    'layout_id' => (int) $layoutPage->layout_id,
                    'plugin_id' => (int) $placement->plugin_id,
                    'position' => $position,
                    'exception_type' => $exception::class,
                ]);
            }
        }

        return $html;
    }
}
