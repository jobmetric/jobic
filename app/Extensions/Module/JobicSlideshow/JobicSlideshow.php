<?php
declare(strict_types=1);

namespace App\Extensions\Module\JobicSlideshow;

use JobMetric\Extension\Contracts\AbstractExtension;
use JobMetric\Extension\Kernel\ExtensionCore;
use JobMetric\Form\FormBuilder;

/** Register a bundled slideshow extension. */
final class JobicSlideshow extends AbstractExtension
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
     * Render the starter slideshow content.
     *
     * @param array<string, mixed> $options
     *
     * @return string|null
     */
    public function handle(array $options = []): ?string
    {
        return view('site.starter.slideshow')->render();
    }
}
