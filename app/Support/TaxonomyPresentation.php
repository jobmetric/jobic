<?php

declare(strict_types=1);

namespace App\Support;

use JobMetric\Taxonomy\Models\Taxonomy;

final class TaxonomyPresentation
{
    /** Resolve a registry type label in the current request locale. */
    public static function typeLabel(string $type, string $fallback): string
    {
        if (preg_match('/^menu_(\d+)$/', $type, $matches) !== 1) {
            return $fallback;
        }

        $menu = Taxonomy::query()->with('translations')->where('type', 'menu')->find((int) $matches[1]);

        return $menu ? self::name($menu) : $fallback;
    }

    public static function name(Taxonomy $taxonomy): string
    {
        $translations = $taxonomy->getTranslations();
        $preferred = [LocaleCatalog::resolve(app()->getLocale()), config('localization.fallback', 'en')];

        foreach (array_unique($preferred) as $locale) {
            $name = trim((string) ($translations[$locale]['name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        foreach ($translations as $fields) {
            $name = trim((string) ($fields['name'] ?? ''));
            if ($name !== '') {
                return $name;
            }
        }

        return '#'.$taxonomy->getKey();
    }
}
