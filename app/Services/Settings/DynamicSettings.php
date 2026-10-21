<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Facades\Hooks;
use App\Settings\SocialNetworksSetting;
use Illuminate\Support\Facades\Log;
use JobMetric\Form\FormBuilder;
use JobMetric\PackageCore\Output\Response;
use JobMetric\Setting\Contracts\AbstractSetting;
use JobMetric\Setting\Facades\Setting;
use JobMetric\Setting\Facades\SettingRegistry;
use ReflectionMethod;
use Throwable;

/**
 * Resolve, present, and persist discovered database-backed setting forms.
 */
class DynamicSettings
{
    /**
     * Return normalized descriptors for every registered setting form.
     *
     * @return array<int, array<string, string>>
     */
    public function descriptors(): array
    {
        return array_map(function (string $class): array {
            $setting = app($class);

            return [
                'class'       => $class,
                'application' => $setting->application(),
                'key'         => $setting->key(),
                'form_name'   => $setting->formName(),
                'title'       => $setting->title(),
                'description' => $setting->description(),
                'icon'        => method_exists($setting, 'icon') ? $setting->icon() : 'settings',
                'permission'  => method_exists($setting, 'permission') ? $setting->permission() : 'admin.settings.' . strtolower($setting->application()) . '.' . strtolower($setting->key()),
            ];
        }, $this->classes());
    }

    /**
     * Resolve the registered permission for a dynamic settings route.
     *
     * @param string $application
     * @param string $key
     *
     * @return string
     */
    public function permission(string $application, string $key): string
    {
        $setting = $this->resolve($application, $key);

        return method_exists($setting, 'permission') ? $setting->permission() : 'admin.settings.' . strtolower($application) . '.' . strtolower($key);
    }

    /**
     * Resolve a registered setting class from its public route identity.
     */
    public function resolve(string $application, string $key): AbstractSetting
    {
        foreach ($this->classes() as $class) {
            $setting = app($class);
            if ($setting->application() === $application && $setting->key() === $key) {
                return $setting;
            }
        }

        abort(404);
    }

    /**
     * Return package-discovered forms plus application-owned registrations.
     *
     * @return array<int, class-string<AbstractSetting>>
     */
    public function classes(): array
    {
        return array_values(array_unique(array_merge(SettingRegistry::all(), [
            SocialNetworksSetting::class,
        ])));
    }

    /**
     * Resolve the final builder through package and CMS extension points.
     */
    public function builder(AbstractSetting $setting): FormBuilder
    {
        if (method_exists($setting, 'resolvedForm')) {
            return $setting->resolvedForm();
        }

        $builder = Hooks::filter('jobic.admin.settings.form.builder', $setting->form(), [
            'application' => $setting->application(),
            'key'         => $setting->key(),
            'form_name'   => $setting->formName(),
            'user_id'     => auth()->id(),
        ]);
        if (! $builder instanceof FormBuilder) {
            throw new \UnexpectedValueException('The dynamic settings form hook must return a FormBuilder.');
        }

        return $builder;
    }

    /**
     * Return safe presentation values without exposing sensitive fields.
     *
     *
     * @return array<string, mixed>
     */
    public function values(AbstractSetting $setting, FormBuilder $builder): array
    {
        $stored = Setting::form($setting->formName());
        $values = [];
        foreach (
            $builder->build()
                ->getAllCustomFields() as $field) {
            $name = $field->params['name'] ?? null;
            if (! is_string($name) || $field->type === 'password' || ($field->params['sensitive'] ?? false)) {
                continue;
            }

            $values[$name] = $stored[$setting->formName() . '_' . $name] ?? null;
        }

        return $values;
    }

    /**
     * Persist one validated dynamic form with the documented hook lifecycle.
     *
     * @param array<string, mixed> $data
     *
     * @throws Throwable
     */
    public function save(AbstractSetting $setting, FormBuilder $builder, array $data, int $userId): Response
    {
        $sensitive = [];
        foreach (
            $builder->build()
                ->getAllCustomFields() as $field) {
            if ($field->type === 'password' || ($field->params['sensitive'] ?? false)) {
                $sensitive[] = $field->params['name'] ?? '';
            }
        }

        $context = [
            'application' => $setting->application(),
            'key'         => $setting->key(),
            'form_name'   => $setting->formName(),
            'user_id'     => $userId,
            'fields'      => array_values(array_diff(array_keys($data), $sensitive)),
        ];

        Hooks::action('jobic.settings.dynamic.saving', $context);

        try {
            $dispatch = new ReflectionMethod(app('Setting'), 'dispatch');
            if ($dispatch->getNumberOfParameters() >= 4) {
                Setting::dispatch($setting::class, $data, true, $builder);
            } else {
                Setting::dispatchByForm($setting->formName(), collect($data)
                    ->mapWithKeys(fn (
                        mixed $value,
                        string $key
                    ): array => [$setting->formName() . '_' . $key => $value])
                    ->all());
            }
        } catch (Throwable $exception) {
            Log::error('Dynamic settings persistence failed.', [
                'form_name'      => $setting->formName(),
                'exception_type' => $exception::class,
                'error'          => $exception->getMessage(),
            ]);

            return Response::make(false, trans('admin.save_failed'), null, 422);
        }
        Hooks::notify('jobic.settings.dynamic.saved', $context);

        return Response::make(true, trans('admin.saved'));
    }
}
