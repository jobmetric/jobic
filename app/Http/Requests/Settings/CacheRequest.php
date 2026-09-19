<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Support\Installation\Fields;
use Illuminate\Foundation\Http\FormRequest;

/** Validate supported file and Redis cache configuration. */
class CacheRequest extends FormRequest
{
    /**
     * Limit diagnostics and mutations to signed-in administrators.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Allow Redis credentials only for the supported cache backend.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return self::rulesFor($this->all());
    }

    /**
     * Reuse the same rules for HTTP and domain validation.
     *
     * @param array<string, mixed> $input
     * @param array<string, mixed> $context
     *
     * @return array<string, mixed>
     */
    public static function rulesFor(array $input, array $context = []): array
    {
        return [
                'CACHE_STORE' => [
                    'required',
                    'in:file,redis',
                ],
            ] + (($input['CACHE_STORE'] ?? '') === 'redis' ? Fields::rules(Fields::groups(3)['redis']) : []);
    }
}
