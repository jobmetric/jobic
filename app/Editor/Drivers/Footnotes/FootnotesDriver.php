<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Footnotes;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the footnotes content block. */
final class FootnotesDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.footnotes')
            ->category('text')
            ->icon('bookmark')
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
