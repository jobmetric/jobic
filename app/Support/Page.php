<?php

declare(strict_types=1);

namespace App\Support;

use JobMetric\Domi\Exceptions\SetPluginNotFoundException;
use JobMetric\Domi\Facades\Domi;
use Throwable;

/** Page-only presentation data; never pass requests, models or credentials. */
class Page
{
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
     *
     * @return void
     */
    public static function addScript(
        string $url,
        string $position = 'bottom',
        string $type = 'application/javascript'
    ): void {
        Domi::setScript($url, $type, false, false, $position);
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
