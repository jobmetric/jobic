<?php

declare(strict_types=1);

namespace App\Editor\Drivers\List;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the list content block. */
final class ListDriver implements EditorBlockDriver
{
    /**
     * Describe the list tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.list')
            ->clientClass('EditorjsList')
            ->category('text')
            ->icon('list-tree')
            ->schema([
                'style' => 'string',
                'items' => 'array',
            ])
            ->clientConfig(['inlineToolbar' => true]);
    }
}
