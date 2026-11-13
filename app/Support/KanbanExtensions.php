<?php

declare(strict_types=1);

namespace App\Support;

use App\Facades\Hooks;
use UnexpectedValueException;

/** Provide scoped, presentation-only extension data for authorized Kanban reads. */
final class KanbanExtensions
{
    /**
     * Render trusted extension views after the owning board or card is authorized.
     *
     * @param string $zone
     * @param array<string, int|string|null> $context
     *
     * @return string
     */
    public static function view(string $zone, array $context): string
    {
        return (string) Hooks::render('jobic.admin.posts.kanban.'.$zone, $context);
    }

    /**
     * Filter an isolated extension namespace without allowing core fields to change.
     *
     * @param string $zone
     * @param array<string, int|string|null> $context
     *
     * @return array<string, string|int|float|bool|null>
     * @throws UnexpectedValueException
     */
    public static function data(string $zone, array $context): array
    {
        $value = Hooks::filter('jobic.posts.kanban.'.$zone.'.extra', [], $context);
        if (! is_array($value) || count($value) > 20) {
            throw new UnexpectedValueException('Invalid Kanban extension data.');
        }
        foreach ($value as $key => $entry) {
            if (! is_string($key) || ! preg_match('/^[a-z][a-z0-9_]{0,63}$/', $key)
                || (! is_scalar($entry) && $entry !== null)) {
                throw new UnexpectedValueException('Invalid Kanban extension data.');
            }
        }

        return $value;
    }
}
