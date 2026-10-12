<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Code;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the code content block. */
final class CodeDriver implements EditorBlockDriver
{
    /**
     * Describe the code tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.code')
            ->clientClass('CodeTool')
            ->category('text')
            ->icon('code')
            ->schema(['code' => 'string'])
            ->clientConfig(['inlineToolbar' => true]);
    }
}
