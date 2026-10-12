<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Details;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the details content block. */
final class DetailsDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.details')
            ->category('text')
            ->icon('info')
            ->clientClass('StructuredTool')
            ->schema([
                'title' => 'string?',
                'text'  => 'string?',
            ])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields' => [
                        'title' => [],
                        'text'  => ['type' => 'textarea'],
                    ],
                    'media' => null,
                    'multiple' => false,
                    'nested' => false,
                ],
            ]);
    }
}
