<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts;

use App\Rules\EditorContentRule;
use App\Support\EditorDriverRegistry;
use App\Support\LocaleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use JobMetric\Media\Models\Media;
use JobMetric\Post\Facades\PostTypeRegistry;

/**
 * Validate one language's editable post fields from the list dialog.
 */
final class PostTranslationRequest extends FormRequest
{
    /**
     * Keep authorization at the registered post type's route boundary.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Restrict submitted fields to the active type's translated fields.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $builder = PostTypeRegistry::for((string) $this->route('type'));

        $fields = $builder->getTranslationFields();

        $rules = [
            'locale' => [
                'required',
                'string',
                Rule::in(LocaleCatalog::codes()),
            ],
            'fields' => [
                'required',
                'array:' . implode(',', $fields),
            ],
        ];
        if ($builder->hasOptimisticLocking()) {
            $rules['_version'] = [
                'required',
                'string',
                'size:64',
            ];
        }

        foreach ($fields as $field) {
            $rules['fields.' . $field] = match ($field) {
                'title' => ['required', 'string', 'max:255'],
                'meta_title' => ['nullable', 'string', 'max:255'],
                'meta_description' => ['nullable', 'string', 'max:500'],
                'meta_keywords' => ['nullable', 'string', 'max:1000'],
                'content' => ['nullable', new EditorContentRule(app(EditorDriverRegistry::class))],
                default => ['nullable', 'string', 'max:100000'],
            };
        }

        return $rules;
    }

    /**
     * Add the locale route parameter to the validated request payload.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        $this->merge(['locale' => (string) $this->route('locale')]);
    }

    /**
     * Ensure images embedded in Editor.js still reference existing image files.
     *
     * @param mixed $validator
     *
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()
                ->isNotEmpty()) {
                return;
            }

            $content = $this->input('fields.content');
            $document = is_string($content) ? json_decode($content, true) : $content;

            $ids = [];
            foreach ((array) ($document['blocks'] ?? []) as $block) {
                if (is_array($block) && ($block['type'] ?? null) === 'image' && is_numeric($block['data']['file']['id'] ?? null)) {
                    $ids[] = (int) $block['data']['file']['id'];
                }
            }

            $ids = array_values(array_unique($ids));
            if ($ids === []) {
                return;
            }

            $valid = Media::query()
                ->whereIn('id', $ids)
                ->where('type', 'f')
                ->where('mime_type', 'like', 'image/%')
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
            if (array_diff($ids, $valid) !== []) {
                $validator->errors()
                    ->add('fields.content', trans('posts.validation.editor_image_media'));
            }
        });
    }
}
