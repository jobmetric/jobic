<?php

declare(strict_types=1);

namespace App\Listeners\Settings;

use App\Support\Hooks;
use JobMetric\Form\FormBuilder;
use JobMetric\Setting\Events\BuildingSettingForm;
use UnexpectedValueException;

/**
 * Apply CMS hooks to setting builders and enforce safe field identities.
 */
readonly class ExtendSettingForm
{
    /**
     * Create the setting form extension listener.
     *
     * @param Hooks $hooks
     *
     * @return void
     */
    public function __construct(private Hooks $hooks)
    {
    }

    /**
     * Apply external fields and reject duplicate or reserved field names.
     *
     * @param BuildingSettingForm $event
     *
     * @return void
     */
    public function handle(BuildingSettingForm $event): void
    {
        $builder = $this->hooks->filter('jobic.admin.settings.form.builder', $event->builder, [
            'application' => $event->setting->application(),
            'key'         => $event->setting->key(),
            'form_name'   => $event->setting->formName(),
            'user_id'     => auth()->id(),
        ]);

        if (! $builder instanceof FormBuilder) {
            throw new UnexpectedValueException('The dynamic settings form hook must return a FormBuilder.');
        }

        $names = [];
        foreach ($builder->build()->getAllCustomFields() as $field) {
            $name = $field->params['name'] ?? null;
            if (! is_string($name) || $name === '' || str_starts_with($name, '_') || in_array($name, $names, true)) {
                throw new UnexpectedValueException('Dynamic setting field names must be unique, non-empty, and not reserved.');
            }

            $names[] = $name;
        }

        $event->builder = $builder;
    }
}
