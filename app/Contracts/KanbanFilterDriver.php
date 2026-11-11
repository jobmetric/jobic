<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Builder;

/** Supply trusted presentation, validation and query restrictions for one board filter. */
interface KanbanFilterDriver
{
    /**
     * Determine support without loading presentation option catalogs.
     *
     * @param string $type
     *
     * @return bool
     */
    public function supports(string $type): bool;

    /**
     * Describe translated controls for one content type.
     *
     * @param string $type
     *
     * @return array<string, mixed>
     */
    public function form(string $type): array;

    /**
     * Validate and normalize stored filter configuration.
     *
     * @param string $type
     * @param array<string, mixed> $config
     *
     * @return array<string, mixed>
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validate(string $type, array $config): array;

    /**
     * Restrict an existing content query without broadening authorization.
     *
     * @param Builder $query
     * @param array<string, mixed> $config
     *
     * @return void
     */
    public function apply(Builder $query, array $config): void;
}
