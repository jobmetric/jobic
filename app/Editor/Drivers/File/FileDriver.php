<?php

declare(strict_types=1);

namespace App\Editor\Drivers\File;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the file content block. */
final class FileDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.file')
            ->category('media')
            ->icon('paperclip')
            ->clientClass('StructuredTool')
            ->schema([
                'caption' => 'string?',
                'file'    => 'array?',
            ])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields' => ['caption' => []],
                    'media' => 'file',
                    'multiple' => false,
                    'nested' => false,
                ],
            ])
            ->mediaPaths(['file']);
    }
}
