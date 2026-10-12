<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Playlist;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the playlist content block. */
final class PlaylistDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.playlist')
            ->category('media')
            ->icon('headphones')
            ->clientClass('StructuredTool')
            ->schema([
                'title' => 'string?',
                'files' => 'array?',
            ])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields' => ['title' => []],
                    'media' => 'audio',
                    'multiple' => true,
                    'nested' => false,
                ],
            ])
            ->mediaPaths(['files.*']);
    }
}
