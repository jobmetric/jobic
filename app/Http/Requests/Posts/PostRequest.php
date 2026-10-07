<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts;

use App\Rules\EditorContentRule;
use App\Support\LocaleCatalog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use JobMetric\Post\Facades\PostTypeRegistry;
use JobMetric\Post\Models\Post;
use JobMetric\Media\Models\Media;
use JobMetric\Url\Rules\SlugExistRule;

final class PostRequest extends FormRequest
{
    public function authorize(): bool { return auth()->check(); }

    protected function prepareForValidation(): void
    {
        $type = (string) $this->route('type');
        $builder = PostTypeRegistry::for($type);
        $media = $this->input('media', []);
        $media = is_array($media) ? $media : [];
        foreach ($media as $collection => $ids) {
            if (is_string($ids)) {
                $decoded = json_decode($ids, true);
                $media[$collection] = is_array($decoded) ? $decoded : [];
            }
        }

        foreach (['base' => [], ...($builder->get()['media-collections'] ?? [])] as $collection => $_) {
            $media[$collection] ??= [];
        }

        $taxonomyTypes = (array) ($builder->get()['taxonomy-types'] ?? []);
        $taxonomies = $this->input('taxonomies', []);
        $taxonomies = is_array($taxonomies) ? $taxonomies : [];
        foreach ($taxonomyTypes as $collection => $_) {
            $taxonomies[$collection] ??= [];
        }

        $this->merge(['media' => $media, 'taxonomies' => $taxonomies]);
    }

    public function rules(): array
    {
        $type = (string) $this->route('type');
        $builder = PostTypeRegistry::for($type);
        $postType = new Post;
        $postType->type = $type;
        $taxonomyTypes = $postType->taxonomyAllowTypes();
        $locales = LocaleCatalog::codes();
        $translationFields = $builder->get()['translation-fields'] ?? ['title', 'excerpt', 'content'];
        $rules = [
            'status' => ['required', 'string', Rule::in(['publish', 'future', 'draft', 'pending', 'private', 'archive', 'disabled'])],
            'published_at' => ['nullable', 'date'],
            'comment_status' => ['sometimes', 'boolean'],
            'password' => ['nullable', 'string', 'max:255'],
            'translation' => ['required', 'array:'.implode(',', $locales)],
            'taxonomies' => ['sometimes', 'array:'.implode(',', array_keys($taxonomyTypes))],
        ];
        if (array_key_exists('url-prefix', $builder->get())) {
            $rules['slug'] = ['required', 'string', 'max:100', new SlugExistRule(Post::class, $type, $this->route('post') ? (int) $this->route('post') : null)];
        }
        foreach ($locales as $locale) {
            $rules["translation.$locale"] = ['sometimes', 'array:'.implode(',', $translationFields)];
            foreach ($translationFields as $field) {
                $required = $field === 'title' && $locale === LocaleCatalog::resolve(app()->getLocale());
                $rules["translation.$locale.$field"] = $field === 'content'
                    ? [($required ? 'required' : 'nullable'), new EditorContentRule(app(\App\Support\EditorDriverRegistry::class))]
                    : [($required ? 'required' : 'nullable'), 'string', $field === 'title' ? 'max:255' : 'max:100000'];
            }
        }
        foreach ($taxonomyTypes as $collection => $definition) {
            $rules["taxonomies.$collection"] = ['sometimes', 'array', ...(($definition['multiple'] ?? true) ? [] : ['max:1'])];
            $rules["taxonomies.$collection.*"] = ['integer', 'distinct', Rule::exists(config('taxonomy.tables.taxonomy', 'taxonomies'), 'id')->where('type', $definition['type'])->where('status', true)];
        }
        $mediaCollections = ['base' => ['multiple' => false], ...($builder->get()['media-collections'] ?? [])];
        $rules['media'] = ['sometimes', 'array:'.implode(',', array_keys($mediaCollections))];
        foreach ($mediaCollections as $collection => $definition) {
            $rules["media.$collection"] = ['sometimes', 'array', ...(($definition['multiple'] ?? false) ? [] : ['max:1'])];
            $exists = Rule::exists(config('media.tables.media', 'media'), 'id')->where(function ($query) use ($definition): void {
                $query->where('type', 'f');
                $mimeTypes = (array) ($definition['mimeTypes'] ?? []);
                if ($mimeTypes !== []) {
                    $query->where(function ($mimeQuery) use ($mimeTypes): void {
                        foreach ($mimeTypes as $index => $mimeType) {
                            $method = $index === 0 ? 'where' : 'orWhere';
                            $mimeQuery->{$method}('mime_type', 'like', str_contains($mimeType, '/') ? $mimeType : $mimeType.'/%');
                        }
                    });
                }
            });
            $rules["media.$collection.*"] = ['integer', 'distinct', $exists];
        }
        $customFields = $builder->get()['custom-fields'] ?? [];
        if ($customFields !== []) {
            $rules['metadata'] = ['sometimes', 'array:'.implode(',', array_keys($customFields))];
        }
        foreach ($customFields as $key => $definition) {
            $rules['metadata.'.$key] = $definition['rules'] ?? ['nullable', 'string', 'max:255'];
        }
        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $references = [];
            foreach ((array) $this->input('translation', []) as $locale => $fields) {
                if (! is_array($fields)) {
                    continue;
                }
                $content = $fields['content'] ?? null;
                $document = is_string($content) ? json_decode($content, true) : $content;
                if (! is_array($document)) {
                    continue;
                }

                foreach ((array) ($document['blocks'] ?? []) as $block) {
                    if (! is_array($block) || ($block['type'] ?? null) !== 'image') {
                        continue;
                    }
                    $mediaId = $block['data']['file']['id'] ?? null;
                    if (is_numeric($mediaId) && (int) $mediaId > 0) {
                        $references[(int) $mediaId][] = (string) $locale;
                    }
                }
            }

            if ($references === []) {
                return;
            }

            $validIds = Media::query()
                ->whereIn('id', array_keys($references))
                ->where('type', 'f')
                ->where('mime_type', 'like', 'image/%')
                ->pluck('id')->map(static fn ($id): int => (int) $id)->all();

            foreach (array_diff(array_keys($references), $validIds) as $mediaId) {
                foreach (array_unique($references[$mediaId]) as $locale) {
                    $validator->errors()->add("translation.{$locale}.content", trans('posts.validation.editor_image_media'));
                }
            }
        });
    }
}
