<?php

declare(strict_types=1);

namespace App\Editor\Drivers\More;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the read more content block. */
final class MoreDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.more')
            ->category('design')
            ->icon('more-horizontal')
            ->clientClass('StructuredTool')
            ->schema(['text' => 'string?'])
            ->defaults([])
            ->clientConfig([
                'config' => [
                    'fields'   => ['text' => []],
                    'media'    => null,
                    'multiple' => false,
                    'nested'   => false,
                ],
            ]);
    }
}
