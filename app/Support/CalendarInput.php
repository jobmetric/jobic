<?php

declare(strict_types=1);
namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use JobMetric\MultiCalendar\Factory\CalendarConverterFactory;
use JobMetric\MultiCalendar\Helpers\NumberTransliterator;
use Throwable;

/** Convert calendar-aware administration input to a validated Gregorian timestamp. */
class CalendarInput
{
    /** Parse a local date using the active language calendar and server administration timezone.
     *
     * @param string $value
     *
     * @return string
     * @throws ValidationException
     */
    public static function parse(string $value): string
    {
        $value = NumberTransliterator::trNum(trim($value), 'en');
        try {
            if (!preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})[ T](\d{1,2}):(\d{2})$/', $value, $parts)) throw new \InvalidArgumentException;
            $converter = CalendarConverterFactory::make(LocaleCatalog::calendar());
            [$year, $month, $day] = $converter->toGregorian((int) $parts[1], (int) $parts[2], (int) $parts[3]);
            if (!checkdate($month, $day, $year) || (int) $parts[4] > 23 || (int) $parts[5] > 59) throw new \InvalidArgumentException;
            $roundtrip = $converter->fromGregorian($year, $month, $day);
            if (array_values($roundtrip) !== [(int) $parts[1], (int) $parts[2], (int) $parts[3]]) throw new \InvalidArgumentException;
            return Carbon::create($year, $month, $day, (int) $parts[4], (int) $parts[5], 0, config('app.timezone'))->toIso8601String();
        } catch (Throwable) {
            throw ValidationException::withMessages(['due_local' => [__('kanban.due_error')]]);
        }
    }
}
