<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Schema;
use JobMetric\Language\Models\Language;

/**
 * Resolve interface language identity and direction from the active registry.
 */
class LocaleCatalog
{
    /**
     * Read active registry languages, using bootstrap entries before database setup.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function all(): array
    {
        if (! config('installation.completed') || ! Schema::hasTable((new Language)->getTable())) {
            return config('localization.bootstrap');
        }

        return Language::query()->where('status', true)->orderBy('name')->get([
                'name',
                'locale',
                'direction',
                'flag',
            ])->keyBy('locale')->toArray();
    }

    /**
     * Return locale codes allowed by the current language registry.
     *
     * @return list<string>
     */
    public static function codes(): array
    {
        return array_keys(self::all());
    }

    /**
     * Resolve stale preferences to an active locale with a deterministic fallback.
     *
     * @param string|null $preferred
     *
     * @return string
     */
    public static function resolve(?string $preferred = null): string
    {
        $languages = self::all();
        if (isset($languages[$preferred])) {
            return $preferred;
        }

        $fallback = config('localization.fallback', 'en');

        return isset($languages[$fallback]) ? $fallback : (array_key_first($languages) ?? $fallback);
    }

    /**
     * Read layout direction independently of whether translation files are installed.
     *
     * @param string|null $locale
     *
     * @return string
     */
    public static function direction(?string $locale = null): string
    {
        return self::all()[$locale ?? app()->getLocale()]['direction'] ?? 'ltr';
    }

    /**
     * Resolve a published package flag, preserving legacy country-code values.
     *
     * @param string|null $flag
     *
     * @return string|null
     * @throws \RuntimeException
     */
    public static function flagUrl(?string $flag): ?string
    {
        $flag = [
                    'us' => 'united-states.svg',
                    'ir' => 'iran.svg',
                ][$flag ?? ''] ?? $flag;
        foreach (\JobMetric\Language\Facades\Language::getFlags() as $item) {
            if ($item['value'] === $flag) {
                return $item['url'];
            }
        }

        return null;
    }
}
