<?php

namespace App\Http\Controllers\Vuero\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

/**
 * Handle layout page requests.
 */
class LayoutController extends Controller
{
    /**
     * Render the requested page.
     *
     * @param string $layout
     *
     * @return View
     */
    public function show(string $layout): View
    {
        abort_unless(in_array($layout, [
            'list-view-v1',
            'list-view-v2',
            'list-view-v3',
            'list-view-v4',
            'flex-list-v1',
            'flex-list-v2',
            'flex-list-v3',
            'list-datatable-v1',
            'list-datatable-v2',
            'list-datatable-v3',
            'form-layout-v1',
            'form-layout-v2',
            'form-layout-v3',
            'form-layout-v4',
            'form-layout-v5',
            'placeload-v1',
            'placeload-v2',
            'placeload-v3',
            'placeload-v4',
            'grid-card-v1',
            'grid-card-v2',
            'grid-card-v3',
            'grid-card-v4',
            'grid-tile-v1',
            'grid-tile-v2',
            'grid-tile-v3',
            'user-grid-v1',
            'user-grid-v2',
            'user-grid-v3',
            'user-grid-v4',
            'projects-v1',
            'projects-v2',
            'projects-v3',
            'project-details',
            'kanban-board',
            'onboarding-welcome',
            'onboarding-v1',
            'onboarding-v2',
            'onboarding-v3',
            'onboarding-v4',
            'onboarding-v5',
            'saas-billing',
            'action-page-v1',
            'action-page-v2',
            'utility-account-confirm',
            'utility-promotion',
            'utility-invoice',
        ], true), 404);

        if (in_array($layout, [
            'onboarding-v1',
            'onboarding-v2',
            'onboarding-v3',
            'onboarding-v4',
            'onboarding-v5',
        ], true)) {
            return view('vuero.admin.layouts.onboarding-page', $this->onboardingPage(str_replace('onboarding-', '', $layout)));
        }

        return view("vuero.admin.layouts.{$layout}", match ($layout) {
            'list-view-v1' => [
                'users'        => $this->users(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-view-v1.js',
            ],
            'list-view-v2' => [
                'properties'   => $this->properties(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-view-v2.js',
            ],
            'list-view-v3' => [
                'jobs'         => $this->jobs(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-view-v3.js',
            ],
            'list-view-v4' => [
                'recipes'      => $this->recipes(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-view-v4.js',
            ],
            'flex-list-v1' => [
                'users'        => $this->flexUsers(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/flex-list-v1.js',
            ],
            'flex-list-v2' => [
                'projects'     => $this->flexProjects(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/flex-list-v2.js',
            ],
            'flex-list-v3' => [
                'courses'      => $this->flexCourses(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/flex-list-v3.js',
            ],
            'list-datatable-v1' => [
                'users'        => $this->flexUsers(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-datatable-v1.js',
            ],
            'list-datatable-v2' => [
                'projects'     => $this->flexProjects(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-datatable-v2.js',
            ],
            'list-datatable-v3' => [
                'offers'       => $this->jobs(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-datatable-v3.js',
            ],
            'form-layout-v1' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/form-layout-v1.js',
            ],
            'form-layout-v2' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/form-layout-v2.js',
            ],
            'form-layout-v3' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/form-layout-v3.js',
            ],
            'form-layout-v4' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/form-layout-v4.js',
            ],
            'form-layout-v5' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/form-layout-v5.js',
            ],
            'placeload-v1' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/placeload-v1.js',
            ],
            'placeload-v2' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/placeload-v2.js',
            ],
            'placeload-v3' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/placeload-v3.js',
            ],
            'placeload-v4' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/placeload-v4.js',
            ],
            'grid-card-v1' => [
                'users'        => $this->gridCardUsers(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-card-v1.js',
            ],
            'grid-card-v2' => [
                'projects'     => $this->gridCardProjects(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-card-v2.js',
            ],
            'grid-card-v3' => [
                'projects'     => $this->gridCardProjectsV3(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-card-v3.js',
            ],
            'grid-card-v4' => [
                'posts'        => $this->gridCardPosts(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-card-v4.js',
            ],
            'grid-tile-v1' => [
                'users'        => $this->tileGridUsers(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-tile-v1.js',
            ],
            'grid-tile-v2' => [
                'files'        => $this->tileGridFiles(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-tile-v2.js',
            ],
            'grid-tile-v3' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-tile-v3.js',
            ],
            'user-grid-v1' => [
                'users'        => $this->userGridUsersV1(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/user-grid-v1.js',
            ],
            'user-grid-v2' => [
                'users'        => $this->userGridUsersV2(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/user-grid-v2.js',
            ],
            'user-grid-v3' => [
                'users'        => $this->userGridUsersV3(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/user-grid-v3.js',
            ],
            'user-grid-v4' => [
                'users'        => $this->userGridUsersV4(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/user-grid-v4.js',
            ],
            'projects-v1' => [
                'members'      => $this->projectMembers(),
                'recent'       => $this->projectRecent(),
                'users'        => $this->projectAdvancedUsers(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/projects-v1.js',
            ],
            'projects-v2' => [
                'members'      => $this->projectMembers(),
                'stats'        => $this->projectHeaderStats(),
                'projects'     => $this->projectCards(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/projects-v2.js',
            ],
            'projects-v3' => [
                'stats'        => $this->projectIllustrationStats(),
                'recent'       => $this->projectRecentBoxes(),
                'projects'     => $this->projectMinimalItems(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/projects-v3.js',
            ],
            'project-details' => [
                'participants' => $this->projectDetailParticipants(),
                'features'     => $this->projectDetailFeatures(),
                'files'        => $this->projectDetailFiles(),
                'sideCards'    => $this->projectDetailSideCards(),
                'team'         => $this->projectDetailTeam(),
                'tasks'        => $this->projectDetailTasks(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/project-details.js',
            ],
            'kanban-board' => [
                'participants' => $this->kanbanParticipants(),
                'columns'      => $this->kanbanColumns(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/kanban-board.js',
            ],
            'onboarding-welcome' => [
                'cards'        => $this->onboardingCards('standard'),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/onboarding-welcome.js',
            ],
            'saas-billing' => [
                'plans'        => $this->saasBillingPlans(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/saas-billing.js',
            ],
            'action-page-v1' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/action-page-v1.js',
            ],
            'action-page-v2' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/action-page-v2.js',
            ],
            'utility-account-confirm' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/utility-account-confirm.js',
            ],
            'utility-promotion' => [
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/utility-promotion.js',
            ],
            'utility-invoice' => [
                'items'        => $this->invoiceItems(),
                'layoutScript' => 'resources/js/vuero/pages/admin/layouts/utility-invoice.js',
            ],
        });
    }

    /**
     * Build the demo user grid users v1 data.
     *
     * @return list<array<string, mixed>>
     */
    private function userGridUsersV1(): array
    {
        return $this->loadUserGridData('user-grid-v1');
    }

    /**
     * Build the demo user grid users v2 data.
     *
     * @return list<array<string, mixed>>
     */
    private function userGridUsersV2(): array
    {
        return $this->loadUserGridData('user-grid-v2');
    }

    /**
     * Build the demo user grid users v3 data.
     *
     * @return list<array<string, mixed>>
     */
    private function userGridUsersV3(): array
    {
        return $this->loadUserGridData('user-grid-v3');
    }

    /**
     * Build the demo user grid users v4 data.
     *
     * @return list<array<string, mixed>>
     */
    private function userGridUsersV4(): array
    {
        return $this->loadUserGridData('user-grid-v4');
    }

    /**
     * Build the demo load user grid data data.
     *
     * @param string $slug
     *
     * @return list<array<string, mixed>>
     */
    private function loadUserGridData(string $slug): array
    {
        $path = resource_path("data/vuero/layouts/{$slug}.json");

        abort_unless(is_file($path), 500, "Missing user grid data: {$slug}");

        $data = json_decode((string) file_get_contents($path), true);

        return is_array($data) ? $data : [];
    }

    /**
     * Build the demo users data.
     *
     * @return array<array-key, mixed>
     */
    private function users(): array
    {
        $avatar = fn (string $id): string => "/vuero/images/layouts/list-view-v1/avatars/{$id}.jpg";
        $flag = fn (string $country): string => "/vuero/images/layouts/list-view-v1/flags/{$country}.svg";

        return [
            [
                'name'     => 'Mary Lebowski',
                'location' => 'San Diego, CA',
                'role'     => 'Project Manager',
                'tone'     => 'primary',
                'avatar'   => $avatar('5'),
                'flag'     => $flag('united-states-of-america'),
                'stats'    => [
                    14,
                    97,
                    16,
                ],
                'team'     => [
                    '9.jpg',
                    'vuero-1.svg',
                ],
            ],
            [
                'name'     => 'Daniel Redbird',
                'location' => 'Toronto, Canada',
                'role'     => 'Web Developer',
                'tone'     => 'orange',
                'avatar'   => $avatar('34'),
                'flag'     => $flag('canada'),
                'stats'    => [
                    45,
                    12,
                    5,
                ],
                'team'     => [
                    '12.jpg',
                    '22.jpg',
                    '40.jpg',
                ],
            ],
            [
                'name'     => 'Erik Kovalsky',
                'location' => 'New York, NY',
                'role'     => 'Product Manager',
                'tone'     => 'secondary',
                'avatar'   => '/vuero/images/avatars/svg/vuero-1.svg',
                'flag'     => $flag('united-states-of-america'),
                'stats'    => [
                    14,
                    97,
                    16,
                ],
                'team'     => [
                    '7.jpg',
                    '23.jpg',
                ],
            ],
            [
                'name'     => 'Jeanne Marchand',
                'location' => 'Paris, France',
                'role'     => 'Mobile Developer',
                'tone'     => 'orange',
                'avatar'   => $avatar('40'),
                'flag'     => $flag('france'),
                'stats'    => [
                    6,
                    12,
                    8,
                ],
                'team'     => [
                    '18.jpg',
                    '13.jpg',
                    'vuero-1.svg',
                    '9.jpg',
                    '5.jpg',
                    '7.jpg',
                    '12.jpg',
                    '22.jpg',
                ],
            ],
            [
                'name'     => 'Carmen Escudero',
                'location' => 'Madrid, Spain',
                'role'     => 'HR Manager',
                'tone'     => 'primary',
                'avatar'   => $avatar('27'),
                'flag'     => $flag('spain'),
                'stats'    => [
                    8,
                    158,
                    54,
                ],
                'team'     => [
                    '18.jpg',
                ],
            ],
            [
                'name'     => 'Anna Baker',
                'location' => 'San Francisco, CA',
                'role'     => 'UI/UX Designer',
                'tone'     => 'green',
                'avatar'   => $avatar('9'),
                'flag'     => $flag('united-states-of-america'),
                'stats'    => [
                    19,
                    28,
                    12,
                ],
                'team'     => [
                    'vuero-1.svg',
                    '5.jpg',
                ],
            ],
            [
                'name'     => 'Esteban Castellanos',
                'location' => 'Miami, FL',
                'role'     => 'UI/UX Designer',
                'tone'     => 'green',
                'avatar'   => $avatar('18'),
                'flag'     => $flag('united-states-of-america'),
                'stats'    => [
                    59,
                    158,
                    16,
                ],
                'team'     => [
                    '27.jpg',
                    '16.jpg',
                    '29.jpg',
                    '5.jpg',
                    '9.jpg',
                ],
            ],
            [
                'name'     => 'Henry Grobstone',
                'location' => 'Miami, FL',
                'role'     => 'Business Analyst',
                'tone'     => 'primary',
                'avatar'   => $avatar('10'),
                'flag'     => $flag('united-states-of-america'),
                'stats'    => [
                    42,
                    15,
                    2,
                ],
                'team'     => [
                    '32.jpg',
                    '22.jpg',
                ],
            ],
            [
                'name'     => 'Melany Wallace',
                'location' => 'San Jose, CA',
                'role'     => 'Web Developer',
                'tone'     => 'orange',
                'avatar'   => $avatar('25'),
                'flag'     => $flag('united-states-of-america'),
                'stats'    => [
                    17,
                    22,
                    4,
                ],
                'team'     => [
                    '26.jpg',
                    '38.jpg',
                    '21.jpg',
                    '5.jpg',
                ],
            ],
            [
                'name'     => 'Alice Carasca',
                'location' => 'San Diego, CA',
                'role'     => 'Software Engineer',
                'tone'     => 'orange',
                'avatar'   => $avatar('7'),
                'flag'     => $flag('united-states-of-america'),
                'stats'    => [
                    31,
                    82,
                    14,
                ],
                'team'     => [
                    '9.jpg',
                    'vuero-1.svg',
                ],
            ],
        ];
    }

    /**
     * Build the demo properties data.
     *
     * @return array<array-key, mixed>
     */
    private function properties(): array
    {
        $items = [
            [
                '1396 Redmond Street',
                '1396 Redmond Street, Apartment 12, Suite H 102, Los Angeles, CA',
                4,
                [
                    5,
                    3,
                    1,
                ],
                [
                    'parking',
                    'wifi',
                    'heater',
                    'cleaning',
                ],
                3,
            ],
            ['24 Mulberry Street', '24 Mulberry Street, Suite 43, New York, NY', 5, [3, 2, 1], ['wifi', 'cleaning'], 0],
            [
                '62 John Walberg Ave',
                '62 John Walberg Avenue, Apartment 10, Suite 24, Los Angeles, CA',
                5,
                [
                    4,
                    2,
                    2,
                ],
                [
                    'parking',
                    'wifi',
                    'heater',
                    'cleaning',
                ],
                3,
            ],
            [
                '48 Manhattan Ave',
                '48 Manhattan Avenue, Suite G12, New York, NY',
                5,
                [
                    5,
                    4,
                    3,
                ],
                [
                    'parking',
                    'wifi',
                    'heater',
                    'cleaning',
                ],
                3,
            ],
            [
                '12 Charity Street',
                '12 Charity Street, Apartment 49, Brooklynn, NY',
                4,
                [
                    3,
                    2,
                    1,
                ],
                [
                    'parking',
                    'wifi',
                    'cleaning',
                ],
                0,
            ],
            [
                '23 Bakery Street',
                '23 Bakery Street, Suite 121, New York, NY',
                5,
                [
                    3,
                    2,
                    1,
                ],
                [
                    'parking',
                    'wifi',
                    'heater',
                    'cleaning',
                ],
                4,
            ],
            [
                '1028 Pasadena Ave',
                '1028 Pasadena Avenue, Suite F 24, Los Angeles, CA',
                5,
                [
                    7,
                    5,
                    3,
                ],
                [
                    'parking',
                    'wifi',
                    'heater',
                    'cleaning',
                ],
                3,
            ],
            [
                '54 Church Street',
                '54 Church Street, Apartment 2, New York, NY',
                5,
                [
                    2,
                    1,
                    1,
                ],
                [
                    'parking',
                    'wifi',
                    'heater',
                    'cleaning',
                ],
                0,
            ],
            [
                '112 Old City District',
                '112 Old City District, Apartment 5 Suite 42, New York, NY',
                5,
                [
                    3,
                    2,
                    1,
                ],
                [
                    'parking',
                    'wifi',
                    'heater',
                    'cleaning',
                ],
                0,
            ],
            [
                '51 St Leonard Street',
                '51 St Leonard Street, Suite 23, New York, NY',
                5,
                [
                    4,
                    2,
                    1,
                ],
                [
                    'parking',
                    'wifi',
                    'heater',
                    'cleaning',
                ],
                2,
            ],
        ];

        return collect($items)->map(fn (array $item, int $index): array => [
            'name'      => $item[0],
            'location'  => $item[1],
            'rating'    => $item[2],
            'details'   => $item[3],
            'amenities' => $item[4],
            'more'      => $item[5],
            'picture'   => '/vuero/images/layouts/list-view-v2/properties/' . ($index + 1) . '.jpg',
        ])->all();
    }

    /**
     * Build the demo jobs data.
     *
     * @return array<array-key, mixed>
     */
    private function jobs(): array
    {
        $items = [
            [
                'airbnb',
                'Node JS Fullstack Developer wanted 👌',
                'San Diego',
                'Full Time',
                '3 Years Exp.',
            ],
            [
                'hubspot',
                'Project Manager for React Native Project',
                'Los Angeles',
                'Part Time',
                '2 Years Exp.',
            ],
            [
                'slack',
                'UI/UX Designer to Consolidate the UX Team 💪💪',
                'Los Angeles',
                'Full Time',
                '3 Years Exp.',
            ],
            [
                'gitlab',
                'IT Team Senior DevOps Manager',
                'Los Angeles',
                'Full Time',
                '10 Years Exp.',
            ],
            [
                'google',
                'Fullstack Developer With Solid MongoDB Skills',
                'San Francisco',
                'Full Time',
                '5 Years Exp.',
            ],
            [
                'facebook',
                'Fullstack Javascript and Node JS Developer',
                'Pasadena',
                'Full Time',
                '4 Years Exp.',
            ],
            [
                'tnw',
                'Product Owner for React Native Project',
                'Los Angeles',
                'Full Time',
                '2 Years Exp.',
            ],
            [
                'dribbble',
                'UI/UX Designer With Html5 and Sass Skills 👍',
                'San Diego',
                'Full Time',
                '4 Years Exp.',
            ],
            [
                'atlassian',
                'Experienced Senior UX Team Manager',
                'Melbourne',
                'Full Time',
                '10 Years Exp.',
            ],
            [
                'github',
                'Fullstack Javascript and Node JS Developer',
                'Los Angeles',
                'Part Time',
                '1 Year Exp.',
            ],
            [
                'airbnb',
                'Node JS Fullstack Developer wanted 👌',
                'San Diego',
                'Full Time',
                '3 Years Exp.',
            ],
            [
                'airbnb',
                'Node JS Fullstack Developer wanted 👌',
                'San Diego',
                'Full Time',
                '3 Years Exp.',
            ],
        ];

        return collect($items)->map(fn (array $item, int $index): array => [
            'id'           => $index + 1,
            'brand'        => $item[0],
            'title'        => $item[1],
            'location'     => $item[2],
            'duration'     => $item[3],
            'requirements' => $item[4],
            'logo'         => "/vuero/images/layouts/list-view-v3/brands/{$item[0]}.svg",
        ])->all();
    }

    /**
     * Build the demo recipes data.
     *
     * @return array<array-key, mixed>
     */
    private function recipes(): array
    {
        $avatar = fn (string $file): string => "/vuero/images/layouts/list-view-v1/avatars/{$file}";

        $icon = fn (int $number): string => "/vuero/images/layouts/list-view-v4/illustrations/cake-{$number}.svg";

        $items = [
            ['1', 'Strawberry & Cream Cake', 'Cakes', '1h 45m', 3, 'Alice C.', '7.jpg', [['9.jpg'], ['vuero-1.svg']]],
            [
                '2',
                'Orange & Lemon Cake',
                'Cakes',
                '1h 15m',
                2,
                'Jimmy H.',
                '22.jpg',
                [
                    ['12.jpg'],
                    ['27.jpg'],
                    ['40.jpg'],
                    ['SC', 'success'],
                    ['AD', 'info'],
                    ['TF', 'purple'],
                    ['GR', 'orange'],
                ],
            ],
            [
                '3',
                'Three Chocolates Cake',
                'Cakes',
                '2h',
                2,
                'Hilde V.',
                '15.jpg',
                [
                    ['19.jpg'],
                    ['24.jpg'],
                    ['34.jpg'],
                    ['SC', 'success'],
                    ['AD', 'info'],
                    ['TF', 'purple'],
                    ['GR', 'orange'],
                ],
            ],
            [
                '4',
                'Wedding Cake',
                'Cakes',
                '3h 30m',
                7,
                'Courtney W.',
                '26.jpg',
                [
                    ['19.jpg'],
                    ['24.jpg'],
                    ['34.jpg'],
                    ['SC', 'success'],
                    ['AD', 'info'],
                    ['TF', 'purple'],
                    ['GR', 'orange'],
                ],
            ],
            [
                '5',
                'Vanilla Waffles',
                'Waffles',
                '1h 0m',
                2,
                'Ryan B.',
                '14.jpg',
                [
                    ['vuero-1.svg'],
                    ['9.jpg'],
                    ['13.jpg'],
                    ['SC', 'success'],
                    ['AD', 'info'],
                    ['TF', 'purple'],
                    ['GR', 'orange'],
                ],
            ],
            [
                '6',
                'Strawberry Slice',
                'Slices',
                '1h 0m',
                2,
                'Alice C.',
                '7.jpg',
                [
                    ['vuero-1.svg'],
                    ['9.jpg'],
                    ['13.jpg'],
                    ['SC', 'success'],
                    ['AD', 'info'],
                    ['TF', 'purple'],
                    ['GR', 'orange'],
                ],
            ],
            [
                '7',
                'Marmelade Donuts',
                'Donuts',
                '2h 15m',
                2,
                'Elizabeth F.',
                '21.jpg',
                [
                    ['5.jpg'],
                    ['18.jpg'],
                    ['25.jpg'],
                    ['SC', 'success'],
                    ['AD', 'info'],
                    ['TF', 'purple'],
                    ['GR', 'orange'],
                ],
            ],
            [
                '8',
                'Peanut Butter Sandwich',
                'Sandwiches',
                '15m',
                1,
                'Joshua S.',
                '12.jpg',
                [
                    ['9.jpg'],
                    ['28.jpg'],
                    ['36.jpg'],
                    ['SC', 'success'],
                    ['AD', 'info'],
                    ['TF', 'purple'],
                    ['GR', 'orange'],
                ],
            ],
            [
                '9',
                'Peanut Butter Slice',
                'Slices',
                '15m',
                1,
                'Joshua S.',
                '12.jpg',
                [
                    ['5.jpg'],
                    ['31.jpg'],
                    ['16.jpg'],
                    ['SC', 'success'],
                    ['AD', 'info'],
                    ['TF', 'purple'],
                    ['GR', 'orange'],
                ],
            ],
            [
                '10',
                'Strawberry Spicy',
                'Slices',
                '1h 15m',
                1,
                'Kelly M.',
                '11.jpg',
                [
                    ['24.jpg'],
                    ['23.jpg'],
                    ['13.jpg'],
                    ['SC', 'success'],
                    ['AD', 'info'],
                    ['TF', 'purple'],
                    ['GR', 'orange'],
                ],
            ],
        ];

        return collect($items)->map(function (array $item) use ($avatar, $icon): array {
            [$id, $name, $category, $duration, $attachments, $authorName, $authorAvatar, $followers] = $item;

            return [
                'id'          => $id,
                'name'        => $name,
                'category'    => $category,
                'duration'    => $duration,
                'attachments' => $attachments,
                'icon'        => $icon((int) $id === 10 ? 9 : (int) $id),
                'author'      => [
                    'name'   => $authorName,
                    'avatar' => $avatar($authorAvatar),
                ],
                'followers'   => collect($followers)->map(function (array $follower) use ($avatar): array {
                    if (count($follower) === 1) {
                        return ['picture' => $avatar($follower[0])];
                    }

                    return ['initials' => $follower[0], 'color' => $follower[1]];
                })->all(),
            ];
        })->all();
    }

    /**
     * Build the demo flex users data.
     *
     * @return array<array-key, mixed>
     */
    private function flexUsers(): array
    {
        $avatar = fn (?string $file): ?string => match (true) {
            $file === null => null,
            str_contains($file, 'vuero-1.svg') => '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg',
            default => '/vuero/images/layouts/list-view-v1/avatars/' . basename($file),
        };
        $flag = fn (string $country): string => "/vuero/images/layouts/list-view-v1/flags/{$country}.svg";

        $items = [
            [
                'Erik K.',
                'Product Manager',
                'vuero-1.svg',
                'united-states-of-america',
                'Las Vegas, NV',
                'Software',
                'Online',
                [
                    ['25.jpg'],
                    ['JP', 'info'],
                    ['vuero-1.svg'],
                    ['5.jpg'],
                    ['SC', 'success'],
                ],
            ],
            [
                'Jimmy H.',
                'Project Manager',
                '22.jpg',
                'united-states-of-america',
                'Los Angeles, CA',
                'Business',
                'Working',
                [
                    ['SC', 'h-purple'],
                ],
            ],
            [
                'Melany W.',
                'Web Developer',
                '25.jpg',
                'united-states-of-america',
                'San Jose, CA',
                'Design',
                'Online',
                [
                    ['BT', 'success'],
                    ['7.jpg'],
                    ['19.jpg'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                ],
            ],
            [
                'Joshua S.',
                'Backend Developer',
                '12.jpg',
                'united-states-of-america',
                'Las Vegas, NV',
                'Accounting',
                'Suspended',
                [
                    ['13.jpg'],
                ],
            ],
            [
                'Tara S.',
                'UI/UX Designer',
                '13.jpg',
                'united-states-of-america',
                'Los Angeles, CA',
                'Finance',
                'Working',
                [
                    ['25.jpg'],
                    ['11.jpg'],
                    ['5.jpg'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                ],
            ],
            [
                'Greta K.',
                'Sales Manager',
                '19.jpg',
                'germany',
                'Berlin, Germany',
                'Business',
                'Online',
                [
                    ['25.jpg'],
                    ['AT', 'h-yellow'],
                    ['5.jpg'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                ],
            ],
            [
                'Clément D.',
                'Software Engineer',
                '30.jpg',
                'france',
                'Toulouse, France',
                'Software',
                'Offline',
                [
                    ['25.jpg'],
                    ['BV', 'h-purple'],
                    ['vuero-1.svg'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                ],
            ],
            [
                'Kelly M.',
                'Product Manager',
                '11.jpg',
                'england',
                'London, UK',
                'Software',
                'Online',
                [
                    ['25.jpg'],
                    ['13.jpg'],
                    ['5.jpg'],
                ],
            ],
            [
                'Alan T.',
                'Software Engineer',
                null,
                'united-states-of-america',
                'San Francisco, CA',
                'Software',
                'Offline',
                [
                    ['25.jpg'],
                ],
                'AT',
                'h-yellow',
            ],
            [
                'Courtney W.',
                'Web Developer',
                '26.jpg',
                'australia',
                'Melbourne, Australia',
                'Software',
                'Offline',
                [
                    ['7.jpg'],
                    ['JD', 'info'],
                    ['27.jpg'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                ],
            ],
        ];

        return collect($items)->map(function (array $item, int $index) use ($avatar, $flag): array {
            [$username, $position, $picture, $badge, $location, $industry, $status, $contacts] = $item;
            $initials = $item[8] ?? null;
            $color = $item[9] ?? null;

            return [
                'id'       => $index,
                'username' => $username,
                'position' => $position,
                'picture'  => $avatar($picture),
                'initials' => $initials,
                'color'    => $color,
                'badge'    => $flag($badge),
                'location' => $location,
                'industry' => $industry,
                'status'   => $status,
                'contacts' => $this->flexContacts($contacts),
            ];
        })->all();
    }

    /**
     * Build the demo flex projects data.
     *
     * @return array<array-key, mixed>
     */
    private function flexProjects(): array
    {
        $picture = fn (int $number): string => "/vuero/images/layouts/flex-list/abstracts/{$number}.png";

        $items = [
            [
                'Website Redesign',
                'Blake & Mortimer',
                '3 months',
                1,
                'HR Vetting',
                'In Progress',
                [
                    ['25.jpg'],
                    ['JP', 'info'],
                    ['vuero-1.svg'],
                    ['5.jpg'],
                    ['SC', 'success'],
                ],
            ],
            ['Mobile App Rework', 'Pollos Hermanos', '2 months', 2, 'Food', 'In Progress', [['SC', 'h-purple']]],
            [
                'ERP Solution Development',
                'Best Mart',
                '6 months',
                3,
                'Distribution',
                'Paused',
                [
                    ['BT', 'success'],
                    ['7.jpg'],
                    ['19.jpg'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                ],
            ],
            [
                'Website Proof Of Concept',
                'Los Fragueros',
                '1 month',
                4,
                'Clothing',
                'In Progress',
                [
                    ['BT', 'success'],
                    ['AT', 'h-yellow'],
                    ['5.jpg'],
                ],
            ],
            ['Add an Accouting Module', 'WorkForce', '3 weeks', 5, 'Software', 'In Progress', [['18.jpg']]],
            [
                'New UX Exploration',
                'WorkForce',
                '3 weeks',
                6,
                'Software',
                'In Progress',
                [
                    ['25.jpg'],
                    ['11.jpg'],
                    ['5.jpg'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                ],
            ],
            [
                'Ordering App Development',
                'Find Your Stuff',
                '5 months',
                7,
                'Retail',
                'In Progress',
                [
                    ['25.jpg'],
                    ['AT', 'h-yellow'],
                    ['5.jpg'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                ],
            ],
            [
                'Learning App for Children',
                'Coding Kids',
                '2 months',
                8,
                'Education',
                'In Progress',
                [
                    ['25.jpg'],
                    ['BV', 'h-purple'],
                    ['vuero-1.svg'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                ],
            ],
            [
                'App Wireframe Implementation',
                'Freako',
                '2 weeks',
                9,
                'Retail',
                'In Progress',
                [
                    ['25.jpg'],
                    ['13.jpg'],
                    ['5.jpg'],
                ],
            ],
            [
                'Corporate Social Network',
                'Publicis',
                '5 months',
                9,
                'Media',
                'In Progress',
                [
                    ['7.jpg'],
                    ['JD', 'info'],
                    ['27.jpg'],
                    ['SC', 'h-purple'],
                    ['SC', 'h-purple'],
                ],
            ],
        ];

        return collect($items)->map(function (array $item, int $index) use ($picture): array {
            [$name, $customer, $duration, $image, $industry, $status, $team] = $item;

            return [
                'id'       => $index,
                'name'     => $name,
                'customer' => $customer,
                'duration' => $duration,
                'picture'  => $picture($image),
                'industry' => $industry,
                'status'   => $status,
                'team'     => $this->flexContacts($team),
            ];
        })->all();
    }

    /**
     * Build the demo flex courses data.
     *
     * @return array<array-key, mixed>
     */
    private function flexCourses(): array
    {
        $picture = fn (string $file): string => "/vuero/images/layouts/flex-list/courses/{$file}";
        $avatar = fn (string $file): string => '/vuero/images/layouts/list-view-v1/avatars/' . basename($file);

        $items = [
            ['Introduction to Html5', '1.jpg', 'Web Development', 1, 26, 'html5', 'Html5', 'vuero-1.svg', 'Erik K.'],
            [
                'Mastering VueJS and Typescript',
                '2.png',
                'Web Development',
                4,
                12,
                'nodejs',
                'NodeJS',
                '12.jpg',
                'Joshua S.',
            ],
            ['Discovering CSS3 and Stylesheets', '3.jpg', 'Web Development', 2, 16, 'css3', 'CSS3', '5.jpg', 'Mary L.'],
            [
                'Get Started with Laravel 6',
                '4.png',
                'Web Development',
                3,
                0,
                'laravel',
                'Laravel',
                '18.jpg',
                'Esteban C.',
            ],
            [
                'Python Basics for Beginners',
                '5.jpeg',
                'Web Development',
                1,
                8,
                'python',
                'Python',
                '18.jpg',
                'Esteban C.',
            ],
            [
                'Mastering State with React Hooks',
                '6.jpg',
                'Web Development',
                4,
                25,
                'react',
                'ReactJS',
                '30.jpg',
                'Clément D.',
            ],
            [
                'Setting up an Express Server',
                '7.png',
                'Web Development',
                3,
                12,
                'nodejs',
                'NodeJS',
                '38.jpg',
                'Christie D.',
            ],
            [
                'Learning to use Kubernetes',
                '9.jpg',
                'Web Development',
                5,
                34,
                'kubernetes',
                'Kubernetes',
                '30.jpg',
                'Clément D.',
            ],
            [
                'Another Angular Crash Course',
                '11.jpg',
                'Web Development',
                4,
                22,
                'angular',
                'Angular',
                '34.jpg',
                'Daniel R.',
            ],
            [
                'learning Advanced Docker Concepts',
                '12.jpg',
                'Web Development',
                5,
                35,
                'docker',
                'Docker',
                '30.jpg',
                'Clément D.',
            ],
        ];

        return collect($items)->map(function (array $item, int $index) use ($picture, $avatar): array {
            [$name, $image, $category, $difficulty, $price, $skillSlug, $skillName, $authorAvatar, $authorName] = $item;

            return [
                'id'         => $index,
                'name'       => $name,
                'picture'    => $picture($image),
                'category'   => $category,
                'difficulty' => $difficulty,
                'price'      => $price,
                'skill'      => ['slug' => $skillSlug, 'name' => $skillName],
                'author'     => [
                    'username' => $authorName,
                    'picture'  => $avatar($authorAvatar),
                ],
            ];
        })->all();
    }

    /**
     * Build the demo flex contacts data.
     *
     * @param list<array{0: string}|list{string}> $contacts
     *
     * @return array<array-key, mixed>
     */
    private function flexContacts(array $contacts): array
    {
        return collect($contacts)->map(function (array $contact): array {
            if (count($contact) === 1 && str_contains($contact[0], '.')) {
                return ['picture' => '/vuero/images/layouts/list-view-v1/avatars/' . $contact[0]];
            }

            if (count($contact) === 1 && str_contains($contact[0], 'vuero-1.svg')) {
                return ['picture' => '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg'];
            }

            return ['initials' => $contact[0], 'color' => $contact[1] ?? 'info'];
        })->all();
    }

    /**
     * Build the demo grid card social links data.
     *
     * @return array<array-key, mixed>
     */
    private function gridCardSocialLinks(): array
    {
        return [
            [
                'channel' => 'Linkedin',
                'url'     => 'https://linkedin.com',
                'icon'    => 'brand-linkedin',
            ],
            [
                'channel' => 'Twitter',
                'url'     => 'https://twitter.com',
                'icon'    => 'brand-twitter',
            ],
            [
                'channel' => 'Dribbble',
                'url'     => 'https://dribbble.com',
                'icon'    => 'brand-dribbble',
            ],
            [
                'channel' => 'GitHub',
                'url'     => 'https://github.com',
                'icon'    => 'brand-github',
            ],
        ];
    }

    /**
     * Build the demo grid card badge data.
     *
     * @param string $file
     *
     * @return string
     */
    private function gridCardBadge(string $file): string
    {
        $flags = [
            'united-states-of-america',
            'france',
            'germany',
            'england',
            'australia',
            'canada',
            'spain',
        ];

        if (in_array($file, $flags, true)) {
            return "/vuero/images/layouts/list-view-v1/flags/{$file}.svg";
        }

        return '/vuero/images/layouts/grid-cards/stacks/' . pathinfo($file, PATHINFO_FILENAME) . '.svg';
    }

    /**
     * Build the demo grid card users data.
     *
     * @return array<array-key, mixed>
     */
    private function gridCardUsers(): array
    {
        $avatar = fn (string $file): string => '/vuero/images/layouts/list-view-v1/avatars/' . $file;
        $social = $this->gridCardSocialLinks();

        $items = [
            [
                'Tara Svenson',
                'UI/UX Designer',
                65,
                '13.jpg',
                'united-states-of-america',
            ],
            [
                'Helmut Fritz',
                'Product Manager',
                38,
                '37.jpg',
                'france',
            ],
            [
                'Melany Wallace',
                'Web Developer',
                58,
                '25.jpg',
                'js',
            ],
            [
                'Hakeem Calami',
                'Software Engineer',
                85,
                '29.jpg',
                'python',
            ],
            [
                'Alejandro Badajoz',
                'Business Analyst',
                28,
                '39.jpg',
                'united-states-of-america',
            ],
            [
                'Jeanne Marchand',
                'Mobile Developer',
                67,
                '40.jpg',
                'android',
            ],
            [
                'Greta Kroppfer',
                'Sales Manager',
                56,
                '19.jpg',
                'germany',
            ],
            [
                'Ana Baker',
                'UI/UX Designer',
                12,
                '9.jpg',
                'photoshop',
            ],
            [
                'Mary Lebowski',
                'Project Manager',
                29,
                '5.jpg',
                'united-states-of-america',
            ],
            [
                'Jonathan Krugger',
                'Product Manager',
                91,
                '32.jpg',
                'united-states-of-america',
            ],
            [
                'Benoit Leblanc',
                'Software Engineer',
                44,
                '36.jpg',
                'reactjs',
            ],
            [
                'Christie Dallas',
                'Web Developer',
                61,
                '38.jpg',
                'vuejs',
            ],
            [
                'Courtney Wilson',
                'Web Developer',
                53,
                '26.jpg',
                'html5',
            ],
            [
                'Jason Guarank',
                'Business Analyst',
                78,
                '16.jpg',
                'united-states-of-america',
            ],
            [
                'Irina Vierbovsky',
                'Project Manager',
                36,
                '23.jpg',
                'united-states-of-america',
            ],
            [
                'Edouard Falant',
                'Web Developer',
                49,
                '28.jpg',
                'angular',
            ],
            [
                'Alice Carasca',
                'Software Engineer',
                78,
                '7.jpg',
                'csharp',
            ],
            [
                'Dwayne Hicks',
                'Product Manager',
                17,
                '22.jpg',
                'united-states-of-america',
            ],
        ];

        return collect($items)->map(fn (array $item): array => [
            'name'     => $item[0],
            'role'     => $item[1],
            'progress' => $item[2],
            'avatar'   => $avatar($item[3]),
            'badge'    => $this->gridCardBadge($item[4]),
            'social'   => $social,
        ])->all();
    }

    /**
     * Build the demo grid card projects data.
     *
     * @return array<array-key, mixed>
     */
    private function gridCardProjects(): array
    {
        $photo = fn (string $file): string => '/vuero/images/layouts/grid-cards/apps/' . $file;
        $avatar = fn (string $file
        ): string => str_contains($file, 'vuero-1.svg') ? '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg' : '/vuero/images/layouts/list-view-v1/avatars/' . $file;

        $items = [
            [
                'Delivery App Project',
                'August 2020',
                '3m ago',
                '1.jpg',
                '7.jpg',
                'Alice C.',
                [
                    ['27.jpg'],
                    ['15.jpg'],
                    ['12.jpg'],
                ],
            ],
            [
                'Health and Fitness Dashboard',
                'October 2020',
                '5h ago',
                '2.png',
                'vuero-1.svg',
                'Erik K.',
                [
                    ['13.jpg'],
                    ['21.jpg'],
                ],
            ],
            ['Learning Tracker Dashboard', 'September 2020', '5h ago', '3.png', '25.jpg', 'Melany W.', [['14.jpg']]],
            [
                'Banking and Finance Dashboard',
                'October 2020',
                '5h ago',
                '4.png',
                '23.jpg',
                'Irina V.',
                [
                    ['13.jpg'],
                    ['21.jpg'],
                ],
            ],
            [
                'Resume Management Mobile App',
                'October 2020',
                '2h ago',
                '5.png',
                '11.jpg',
                'Kelly M.',
                [
                    ['30.jpg'],
                    ['39.jpg'],
                ],
            ],
            [
                'Banking Landing Page',
                'December 2020',
                '3d ago',
                '6.png',
                '9.jpg',
                'Ana B.',
                [
                    ['15.jpg'],
                    ['40.jpg'],
                    ['vuero-1.svg'],
                ],
            ],
            ['Learning App Design', 'November 2020', '1h ago', '7.png', '25.jpg', 'Melany W.', [['11.jpg']]],
            [
                'Educational App Design',
                'November 2020',
                '4d ago',
                '8.png',
                '5.jpg',
                'Mary L.',
                [
                    ['21.jpg'],
                    ['29.jpg'],
                    ['33.jpg'],
                ],
            ],
            [
                'Banking Solution Website',
                'October 2020',
                '2h ago',
                '9.png',
                '27.jpg',
                'Carmen E.',
                [
                    ['30.jpg'],
                    ['19.jpg'],
                ],
            ],
            [
                'HR Webapp Design',
                'August 2020',
                '2h ago',
                '10.png',
                '27.jpg',
                'Carmen E.',
                [
                    ['24.jpg'],
                    ['22.jpg'],
                ],
            ],
            [
                'Ice Cream Delivery Dashboard',
                'January 2021',
                '2h ago',
                '11.png',
                '13.jpg',
                'Tara S.',
                [
                    ['28.jpg'],
                ],
            ],
            [
                'Mobile App Landing Page',
                'November 2020',
                '4d ago',
                '12.jpg',
                '12.jpg',
                'Joshua S.',
                [
                    ['SC', 'h-purple'],
                ],
            ],
            [
                'Web Marketing Landing Page',
                'November 2020',
                '3m ago',
                '13.png',
                '12.jpg',
                'Joshua S.',
                [
                    ['vuero-1.svg'],
                    ['15.jpg'],
                    ['40.jpg'],
                ],
            ],
            [
                'Startup Landing Page',
                'November 2020',
                '3m ago',
                '14.jpg',
                '15.jpg',
                'Hilde V.',
                [
                    ['31.jpg'],
                ],
            ],
            [
                'Expense Management System',
                'January 2021',
                '2d ago',
                '15.png',
                '39.jpg',
                'Alejandro B.',
                [
                    ['5.jpg'],
                    ['24.jpg'],
                ],
            ],
        ];

        return collect($items)->map(function (array $item, int $index) use ($photo, $avatar): array {
            return [
                'id'      => $index + 1,
                'name'    => $item[0],
                'dueDate' => $item[1],
                'updated' => $item[2],
                'image'   => $photo($item[3]),
                'owner'   => [
                    'name'   => $item[5],
                    'avatar' => $avatar($item[4]),
                ],
                'team'    => $this->flexContacts($item[6]),
            ];
        })->all();
    }

    /**
     * Build the demo grid card projects v3 data.
     *
     * @return array<array-key, mixed>
     */
    private function gridCardProjectsV3(): array
    {
        $photo = fn (string $file): string => '/vuero/images/layouts/grid-cards/apps/' . $file;
        $stack = fn (string $name): string => '/vuero/images/layouts/grid-cards/stacks/' . $name . '.svg';

        $items = [
            [
                'Delivery App Project',
                '3 months',
                'illustrator',
                '1.jpg',
                true,
                false,
                [
                    ['BT', 'h-yellow'],
                    ['18.jpg'],
                    ['JD', 'info'],
                    ['7.jpg'],
                ],
            ],
            [
                'Health and Fitness Dashboard',
                '2 months',
                'reactjs',
                '2.png',
                false,
                false,
                [
                    ['21.jpg'],
                    ['AT', 'success'],
                    ['39.jpg'],
                ],
            ],
            [
                'Learning Tracker Dashboard',
                '4 months',
                'angular',
                '3.png',
                true,
                true,
                [
                    ['21.jpg'],
                    ['SC', 'h-purple'],
                    ['39.jpg'],
                ],
            ],
            ['Banking and Finance Dashboard', '2 weeks', 'js', '4.png', false, false, [['28.jpg'], ['33.jpg']]],
            [
                'Resume Management Mobile App',
                '1 month',
                'csharp',
                '5.png',
                true,
                false,
                [
                    ['AT', 'success'],
                    ['23.jpg'],
                ],
            ],
            [
                'Banking Landing Page',
                '3 weeks',
                'vuejs',
                '6.png',
                false,
                false,
                [
                    ['BT', 'h-yellow'],
                    ['18.jpg'],
                    ['JD', 'info'],
                ],
            ],
            [
                'Learning App Design',
                '5 months',
                'swift',
                '7.png',
                false,
                false,
                [
                    ['SC', 'h-purple'],
                    ['27.jpg'],
                ],
            ],
            [
                'Educational App Design',
                '2 months',
                'android',
                '8.png',
                false,
                false,
                [
                    ['SC', 'h-purple'],
                    ['27.jpg'],
                ],
            ],
            [
                'Banking Solution Website',
                '2 months',
                'html5',
                '9.png',
                false,
                false,
                [
                    ['AT', 'success'],
                    ['23.jpg'],
                ],
            ],
            [
                'HR Webapp Design',
                '2 months',
                'photoshop',
                '10.png',
                false,
                false,
                [
                    ['AT', 'success'],
                    ['23.jpg'],
                ],
            ],
            [
                'Ice Cream Delivery Dashboard',
                '1 week',
                'js',
                '11.png',
                false,
                false,
                [
                    ['BT', 'h-yellow'],
                    ['18.jpg'],
                    ['JD', 'info'],
                    ['7.jpg'],
                ],
            ],
            [
                'Mobile App Landing Page',
                '3 months',
                'css3',
                '12.jpg',
                false,
                false,
                [
                    ['21.jpg'],
                    ['SC', 'h-purple'],
                    ['39.jpg'],
                ],
            ],
            [
                'Web Marketing Landing Page',
                '4 months',
                'reactjs',
                '13.png',
                false,
                false,
                [
                    ['BT', 'h-yellow'],
                    ['31.jpg'],
                ],
            ],
            ['Startup Landing Page', '1 month', 'reactjs', '14.jpg', false, false, [['33.jpg'], ['26.jpg']]],
            [
                'Expense Management System',
                '1 month',
                'reactjs',
                '15.png',
                false,
                false,
                [['21.jpg'], ['AT', 'success'], ['39.jpg']],
            ],
        ];

        return collect($items)->map(function (array $item, int $index) use ($photo, $stack): array {
            return [
                'id'          => $index + 1,
                'name'        => $item[0],
                'remaining'   => $item[1],
                'badge'       => $stack($item[2]),
                'image'       => $photo($item[3]),
                'lockable'    => $item[4],
                'locked'      => $item[5],
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quo tandem modo? At certe gravius.',
                'team'        => $this->flexContacts($item[6]),
            ];
        })->all();
    }

    /**
     * Build the demo grid card posts data.
     *
     * @return array<array-key, mixed>
     */
    private function gridCardPosts(): array
    {
        $photo = fn (int $number): string => '/vuero/images/layouts/grid-cards/posts/' . $number . '.jpg';
        $avatar = fn (string $file
        ): string => str_contains($file, 'vuero-1.svg') ? '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg' : '/vuero/images/layouts/list-view-v1/avatars/' . $file;

        $items = [
            [
                'How to serve coffee at the office the proper way',
                '30 minutes ago',
                11,
                '39.jpg',
                'Alejandro B.',
            ],
            [
                'Teamwork can dramatically increase productivity',
                '4 hours ago',
                12,
                '15.jpg',
                'Hilde V.',
            ],
            [
                'Is team building a scam or the next thing?',
                '5 hours ago',
                13,
                '24.jpg',
                'Sandrine C.',
            ],
            [
                '3 things you should know when applying for developer jobs',
                '8 hours ago',
                14,
                '27.jpg',
                'Carmen E.',
            ],
            [
                'The construction business massively invests in mobile apps',
                '1 day ago',
                15,
                '10.jpg',
                'Henry G.',
            ],
            [
                'Finding the right spot for your startup',
                '1 day ago',
                17,
                '22.jpg',
                'Jimmy H.',
            ],
            [
                '4 tips to make your business lunches awesome',
                '2 days ago',
                18,
                '22.jpg',
                'Jimmy H.',
            ],
            [
                'Setting up a design system for your app project',
                '2 days ago',
                18,
                '26.jpg',
                'Courtney W.',
            ],
            [
                'Setting up a design system for your app project',
                '3 days ago',
                20,
                'vuero-1.svg',
                'Erik K.',
            ],
            [
                'Have you considered product management training?',
                '3 days ago',
                21,
                '14.jpg',
                'Ryan B.',
            ],
            [
                'Using flashy colors in your websites and apps',
                '3 days ago',
                22,
                '9.jpg',
                'Ana B.',
            ],
            [
                'Why it pays to profile your customers',
                '3 days ago',
                23,
                '40.jpg',
                'Jeanne M.',
            ],
            [
                'Building a consistent and talented team',
                '3 days ago',
                24,
                '19.jpg',
                'Greta K.',
            ],
            [
                'Building a consistent and talented team',
                '3 days ago',
                25,
                '28.jpg',
                'Edouard F.',
            ],
            [
                'What framework to choose to build a mobile app in 2020?',
                '3 days ago',
                26,
                '5.jpg',
                'Mary L.',
            ],
            [
                'Diving into building an e-commerce brand - part 1',
                '4 days ago',
                27,
                '33.jpg',
                'Harvey M.',
            ],
            [
                'Diving into building an e-commerce brand - part 2',
                '4 days ago',
                28,
                '33.jpg',
                'Harvey M.',
            ],
            [
                'How to make sure to reach the goals you set',
                '4 days ago',
                29,
                '31.jpg',
                'Yasseen A.',
            ],
        ];

        return collect($items)->map(function (array $item, int $index) use ($photo, $avatar): array {
            return [
                'id'        => $index + 1,
                'title'     => $item[0],
                'published' => $item[1],
                'image'     => $photo($item[2]),
                'author'    => [
                    'name'   => $item[4],
                    'avatar' => $avatar($item[3]),
                ],
            ];
        })->all();
    }

    /**
     * Build the demo tile grid users data.
     *
     * @return array<array-key, mixed>
     */
    private function tileGridUsers(): array
    {
        $avatar = fn (?string $file): ?string => match (true) {
            $file === null => null,
            str_contains($file, 'vuero-1.svg') => '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg',
            default => '/vuero/images/layouts/list-view-v1/avatars/' . basename($file),
        };

        $items = [
            [
                'Hakeem C.',
                'Software Engineer',
                '29.jpg',
                'python',
                'info',
                null,
            ],
            [
                'Elisabeth F.',
                'Mobile Developer',
                '21.jpg',
                'england',
                'success',
                null,
            ],
            [
                'Erik K.',
                'Software Engineer',
                'vuero-1.svg',
                'united-states-of-america',
                'purple',
                null,
            ],
            [
                'Alice C.',
                'Software Engineer',
                '7.jpg',
                'js',
                'info',
                null,
            ],
            [
                'Betty T.',
                'Product Manager',
                null,
                'united-states-of-america',
                'warning',
                'BT',
            ],
            [
                'Irina V.',
                'Project Manager',
                '23.jpg',
                'united-states-of-america',
                'info',
                null,
            ],
            [
                'Helmut F.',
                'Product Manager',
                '37.jpg',
                'france',
                'purple',
                null,
            ],
            [
                'Melany W.',
                'Web Developer',
                '25.jpg',
                'js',
                'success',
                null,
            ],
            [
                'Alejandro B.',
                'Business Analyst',
                '39.jpg',
                'python',
                'purple',
                null,
            ],
            [
                'Greta K.',
                'Sales Manager',
                '19.jpg',
                'germany',
                'yellow',
                null,
            ],
            [
                'Anna B.',
                'UI/UX Designer',
                '9.jpg',
                'illustrator',
                'info',
                null,
            ],
            [
                'Alan T.',
                'Software Engineer',
                null,
                'united-states-of-america',
                'success',
                'AT',
            ],
            [
                'Sara C.',
                'UI/UX Designer',
                null,
                'united-states-of-america',
                'purple',
                'SC',
            ],
            [
                'Jonathan K.',
                'Product Manager',
                '32.jpg',
                'united-states-of-america',
                'info',
                null,
            ],
            [
                'Benoit B.',
                'Software Engineer',
                '36.jpg',
                'reactjs',
                'purple',
                null,
            ],
            [
                'Christie B.',
                'Web Developer',
                '38.jpg',
                'vuejs',
                'info',
                null,
            ],
            [
                'Courtney W.',
                'Web Developer',
                '26.jpg',
                'html5',
                'info',
                null,
            ],
            [
                'Jason G.',
                'Business Analyst',
                '16.jpg',
                'united-states-of-america',
                'danger',
                null,
            ],
            [
                'Edouard F.',
                'Web Developer',
                '28.jpg',
                'angular',
                'success',
                null,
            ],
            [
                'Dwayne H.',
                'Product Manager',
                '22.jpg',
                'united-states-of-america',
                'info',
                null,
            ],
            [
                'Tara S.',
                'UI/UX Designer',
                '13.jpg',
                'illustrator',
                'danger',
                null,
            ],
            [
                'Sandrine C.',
                'HR Manager',
                '24.jpg',
                'france',
                'info',
                null,
            ],
            [
                'Carmen E.',
                'HR Manager',
                '27.jpg',
                'spain',
                'yellow',
                null,
            ],
            [
                'Kelly M.',
                'HR Manager',
                '11.jpg',
                'england',
                'success',
                null,
            ],
            [
                'Hilde V.',
                'Software Engineer',
                '15.jpg',
                'germany',
                'info',
                null,
            ],
            [
                'Ryan B.',
                'Web Developer',
                '14.jpg',
                'england',
                'info',
                null,
            ],
            [
                'Henry G.',
                'Business Analyst',
                '10.jpg',
                'united-states-of-america',
                'danger',
                null,
            ],
            [
                'Esteban C.',
                'UI/UX Designer',
                '18.jpg',
                'united-states-of-america',
                'success',
                null,
            ],
        ];

        return collect($items)->map(fn (array $item): array => [
            'username' => $item[0],
            'position' => $item[1],
            'picture'  => $avatar($item[2]),
            'initials' => $item[5],
            'color'    => $item[4],
            'badge'    => $this->gridCardBadge($item[3]),
        ])->all();
    }

    /**
     * Build the demo tile grid files data.
     *
     * @return array<array-key, mixed>
     */
    private function tileGridFiles(): array
    {
        $icon = fn (string $name): string => '/vuero/images/layouts/grid-tiles/files/' . $name . '.svg';

        $items = [
            [
                'Company UX Guide',
                '2 days ago',
                '4.7 MB',
                'pdf',
            ],
            [
                'Tech Summit Expenses',
                '1 week ago',
                '34 KB',
                'sheet',
            ],
            [
                'Project Outline',
                '2 weeks ago',
                '77 KB',
                'doc-2',
            ],
            [
                'UX Presentation',
                '4 days ago',
                '2.3 MB',
                'ppt',
            ],
            [
                'Website Homepage Redesign',
                '3 hours ago',
                '4.8 MB',
                'ai',
            ],
            [
                'UX Ramp Up for Interns',
                '6 hours ago',
                '1.8 MB',
                'doc-2',
            ],
            [
                '2020 Projects Digest',
                '2 days ago',
                '8.9 MB',
                'pdf',
            ],
            [
                'Financial Report - 2020',
                '2 months ago',
                '1.2 MB',
                'doc-2',
            ],
            [
                '2020 Supplier Expenses',
                '2 weeks ago',
                '250 KB',
                'sheet',
            ],
            [
                'Website About Page Redesign',
                '2 months ago',
                '3.9 MB',
                'ai',
            ],
            [
                'Website Pricing Page Redesign',
                '2 months ago',
                '2.6 MB',
                'ai',
            ],
            [
                'Financial Report - 2019',
                '8 months ago',
                '1.1 MB',
                'doc-2',
            ],
            [
                '2019 Supplier Expenses',
                '8 months ago',
                '250 KB',
                'sheet',
            ],
            [
                'Website Contact Page Redesign',
                '3 months ago',
                '5.8 MB',
                'ai',
            ],
            [
                'Company Brand Book',
                '3 weeks ago',
                '5.3 MB',
                'pdf',
            ],
            [
                '2019 Projects Digest',
                '9 months ago',
                '4.7 MB',
                'pdf',
            ],
        ];

        return collect($items)->map(fn (array $item, int $index): array => [
            'id'      => $index + 1,
            'name'    => $item[0],
            'updated' => $item[1],
            'size'    => $item[2],
            'icon'    => $icon($item[3]),
        ])->all();
    }

    /**
     * Build the demo onboarding page data.
     *
     * @param string $variant
     *
     * @return array<string, mixed>
     */
    private function onboardingPage(string $variant): array
    {
        return [
            'variant'      => $variant,
            'cards'        => $this->onboardingCards($variant),
            'layoutScript' => 'resources/js/vuero/pages/admin/layouts/onboarding-page.js',
        ];
    }

    /**
     * Build the demo demo avatar data.
     *
     * @param string $file
     *
     * @return string
     */
    private function demoAvatar(string $file): string
    {
        if (str_contains($file, 'vuero-1.svg')) {
            return '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg';
        }

        return '/vuero/images/layouts/list-view-v1/avatars/' . basename($file);
    }

    /**
     * Build the demo onboarding image data.
     *
     * @param string $set
     * @param int $number
     *
     * @return array<array-key, mixed>
     */
    private function onboardingImage(string $set, int $number): array
    {
        $base = "/vuero/images/onboarding/{$set}-{$number}";

        return ['image' => "{$base}.svg", 'dark_image' => "{$base}-dark.svg"];
    }

    /**
     * Build the demo onboarding cards data.
     *
     * @param string $variant
     *
     * @return array<array-key, mixed>
     */
    private function onboardingCards(string $variant): array
    {
        $cards = match ($variant) {
            'standard' => [
                [
                    'title'  => 'Configure Shop',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt quidam, ut eorum vita refellatur oratio. Sed tament.',
                    'button' => 'Configure Shop',
                    'set'    => 'set1',
                    'index'  => 1,
                ],
                [
                    'title'  => 'Manage Products',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt quidam, ut eorum vita refellatur oratio. Sed tament.',
                    'button' => 'Manage Products',
                    'set'    => 'set1',
                    'index'  => 2,
                ],
                [
                    'title'  => 'Setup Payments',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt quidam, ut eorum vita refellatur oratio. Sed tament.',
                    'button' => 'Setup Payments',
                    'set'    => 'set1',
                    'index'  => 3,
                ],
            ],
            'v1' => [
                [
                    'title'  => 'Explore Posts',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt.',
                    'button' => 'Explore',
                    'set'    => 'set2',
                    'index'  => 1,
                ],
                [
                    'title'  => 'Schedule a Meeting',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt.',
                    'button' => 'Schedule',
                    'set'    => 'set2',
                    'index'  => 2,
                ],
                [
                    'title'  => 'Chill Out',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt.',
                    'button' => 'Take a Nap',
                    'set'    => 'set2',
                    'index'  => 3,
                ],
            ],
            'v2' => [
                [
                    'title'  => 'Plan a Trip',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt.',
                    'button' => 'Explore',
                    'set'    => 'set3',
                    'index'  => 1,
                ],
                [
                    'title'  => 'Book a Flight',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt.',
                    'button' => 'Book Now',
                    'set'    => 'set3',
                    'index'  => 2,
                ],
                [
                    'title'  => 'Browse Destinations',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt.',
                    'button' => 'Browse',
                    'set'    => 'set3',
                    'index'  => 3,
                ],
            ],
            'v3' => [
                [
                    'title'  => 'Explore Workboard',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt quidam, ut eorum vita refellatur oratio. Sed tament.',
                    'button' => 'Explore',
                    'set'    => 'set4',
                    'index'  => 1,
                ],
                [
                    'title'  => 'Add some Tasks',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt quidam, ut eorum vita refellatur oratio. Sed tament.',
                    'button' => 'Add Tasks',
                    'set'    => 'set4',
                    'index'  => 2,
                ],
                [
                    'title'  => 'Browse Wiki',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt quidam, ut eorum vita refellatur oratio. Sed tament.',
                    'button' => 'Browse',
                    'set'    => 'set4',
                    'index'  => 3,
                ],
            ],
            'v4' => [
                [
                    'title'  => 'Plan a Trip',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt.',
                    'button' => 'Explore',
                    'set'    => 'set5',
                    'index'  => 1,
                ],
                [
                    'title'  => 'Book a Flight',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt.',
                    'button' => 'Book Now',
                    'set'    => 'set5',
                    'index'  => 2,
                ],
                [
                    'title'  => 'Browse Destinations',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt.',
                    'button' => 'Browse',
                    'set'    => 'set5',
                    'index'  => 3,
                ],
            ],
            'v5' => [
                [
                    'title'  => 'Configure Shop',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt quidam, ut eorum vita refellatur oratio. Sed tament.',
                    'button' => 'Configure',
                    'set'    => 'set6',
                    'index'  => 1,
                ],
                [
                    'title'  => 'Configure Warehouse',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt quidam, ut eorum vita refellatur oratio. Sed tament.',
                    'button' => 'Configure',
                    'set'    => 'set6',
                    'index'  => 2,
                ],
                [
                    'title'  => 'Manage Products',
                    'text'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Recte dicis; Ita enim vivunt quidam, ut eorum vita refellatur oratio. Sed tament.',
                    'button' => 'Manage',
                    'set'    => 'set6',
                    'index'  => 3,
                ],
            ],
            default => [],
        };

        return collect($cards)->map(function (array $card): array {
            return [
                ...$this->onboardingImage($card['set'], $card['index']),
                'title'  => $card['title'],
                'text'   => $card['text'],
                'button' => $card['button'],
            ];
        })->all();
    }

    /**
     * Build the demo project members data.
     *
     * @return array<array-key, mixed>
     */
    private function projectMembers(): array
    {
        return collect([
            '7.jpg',
            '9.jpg',
            '12.jpg',
            'vuero-1.svg',
            '25.jpg',
            '22.jpg',
        ])->map(fn (string $file): array => [
            'picture' => $this->demoAvatar($file),
        ])->all();
    }

    /**
     * Build the demo project recent data.
     *
     * @return array<array-key, mixed>
     */
    private function projectRecent(): array
    {
        return [
            [
                'name'     => 'The Slicer project',
                'type'     => 'Design project',
                'logo'     => '/vuero/images/icons/logos/slicer.svg',
                'progress' => 31,
            ],
            [
                'name'     => 'Metamovies reworked',
                'type'     => 'Design project',
                'logo'     => '/vuero/images/icons/logos/metamovies.svg',
                'progress' => 84,
            ],
            [
                'name'     => 'Supreme Flights app',
                'type'     => 'Software project',
                'logo'     => '/vuero/images/admin/business/flights/company1.svg',
                'progress' => 45,
            ],
            [
                'name'     => 'Fastpizza delivery app',
                'type'     => 'Software project',
                'logo'     => '/vuero/images/icons/logos/fastpizza.svg',
                'progress' => 90,
            ],
            [
                'name'     => 'Drop website redesign',
                'type'     => 'Design project',
                'logo'     => '/vuero/images/icons/logos/drop.svg',
                'progress' => 12,
            ],
        ];
    }

    /**
     * Build the demo project advanced users data.
     *
     * @return array<array-key, mixed>
     */
    private function projectAdvancedUsers(): array
    {
        $flag = fn (string $country): string => "/vuero/images/layouts/list-view-v1/flags/{$country}.svg";

        return [
            [
                'username'       => 'Erik K.',
                'position'       => 'Product Manager',
                'picture'        => '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg',
                'badge'          => $flag('united-states-of-america'),
                'annualEarnings' => 115000,
                'status'         => 'Active',
                'contacts'       => [
                    [
                        'picture' => '25.jpg',
                    ],
                    [
                        'initials' => 'JP',
                        'color'    => 'info',
                    ],
                    [
                        'picture' => 'vuero-1.svg',
                    ],
                    [
                        'picture' => '5.jpg',
                    ],
                    [
                        'initials' => 'SC',
                        'color'    => 'success',
                    ],
                ],
            ],
            [
                'username'       => 'Jimmy H.',
                'position'       => 'Project Manager',
                'picture'        => $this->demoAvatar('22.jpg'),
                'badge'          => $flag('united-states-of-america'),
                'annualEarnings' => 110000,
                'status'         => 'New',
                'contacts'       => [
                    [
                        'initials' => 'SC',
                        'color'    => 'h-purple',
                    ],
                ],
            ],
            [
                'username'       => 'Melany W.',
                'position'       => 'Web Developer',
                'picture'        => $this->demoAvatar('25.jpg'),
                'badge'          => $flag('united-states-of-america'),
                'annualEarnings' => 90000,
                'status'         => 'Active',
                'contacts'       => [
                    [
                        'initials' => 'BT',
                        'color'    => 'success',
                    ],
                    [
                        'picture' => '7.jpg',
                    ],
                ],
            ],
            [
                'username'       => 'Joshua S.',
                'position'       => 'Backend Developer',
                'picture'        => $this->demoAvatar('12.jpg'),
                'badge'          => $flag('united-states-of-america'),
                'annualEarnings' => 160000,
                'status'         => 'Disabled',
                'contacts'       => [
                    [
                        'picture' => '13.jpg',
                    ],
                ],
            ],
            [
                'username'       => 'Tina C.',
                'position'       => 'Business Analyst',
                'picture'        => $this->demoAvatar('13.jpg'),
                'badge'          => $flag('united-states-of-america'),
                'annualEarnings' => 247000,
                'status'         => 'Suspended',
                'contacts'       => [
                    [
                        'picture' => '25.jpg',
                    ],
                    [
                        'picture' => '11.jpg',
                    ],
                    [
                        'picture' => '5.jpg',
                    ],
                ],
            ],
        ];
    }

    /**
     * Build the demo project header stats data.
     *
     * @return array<array-key, mixed>
     */
    private function projectHeaderStats(): array
    {
        return [
            [
                'icon'  => 'chart-bar',
                'value' => '147',
                'label' => 'Published projects',
            ],
            [
                'icon'  => 'target',
                'value' => '1638',
                'label' => 'Completed Tasks',
            ],
            [
                'icon'  => 'pie-chart',
                'value' => '88%',
                'label' => 'Team Performance',
            ],
            [
                'icon'  => 'leaf',
                'value' => '17',
                'label' => 'Team Members',
            ],
        ];
    }

    /**
     * Build the demo project cards data.
     *
     * @return array<array-key, mixed>
     */
    private function projectCards(): array
    {
        $description = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Idem iste, inquam, de voluptate quid sentit? Itaque contra est, ac dicitis; Quae cum essent dicta, discessimus. Iam enim adesse poterit. Duo Reges: constructio interrete.';
        $short = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Idem iste, inquam, de voluptate quid sentit? Itaque contra est, ac dicitis.';

        $items = [
            [
                '#118 Agios Inc. Rebranding',
                [
                    [
                        'initials' => 'FE',
                        'color'    => 'primary',
                    ],
                    [
                        'initials' => 'SD',
                        'color'    => 'success',
                    ],
                    [
                        'initials' => 'NJ',
                        'color'    => 'info',
                    ],
                ],
            ],
            [
                '#117 Sweet Candy Website Updates',
                [
                    [
                        'picture' => '7.jpg',
                    ],
                    [
                        'picture' => 'vuero-1.svg',
                    ],
                    [
                        'initials' => 'DC',
                        'color'    => 'danger',
                    ],
                ],
            ],
            [
                '#116 Update our Design Plugin',
                [
                    [
                        'picture' => '25.jpg',
                    ],
                ],
            ],
            [
                '#115 Refactor Chat App',
                [
                    [
                        'picture' => '7.jpg',
                    ],
                    [
                        'picture' => '23.jpg',
                    ],
                ],
            ],
            [
                '#114 Develop a Marketing Website',
                [
                    [
                        'picture' => '11.jpg',
                    ],
                    [
                        'initials' => 'AG',
                        'color'    => 'orange',
                    ],
                ],
            ],
            [
                '#113 Develop a Landing Page',
                [
                    [
                        'picture' => '12.jpg',
                    ],
                ],
            ],
            [
                '#112 Build a Social Mobile App',
                [
                    [
                        'picture' => '24.jpg',
                    ],
                    [
                        'picture' => '18.jpg',
                    ],
                    [
                        'picture' => '16.jpg',
                    ],
                ],
            ],
            [
                '#111 Bulma Inc. Rebranding',
                [
                    [
                        'initials' => 'ET',
                        'color'    => 'info',
                    ],
                    [
                        'initials' => 'BH',
                        'color'    => 'warning',
                    ],
                ],
            ],
            [
                '#110 Develop a Localisation Plugin',
                [
                    [
                        'picture' => '25.jpg',
                    ],
                    [
                        'initials' => 'ET',
                        'color'    => 'info',
                    ],
                ],
            ],
        ];

        return collect($items)->map(function (array $item, int $index) use ($description, $short): array {
            return [
                'title'       => $item[0],
                'description' => $index % 2 === 0 ? $description : $short,
                'developers'  => collect($item[1])->map(function (array $developer): array {
                    if (isset($developer['picture'])) {
                        return ['picture' => $this->demoAvatar($developer['picture'])];
                    }

                    return ['initials' => $developer['initials'], 'color' => $developer['color']];
                })->all(),
                'deadline'    => 'Aug 7 2020',
                'budget'      => '$15,000',
                'status'      => 'In Progress',
            ];
        })->all();
    }

    /**
     * Build the demo project illustration stats data.
     *
     * @return array<array-key, mixed>
     */
    private function projectIllustrationStats(): array
    {
        return [
            [
                'class' => 'is-success',
                'value' => '12',
                'label' => 'In Progress',
            ],
            [
                'class' => 'is-warning',
                'value' => '4',
                'label' => 'Blocked',
            ],
            [
                'class' => 'is-info',
                'value' => '89',
                'label' => 'Shipped',
            ],
            [
                'class' => 'is-primary',
                'value' => '141',
                'label' => 'Projects Total',
            ],
        ];
    }

    /**
     * Build the demo project recent boxes data.
     *
     * @return array<array-key, mixed>
     */
    private function projectRecentBoxes(): array
    {
        return [
            [
                'title'    => 'Customer Management Dashboard',
                'progress' => 55,
                'tone'     => 'success',
            ],
            [
                'title'    => 'CandyBar Website and App Redesign',
                'progress' => 80,
                'tone'     => 'danger',
            ],
            [
                'title'    => 'Marketing and Investment Platform',
                'progress' => 28,
                'tone'     => 'info',
            ],
        ];
    }

    /**
     * Build the demo project minimal items data.
     *
     * @return array<array-key, mixed>
     */
    private function projectMinimalItems(): array
    {
        $text = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Idem iste, inquam, de voluptate quid sentit? Itaque contra est, ac dicitis; Quae cum essent dicta, discessimus. Iam enim adesse poterit. Duo Reges: constructio interrete.';

        $items = [
            [
                'type'         => 'UI/UX Design',
                'status'       => 'In Progress',
                'status_class' => 'is-success',
                'title'        => 'Finance App Onboarding Revamp',
                'tags'         => [
                    'Finance',
                    'Marketing',
                    'Payments',
                ],
                'description'  => $text,
                'team'         => [
                    ['picture' => '13.jpg'],
                    ['picture' => '11.jpg'],
                ],
                'remaining'    => '1 month left',
                'filter'       => 'progress',
            ],
            [
                'type'         => 'Web Development',
                'status'       => 'Shipping Soon',
                'status_class' => 'is-info',
                'title'        => 'Ecommerce Website for Sunset Surfers',
                'tags'         => [
                    'Sports',
                    'Surf',
                ],
                'description'  => $text,
                'team'         => [
                    ['picture' => '7.jpg'],
                    ['picture' => '9.jpg'],
                    ['initials' => 'JD', 'color' => 'info'],
                    ['picture' => '12.jpg'],
                ],
                'remaining'    => '3 days left',
                'filter'       => 'shipping',
            ],
            [
                'type'         => 'Business',
                'status'       => 'Overdue',
                'status_class' => 'is-warning',
                'title'        => 'Business Model for Growing Startup',
                'tags'         => [
                    'Business',
                    'Startups',
                    'Management',
                ],
                'description'  => $text,
                'team'         => [
                    ['picture' => '14.jpg'],
                ],
                'remaining'    => '1 week left',
                'filter'       => 'progress',
            ],
            [
                'type'         => 'Web Development',
                'status'       => 'Blocked',
                'status_class' => 'is-danger',
                'title'        => 'Build a Document Parser with React Hooks',
                'tags'         => [
                    'Javascript',
                    'Tesseract',
                ],
                'description'  => $text,
                'team'         => [
                    ['picture' => 'vuero-1.svg'],
                    ['picture' => '22.jpg'],
                    ['picture' => '12.jpg'],
                ],
                'remaining'    => '2 months left',
                'filter'       => 'blocked',
            ],
            [
                'type'         => 'AI',
                'status'       => 'In Progress',
                'status_class' => 'is-success',
                'title'        => 'Stabilize AI Project and Consolidate Features',
                'tags'         => [
                    'AI',
                    'Machine Learning',
                ],
                'description'  => $text,
                'team'         => [
                    ['picture' => '13.jpg'],
                    ['picture' => '11.jpg'],
                    ['picture' => 'vuero-1.svg'],
                ],
                'remaining'    => '3 months left',
                'filter'       => 'progress',
            ],
            [
                'type'         => 'UI/UX Design',
                'status'       => 'In Progress',
                'status_class' => 'is-success',
                'title'        => 'Rework BigBro Branding and Identity',
                'tags'         => [
                    'Music',
                    'Venue',
                    'Urban',
                ],
                'description'  => $text,
                'team'         => [
                    ['picture' => '13.jpg'],
                    ['picture' => '11.jpg'],
                ],
                'remaining'    => '2 months left',
                'filter'       => 'progress',
            ],
            [
                'type'         => 'UI/UX Design',
                'status'       => 'In Progress',
                'status_class' => 'is-success',
                'title'        => 'Customer Management Dashboard',
                'tags'         => [
                    'Business',
                    'Customers',
                ],
                'description'  => $text,
                'team'         => [
                    ['picture' => '13.jpg'],
                    ['picture' => '16.jpg'],
                    ['picture' => '10.jpg'],
                ],
                'remaining'    => '2 months left',
                'filter'       => 'progress',
            ],
            [
                'type'         => 'Web Development',
                'status'       => 'Blocked',
                'status_class' => 'is-danger',
                'title'        => 'CandyBar Website and App Redesign',
                'tags'         => [
                    'Mobile',
                    'Hybrid',
                ],
                'description'  => $text,
                'team'         => [
                    ['picture' => '22.jpg'],
                    ['picture' => '10.jpg'],
                ],
                'remaining'    => '5 days left',
                'filter'       => 'blocked',
            ],
            [
                'type'         => 'Business',
                'status'       => 'Shipping Soon',
                'status_class' => 'is-info',
                'title'        => 'Marketing and Investment Platform',
                'tags'         => [
                    'Business',
                    'Finance',
                ],
                'description'  => $text,
                'team'         => [
                    ['picture' => '14.jpg'],
                    ['picture' => '16.jpg'],
                ],
                'remaining'    => '1 week left',
                'filter'       => 'shipping',
            ],
        ];

        return collect($items)->map(function (array $item): array {
            return [
                ...$item,
                'team' => collect($item['team'])->map(function (array $member): array {
                    if (isset($member['picture'])) {
                        return ['picture' => $this->demoAvatar($member['picture'])];
                    }

                    return $member;
                })->all(),
            ];
        })->all();
    }

    /**
     * Build the demo project detail participants data.
     *
     * @return array<array-key, mixed>
     */
    private function projectDetailParticipants(): array
    {
        return collect([
            '7.jpg',
            '9.jpg',
            '12.jpg',
            'vuero-1.svg',
            '25.jpg',
        ])->map(fn (string $file): array => [
            'picture' => $this->demoAvatar($file),
        ])->all();
    }

    /**
     * Build the demo project detail features data.
     *
     * @return array<array-key, mixed>
     */
    private function projectDetailFeatures(): array
    {
        return [
            [
                'icon'  => 'edit',
                'title' => 'UI/UX Design',
                'text'  => 'Designing a perfect user experience is in the scope of this project.',
            ],
            [
                'icon'  => 'clock',
                'title' => '1 Week Sprints',
                'text'  => 'This project development iterations follow a 1 week sprint pattern.',
            ],
            [
                'icon'  => 'calendar',
                'title' => '3 Months',
                'text'  => 'This project and all related tasks should completed within 3 months.',
            ],
            [
                'icon'  => 'diamond',
                'title' => 'Fixed',
                'text'  => "This project's budget planning is based on an imutable fixed bid.",
            ],
        ];
    }

    /**
     * Build the demo project detail files data.
     *
     * @return array<array-key, mixed>
     */
    private function projectDetailFiles(): array
    {
        return [
            [
                'icon' => '/vuero/images/icons/files/zip-format.svg',
                'name' => 'Preliminary Sketches',
                'meta' => '3MB · 5 days ago',
            ],
            ['icon' => '/vuero/images/icons/files/pdf.svg', 'name' => 'Project Scope', 'meta' => '0.8MB · 7 days ago'],
            [
                'icon' => '/vuero/images/icons/files/presentation.svg',
                'name' => 'Project Presentation',
                'meta' => '2.4MB · 7 days ago',
            ],
            [
                'icon' => '/vuero/images/icons/files/sheet.svg',
                'name' => 'Project Budget',
                'meta' => '0.6MB · 8 days ago',
            ],
            ['icon' => '/vuero/images/icons/files/doc.svg', 'name' => 'Project Notes', 'meta' => '0.4MB · 8 days ago'],
            ['icon' => '/vuero/images/icons/files/video.svg', 'name' => 'POC Demo', 'meta' => '8.75MB · 8 days ago'],
        ];
    }

    /**
     * Build the demo project detail side cards data.
     *
     * @return array<array-key, mixed>
     */
    private function projectDetailSideCards(): array
    {
        $stack = fn (string $file): string => '/vuero/images/layouts/grid-cards/stacks/' . $file;

        return [
            [
                'title' => 'Project Tools',
                'items' => [
                    [
                        'icon'     => '/vuero/images/icons/stacks/sketch.svg',
                        'title'    => 'Sketch',
                        'subtitle' => 'Design Software',
                    ],
                    [
                        'icon'     => $stack('illustrator.svg'),
                        'title'    => 'Illustrator',
                        'subtitle' => 'Design Software',
                    ],
                    [
                        'icon'     => $stack('photoshop.svg'),
                        'title'    => 'Photoshop',
                        'subtitle' => 'Design Software',
                    ],
                ],
            ],
            [
                'title' => 'Project Stacks',
                'items' => [
                    [
                        'icon'     => $stack('html5.svg'),
                        'title'    => 'Html5',
                        'subtitle' => 'Hypertext Markup',
                    ],
                    [
                        'icon'     => $stack('css3.svg'),
                        'title'    => 'CSS3',
                        'subtitle' => 'Cascading Stylesheets',
                    ],
                    [
                        'icon'     => $stack('vuejs.svg'),
                        'title'    => 'VueJS',
                        'subtitle' => 'Javascript Framework',
                    ],
                ],
            ],
            [
                'title' => 'Customer',
                'items' => [
                    [
                        'icon'     => '/vuero/images/admin/business/flights/company1.svg',
                        'title'    => 'VFlights',
                        'subtitle' => 'Transportation company',
                    ],
                ],
            ],
        ];
    }

    /**
     * Build the demo project detail team data.
     *
     * @return array<array-key, mixed>
     */
    private function projectDetailTeam(): array
    {
        $bio = 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Si enim ad populum me vocas, eum. Quod cum dixissent, ille contra. Cur id non ita fit? Duo Reges: constructio interrete.';

        return [
            [
                'name'   => 'Alice C.',
                'role'   => 'Software Engineer',
                'avatar' => $this->demoAvatar('7.jpg'),
                'bio'    => $bio,
            ],
            [
                'name'   => 'Anna B.',
                'role'   => 'UX Designer',
                'avatar' => $this->demoAvatar('9.jpg'),
                'bio'    => $bio,
            ],
            [
                'name'   => 'Joshua S.',
                'role'   => 'Backend Developer',
                'avatar' => $this->demoAvatar('12.jpg'),
                'bio'    => $bio,
            ],
            [
                'name'   => 'Erik K.',
                'role'   => 'Product Manager',
                'avatar' => $this->demoAvatar('vuero-1.svg'),
                'bio'    => $bio,
            ],
            [
                'name'   => 'Melany W.',
                'role'   => 'Web Developer',
                'avatar' => $this->demoAvatar('25.jpg'),
                'bio'    => $bio,
            ],
            [
                'name'   => 'Tara S.',
                'role'   => 'UI/UX Designer',
                'avatar' => $this->demoAvatar('13.jpg'),
                'bio'    => $bio,
            ],
            [
                'name'   => 'Esteban C.',
                'role'   => 'UI/UX Designer',
                'avatar' => $this->demoAvatar('18.jpg'),
                'bio'    => $bio,
            ],
            [
                'name'   => 'Mary L.',
                'role'   => 'Project Manager',
                'avatar' => $this->demoAvatar('5.jpg'),
                'bio'    => $bio,
            ],
        ];
    }

    /**
     * Build the demo project detail tasks data.
     *
     * @return array<array-key, mixed>
     */
    private function projectDetailTasks(): array
    {
        $items = [
            [
                'title'       => 'Build a desktop and mobile landing page wireframe',
                'progress'    => 45,
                'tone'        => 'info',
                'attachments' => '3 attachments',
                'team'        => [
                    ['picture' => '7.jpg'],
                    ['picture' => 'vuero-1.svg'],
                    ['picture' => '5.jpg'],
                ],
            ],
            [
                'title'       => 'Build a custom video player javascript library',
                'progress'    => 90,
                'tone'        => 'success',
                'attachments' => '1 attachment',
                'team'        => [
                    ['picture' => '12.jpg'],
                ],
            ],
            [
                'title'       => 'Develop a JWT authentication controller',
                'progress'    => 65,
                'tone'        => 'danger',
                'attachments' => '1 attachment',
                'team'        => [
                    ['picture' => '12.jpg'],
                ],
            ],
            [
                'title'       => 'Design custom illustrations for landing page',
                'progress'    => 38,
                'tone'        => 'warning',
                'attachments' => '1 attachment',
                'team'        => [
                    ['picture' => '5.jpg'],
                    ['picture' => '7.jpg'],
                ],
            ],
            [
                'title'       => 'Implement sign up with social media',
                'progress'    => 59,
                'tone'        => null,
                'attachments' => '2 attachments',
                'team'        => [
                    ['picture' => '25.jpg'],
                ],
            ],
            [
                'title'       => 'Implement user management API controller',
                'progress'    => 78,
                'tone'        => 'info',
                'attachments' => '3 attachments',
                'team'        => [
                    ['picture' => '25.jpg'],
                    ['picture' => '12.jpg'],
                ],
            ],
            [
                'title'       => 'Implement job management API controller',
                'progress'    => 36,
                'tone'        => 'success',
                'attachments' => '3 attachments',
                'team'        => [
                    ['picture' => '25.jpg'],
                    ['picture' => '12.jpg'],
                ],
            ],
            [
                'title'       => 'Review and refactor server side code',
                'progress'    => 49,
                'tone'        => 'danger',
                'attachments' => '2 attachments',
                'team'        => [
                    ['picture' => '25.jpg'],
                    ['picture' => '12.jpg'],
                ],
            ],
        ];

        return collect($items)->map(function (array $item): array {
            return [
                ...$item,
                'team' => collect($item['team'])->map(fn (array $member): array => [
                    'picture' => $this->demoAvatar($member['picture']),
                ])->all(),
            ];
        })->all();
    }

    /**
     * Build the demo kanban participants data.
     *
     * @return array<array-key, mixed>
     */
    private function kanbanParticipants(): array
    {
        return collect([
            '7.jpg',
            '9.jpg',
            '12.jpg',
            'vuero-1.svg',
            '25.jpg',
            '13.jpg',
            '16.jpg',
            '18.jpg',
        ])->map(fn (
            string $file
        ): array => [
            'picture' => $this->demoAvatar($file),
        ])->all();
    }

    /**
     * Build the demo kanban columns data.
     *
     * @return array<array-key, mixed>
     */
    private function kanbanColumns(): array
    {
        $board = fn (string $name): array => [
            'light' => "/vuero/images/projects/board/{$name}.svg",
            'dark'  => "/vuero/images/projects/board/{$name}-dark.svg",
        ];

        $participant = fn (string $file, ?string $color = null): array => [
            'picture' => $this->demoAvatar($file),
            'color'   => $color,
        ];

        $tasks = [
            [
                'id'           => '1',
                'state'        => 'new',
                'title'        => 'Create prototype with JWT authentication',
                'dueDate'      => '3 weeks',
                'participants' => [
                    $participant('7.jpg', 'warning'),
                ],
            ],
            [
                'id'           => '2',
                'state'        => 'new',
                'title'        => 'Design a todo list component using React',
                'dueDate'      => '3 weeks',
                'participants' => [
                    $participant('vuero-1.svg', 'info'),
                ],
            ],
            [
                'id'           => '3',
                'state'        => 'progress',
                'title'        => 'Implement the users REST API',
                'dueDate'      => '3 days',
                'participants' => [
                    $participant('12.jpg', 'danger'),
                    $participant('26.jpg'),
                    $participant('19.jpg'),
                ],
            ],
            [
                'id'           => '4',
                'state'        => 'progress',
                'title'        => 'Review project wireframes',
                'dueDate'      => '1 week',
                'participants' => [
                    $participant('13.jpg', 'success'),
                ],
            ],
            [
                'id'           => '5',
                'state'        => 'progress',
                'title'        => 'Implement new footer in all pages',
                'dueDate'      => '5 days',
                'participants' => [
                    $participant('16.jpg', 'info'),
                ],
            ],
            [
                'id'           => '6',
                'state'        => 'ready',
                'title'        => 'Implement the projects REST API',
                'dueDate'      => '2 days',
                'participants' => [
                    $participant('vuero-1.svg', 'warning'),
                ],
            ],
            [
                'id'           => '7',
                'state'        => 'completed',
                'title'        => 'Landing page redesign',
                'dueDate'      => 'On time',
                'participants' => [
                    $participant('12.jpg', 'success'),
                ],
            ],
            [
                'id'           => '8',
                'state'        => 'completed',
                'title'        => 'Projects REST API Implementation',
                'dueDate'      => 'On time',
                'participants' => [
                    $participant('18.jpg', 'danger'),
                    $participant('9.jpg'),
                ],
            ],
            [
                'id'           => '9',
                'state'        => 'completed',
                'title'        => 'Database replication setup',
                'dueDate'      => 'On time',
                'participants' => [
                    $participant('13.jpg', 'primary'),
                ],
            ],
            [
                'id'           => '10',
                'state'        => 'completed',
                'title'        => 'Firebase demo application setup',
                'dueDate'      => 'On time',
                'participants' => [
                    $participant('16.jpg', 'warning'),
                ],
            ],
        ];

        $columns = [
            [
                'state'     => 'new',
                'label'     => 'New',
                'draggable' => false,
                'droppable' => false,
                'empty'     => array_merge([
                    'text' => 'There are no new tasks to approve for now.',
                ], $board('new')),
            ],
            [
                'state'     => 'progress',
                'label'     => 'In Progress',
                'draggable' => true,
                'droppable' => true,
                'empty'     => array_merge([
                    'text' => 'There are no tasks in progress at the moment.',
                ], $board('progress')),
            ],
            [
                'state'     => 'ready',
                'label'     => 'Ready',
                'draggable' => true,
                'droppable' => true,
                'empty'     => array_merge([
                    'text' => '<span>There are no tasks marked as ready for now.</span>',
                ], $board('ready')),
            ],
            [
                'state'     => 'review',
                'label'     => 'In Review',
                'draggable' => true,
                'droppable' => true,
                'empty'     => array_merge([
                    'text' => 'No tasks are being reviewed at the moment.',
                ], $board('test')),
            ],
            [
                'state'     => 'completed',
                'label'     => 'Completed',
                'draggable' => false,
                'droppable' => false,
                'empty'     => array_merge([
                    'text' => 'You have no completed bounties.',
                ], $board('complete')),
            ],
        ];

        return collect($columns)->map(function (array $column) use ($tasks): array {
            return [
                ...$column,
                'tasks' => collect($tasks)->where('state', $column['state'])->values()->all(),
            ];
        })->all();
    }

    /**
     * Build the demo saas billing plans data.
     *
     * @return array<array-key, mixed>
     */
    private function saasBillingPlans(): array
    {
        $icon = fn (string $file): string => "/vuero/images/logos/logo/{$file}";
        $logo = fn (string $name): array => [
            'light' => "/vuero/images/logos/pricing/{$name}.svg",
            'dark'  => "/vuero/images/logos/pricing/{$name}-dark.svg",
        ];

        return [
            [
                'id'      => 'starter',
                'name'    => 'Vuero Starter',
                'slogan'  => 'Plan for starters',
                'intro'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Utilitatis causa amicitia est quaesita.',
                'summary' => 'Vuero has incredible features and each plan perfectly adapts to your company, wether it is a small business or a bigger one. Vuero can also scale with you, as your business grows.',
                'icon'    => $icon('logo.svg'),
                'logo'    => $logo('vuero-starter'),
                'costs'   => [
                    'monthly' => '$45',
                    'yearly'  => '$530',
                ],
                'limits'  => [
                    'seats'    => '3',
                    'projects' => '20',
                    'storage'  => '20GB',
                    'addons'   => '1',
                ],
            ],
            [
                'id'      => 'pro',
                'name'    => 'Vuero Pro',
                'slogan'  => 'For professionals',
                'intro'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Utilitatis causa amicitia est quaesita.',
                'summary' => 'Vuero has incredible features and each plan perfectly adapts to your company, wether it is a small business or a bigger one. Vuero can also scale with you, as your business grows.',
                'icon'    => $icon('logo-secondary.svg'),
                'logo'    => $logo('vuero-pro'),
                'costs'   => [
                    'monthly' => '$99',
                    'yearly'  => '$1080',
                ],
                'limits'  => [
                    'seats'    => '8',
                    'projects' => '150',
                    'storage'  => '100GB',
                    'addons'   => '3',
                ],
            ],
            [
                'id'      => 'business',
                'name'    => 'Vuero Business',
                'slogan'  => 'For serious people',
                'intro'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Utilitatis causa amicitia est quaesita.',
                'summary' => 'Vuero has incredible features and each plan perfectly adapts to your company, wether it is a small business or a bigger one. Vuero can also scale with you, as your business grows.',
                'icon'    => $icon('logo-accent.svg'),
                'logo'    => $logo('vuero-business'),
                'costs'   => [
                    'monthly' => '$149',
                    'yearly'  => '$1590',
                ],
                'limits'  => [
                    'seats'    => '20',
                    'projects' => 'Unlimited',
                    'storage'  => '500GB',
                    'addons'   => '5',
                ],
            ],
            [
                'id'      => 'enterprise',
                'name'    => 'Vuero Enterprise',
                'slogan'  => 'For big companies',
                'intro'   => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Utilitatis causa amicitia est quaesita.',
                'summary' => 'Vuero has incredible features and each plan perfectly adapts to your company, wether it is a small business or a bigger one. Vuero can also scale with you, as your business grows.',
                'icon'    => $icon('logo-platinum.svg'),
                'logo'    => $logo('vuero-enterprise'),
                'costs'   => [
                    'monthly' => '$199',
                    'yearly'  => '$2230',
                ],
                'limits'  => [
                    'seats'    => '40',
                    'projects' => 'Unlimited',
                    'storage'  => 'Unlimited',
                    'addons'   => 'Unlimited',
                ],
            ],
        ];
    }

    /**
     * Build the demo invoice items data.
     *
     * @return list<array{name: string, unit: string, quantity: int, rate: int}>
     */
    private function invoiceItems(): array
    {
        return [
            [
                'name'     => 'Website Redesign',
                'unit'     => 'hrs',
                'quantity' => 54,
                'rate'     => 24,
            ],
            [
                'name'     => 'Logo Design',
                'unit'     => 'hrs',
                'quantity' => 12,
                'rate'     => 24,
            ],
            [
                'name'     => 'Custom Illustrations',
                'unit'     => 'hrs',
                'quantity' => 7,
                'rate'     => 32,
            ],
        ];
    }
}
