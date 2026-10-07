<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\EditorDriverRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Validate Editor.js output against the currently registered drivers. */
final class EditorContentRule implements ValidationRule
{
    public function __construct(private readonly EditorDriverRegistry $drivers) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') { return; }
        $document = is_string($value) ? json_decode($value, true) : $value;
        if (! is_array($document) || ! is_array($document['blocks'] ?? null)) {
            $fail(trans('posts.validation.editor_json'));
            return;
        }
        if (count($document['blocks']) > 1000) { $fail(trans('posts.validation.editor_limit')); return; }

        foreach ($document['blocks'] as $index => $block) {
            if (! is_array($block) || ! is_string($block['type'] ?? null) || ! is_array($block['data'] ?? null)) {
                $fail(trans('posts.validation.editor_block', ['number' => $index + 1]));
                return;
            }
            if (! $this->drivers->has($block['type'])) {
                $fail(trans('posts.validation.editor_unsupported', ['type' => $block['type']]));
                return;
            }
            if (! $this->drivers->get($block['type'])->validate($block['data'])) {
                $fail(trans('posts.validation.editor_block_data', ['number' => $index + 1]));
                return;
            }
        }
    }
}
