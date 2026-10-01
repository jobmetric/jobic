<?php

declare(strict_types=1);

namespace App\Contracts;

use JobMetric\Form\FormBuilder;

/** Define one extensible destination type for menu taxonomy items. */
interface MenuLinkDriver
{
    public function key(): string;

    public function label(): string;

    public function form(): FormBuilder;

    /** @return array<string, mixed> */
    public function rules(): array;
}
