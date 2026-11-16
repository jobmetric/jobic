<?php

namespace Tests\Fixtures\Extensions\PaymentMethod\Beta;

use JobMetric\CustomField\CustomFieldBuilder;
use JobMetric\Form\FormBuilder;
use JobMetric\Form\Tab\TabBuilder;
use Tests\Fixtures\Extensions\Module\Alpha\Alpha;

class Beta extends Alpha
{
    public function form(): FormBuilder
    {
        return (new FormBuilder)->tab(function (TabBuilder $tab): void {
            $tab->id('main')->label('extensions.plugins')->customField(function (CustomFieldBuilder $field): void {
                $field::text()->name('label')->label('extensions.name')->validation('required|string|max:100');
            })->customField(function (CustomFieldBuilder $field): void {
                $field::password()->name('api_secret')->label('extensions.api_secret')->validation('nullable|string|min:8');
            });
        });
    }

    public function handle(array $options = []): ?string
    {
        return $options['label'] ?? 'missing-options';
    }
}
