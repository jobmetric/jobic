<?php

declare(strict_types=1);

namespace App\Editor\Drivers\PageBreak;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the page break content block. */
final class PageBreakDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.pageBreak')
            ->category('design')
            ->icon('file')
            ->clientClass('StructuredTool')
            ->schema([])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields'   => [],
                    'media'    => null,
                    'multiple' => false,
                    'nested'   => false,
                ],
            ]);
    }
}
