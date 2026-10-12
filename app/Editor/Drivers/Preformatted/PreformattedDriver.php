<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Preformatted;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Describe the preformatted content block. */
final class PreformattedDriver implements EditorBlockDriver
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
        $builder->label('editor.blocks.preformatted')
            ->category('text')
            ->icon('keyboard')
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
