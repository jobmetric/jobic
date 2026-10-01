<?php

declare(strict_types=1);

namespace App\Support;

use JobMetric\Taxonomy\Models\Taxonomy;

final class TaxonomyPresentation
{
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
