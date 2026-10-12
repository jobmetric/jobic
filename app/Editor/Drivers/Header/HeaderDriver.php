<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Header;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the header content block. */
final class HeaderDriver implements EditorBlockDriver
{
    /**
     * Describe the header tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.header')
            ->clientClass('Header')
            ->category('text')
            ->icon('file-text')
            ->schema([
                'text'  => 'string',
                'level' => 'integer',
            ])
            ->settings([
                'level' => [
                    'label' => 'editor.fields.level',
                    'type'  => 'number',
                    'min'   => 1,
                    'max'   => 6,
                ],
            ])
            ->validateUsing(static fn (mixed $data): bool => (int) $data['level'] >= 1 && (int) $data['level'] <= 6)
            ->clientConfig(['inlineToolbar' => true]);
    }
}
