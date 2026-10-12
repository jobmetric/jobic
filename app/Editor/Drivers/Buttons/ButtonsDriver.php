<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Buttons;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the buttons content block. */
final class ButtonsDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.buttons')
            ->category('design')
            ->icon('mouse-pointer')
            ->clientClass('StructuredTool')
            ->schema([
                'text' => 'string?',
                'url'  => 'string?',
            ])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields' => [
                        'text' => [],
                        'url'  => [],
                    ],
                    'media' => null,
                    'multiple' => false,
                    'nested' => false,
                ],
            ]);
    }
}
