<?php

declare(strict_types=1);

namespace App\Support\Layouts;

use App\Support\Extensions\Runtime;
use JobMetric\Extension\Models\Extension;
use JobMetric\Setting\Facades\Setting;

/**
 * Resolve standard Theme extensions and the website's active Theme selection.
 */
class SiteThemes
{
    /**
     * Create the Theme catalog.
     *
     * @param ThemeManifest $manifest
     *
     * @return void
     */
    public function __construct(private readonly ThemeManifest $manifest)
    {
    }

    /**
     * Build the stable storage key for a Theme extension.
     *
     * @param Extension $extension
     *
     * @return string
     */
    public function key(Extension $extension): string
    {
        return 'theme_'.strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $extension->name) ?? $extension->name);
    }

    /**
     * Return all effectively active, standard Theme extensions keyed for settings.
     *
     * @return array<string, Extension>
     */
    public function available(): array
    {
        return Extension::query()->where('extension', 'Theme')->get()->filter(function (Extension $extension): bool {
            return app(Runtime::class)->active($extension->namespace) && $this->manifest->read($extension->namespace) !== null;
        })->mapWithKeys(fn (Extension $extension): array => [$this->key($extension) => $extension])->all();
    }

    /**
     * Return the stable key of the selected website Theme.
     *
     * @return string|null
     */
    public function activeKey(): ?string
    {
        $value = Setting::get('jobic_site_active_theme');

        return is_string($value) && $value !== '' ? $value : null;
    }

    /**
     * Return the selected Theme when it remains available.
     *
     * @return Extension|null
     */
    public function active(): ?Extension
    {
        $key = $this->activeKey();

        return $key !== null ? ($this->available()[$key] ?? null) : null;
    }

    /**
     * Persist the selected website Theme.
     *
     * @param string $key
     *
     * @return void
     */
    public function select(string $key): void
    {
        Setting::dispatchByForm('jobic_site', ['jobic_site_active_theme' => $key]);
    }
}
