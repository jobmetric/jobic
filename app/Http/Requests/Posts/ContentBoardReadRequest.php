<?php

declare(strict_types=1);

namespace App\Http\Requests\Posts;

use Illuminate\Foundation\Http\FormRequest;

/** Bound the board directory and deletion version inputs. */
class ContentBoardReadRequest extends FormRequest
{
    /**
     * Rely on registered route permissions.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Validate bounded directory reads and deletion preconditions.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return ['start' => 'sometimes|integer|min:0|max:1000000', 'length' => 'sometimes|integer|min:1|max:100',
            'search' => 'sometimes|nullable|string|max:160', 'sort' => 'sometimes|in:name,updated_at', 'direction' => 'sometimes|in:asc,desc', 'page' => 'sometimes|integer|min:1',
            'audience' => 'sometimes|nullable|in:open,restricted',
            'has_filters' => 'sometimes|nullable|in:0,1',
            'states' => 'sometimes|array|max:100', 'states.*' => 'required|string|max:80|distinct',
            'roles' => 'sometimes|array|max:100', 'roles.*' => 'required|integer|min:1|distinct',
            'users' => 'sometimes|array|max:100', 'users.*' => 'required|integer|min:1|distinct',
            'version' => ($this->isMethod('DELETE') ? 'required' : 'sometimes').'|integer|min:1'];
    }
}
