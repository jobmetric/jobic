<?php

declare(strict_types=1);

namespace App\Support;

use App\Contracts\KanbanFilterDriver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

/** Register trusted board filters and reject missing drivers without dropping restrictions. */
class KanbanFilterRegistry
{
    /** @var array<string, KanbanFilterDriver> */
    private array $drivers = [];

    /**
     * Register a unique, stable driver key.
     *
     * @param string $key
     * @param KanbanFilterDriver $driver
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function register(string $key, KanbanFilterDriver $driver): void
    {
        if (! preg_match('/^[a-z][a-z0-9_]*$/', $key) || isset($this->drivers[$key])) {
            throw new InvalidArgumentException('Invalid or duplicate Kanban filter key.');
        }
        $this->drivers[$key] = $driver;
    }

    /**
     * Present the supported driver controls for a registered type.
     *
     * @param string $type
     *
     * @return array<string, mixed>
     */
    public function catalog(string $type): array
    {
        $result = [];
        foreach ($this->drivers as $key => $driver) {
            if ($driver->supports($type)) {
                $result[$key] = $driver->form($type);
            }
        }

        return $result;
    }

    /**
     * Validate every filter and preserve its explicit normalized restriction.
     *
     * @param string $type
     * @param array<int, array<string, mixed>> $filters
     *
     * @return array<int, array<string, mixed>>
     * @throws ValidationException
     */
    public function validate(string $type, array $filters): array
    {
        $result = [];
        foreach ($filters as $index => $filter) {
            $driver = $this->drivers[$filter['driver'] ?? ''] ?? null;
            if (! $driver || ! $driver->supports($type) || ! is_array($filter['config'] ?? null)) {
                throw ValidationException::withMessages(['filters' => __('kanban.invalid_filter')]);
            }
            try {
                $config = $driver->validate($type, $filter['config'] ?? []);
            } catch (ValidationException $error) {
                throw ValidationException::withMessages(['filters.'.$index => array_merge(...array_values($error->errors()))]);
            }
            $result[] = ['driver' => $filter['driver'], 'config' => $config];
        }

        return $result;
    }

    /**
     * Apply all stored filters using AND semantics.
     *
     * @param Builder $query
     * @param string $type
     * @param array<int, array<string, mixed>> $filters
     *
     * @return void
     * @throws ValidationException
     */
    public function apply(Builder $query, string $type, array $filters): void
    {
        foreach ($this->validate($type, $filters) as $filter) {
            $this->drivers[$filter['driver']]->apply($query, $filter['config']);
        }
    }
}
