<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Warning;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the warning content block. */
final class WarningDriver implements EditorBlockDriver
{
    /**
     * Describe the warning tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.warning')
            ->clientClass('Warning')
            ->category('text')
            ->icon('info')
            ->schema(['message' => 'string'])
            ->clientConfig(['inlineToolbar' => true]);
    }
}
