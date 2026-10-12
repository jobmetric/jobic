<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Spacer;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the spacer content block. */
final class SpacerDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.spacer')
            ->category('design')
            ->icon('arrows-left-right')
            ->clientClass('StructuredTool')
            ->schema(['height' => 'number?'])
            ->settings([
                'height' => [
                    'label' => 'editor.fields.height',
                    'type'  => 'number',
                    'min'   => 0,
                    'max'   => 500,
                ],
            ])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields'   => [
                        'height' => [
                            'type' => 'number',
                            'default' => 24,
                        ],
                    ],
                    'media'    => null,
                    'multiple' => false,
                    'nested'   => false,
                ],
            ]);
    }
}
