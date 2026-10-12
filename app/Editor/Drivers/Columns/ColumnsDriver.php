<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Columns;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the columns content block. */
final class ColumnsDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.columns')
            ->category('design')
            ->icon('panel')
            ->clientClass('StructuredTool')
            ->schema(['sections' => 'array'])
            ->defaults([
                'sections' => [
                    [
                        'title'  => '',
                        'blocks' => [],
                    ],
                ],
            ])
            ->clientConfig([
                'config' => [
                    'fields'   => [],
                    'media'    => null,
                    'multiple' => false,
                    'nested'   => true,
                ],
            ]);
    }
}
