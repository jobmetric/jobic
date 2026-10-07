<?php

declare(strict_types=1);

namespace App\Support;

use Closure;

/** Immutable view of a registered Editor.js tool. */
final class EditorDriverDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $label,
        public readonly string $clientClass,
        public readonly array $schema,
        public readonly array $clientConfig = [],
        public readonly ?Closure $validator = null,
    ) {}

    public function validate(mixed $data): bool
    {
        if ($this->validator !== null) { return (bool) ($this->validator)($data); }
        if (! is_array($data)) { return false; }
        foreach ($this->schema as $key => $type) {
            if (! array_key_exists($key, $data)) { return false; }
            $valid = match ($type) {
                'string' => is_string($data[$key]),
                'integer' => is_int($data[$key]) || (is_string($data[$key]) && ctype_digit($data[$key])),
                'boolean' => is_bool($data[$key]),
                'array' => is_array($data[$key]),
                'number' => is_int($data[$key]) || is_float($data[$key]),
                default => true,
            };
            if (! $valid) { return false; }
        }
        return true;
    }
}
