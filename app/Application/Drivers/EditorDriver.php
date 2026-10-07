<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Facades\Editor;
use App\Support\EditorDriverRegistry;
use Illuminate\Contracts\Foundation\Application;

/** Register the standard Editor.js tools and bind the extensible registry. */
final class EditorDriver implements ApplicationDriver
{
    public function register(Application $app): void { $app->singleton(EditorDriverRegistry::class); }

    public function boot(Application $app): void
    {
        foreach ([
            'paragraph' => ['Paragraph', ['text' => 'string']],
            'header' => ['Header', ['text' => 'string', 'level' => 'integer']],
            'list' => ['EditorjsList', ['style' => 'string', 'items' => 'array']],
            'checklist' => ['Checklist', ['items' => 'array']],
            'quote' => ['Quote', ['text' => 'string']],
            'warning' => ['Warning', ['message' => 'string']],
            'table' => ['Table', ['content' => 'array']],
            'code' => ['CodeTool', ['code' => 'string']],
            'delimiter' => ['Delimiter', []],
            'raw' => ['RawTool', ['html' => 'string']],
            'embed' => ['Embed', ['service' => 'string', 'source' => 'string', 'embed' => 'string']],
            'image' => ['ImageTool', ['file' => 'array']],
        ] as $key => [$clientClass, $schema]) {
            $builder = Editor::register($key)
                ->clientClass($clientClass)->schema($schema)->clientConfig(['inlineToolbar' => true]);
            if ($key === 'image') {
                $builder->validateUsing(static function (mixed $data): bool {
                    if (! is_array($data) || ! is_array($data['file'] ?? null) || ! is_string($data['file']['url'] ?? null)) {
                        return false;
                    }

                    $url = parse_url($data['file']['url']);
                    if (! is_array($url) || ! in_array(strtolower((string) ($url['scheme'] ?? '')), ['http', 'https'], true)) {
                        return false;
                    }

                    return ! isset($data['file']['id']) || (is_numeric($data['file']['id']) && (int) $data['file']['id'] > 0);
                });
            }
            $builder->register();
        }
    }
}
