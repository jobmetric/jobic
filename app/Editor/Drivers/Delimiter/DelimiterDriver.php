<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Delimiter;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the delimiter content block. */
final class DelimiterDriver implements EditorBlockDriver
{
    /**
     * Describe the delimiter tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.delimiter')
            ->clientClass('Delimiter')
            ->category('design')
            ->icon('minus')
            ->schema([])
            ->clientConfig(['inlineToolbar' => true]);
    }
}
