<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Cover;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the cover content block. */
final class CoverDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.cover')
            ->category('media')
            ->icon('file-image')
            ->clientClass('StructuredTool')
            ->schema([
                'title' => 'string?',
                'text'  => 'string?',
                'file'  => 'array?',
            ])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields' => [
                        'title' => [],
                        'text'  => ['type' => 'textarea'],
                    ],
                    'media' => 'image',
                    'multiple' => false,
                    'nested' => false,
                ],
            ])
            ->mediaPaths(['file']);
    }
}
