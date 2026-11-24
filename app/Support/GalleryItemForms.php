<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use JobMetric\Post\Facades\PostTypeRegistry;

/** Resolve and validate the metadata schema for post media items. */
final class GalleryItemForms
{
    public const METADATA_KEY = 'gallery_item_metadata';

    /**
     * Return the registered Laravel Form fields for one collection.
     *
     * @param string $type
     * @param string $collection
     *
     * @return array
     */
    public static function fields(string $type, string $collection): array
    {
        $fields = PostTypeRegistry::for($type)->getMediaItemForm($collection)?->getAllCustomFields(false) ?? [];

        return array_values(array_filter($fields, static fn ($field): bool =>
            ! in_array($field->type, ['password', 'file'], true)
            && ! in_array($field->attributes['type'] ?? null, ['password', 'file'], true)));
    }

    /**
     * Validate item values against their registered schema and selected media IDs.
     *
     * @param string $type
     * @param array $media
     * @param array $submitted
     * @param array $existing
     *
     * @return array
     * @throws ValidationException
     */
    public static function validate(string $type, array $media, array $submitted, array $existing): array
    {
        foreach (array_keys($submitted) as $collection) {
            if (self::fields($type, (string) $collection) === []) {
                throw ValidationException::withMessages(['media_item_metadata' => __('validation.prohibited')]);
            }
        }

        foreach ($media as $collection => $ids) {
            $fields = self::fields($type, $collection);
            if ($fields === []) {
                continue;
            }

            $values = $submitted[$collection] ?? $existing[$collection] ?? [];
            if (! is_array($values)) {
                throw ValidationException::withMessages(["media_item_metadata.$collection" => __('validation.array')]);
            }
            $existing[$collection] = [];

            foreach ($ids as $id) {
                $path = "media_item_metadata.$collection.$id";
                $names = array_map(static fn ($field): string => $field->params['name'], $fields);
                $rules = [$path => ['array:'.implode(',', $names)]];
                foreach ($fields as $field) {
                    $rules[$path.'.'.$field->params['name']] = $field->validation ?? ['nullable', 'string', 'max:255'];
                }
                $item = Validator::make(
                    ['media_item_metadata' => [$collection => [$id => $values[$id] ?? []]]],
                    $rules,
                )->validate();
                $existing[$collection][$id] = data_get($item, $path, []);
            }
        }

        return $existing;
    }
}
