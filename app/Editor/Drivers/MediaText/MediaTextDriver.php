<?php

declare(strict_types=1);

namespace App\Editor\Drivers\MediaText;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the media and text content block. */
final class MediaTextDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.mediaText')
            ->category('media')
            ->icon('layout')
            ->clientClass('StructuredTool')
            ->schema([
                'text' => 'string?',
                'file' => 'array?',
            ])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields'   => ['text' => ['type' => 'textarea']],
                    'media'    => 'image',
                    'multiple' => false,
                    'nested'   => false,
                ],
            ])
            ->mediaPaths(['file']);
    }
}
