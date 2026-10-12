<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Icon;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the icon content block. */
final class IconDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.icon')
            ->category('media')
            ->icon('star')
            ->clientClass('StructuredTool')
            ->schema([
                'name'    => 'string?',
                'caption' => 'string?',
            ])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields'   => [
                        'name'    => [],
                        'caption' => [],
                    ],
                    'media'    => null,
                    'multiple' => false,
                    'nested'   => false,
                ],
            ]);
    }
}
