<?php

declare(strict_types=1);

namespace App\Application\Drivers;

use App\Contracts\ApplicationDriver;
use App\Facades\Editor;
use App\Models\EditorDocumentRevision;
use App\Services\Editor\EditorDocuments;
use App\Support\EditorDriverRegistry;
use App\Support\EditorTemplateRegistry;
use Illuminate\Contracts\Foundation\Application;
use InvalidArgumentException;
use JobMetric\Post\Models\Post;
use JobMetric\Post\Models\PostRevision;

/** Discover content tools through the application's registered editor directories. */
final class EditorDriver implements ApplicationDriver
{
    /**
     * Bind the shared editor registry.
     *
     * @param Application $app
     *
     * @return void
     */
    public function register(Application $app): void
    {
        $app->singleton(EditorDriverRegistry::class);
        $app->singleton(EditorTemplateRegistry::class);
    }

    /**
     * Load trusted editor driver directories from configuration.
     *
     * @param Application $app
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function boot(Application $app): void
    {
        Post::deleted(static function (Post $post): void {
            app(EditorDocuments::class)->delete($post);
        });
        PostRevision::deleting(static function (PostRevision $revision): void {
            EditorDocumentRevision::query()
                ->where('revision_type', $revision->getMorphClass())
                ->where('revision_id', $revision->id)
                ->delete();
        });
        foreach (config('editor.paths', []) as $path) {
            Editor::addPath($path);
        }
        foreach (
            [
                'article',
                'faq',
                'visual',
                'guide',
                'comparison',
                'announcement',
                'interview',
                'case_study',
                'resources',
                'timeline',
            ] as $pattern) {
            $label = in_array($pattern, ['article', 'faq', 'visual'], true) ? 'editor.' . $pattern : 'editor.patterns.' . $pattern;

            Editor::templates()
                ->register($pattern, $label, static function () use ($pattern, $label): array {
                    $blocks = [
                        [
                            'type' => 'header',
                            'data' => [
                                'text'  => __($label),
                                'level' => 2,
                            ],
                        ],
                        [
                            'type' => 'paragraph',
                            'data' => [
                                'text' => __('editor-patterns.intro'),
                            ],
                        ],
                    ];
                    $sections = trans('editor-patterns.' . $pattern);
                    foreach ($sections as $index => [$title, $body]) {
                        $blocks[] = [
                            'type' => 'header',
                            'data' => ['text' => $title, 'level' => 3],
                        ];
                        $blocks[] = [
                            'type' => 'paragraph',
                            'data' => ['text' => $body],
                        ];
                        if ($pattern === 'visual' && $index === 0) {
                            $blocks[] = [
                                'type' => 'gallery',
                                'data' => [
                                    'files'   => [],
                                    'caption' => __('editor.sample_caption'),
                                ],
                            ];
                        }
                    }
                    if (in_array($pattern, ['comparison', 'resources'], true)) {
                        $blocks[] = [
                            'type' => 'table',
                            'data' => [
                                'withHeadings' => true,
                                'content'      => array_merge([[__('editor.section'), __('editor.pattern_description')]], array_map(static fn (array $section): array => [
                                    $section[0],
                                    $section[1],
                                ], array_slice($sections, 0, 3))),
                            ],
                        ];
                    }
                    if (in_array($pattern, ['guide', 'timeline'], true)) {
                        $blocks[] = [
                            'type' => 'list',
                            'data' => [
                                'style' => 'ordered',
                                'items' => array_map(static fn (array $section): array => ['content' => $section[0], 'items' => []], $sections),
                            ],
                        ];
                    }
                    $blocks[] = [
                        'type' => 'delimiter',
                        'data' => [],
                    ];
                    $blocks[] = [
                        'type' => 'paragraph',
                        'data' => [
                            'text' => __('editor-patterns.closing'),
                        ],
                    ];

                    return [
                        'blocks' => $blocks,
                    ];
                });
        }
    }
}
