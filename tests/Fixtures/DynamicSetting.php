<?php

declare(strict_types=1);

namespace Tests\Fixtures;

use JobMetric\CustomField\CustomFieldBuilder;
use JobMetric\Form\FormBuilder;
use JobMetric\Form\Tab\TabBuilder;
use JobMetric\Setting\Contracts\AbstractSetting;

/** Provide a representative discovered setting form for feature tests. */
class DynamicSetting extends AbstractSetting
{
    /** Return the owning application slug. */
    public function application(): string
    {
        return 'test';
    }

    /** Return the form route and storage key. */
    public function key(): string
    {
        return 'dynamic';
    }

    /** Return the translated title key. */
    public function title(): string
    {
        return 'admin.settings';
    }

    /** Return the translated description key. */
    public function description(): string
    {
        return 'admin.settings_intro';
    }

    /** Build public and sensitive test fields. */
    public function form(): FormBuilder
    {
        return (new FormBuilder)->tab(function (TabBuilder $tab): void {
            $tab->id('general')->label('admin.settings')->selected()
                ->customField(function (CustomFieldBuilder $field): void {
                    $field::text()->name('label')->label('admin.settings')->validation('required|string|max:40');
                })->customField(function (CustomFieldBuilder $field): void {
                    $field::password()->name('secret')->label('admin.password')->validation('nullable|string|min:8');
                });
        });
    }
}
