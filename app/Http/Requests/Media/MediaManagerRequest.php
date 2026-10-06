<?php

declare(strict_types=1);

namespace App\Http\Requests\Media;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Validate the file manager's bounded commands and browsing options. */
class MediaManagerRequest extends FormRequest
{
    /** Allow the route's registered permission middleware to authorize access.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /** Validate operation inputs without accepting arbitrary storage paths.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'parent_id'            => ['nullable', 'integer', 'min:0'],
            'destination_id'       => ['nullable', 'integer', 'min:0'],
            'name'                 => ['sometimes', 'required', 'string', 'max:200'],
            'file'                 => ['sometimes', 'required', 'file'],
            'item_ids'             => ['sometimes', 'required', 'array', 'min:1', 'max:100'],
            'item_ids.*'           => ['integer', 'distinct', 'min:1'],
            'ids'                  => ['sometimes', 'required', 'array', 'min:1', 'max:100'],
            'ids.*'                => ['integer', 'distinct', 'min:1'],
            'mode'                 => ['sometimes', Rule::in(['copy', 'move'])],
            'resolution'           => ['nullable', Rule::in(['skip', 'replace', 'keep_both'])],
            'allow_duplicate'      => ['sometimes', 'boolean'],
            'confirm_used_replace' => ['sometimes', 'boolean'],
            'destination'          => ['sometimes', Rule::in(['current', 'named_folder'])],
            'search'               => ['nullable', 'string', 'max:120'],
            'group'                => ['nullable', Rule::in(['image', 'video', 'audio', 'document', 'archive'])],
            'scope'                => ['nullable', Rule::in(['folder', 'global'])],
            'sort'                 => ['nullable', Rule::in(['name', 'created_at', 'updated_at', 'type'])],
            'direction'            => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page'             => ['nullable', 'integer', 'between:12,100'],
            'page'                 => ['nullable', 'integer', 'between:1,10000'],
            'sheet'                => ['nullable', 'integer', 'between:0,100'],
            'offset'               => ['nullable', 'integer', 'between:0,100000'],
            'limit'                => ['nullable', 'integer', 'between:1,250'],
        ];
    }
}
