<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Paragraph;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the paragraph content block. */
final class ParagraphDriver implements EditorBlockDriver
{
    /**
     * Describe the paragraph tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.paragraph')
            ->clientClass('Paragraph')
            ->category('text')
            ->icon('align-right')
            ->schema(['text' => 'string'])
            ->clientConfig(['inlineToolbar' => true]);
    }
}
