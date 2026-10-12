<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Math;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the formula content block. */
final class MathDriver implements EditorBlockDriver
{
    /**
     * Register the editable data and browser controls.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.math')
            ->category('text')
            ->icon('research')
            ->clientClass('StructuredTool')
            ->schema(['formula' => 'string?'])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields'   => ['formula' => ['type' => 'textarea']],
                    'media'    => null,
                    'multiple' => false,
                    'nested'   => false,
                ],
            ]);
    }
}
