<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Gallery;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the gallery content block. */
final class GalleryDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.gallery')
            ->category('media')
            ->icon('apps')
            ->clientClass('StructuredTool')
            ->schema([
                'caption' => 'string?',
                'files'   => 'array?',
            ])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields' => ['caption' => []],
                    'media' => 'image',
                    'multiple' => true,
                    'nested' => false,
                ],
            ])
            ->mediaPaths(['files.*']);
    }
}
