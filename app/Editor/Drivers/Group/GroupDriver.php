<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Group;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the group content block. */
final class GroupDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.group')
            ->category('design')
            ->icon('box')
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
