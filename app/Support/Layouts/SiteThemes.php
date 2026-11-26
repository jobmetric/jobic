<?php

declare(strict_types=1);

namespace App\Support\Layouts;

use App\Contracts\ThemeHeaderNavigation;
use App\Support\Extensions\Runtime;
use JobMetric\Extension\Models\Extension;
use JobMetric\Setting\Facades\Setting;
use ReflectionClass;

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
     * Render the active theme's header navigation from its enabled instance.
     *
     * @param Extension|null $theme
     *
     * @return string|null
     */
    public function headerNavigation(?Extension $theme): ?string
    {
        if ($theme === null || ! is_subclass_of($theme->namespace, ThemeHeaderNavigation::class)) {
            return null;
        }

        $instance = $theme->plugins()->where('status', true)->oldest('id')->first();
        if ($instance === null) {
            return null;
        }

        return app($theme->namespace)->headerNavigation($instance->fields?->getArrayCopy() ?? []);
    }

    /**
     * Return menu displays declared by the selected Theme with a matching Blade file.
     *
     * @return array<string, array{label: string, path: string}>
     */
    public function menuViews(): array
    {
        $theme = $this->active();
        if ($theme === null) {
            return [];
        }

        $declared = $this->manifest->read($theme->namespace)['menu_views'] ?? [];
        $entry = (new ReflectionClass($theme->namespace))->getFileName();
        $root = realpath(dirname((string) $entry));
        $directory = $root === false ? false : realpath($root.'/resources/views/menus');
        if ($directory === false || ! str_starts_with($directory, $root.DIRECTORY_SEPARATOR)) {
            return [];
        }

        $views = [];
        foreach ($declared as $key => $label) {
            $path = realpath($directory.DIRECTORY_SEPARATOR.$key.'.blade.php');
            if ($path !== false && dirname($path) === $directory && is_file($path)) {
                $views[$key] = ['label' => trans($label), 'path' => $path];
            }
        }

        return $views;
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
