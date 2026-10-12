<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Video;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the video content block. */
final class VideoDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.video')
            ->category('media')
            ->icon('file-video')
            ->clientClass('StructuredTool')
            ->schema([
                'caption' => 'string?',
                'file'    => 'array?',
            ])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields' => ['caption' => []],
                    'media' => 'video',
                    'multiple' => false,
                    'nested' => false,
                ],
            ])
            ->mediaPaths(['file']);
    }
}
