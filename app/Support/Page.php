<?php

declare(strict_types=1);

namespace App\Support;

use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use JobMetric\Domi\Facades\Domi;
use JobMetric\Setting\Contracts\AbstractSetting;
use Throwable;
use UnexpectedValueException;

/** Page-only presentation data; never pass requests, models or credentials. */
class Page
{
    /**
     * Add a Domi plugin to the current page without replacing plugins already loaded.
     */
    public static function addPlugin(string $plugin): void
    {
        Domi::setPlugins($plugin);
    }

    /**
     * Register public page data in the Domi localization registry.
     *
     * @param string $namespace
     * @param array<string, mixed> $data
     *
     * @return void
     */
    public static function addLocalize(string $namespace, array $data): void
    {
        Domi::setLocalize($namespace, $data);
    }

    /**
     * Register a page stylesheet with Domi.
     *
     * @param string $url
     * @param string|null $media
     *
     * @return void
     * @throws Throwable
     */
    public static function addStyle(string $url, ?string $media = null): void
    {
        Domi::setStyle($url, $media);
    }

    /**
     * Register a page script at the requested Domi position.
     *
     * @param string $url
     * @param string $position
     * @param string $type
     * @param bool $async
     * @param bool $defer
     *
     * @return void
     */
    public static function addScript(
        string $url,
        string $position = 'bottom',
        string $type = 'application/javascript',
        bool $async = false,
        bool $defer = false
    ): void {
        Domi::setScript($url, $type, $async, $defer, $position);
    }

    /**
     * Register the styles and scripts declared by one settings driver.
     *
     * @param AbstractSetting $setting
     *
     * @return void
     * @throws Throwable
     */
    public static function addSettingAssets(AbstractSetting $setting): void
    {
        $styles = method_exists($setting, 'domiStyles') ? $setting->domiStyles() : [];
        $scripts = method_exists($setting, 'domiScripts') ? $setting->domiScripts() : [];

        foreach ($styles as $style) {
            $definition = is_string($style) ? ['href' => $style] : $style;
            if (! is_array($definition) || ! is_string($definition['href'] ?? null) || $definition['href'] === '') {
                throw new UnexpectedValueException('Setting driver styles must define a non-empty href.');
            }
            Domi::setStyle($definition['href'], $definition['media'] ?? null, $definition['integrity'] ?? null, $definition['crossorigin'] ?? null);
        }

        foreach ($scripts as $script) {
            $definition = is_string($script) ? ['src' => $script] : $script;
            if (! is_array($definition)) {
                throw new UnexpectedValueException('Setting driver scripts must define a non-empty src and a valid position.');
            }
            $position = $definition['position'] ?? 'bottom';
            if (! is_string($definition['src'] ?? null) || $definition['src'] === '' || ! in_array($position, [
                    'top',
                    'bottom',
                ], true)) {
                throw new UnexpectedValueException('Setting driver scripts must define a non-empty src and a valid position.');
            }
            Domi::setScript($definition['src'], $definition['type'] ?? 'application/javascript', (bool) ($definition['async'] ?? false), (bool) ($definition['defer'] ?? false), $position);
        }
    }

    /**
     * Reset page presentation data and register localized interface messages.
     *
     * @param string $page
     * @param string|null $locale
     *
     * @return void
     * @throws SetPluginNotFoundException
     */
    public static function prepare(string $page, ?string $locale = null): void
    {
        Domi::clearResolvedInstance(\JobMetric\Domi\Domi::class);

        app()->forgetInstance(\JobMetric\Domi\Domi::class);
        $locale = LocaleCatalog::resolve($locale ?? app()->getLocale());
        app()->setLocale($locale);
        app('translator')->setFallback(config('localization.fallback', 'en'));
        Domi::setSiteName(config('app.name', 'Jobic'));
        Domi::setTitle(null);
        Domi::setPlugins(['jquery', 'select2']);

        self::addLocalize('selects', [
            'placeholder' => trans('admin.options.select', [], $locale),
            'no_results' => trans('languages.no_results'),
            'searching'  => trans('languages.searching'),
        ]);
        self::addLocalize('page', [
            'name'   => $page,
            'locale' => $locale,
        ]);
        self::addLocalize('ui', [
            'loading'     => trans('admin.loading', [], $locale),
            'save_failed' => trans('admin.save_failed', [], $locale),
            'saved'       => trans('admin.saved', [], $locale),
        ]);
    }
}
