<?php

namespace Tests\Fixtures\Extensions\Module\Alpha;

use JobMetric\Extension\Contracts\AbstractExtension;
use JobMetric\Extension\Kernel\ExtensionCore;
use JobMetric\Form\FormBuilder;

class Alpha extends AbstractExtension
{
    public function configuration(ExtensionCore $extension): void {}

    public function form(): FormBuilder
    {
        return new FormBuilder;
    }

    public function handle(array $options = []): ?string
    {
        return 'alpha';
    }
}
