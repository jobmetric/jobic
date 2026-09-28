<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Services\Settings\DynamicSettings;
use App\Settings\SocialNetworksSetting;
use App\Support\SocialNetworks\SocialNetworkRegistry;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\CircularDependencyException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use JobMetric\Form\FormBuilder;
use JobMetric\Form\Http\Requests\FormBuilderRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Validate a discovered dynamic settings form.
 */
class DynamicSettingRequest extends FormRequest
{
    private ?FormBuilder $resolvedBuilder = null;

    /**
     * Determine whether middleware-authorized requests may be validated.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Build rules from the exact form definition used by persistence.
     *
     * @return array<string, mixed>
     * @throws BindingResolutionException
     * @throws CircularDependencyException
     */
    public function rules(): array
    {
        $rules = app(FormBuilderRequest::class, [
            'formBuilder' => $this->builder(),
        ])->rules();

        $setting = app(DynamicSettings::class)->resolve((string) $this->route('application'), (string) $this->route('key'));

        if (method_exists($setting, 'validationRules')) {
            $rules = array_merge($rules, $setting->validationRules());
        }

        if ($setting instanceof SocialNetworksSetting) {
            $rules['networks.*.type'][] = Rule::in(array_keys(app(SocialNetworkRegistry::class)->all()));
        }

        return $rules;
    }

    /**
     * Remove visually empty repeatable rows before validation and persistence.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        if ($this->route('application') !== 'jobic' || $this->route('key') !== 'social_networks') {
            return;
        }

        $rows = array_filter((array) $this->input('networks', []), static function (mixed $row): bool {
            return is_array($row) && (trim((string) ($row['type'] ?? '')) !== '' || trim((string) ($row['slug'] ?? '')) !== '');
        });

        $this->merge([
            'networks' => $rows,
        ]);
    }

    /**
     * Build translated attributes from the dynamic fields.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return app(FormBuilderRequest::class, [
            'formBuilder' => $this->builder(),
        ])->attributes();
    }

    /**
     * Resolve and cache the requested extensible form builder.
     *
     * @return FormBuilder
     * @throws NotFoundHttpException
     */
    public function builder(): FormBuilder
    {
        if ($this->resolvedBuilder !== null) {
            return $this->resolvedBuilder;
        }

        foreach (app(DynamicSettings::class)->classes() as $class) {
            $setting = app($class);
            if ($setting->application() === $this->route('application') && $setting->key() === $this->route('key')) {
                return $this->resolvedBuilder = app(DynamicSettings::class)->builder($setting);
            }
        }

        throw new NotFoundHttpException;
    }
}
