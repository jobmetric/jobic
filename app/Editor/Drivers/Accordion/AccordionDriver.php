<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Accordion;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the accordion content block. */
final class AccordionDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.accordion')
            ->category('design')
            ->icon('list-tree')
            ->clientClass('StructuredTool')
            ->schema([
                'sections' => 'array',
            ])
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
