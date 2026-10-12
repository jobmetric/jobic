<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Embed;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the embed content block. */
final class EmbedDriver implements EditorBlockDriver
{
    /**
     * Describe the embed tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.embed')
            ->clientClass('Embed')
            ->category('embed')
            ->icon('link')
            ->schema([
                'service' => 'string',
                'source'  => 'string',
                'embed'   => 'string',
            ])
            ->clientConfig(['inlineToolbar' => true]);
    }
}
