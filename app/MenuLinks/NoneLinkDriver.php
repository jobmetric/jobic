<?php

declare(strict_types=1);

namespace App\MenuLinks;

use App\Contracts\MenuLinkDriver;
use JobMetric\Form\FormBuilder;

/** Render a label-only menu item without a destination. */
final class NoneLinkDriver implements MenuLinkDriver
{
    public function key(): string
    {
        return 'none';
    }

    public function label(): string
    {
        return __('taxonomies.link_driver_none');
    }

    public function form(): FormBuilder
    {
        return new FormBuilder;
    }

    public function rules(): array
    {
        return [];
    }
}
