<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Quote;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the quote content block. */
final class QuoteDriver implements EditorBlockDriver
{
    /**
     * Describe the quote tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.quote')
            ->clientClass('Quote')
            ->category('text')
            ->icon('message')
            ->schema(['text' => 'string'])
            ->clientConfig(['inlineToolbar' => true]);
    }
}
