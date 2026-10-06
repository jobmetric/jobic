<?php

declare(strict_types=1);

namespace App\Support;

use DateTimeInterface;
use Illuminate\Support\Carbon;
use JobMetric\MultiCalendar\Factory\CalendarConverterFactory;
use JobMetric\MultiCalendar\Helpers\NumberTransliterator;
use Throwable;

/**
 * Format stored Gregorian dates with the calendar of the active interface language.
 */
class LocalizedDate
{
    /**
     * Convert a date for human-facing output while preserving its local time.
     */
    public static function format(
        DateTimeInterface|string|null $value,
        bool $withTime = true,
        ?string $locale = null
    ): ?string {
        if ($value === null || $value === '') {
            return null;
        }

        $locale = LocaleCatalog::resolve($locale ?? app()->getLocale());
        $date = $value instanceof DateTimeInterface ? Carbon::instance($value) : Carbon::parse($value);

        try {
            $converter = CalendarConverterFactory::make(LocaleCatalog::calendar($locale));
            $formatted = $converter->fromGregorian($date->year, $date->month, $date->day, '/');
        } catch (Throwable) {
            $formatted = $date->format('Y/m/d');
        }

        if ($withTime) {
            $formatted .= ($locale === 'fa' || $locale === 'ar' ? '، ' : ', ') . $date->format('H:i');
        }

        return NumberTransliterator::trNum($formatted, in_array($locale, ['fa', 'ar'], true) ? $locale : 'en');
    }
}
