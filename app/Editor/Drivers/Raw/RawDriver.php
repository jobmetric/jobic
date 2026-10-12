<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Raw;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the raw content block. */
final class RawDriver implements EditorBlockDriver
{
    /**
     * Describe the raw tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.raw')
            ->clientClass('RawTool')
            ->category('text')
            ->icon('code')
            ->schema(['html' => 'string'])
            ->clientConfig(['inlineToolbar' => true]);
    }
}
