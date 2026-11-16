<?php

declare(strict_types=1);

namespace App\Extensions\Component\JobicSystemExample\Extensions\Module\JobicSystemCards;

use JobMetric\Extension\Contracts\AbstractExtension;
use JobMetric\Extension\Kernel\ExtensionCore;
use JobMetric\Form\FormBuilder;

/**
 * Demonstrate a separately managed child whose files belong to the system component.
 */
class JobicSystemCards extends AbstractExtension
{
    /**
     * Keep this child's resources scoped to its own extension lifecycle.
     *
     * @param  ExtensionCore  $extension
     *
     * @return void
     */
    public function configuration(ExtensionCore $extension): void {}

    /**
     * Provide an empty configuration form for the ownership example.
     *
     * @return FormBuilder
     */
    public function form(): FormBuilder
    {
        return new FormBuilder;
    }

    /**
     * Return escaped public text when this child is explicitly executed.
     *
     * @param  array<string, mixed>  $options
     *
     * @return string|null
     */
    public function handle(array $options = []): ?string
    {
        return e(static::title());
    }
}
