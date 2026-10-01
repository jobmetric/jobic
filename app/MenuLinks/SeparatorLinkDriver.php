<?php

declare(strict_types=1);

namespace App\MenuLinks;

use App\Contracts\MenuLinkDriver;
use JobMetric\Form\FormBuilder;

/** Render a non-clickable separator in a menu. */
final class SeparatorLinkDriver implements MenuLinkDriver
{
    public function key(): string
    {
        return 'separator';
    }

    public function label(): string
    {
        return __('taxonomies.link_driver_separator');
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
