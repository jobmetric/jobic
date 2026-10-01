<?php

declare(strict_types=1);

namespace App\MenuLinks;

use App\Contracts\MenuLinkDriver;
use JobMetric\CustomField\CustomFieldBuilder;
use JobMetric\Form\FormBuilder;
use JobMetric\Form\Tab\TabBuilder;

/** Collect an absolute external URL for a menu item. */
final class ExternalUrlLinkDriver implements MenuLinkDriver
{
    public function key(): string
    {
        return 'external';
    }

    public function label(): string
    {
        return __('taxonomies.link_driver_external');
    }

    public function form(): FormBuilder
    {
        return (new FormBuilder)->tab(function (TabBuilder $tab): void {
            $tab->id('destination')->label('taxonomies.link_destination')->selected()
                ->customField(function (CustomFieldBuilder $field): void {
                    $field::text()->name('menu_link_url')->label('taxonomies.link_url')->validation('required|url|max:2048');
                });
        });
    }

    public function rules(): array
    {
        return ['menu_link_url' => ['required', 'url', 'max:2048']];
    }
}
