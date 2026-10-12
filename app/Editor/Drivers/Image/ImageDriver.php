<?php

declare(strict_types=1);

namespace App\Editor\Drivers\Image;

use App\Contracts\EditorBlockDriver;
use App\Support\EditorDriverBuilder;

/** Register the image content block. */
final class ImageDriver implements EditorBlockDriver
{
    /**
     * Describe the image tool for the editor registry.
     *
     * @param EditorDriverBuilder $builder
     *
     * @return void
     */
    public function configure(EditorDriverBuilder $builder): void
    {
        $builder->label('editor.blocks.image')
            ->clientClass('StructuredTool')
            ->category('media')
            ->icon('media')
            ->schema(['file' => 'array'])
            ->settings([
                'caption' => [
                    'label' => 'editor.fields.caption',
                    'type'  => 'text',
                ],
            ])
            ->defaults(['file' => []])
            ->clientConfig([
                'config' => [
                    'fields'   => ['caption' => []],
                    'media'    => 'image',
                    'multiple' => false,
                    'nested'   => false,
                ],
            ])
            ->mediaPaths(['file'])
            ->validateUsing(static function (mixed $data): bool {
                if (($data['file'] ?? null) === []) {
                    return true;
                }
                if (! is_array($data['file'] ?? null) || ! is_string($data['file']['url'] ?? null)) {
                    return false;
                }
                $url = parse_url($data['file']['url']);

                return is_array($url) && in_array(strtolower((string) ($url['scheme'] ?? '')), [
                        'http',
                        'https',
                    ], true) && (! isset($data['file']['id']) || (is_numeric($data['file']['id']) && (int) $data['file']['id'] > 0));
            });
    }
}
