<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Allow a theme to render navigation in the public website header.
 */
interface ThemeHeaderNavigation
{
    /**
     * Render the header navigation from the enabled theme instance settings.
     *
     * @param array<string, mixed> $options
     *
     * @return string|null
     */
    public function headerNavigation(array $options): ?string;
}
