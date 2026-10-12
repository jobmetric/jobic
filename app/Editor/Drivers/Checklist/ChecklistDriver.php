<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Checklist;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the checklist content block. */
final class ChecklistDriver implements EditorBlockDriver
{
    /**
     * Describe the checklist tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.checklist')
            ->clientClass('Checklist')
            ->category('text')
            ->icon('user-check')
            ->schema(['items' => 'array'])
            ->clientConfig(['inlineToolbar' => true]);
    }
}
