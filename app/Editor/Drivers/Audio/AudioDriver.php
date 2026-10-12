<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Audio;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the audio content block. */
final class AudioDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.audio')
            ->category('media')
            ->icon('file-audio')
            ->clientClass('StructuredTool')
            ->schema([
                'caption' => 'string?',
                'file'    => 'array?',
            ])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields' => ['caption' => []],
                    'media' => 'audio',
                    'multiple' => false,
                    'nested' => false,
                ],
            ])
            ->mediaPaths(['file']);
    }
}
