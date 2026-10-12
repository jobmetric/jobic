<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Table;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the table content block. */
final class TableDriver implements EditorBlockDriver
{
    /**
     * Describe the table tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.table')
            ->clientClass('Table')
            ->category('text')
            ->icon('list-tree')
            ->schema(['content' => 'array'])
            ->clientConfig(['inlineToolbar' => true]);
    }
}
