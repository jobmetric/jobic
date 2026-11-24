<?php
declare(strict_types=1);

namespace App\Extensions\Theme\JobicStarterTheme;

use JobMetric\Extension\Contracts\AbstractExtension;
use JobMetric\Extension\Kernel\ExtensionCore;
use JobMetric\Form\FormBuilder;

/** Register a bundled website theme extension. */
final class JobicStarterTheme extends AbstractExtension
{
    /**
     * Configure the bundled extension.
     *
     * @param ExtensionCore $extension
     *
     * @return void
     */
    public function configuration(ExtensionCore $extension): void {}

    /**
     * Provide the instance configuration form.
     *
     * @return FormBuilder
     */
    public function form(): FormBuilder
    {
        return new FormBuilder;
    }

    /**
     * Render the starter theme content.
     *
     * @param array<string, mixed> $options
     *
     * @return string|null
     */
    public function handle(array $options = []): ?string
    {
        return null;
    }
}
