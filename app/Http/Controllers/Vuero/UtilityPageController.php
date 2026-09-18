<?php

namespace App\Http\Controllers\Vuero;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Handle utility page page requests.
 */
class UtilityPageController extends Controller
{
    /**
     * Build the demo roadmap data.
     *
     * @return View
     */
    public function roadmap(): View
    {
        return view('vuero.utility.roadmap', [
            'roadmap'   => $this->roadmapData(),
            'changelog' => $this->changelogData(),
        ]);
    }

    /**
     * Build the demo status data.
     *
     * @return View
     */
    public function status(): View
    {
        return view('vuero.utility.status', [
            'services' => [
                [
                    'name'        => 'REST API',
                    'description' => 'Including replicated servers',
                    'value'       => 99,
                    'tone'        => 'primary',
                    'icon'        => 'lightbulb',
                ],
                [
                    'name'        => 'CDN Assets',
                    'description' => 'Assets delivered from our CDN',
                    'value'       => 100,
                    'tone'        => 'success',
                    'icon'        => 'image',
                ],
                [
                    'name'        => 'Search Indexes',
                    'description' => 'Including replicated search',
                    'value'       => 99,
                    'tone'        => 'primary',
                    'icon'        => 'search',
                ],
                [
                    'name'        => 'Integrations',
                    'description' => 'We are experiencing some issues',
                    'value'       => 72,
                    'tone'        => 'warning',
                    'icon'        => 'diamond',
                ],
                [
                    'name'        => 'Load Balancing',
                    'description' => 'Everything is working fine',
                    'value'       => 100,
                    'tone'        => 'success',
                    'icon'        => 'chart',
                ],
                [
                    'name'        => 'Load Booster',
                    'description' => 'Everything is working fine',
                    'value'       => 100,
                    'tone'        => 'success',
                    'icon'        => 'rocket',
                ],
                [
                    'name'        => 'Security',
                    'description' => 'Everything is working fine',
                    'value'       => 100,
                    'tone'        => 'primary',
                    'icon'        => 'check',
                ],
            ],
        ]);
    }

    /**
     * Build the demo roadmap data data.
     *
     * @return array<string, array{progress: int, quarters: array<int, list<array<string, mixed>>>}>
     */
    private function roadmapData(): array
    {
        $description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Omnis enim est natura diligens sui. Philosophi autem in suis lectulis plerumque moriuntur. Ad eas enim res ab Epicuro praecepta dantur.';
        $feature = fn (string $title, int $value, ?string $url = null): array => array_filter([
            'title'   => $title,
            'value'   => $value,
            'content' => $description,
            'url'     => $url,
        ], fn (mixed $value): bool => $value !== null);

        $roadmap = [
            '2022' => [
                'progress' => 90,
                'quarters' => [
                    1 => [
                        $feature('AI driven website design', 100, 'https://vuero.cssninja.io'),
                        $feature('AI driven marketing automation', 100),
                        $feature('Account personalization', 100),
                        $feature('Theme color settings persistance', 100),
                        $feature('Refreshed landing page UI', 100),
                    ],
                    2 => [
                        $feature('Ecommerce storefront configuration', 100),
                        $feature('algolia deep search integration', 100),
                        $feature('Payments gateways integration', 100),
                        $feature('Auth and user modules', 100),
                    ],
                    3 => [
                        $feature('Ecommerce storefront configuration', 27),
                        $feature('algolia deep search integration', 31),
                        $feature('Payments gateways integration', 68),
                        $feature('Auth and user modules', 84),
                        $feature('Advanced user permissions', 90),
                        $feature('Production deployment workflow', 100),
                    ],
                    4 => [
                        $feature('Integration with Github services', 0),
                        $feature('Integration with Gitlab services', 0),
                        $feature('Integration with Digital Ocean services', 0),
                        $feature('Automated production backups', 0),
                        $feature('Task scheduler implementation', 0),
                        $feature('Calendar implementation', 0),
                    ],
                ],
            ],
        ];

        foreach (['2021', '2020', '2019'] as $year) {
            $quarters = [];
            foreach (range(1, 4) as $quarter) {
                $quarters[$quarter] = [
                    $feature("Some nice feature done in Q{$quarter} {$year}", 100),
                    $feature("Another feature done in Q{$quarter} {$year}", 100),
                    $feature("Some cool feature done in Q{$quarter} {$year}", 100),
                    $feature("App UI revamp done in Q{$quarter} {$year}", 100),
                    $feature("Some difficult feature done in Q{$quarter} {$year}", 100),
                ];
            }
            $roadmap[$year] = ['progress' => 100, 'quarters' => $quarters];
        }

        return $roadmap;
    }

    /**
     * Build the demo changelog data data.
     *
     * @return list<array<string, mixed>>
     */
    private function changelogData(): array
    {
        return [
            [
                'month'   => 'September 2022',
                'date'    => 'September 24th, 2022',
                'tag'     => '1.0.0',
                'entries' => [
                    [
                        'title' => 'Send dynamic suggestions to users',
                        'type'  => 'Enhancements',
                    ],
                    [
                        'title' => 'Fix product search showing only best sellers',
                        'type'  => 'Bug fixes',
                    ],
                ],
            ],
            [
                'month'   => 'September 2022',
                'date'    => 'September 18th, 2022',
                'tag'     => '1.2.0',
                'entries' => [
                    [
                        'title' => 'Fix a bug that was occuring when using Paypal',
                        'type'  => 'Bug fixes',
                    ],
                ],
            ],
            [
                'month'   => 'October 2022',
                'date'    => 'October 24th, 2022',
                'tag'     => '1.3.0',
                'entries' => [
                    [
                        'title' => 'Improve account personalization settings',
                        'type'  => 'Enhancements',
                    ],
                    [
                        'title' => 'Fix product search showing only best sellers',
                        'type'  => 'Bug fixes',
                    ],
                ],
            ],
            [
                'month'   => 'October 2022',
                'date'    => 'October 18th, 2022',
                'tag'     => '1.4.0',
                'entries' => [
                    [
                        'title' => 'Send dynamic suggestions to users',
                        'type'  => 'Features',
                    ],
                ],
            ],
        ];
    }
}
