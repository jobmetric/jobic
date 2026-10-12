<?php

declare(strict_types=1);

namespace App\Support;

use Closure;

/** Hold the shared server and browser definition of a content block. */
final readonly class EditorDriverDefinition
{
    /**
     * Create an immutable block definition.
     *
     * @param string $key
     * @param string $label
     * @param string $clientClass
     * @param array<string, string> $schema
     * @param array<string, mixed> $clientConfig
     * @param Closure|null $validator
     * @param array<string, mixed> $presentation
     *
     * @return void
     */
    public function __construct(
        public string $key,
        public string $label,
        public string $clientClass,
        public array $schema,
        public array $clientConfig = [],
        public ?Closure $validator = null,
        public array $presentation = [],
    ) {
    }

    /**
     * Validate block attributes before persistence or rendering.
     *
     * @param mixed $data
     *
     * @return bool
     */
    public function validate(mixed $data): bool
    {
        if (! is_array($data)) {
            return false;
        }
        foreach ($this->schema as $key => $type) {
            $optional = str_ends_with($type, '?');
            $type = rtrim($type, '?');

            if (! array_key_exists($key, $data)) {
                if ($optional) {
                    continue;
                }

                return false;
            }

            $value = $data[$key];
            $valid = match ($type) {
                'string' => is_string($value),
                'integer' => is_int($value) || (is_string($value) && ctype_digit($value)),
                'boolean' => is_bool($value),
                'array' => is_array($value),
                'number' => is_int($value) || is_float($value),
                default => false,
            };
            if (! $valid) {
                return false;
            }
        }

        return $this->validator === null || (bool) ($this->validator)($data);
    }
}
