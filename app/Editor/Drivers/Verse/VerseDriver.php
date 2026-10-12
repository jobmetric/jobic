<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Verse;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the poetry content block. */
final class VerseDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.verse')
            ->category('text')
            ->icon('book-open')
            ->clientClass('StructuredTool')
            ->schema(['text' => 'string?'])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields'   => ['text' => ['type' => 'textarea']],
                    'media'    => null,
                    'multiple' => false,
                    'nested'   => false,
                ],
            ]);
    }
}
