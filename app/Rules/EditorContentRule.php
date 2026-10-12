<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\EditorDriverRegistry;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Validate Editor.js output against the currently registered drivers. */
final readonly class EditorContentRule implements ValidationRule
{
    /**
     * Validate new data while retaining unchanged unavailable blocks from stored content.
     *
     * @param EditorDriverRegistry $drivers
     * @param mixed $original
     *
     * @return void
     */
    public function __construct(private EditorDriverRegistry $drivers, private mixed $original = null)
    {
    }

    /**
     * Check block data recursively while preserving unchanged unavailable tools.
     *
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     *
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($value === null || $value === '') {
            return;
        }
        $document = is_string($value) ? json_decode($value, true) : $value;
        if (! is_array($document) || ! is_array($document['blocks'] ?? null)) {
            $fail(trans('posts.validation.editor_json'));

            return;
        }
        if (count($document['blocks']) > 1000) {
            $fail(trans('posts.validation.editor_limit'));

            return;
        }

        $pending = array_map(static fn ($block) => [
            $block,
            0,
        ], $document['blocks']);
        $count = 0;
        while ($pending !== []) {
            [
                $block,
                $depth,
            ] = array_pop($pending);

            $index = $count++;
            if ($count > 1000 || $depth > 8) {
                $fail(trans('posts.validation.editor_limit'));

                return;
            }

            if (! is_array($block) || ! is_string($block['type'] ?? null) || ! is_array($block['data'] ?? null)) {
                $fail(trans('posts.validation.editor_block', ['number' => $index + 1]));

                return;
            }

            if (! $this->drivers->has($block['type'])) {
                $original = is_string($this->original) ? json_decode($this->original, true) : $this->original;
                $stored = (array) ($original['blocks'] ?? []);
                $found = false;
                while ($stored !== []) {
                    $candidate = array_pop($stored);
                    if (is_array($candidate) && ($candidate['type'] ?? null) === $block['type'] && ($candidate['data'] ?? null) === $block['data']) {
                        $found = true;
                        break;
                    }

                    foreach ($candidate['data']['sections'] ?? [] as $section) {
                        foreach ($section['blocks'] ?? [] as $child) {
                            $stored[] = $child;
                        }
                    }
                }

                if ($found) {
                    continue;
                }

                $fail(trans('posts.validation.editor_unsupported', ['type' => $block['type']]));

                return;
            }

            if (! $this->drivers->get($block['type'])
                ->validate($block['data'])) {
                $fail(trans('posts.validation.editor_block_data', ['number' => $index + 1]));

                return;
            }

            $definition = $this->drivers->get($block['type']);
            foreach ($definition->presentation['mediaPaths'] ?? [] as $path) {
                $files = data_get($block['data'], $path, []);
                $files = str_contains($path, '*') ? $files : [$files];
                foreach ($files as $file) {
                    if ($file === []) {
                        continue;
                    }

                    if (! is_array($file) || ! is_string($file['url'] ?? null) || ! in_array(strtolower((string) parse_url($file['url'], PHP_URL_SCHEME)), [
                            'http',
                            'https',
                        ], true)) {
                        $fail(trans('posts.validation.editor_block_data', ['number' => $index + 1]));

                        return;
                    }
                }
            }

            if ($definition->clientConfig['config']['nested'] ?? false) {
                foreach ($block['data']['sections'] as $section) {
                    if (! is_array($section) || ! is_string($section['title'] ?? null) || ! is_array($section['blocks'] ?? null)) {
                        $fail(trans('posts.validation.editor_block_data', ['number' => $index + 1]));

                        return;
                    }

                    foreach ($section['blocks'] as $child) {
                        $pending[] = [
                            $child,
                            $depth + 1,
                        ];
                    }
                }
            }
        }
    }
}
