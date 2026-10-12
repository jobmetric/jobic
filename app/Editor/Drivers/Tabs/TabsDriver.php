<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Tabs;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the tabs content block. */
final class TabsDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.tabs')
            ->category('design')
            ->icon('layout-navbar')
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
