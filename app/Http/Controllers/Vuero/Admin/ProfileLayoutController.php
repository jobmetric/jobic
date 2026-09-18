<?php

namespace App\Http\Controllers\Vuero\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Handle profile layout page requests.
 */
class ProfileLayoutController extends Controller
{
    private const EDIT_TABS = [
        'general',
        'experience',
        'skills',
        'settings',
    ];

    /**
     * Build the demo view data.
     *
     * @return View
     */
    public function view(): View
    {
        return view('vuero.admin.layouts.profile-view', [
            'layoutScript'    => 'resources/js/vuero/pages/admin/layouts/profile-view.js',
            'profile'         => $this->profileHeader(),
            'about'           => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Falli igitur possumus. Praeteritis, inquit, gaudeo. Duo Reges: constructio interrete. Idemne, quod iucunde? Quis enim redargueret? Non semper, inquam; Quid nunc honeste dicit? Eam tum adesse, cum dolor omnis absit; Negat enim summo bono afferre incrementum diem.',
            'experiences'     => $this->experiences(includeExtra: true),
            'languages'       => $this->languages(),
            'skills'          => $this->profileSkills(),
            'recommendations' => $this->recommendations(),
            'tools'           => $this->tools(),
            'recentViews'     => $this->recentViews(),
        ]);
    }

    /**
     * Build the demo notifications data.
     *
     * @return View
     */
    public function notifications(): View
    {
        return view('vuero.admin.layouts.profile-notifications', [
            'layoutScript' => 'resources/js/vuero/pages/admin/layouts/profile-notifications.js',
            'items'        => $this->timelineItems(),
        ]);
    }

    /**
     * Render the requested settings form.
     *
     * @param string|null $tab
     *
     * @return View
     */
    public function edit(?string $tab = null): View
    {
        $tab = in_array($tab, self::EDIT_TABS, true) ? $tab : 'general';

        return view('vuero.admin.layouts.profile-edit', [
            'layoutScript' => 'resources/js/vuero/pages/admin/layouts/profile-edit.js',
            'tab'          => $tab,
            'profile'      => $this->profileHeader(),
            'experiences'  => $this->experiences(includeExtra: true),
            'languages'    => $this->languages(),
            'skills'       => $this->editSkills(),
            'tools'        => $this->editTools(),
        ]);
    }

    /**
     * Build the demo search results data.
     *
     * @return View
     */
    public function searchResults(): View
    {
        return view('vuero.admin.layouts.search-results', [
            'layoutScript' => 'resources/js/vuero/pages/admin/layouts/search-results.js',
            'people'       => $this->searchPeople(),
            'records'      => $this->searchRecords(),
            'filters'      => $this->searchFilters(),
            'resultCount'  => 54,
        ]);
    }

    /**
     * Build the demo search empty data.
     *
     * @return View
     */
    public function searchEmpty(): View
    {
        return view('vuero.admin.layouts.search-empty', [
            'layoutScript' => 'resources/js/vuero/pages/admin/layouts/search-empty.js',
        ]);
    }

    /**
     * Build the demo avatar data.
     *
     * @param string $file
     *
     * @return string
     */
    private function avatar(string $file): string
    {
        if (str_contains($file, 'vuero-1.svg')) {
            return '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg';
        }

        return '/vuero/images/layouts/list-view-v1/avatars/' . $file;
    }

    /**
     * Build the demo flag data.
     *
     * @param string $country
     *
     * @return string
     */
    private function flag(string $country): string
    {
        return "/vuero/images/layouts/list-view-v1/flags/{$country}.svg";
    }

    /**
     * Build the demo stack data.
     *
     * @param string $name
     *
     * @return string
     */
    private function stack(string $name): string
    {
        return "/vuero/images/layouts/grid-cards/stacks/{$name}.svg";
    }

    /**
     * Build the demo brand data.
     *
     * @param string $name
     *
     * @return string
     */
    private function brand(string $name): string
    {
        return "/vuero/images/layouts/list-view-v3/brands/{$name}.svg";
    }

    /**
     * Build the demo profile header data.
     *
     * @return array<array-key, mixed>
     */
    private function profileHeader(): array
    {
        $userName = auth()->user()->name ?? 'Erik Kovalsky';

        return [
            'name'      => $userName,
            'role'      => 'Product Manager',
            'bio'       => 'Hey everyone, I am a product manager from New York and I am looking for new opportunities in the software business.',
            'avatar'    => '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg',
            'flag'      => $this->flag('united-states-of-america'),
            'relations' => '500+ Relations',
            'projects'  => '78 Projects',
        ];
    }

    /**
     * Build the demo experiences data.
     *
     * @param bool $includeExtra
     *
     * @return array<array-key, mixed>
     */
    private function experiences(bool $includeExtra = false): array
    {
        $items = [
            [
                'Airbnb HQ',
                'airbnb',
                'January 2018',
                'October 2020',
                'Product Manager',
            ],
            [
                'Facebook',
                'facebook',
                'January 2018',
                'October 2020',
                'Product Manager',
            ],
            [
                'Atlassian',
                'atlassian',
                'January 2018',
                'October 2020',
                'Web Developer',
            ],
            [
                'Github',
                'github',
                'January 2018',
                'October 2020',
                'Web Developer',
            ],
        ];

        if ($includeExtra) {
            $items[] = ['Slack', 'slack', 'January 2018', 'October 2020', 'Web Developer'];
            $items[] = ['Gitlab', 'gitlab', 'January 2018', 'October 2020', 'Web Developer'];
        }

        return collect($items)->map(fn (array $item): array => [
            'company' => $item[0],
            'logo'    => $this->brand($item[1]),
            'start'   => $item[2],
            'end'     => $item[3],
            'role'    => $item[4],
        ])->all();
    }

    /**
     * Build the demo languages data.
     *
     * @return array<array-key, mixed>
     */
    private function languages(): array
    {
        return [
            [
                'English',
                $this->flag('united-states-of-america'),
                'Native Speaker, fluent',
                100,
            ],
            [
                'French',
                $this->flag('france'),
                'Fluent, written and spoken',
                80,
            ],
            [
                'German',
                $this->flag('germany'),
                'Beginner level',
                30,
            ],
            [
                'Spanish',
                $this->flag('spain'),
                'Beginner level',
                40,
            ],
        ];
    }

    /**
     * Build the demo profile skills data.
     *
     * @return array<array-key, mixed>
     */
    private function profileSkills(): array
    {
        return [
            [
                'Javascript',
                $this->stack('js'),
                '7 years of experience',
                [
                    ['initials' => 'BT', 'color' => 'warning'],
                    ['picture' => $this->avatar('18.jpg')],
                    ['initials' => 'JD', 'color' => 'info'],
                    ['picture' => $this->avatar('7.jpg')],
                    ['initials' => '38'],
                ],
            ],
            [
                'Product Management',
                null,
                '4 years of experience',
                [
                    ['picture' => $this->avatar('21.jpg')],
                    ['initials' => 'AT', 'color' => 'success'],
                    ['picture' => $this->avatar('39.jpg')],
                    ['picture' => $this->avatar('23.jpg')],
                    ['initials' => '27'],
                ],
            ],
            [
                'Html 5',
                $this->stack('html5'),
                '10+ years of experience',
                [
                    ['picture' => $this->avatar('38.jpg')],
                    ['picture' => $this->avatar('11.jpg')],
                    ['initials' => 'SC', 'color' => 'purple'],
                    ['picture' => $this->avatar('13.jpg')],
                    ['initials' => '19'],
                ],
            ],
            [
                'CSS 3',
                $this->stack('css3'),
                '10+ years of experience',
                [
                    ['picture' => $this->avatar('21.jpg')],
                    ['initials' => 'SC', 'color' => 'purple'],
                    ['picture' => $this->avatar('5.jpg')],
                    ['picture' => $this->avatar('7.jpg')],
                    ['initials' => '31'],
                ],
            ],
            [
                'Vue.js',
                $this->stack('vuejs'),
                '5 years of experience',
                [
                    ['picture' => $this->avatar('25.jpg')],
                    ['picture' => $this->avatar('29.jpg')],
                    ['initials' => 'AT', 'color' => 'success'],
                ],
            ],
            [
                'Python',
                $this->stack('python'),
                '3 years of experience',
                [
                    ['picture' => $this->avatar('28.jpg')],
                    ['initials' => 'JD', 'color' => 'info'],
                    ['picture' => $this->avatar('19.jpg')],
                ],
            ],
        ];
    }

    /**
     * Build the demo edit skills data.
     *
     * @return array<array-key, mixed>
     */
    private function editSkills(): array
    {
        return [
            [
                'Javascript',
                $this->stack('js'),
                '7 years of experience',
            ],
            [
                'Html5',
                $this->stack('html5'),
                '10+ years of experience',
            ],
            [
                'CSS3',
                $this->stack('css3'),
                '10+ years of experience',
            ],
        ];
    }

    /**
     * Build the demo edit tools data.
     *
     * @return array<array-key, mixed>
     */
    private function editTools(): array
    {
        return [
            [
                'Illustrator',
                $this->stack('illustrator'),
                'Advanced level',
            ],
            [
                'Jira Software',
                '/vuero/images/personal/brands/jira.svg',
                'Intermediate level',
            ],
            [
                'MS Office',
                '/vuero/images/personal/brands/office.svg',
                'Expert level',
            ],
        ];
    }

    /**
     * Build the demo recommendations data.
     *
     * @return array<array-key, mixed>
     */
    private function recommendations(): array
    {
        return [
            [
                'name'   => 'Mary L.',
                'avatar' => $this->avatar('5.jpg'),
                'badge'  => $this->flag('united-states-of-america'),
                'role'   => 'Project Manager',
                'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. At multis malis affectus. Certe, nisi voluptatem tanti aestimaretis.',
                'date'   => 'September 3, 2020',
            ],
            [
                'name'   => 'Esteban C.',
                'avatar' => $this->avatar('18.jpg'),
                'badge'  => $this->flag('united-states-of-america'),
                'role'   => 'UI/UX Designer',
                'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. At multis malis affectus. Certe, nisi voluptatem tanti aestimaretis.',
                'date'   => 'September 9, 2020',
            ],
        ];
    }

    /**
     * Build the demo tools data.
     *
     * @return array<array-key, mixed>
     */
    private function tools(): array
    {
        return [
            [
                'Adobe Illustrator',
                $this->stack('illustrator'),
                'Advanced level',
                80,
            ],
            [
                'Jira Software',
                '/vuero/images/personal/brands/jira.svg',
                'Intermediate level',
                60,
            ],
            [
                'MS Office',
                '/vuero/images/personal/brands/office.svg',
                'Expert level',
                95,
            ],
        ];
    }

    /**
     * Build the demo recent views data.
     *
     * @return array<array-key, mixed>
     */
    private function recentViews(): array
    {
        return [
            [
                'Melany W.',
                'Web Developer',
                $this->avatar('25.jpg'),
                $this->stack('js'),
            ],
            [
                'Hakeem C.',
                'Web Developer',
                $this->avatar('29.jpg'),
                $this->stack('python'),
            ],
            [
                'Christie D.',
                'Web Developer',
                $this->avatar('38.jpg'),
                $this->stack('vuejs'),
            ],
            [
                'Edouard F.',
                'Software Engineer',
                $this->avatar('28.jpg'),
                $this->stack('angular'),
            ],
            [
                'Greta K.',
                'Sales Manager',
                $this->avatar('19.jpg'),
                '/vuero/images/layouts/grid-cards/stacks/cplus.svg',
            ],
        ];
    }

    /**
     * Build the demo timeline items data.
     *
     * @return array<array-key, mixed>
     */
    private function timelineItems(): array
    {
        $avatar = fn (string $file): string => $this->avatar($file);

        return [
            [
                'date'   => 'Sep 23, 2020',
                'tone'   => 'info',
                'unread' => true,
                'avatar' => $avatar('13.jpg'),
                'html'   => '<span>Tara S.</span> added you to the <a href="#">Barber Website Redesign Project</a>.',
                'time'   => '11:42 am',
                'end'    => [['picture' => '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg']],
            ],
            [
                'date'   => 'Sep 23, 2020',
                'tone'   => 'danger',
                'unread' => true,
                'avatar' => $avatar('23.jpg'),
                'html'   => '<span>Irina V.</span> left a comment on a <a href="#">Task</a>, in <a href="#">Corporate Tools Rebranding</a>.',
                'time'   => '9:18 am',
                'end'    => [],
            ],
            [
                'date'   => 'Sep 22, 2020',
                'tone'   => 'success',
                'avatar' => $avatar('5.jpg'),
                'html'   => '<span>Mary L.</span> added 3 new members to the <a href="#">Blake &amp; Mortimer Project</a>.',
                'time'   => '9:18 am',
                'end'    => [
                    ['initials' => 'BT', 'color' => 'warning'],
                    ['picture' => $avatar('18.jpg')],
                    ['initials' => 'JD', 'color' => 'info'],
                ],
            ],
            [
                'date'   => 'Sep 23, 2020',
                'tone'   => 'purple',
                'avatar' => $avatar('12.jpg'),
                'html'   => '<span>Joshua S.</span> changed the status of a <a href="#">Task</a> from <span class="flex-tag is-rounded is-purple">Pending</span> to <span class="flex-tag is-rounded is-success">Completed</span>',
                'time'   => '9:18 am',
                'end'    => [],
            ],
            [
                'date'   => 'Sep 22, 2020',
                'tone'   => 'warning',
                'avatar' => $avatar('26.jpg'),
                'html'   => '<span>Courtney W.</span> uploaded 2 new files to the <a href="#">Blake &amp; Mortimer Project</a>.',
                'time'   => '9:18 am',
                'end'    => [
                    ['picture' => '/vuero/images/layouts/grid-cards/apps/5.png', 'squared' => true],
                    ['picture' => '/vuero/images/layouts/grid-cards/apps/8.png', 'squared' => true],
                ],
            ],
            [
                'date'   => 'Sep 23, 2020',
                'tone'   => 'info',
                'avatar' => $avatar('31.jpg'),
                'html'   => '<span>Yassine A.</span> mentionned you in a <a href="#">comment</a> on <a href="#">Delivery tracking Dashboard</a>.',
                'time'   => '11:42 am',
                'end'    => [['picture' => '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg']],
            ],
            [
                'date'   => 'Sep 23, 2020',
                'tone'   => 'danger',
                'avatar' => $avatar('13.jpg'),
                'html'   => '<span>Tara S.</span> left a comment on a <a href="#">Task</a>, in <a href="#">Corporate Tools Rebranding</a>.',
                'time'   => '9:18 am',
                'end'    => [],
            ],
            [
                'date'   => 'Sep 23, 2020',
                'tone'   => 'purple',
                'avatar' => $avatar('25.jpg'),
                'html'   => '<span>Melany W.</span> changed the status of a <a href="#">Task</a> from <span class="flex-tag is-rounded is-danger">Overdue</span> to <span class="flex-tag is-rounded is-success">Completed</span>',
                'time'   => '9:18 am',
                'end'    => [],
            ],
        ];
    }

    /**
     * Build the demo search people data.
     *
     * @return array<array-key, mixed>
     */
    private function searchPeople(): array
    {
        return [
            [
                'Alice Carasca',
                'Software Engineer',
                $this->avatar('5.jpg'),
            ],
            [
                'Mary Lebowski',
                'Project Manager',
                $this->avatar('5.jpg'),
            ],
            [
                'Ryan Brentman',
                'Web Developer',
                $this->avatar('14.jpg'),
            ],
            [
                'Irina Vierbovsky',
                'Project Manager',
                $this->avatar('23.jpg'),
            ],
            [
                'Jonathan Krugger',
                'Product Manager',
                $this->avatar('32.jpg'),
            ],
            [
                'Alejandro Badajoz',
                'Business Analyst',
                $this->avatar('39.jpg'),
            ],
            [
                'Greta Kroppfer',
                'Sales Manager',
                $this->avatar('19.jpg'),
            ],
        ];
    }

    /**
     * Build the demo search records data.
     *
     * @return array<array-key, mixed>
     */
    private function searchRecords(): array
    {
        return [
            [
                'Delivery App Project',
                'August 2020',
                '/vuero/images/layouts/grid-cards/apps/1.jpg',
                false,
            ],
            [
                'Health and Fitness Dashboard',
                'October 2020',
                '/vuero/images/layouts/grid-cards/apps/2.png',
                false,
            ],
            [
                'Tech Summit Expenses',
                'XLS file',
                '/vuero/images/icons/files/sheet.svg',
                true,
            ],
            [
                'Learning Tracker Dashboard',
                'September 2020',
                '/vuero/images/layouts/grid-cards/apps/3.png',
                false,
            ],
            [
                'Learning Tracker Dashboard',
                'September 2020',
                '/vuero/images/layouts/grid-cards/apps/4.png',
                false,
            ],
            [
                'Project Outline',
                'DOCX file',
                '/vuero/images/icons/files/doc-2.svg',
                true,
            ],
            [
                '2020 Projects Digest',
                'PDF file',
                '/vuero/images/icons/files/pdf.svg',
                true,
            ],
            [
                'Resume Management Mobile App',
                'October 2020',
                '/vuero/images/layouts/grid-cards/apps/5.png',
                false,
            ],
            [
                'Website Homepage Redesign',
                'AI file',
                '/vuero/images/icons/files/ai.svg',
                true,
            ],
        ];
    }

    /**
     * Build the demo search filters data.
     *
     * @return array<array-key, mixed>
     */
    private function searchFilters(): array
    {
        return [
            'Publication' => [
                [
                    'label'   => 'Newest',
                    'count'   => 23,
                    'checked' => true,
                ],
                [
                    'label' => 'Recently updated',
                    'count' => 31,
                ],
                [
                    'label' => 'Upvoted',
                    'count' => 12,
                ],
            ],
            'Type'        => [
                [
                    'label' => 'User profile',
                    'count' => 19,
                ],
                [
                    'label' => 'Project',
                    'count' => 174,
                ],
                [
                    'label' => 'Blog Post',
                    'count' => 71,
                ],
                [
                    'label' => 'Wireframes',
                    'count' => 54,
                ],
                [
                    'label' => 'Files',
                    'count' => 8,
                ],
            ],
            'Topics'      => [
                [
                    'label' => 'Engineering',
                    'count' => 45,
                ],
                [
                    'label' => 'Management',
                    'count' => 12,
                ],
                [
                    'label' => 'Software',
                    'count' => 36,
                ],
                [
                    'label' => 'Human Resources',
                    'count' => 3,
                ],
                [
                    'label' => 'Finance',
                    'count' => 7,
                ],
            ],
        ];
    }
}
