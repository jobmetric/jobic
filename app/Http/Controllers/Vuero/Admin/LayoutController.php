<?php

namespace App\Http\Controllers\Vuero\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LayoutController extends Controller
{
    public function show(string $layout): View
    {
        abort_unless(in_array($layout, [
            'list-view-v1', 'list-view-v2', 'list-view-v3', 'list-view-v4',
            'flex-list-v1', 'flex-list-v2', 'flex-list-v3',
            'list-datatable-v1', 'list-datatable-v2', 'list-datatable-v3',
            'form-layout-v1', 'form-layout-v2', 'form-layout-v3', 'form-layout-v4', 'form-layout-v5',
        ], true), 404);

        return view("vuero.admin.layouts.{$layout}", match ($layout) {
            'list-view-v1' => ['users' => $this->users(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-view-v1.js'],
            'list-view-v2' => ['properties' => $this->properties(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-view-v2.js'],
            'list-view-v3' => ['jobs' => $this->jobs(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-view-v3.js'],
            'list-view-v4' => ['recipes' => $this->recipes(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-view-v4.js'],
            'flex-list-v1' => ['users' => $this->flexUsers(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/flex-list-v1.js'],
            'flex-list-v2' => ['projects' => $this->flexProjects(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/flex-list-v2.js'],
            'flex-list-v3' => ['courses' => $this->flexCourses(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/flex-list-v3.js'],
            'list-datatable-v1' => ['users' => $this->flexUsers(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-datatable-v1.js'],
            'list-datatable-v2' => ['projects' => $this->flexProjects(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-datatable-v2.js'],
            'list-datatable-v3' => ['offers' => $this->jobs(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-datatable-v3.js'],
            'form-layout-v1' => ['layoutScript' => 'resources/js/vuero/pages/admin/layouts/form-layout-v1.js'],
            'form-layout-v2' => ['layoutScript' => 'resources/js/vuero/pages/admin/layouts/form-layout-v2.js'],
            'form-layout-v3' => ['layoutScript' => 'resources/js/vuero/pages/admin/layouts/form-layout-v3.js'],
            'form-layout-v4' => ['layoutScript' => 'resources/js/vuero/pages/admin/layouts/form-layout-v4.js'],
            'form-layout-v5' => ['layoutScript' => 'resources/js/vuero/pages/admin/layouts/form-layout-v5.js'],
        });
    }

    private function users(): array
    {
        $avatar = fn (string $id): string => "/vuero/images/layouts/list-view-v1/avatars/{$id}.jpg";
        $flag = fn (string $country): string => "/vuero/images/layouts/list-view-v1/flags/{$country}.svg";

        return [
            ['name' => 'Mary Lebowski', 'location' => 'San Diego, CA', 'role' => 'Project Manager', 'tone' => 'primary', 'avatar' => $avatar('5'), 'flag' => $flag('united-states-of-america'), 'stats' => [14, 97, 16], 'team' => ['9.jpg', 'vuero-1.svg']],
            ['name' => 'Daniel Redbird', 'location' => 'Toronto, Canada', 'role' => 'Web Developer', 'tone' => 'orange', 'avatar' => $avatar('34'), 'flag' => $flag('canada'), 'stats' => [45, 12, 5], 'team' => ['12.jpg', '22.jpg', '40.jpg']],
            ['name' => 'Erik Kovalsky', 'location' => 'New York, NY', 'role' => 'Product Manager', 'tone' => 'secondary', 'avatar' => '/vuero/images/avatars/svg/vuero-1.svg', 'flag' => $flag('united-states-of-america'), 'stats' => [14, 97, 16], 'team' => ['7.jpg', '23.jpg']],
            ['name' => 'Jeanne Marchand', 'location' => 'Paris, France', 'role' => 'Mobile Developer', 'tone' => 'orange', 'avatar' => $avatar('40'), 'flag' => $flag('france'), 'stats' => [6, 12, 8], 'team' => ['18.jpg', '13.jpg', 'vuero-1.svg', '9.jpg', '5.jpg', '7.jpg', '12.jpg', '22.jpg']],
            ['name' => 'Carmen Escudero', 'location' => 'Madrid, Spain', 'role' => 'HR Manager', 'tone' => 'primary', 'avatar' => $avatar('27'), 'flag' => $flag('spain'), 'stats' => [8, 158, 54], 'team' => ['18.jpg']],
            ['name' => 'Anna Baker', 'location' => 'San Francisco, CA', 'role' => 'UI/UX Designer', 'tone' => 'green', 'avatar' => $avatar('9'), 'flag' => $flag('united-states-of-america'), 'stats' => [19, 28, 12], 'team' => ['vuero-1.svg', '5.jpg']],
            ['name' => 'Esteban Castellanos', 'location' => 'Miami, FL', 'role' => 'UI/UX Designer', 'tone' => 'green', 'avatar' => $avatar('18'), 'flag' => $flag('united-states-of-america'), 'stats' => [59, 158, 16], 'team' => ['27.jpg', '16.jpg', '29.jpg', '5.jpg', '9.jpg']],
            ['name' => 'Henry Grobstone', 'location' => 'Miami, FL', 'role' => 'Business Analyst', 'tone' => 'primary', 'avatar' => $avatar('10'), 'flag' => $flag('united-states-of-america'), 'stats' => [42, 15, 2], 'team' => ['32.jpg', '22.jpg']],
            ['name' => 'Melany Wallace', 'location' => 'San Jose, CA', 'role' => 'Web Developer', 'tone' => 'orange', 'avatar' => $avatar('25'), 'flag' => $flag('united-states-of-america'), 'stats' => [17, 22, 4], 'team' => ['26.jpg', '38.jpg', '21.jpg', '5.jpg']],
            ['name' => 'Alice Carasca', 'location' => 'San Diego, CA', 'role' => 'Software Engineer', 'tone' => 'orange', 'avatar' => $avatar('7'), 'flag' => $flag('united-states-of-america'), 'stats' => [31, 82, 14], 'team' => ['9.jpg', 'vuero-1.svg']],
        ];
    }

    private function properties(): array
    {
        $items = [
            ['1396 Redmond Street', '1396 Redmond Street, Apartment 12, Suite H 102, Los Angeles, CA', 4, [5, 3, 1], ['parking', 'wifi', 'heater', 'cleaning'], 3],
            ['24 Mulberry Street', '24 Mulberry Street, Suite 43, New York, NY', 5, [3, 2, 1], ['wifi', 'cleaning'], 0],
            ['62 John Walberg Ave', '62 John Walberg Avenue, Apartment 10, Suite 24, Los Angeles, CA', 5, [4, 2, 2], ['parking', 'wifi', 'heater', 'cleaning'], 3],
            ['48 Manhattan Ave', '48 Manhattan Avenue, Suite G12, New York, NY', 5, [5, 4, 3], ['parking', 'wifi', 'heater', 'cleaning'], 3],
            ['12 Charity Street', '12 Charity Street, Apartment 49, Brooklynn, NY', 4, [3, 2, 1], ['parking', 'wifi', 'cleaning'], 0],
            ['23 Bakery Street', '23 Bakery Street, Suite 121, New York, NY', 5, [3, 2, 1], ['parking', 'wifi', 'heater', 'cleaning'], 4],
            ['1028 Pasadena Ave', '1028 Pasadena Avenue, Suite F 24, Los Angeles, CA', 5, [7, 5, 3], ['parking', 'wifi', 'heater', 'cleaning'], 3],
            ['54 Church Street', '54 Church Street, Apartment 2, New York, NY', 5, [2, 1, 1], ['parking', 'wifi', 'heater', 'cleaning'], 0],
            ['112 Old City District', '112 Old City District, Apartment 5 Suite 42, New York, NY', 5, [3, 2, 1], ['parking', 'wifi', 'heater', 'cleaning'], 0],
            ['51 St Leonard Street', '51 St Leonard Street, Suite 23, New York, NY', 5, [4, 2, 1], ['parking', 'wifi', 'heater', 'cleaning'], 2],
        ];

        return collect($items)->map(fn (array $item, int $index): array => [
            'name' => $item[0], 'location' => $item[1], 'rating' => $item[2], 'details' => $item[3],
            'amenities' => $item[4], 'more' => $item[5],
            'picture' => '/vuero/images/layouts/list-view-v2/properties/'.($index + 1).'.jpg',
        ])->all();
    }

    private function jobs(): array
    {
        $items = [
            ['airbnb', 'Node JS Fullstack Developer wanted 👌', 'San Diego', 'Full Time', '3 Years Exp.'],
            ['hubspot', 'Project Manager for React Native Project', 'Los Angeles', 'Part Time', '2 Years Exp.'],
            ['slack', 'UI/UX Designer to Consolidate the UX Team 💪💪', 'Los Angeles', 'Full Time', '3 Years Exp.'],
            ['gitlab', 'IT Team Senior DevOps Manager', 'Los Angeles', 'Full Time', '10 Years Exp.'],
            ['google', 'Fullstack Developer With Solid MongoDB Skills', 'San Francisco', 'Full Time', '5 Years Exp.'],
            ['facebook', 'Fullstack Javascript and Node JS Developer', 'Pasadena', 'Full Time', '4 Years Exp.'],
            ['tnw', 'Product Owner for React Native Project', 'Los Angeles', 'Full Time', '2 Years Exp.'],
            ['dribbble', 'UI/UX Designer With Html5 and Sass Skills 👍', 'San Diego', 'Full Time', '4 Years Exp.'],
            ['atlassian', 'Experienced Senior UX Team Manager', 'Melbourne', 'Full Time', '10 Years Exp.'],
            ['github', 'Fullstack Javascript and Node JS Developer', 'Los Angeles', 'Part Time', '1 Year Exp.'],
            ['airbnb', 'Node JS Fullstack Developer wanted 👌', 'San Diego', 'Full Time', '3 Years Exp.'],
            ['airbnb', 'Node JS Fullstack Developer wanted 👌', 'San Diego', 'Full Time', '3 Years Exp.'],
        ];

        return collect($items)->map(fn (array $item, int $index): array => [
            'id' => $index + 1, 'brand' => $item[0], 'title' => $item[1], 'location' => $item[2],
            'duration' => $item[3], 'requirements' => $item[4],
            'logo' => "/vuero/images/layouts/list-view-v3/brands/{$item[0]}.svg",
        ])->all();
    }

    private function recipes(): array
    {
        $avatar = fn (string $file): string => "/vuero/images/layouts/list-view-v1/avatars/{$file}";

        $icon = fn (int $number): string => "/vuero/images/layouts/list-view-v4/illustrations/cake-{$number}.svg";

        $items = [
            ['1', 'Strawberry & Cream Cake', 'Cakes', '1h 45m', 3, 'Alice C.', '7.jpg', [['9.jpg'], ['vuero-1.svg']]],
            ['2', 'Orange & Lemon Cake', 'Cakes', '1h 15m', 2, 'Jimmy H.', '22.jpg', [['12.jpg'], ['27.jpg'], ['40.jpg'], ['SC', 'success'], ['AD', 'info'], ['TF', 'purple'], ['GR', 'orange']]],
            ['3', 'Three Chocolates Cake', 'Cakes', '2h', 2, 'Hilde V.', '15.jpg', [['19.jpg'], ['24.jpg'], ['34.jpg'], ['SC', 'success'], ['AD', 'info'], ['TF', 'purple'], ['GR', 'orange']]],
            ['4', 'Wedding Cake', 'Cakes', '3h 30m', 7, 'Courtney W.', '26.jpg', [['19.jpg'], ['24.jpg'], ['34.jpg'], ['SC', 'success'], ['AD', 'info'], ['TF', 'purple'], ['GR', 'orange']]],
            ['5', 'Vanilla Waffles', 'Waffles', '1h 0m', 2, 'Ryan B.', '14.jpg', [['vuero-1.svg'], ['9.jpg'], ['13.jpg'], ['SC', 'success'], ['AD', 'info'], ['TF', 'purple'], ['GR', 'orange']]],
            ['6', 'Strawberry Slice', 'Slices', '1h 0m', 2, 'Alice C.', '7.jpg', [['vuero-1.svg'], ['9.jpg'], ['13.jpg'], ['SC', 'success'], ['AD', 'info'], ['TF', 'purple'], ['GR', 'orange']]],
            ['7', 'Marmelade Donuts', 'Donuts', '2h 15m', 2, 'Elizabeth F.', '21.jpg', [['5.jpg'], ['18.jpg'], ['25.jpg'], ['SC', 'success'], ['AD', 'info'], ['TF', 'purple'], ['GR', 'orange']]],
            ['8', 'Peanut Butter Sandwich', 'Sandwiches', '15m', 1, 'Joshua S.', '12.jpg', [['9.jpg'], ['28.jpg'], ['36.jpg'], ['SC', 'success'], ['AD', 'info'], ['TF', 'purple'], ['GR', 'orange']]],
            ['9', 'Peanut Butter Slice', 'Slices', '15m', 1, 'Joshua S.', '12.jpg', [['5.jpg'], ['31.jpg'], ['16.jpg'], ['SC', 'success'], ['AD', 'info'], ['TF', 'purple'], ['GR', 'orange']]],
            ['10', 'Strawberry Spicy', 'Slices', '1h 15m', 1, 'Kelly M.', '11.jpg', [['24.jpg'], ['23.jpg'], ['13.jpg'], ['SC', 'success'], ['AD', 'info'], ['TF', 'purple'], ['GR', 'orange']]],
        ];

        return collect($items)->map(function (array $item) use ($avatar, $icon): array {
            [$id, $name, $category, $duration, $attachments, $authorName, $authorAvatar, $followers] = $item;

            return [
                'id' => $id,
                'name' => $name,
                'category' => $category,
                'duration' => $duration,
                'attachments' => $attachments,
                'icon' => $icon((int) $id === 10 ? 9 : (int) $id),
                'author' => [
                    'name' => $authorName,
                    'avatar' => $avatar($authorAvatar),
                ],
                'followers' => collect($followers)->map(function (array $follower) use ($avatar): array {
                    if (count($follower) === 1) {
                        return ['picture' => $avatar($follower[0])];
                    }

                    return ['initials' => $follower[0], 'color' => $follower[1]];
                })->all(),
            ];
        })->all();
    }

    private function flexUsers(): array
    {
        $avatar = fn (?string $file): ?string => match (true) {
            $file === null => null,
            str_contains($file, 'vuero-1.svg') => '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg',
            default => '/vuero/images/layouts/list-view-v1/avatars/'.basename($file),
        };
        $flag = fn (string $country): string => "/vuero/images/layouts/list-view-v1/flags/{$country}.svg";

        $items = [
            ['Erik K.', 'Product Manager', 'vuero-1.svg', 'united-states-of-america', 'Las Vegas, NV', 'Software', 'Online', [['25.jpg'], ['JP', 'info'], ['vuero-1.svg'], ['5.jpg'], ['SC', 'success']]],
            ['Jimmy H.', 'Project Manager', '22.jpg', 'united-states-of-america', 'Los Angeles, CA', 'Business', 'Working', [['SC', 'h-purple']]],
            ['Melany W.', 'Web Developer', '25.jpg', 'united-states-of-america', 'San Jose, CA', 'Design', 'Online', [['BT', 'success'], ['7.jpg'], ['19.jpg'], ['SC', 'h-purple'], ['SC', 'h-purple'], ['SC', 'h-purple']]],
            ['Joshua S.', 'Backend Developer', '12.jpg', 'united-states-of-america', 'Las Vegas, NV', 'Accounting', 'Suspended', [['13.jpg']]],
            ['Tara S.', 'UI/UX Designer', '13.jpg', 'united-states-of-america', 'Los Angeles, CA', 'Finance', 'Working', [['25.jpg'], ['11.jpg'], ['5.jpg'], ['SC', 'h-purple'], ['SC', 'h-purple'], ['SC', 'h-purple'], ['SC', 'h-purple']]],
            ['Greta K.', 'Sales Manager', '19.jpg', 'germany', 'Berlin, Germany', 'Business', 'Online', [['25.jpg'], ['AT', 'h-yellow'], ['5.jpg'], ['SC', 'h-purple'], ['SC', 'h-purple'], ['SC', 'h-purple']]],
            ['Clément D.', 'Software Engineer', '30.jpg', 'france', 'Toulouse, France', 'Software', 'Offline', [['25.jpg'], ['BV', 'h-purple'], ['vuero-1.svg'], ['SC', 'h-purple'], ['SC', 'h-purple']]],
            ['Kelly M.', 'Product Manager', '11.jpg', 'england', 'London, UK', 'Software', 'Online', [['25.jpg'], ['13.jpg'], ['5.jpg']]],
            ['Alan T.', 'Software Engineer', null, 'united-states-of-america', 'San Francisco, CA', 'Software', 'Offline', [['25.jpg']], 'AT', 'h-yellow'],
            ['Courtney W.', 'Web Developer', '26.jpg', 'australia', 'Melbourne, Australia', 'Software', 'Offline', [['7.jpg'], ['JD', 'info'], ['27.jpg'], ['SC', 'h-purple'], ['SC', 'h-purple']]],
        ];

        return collect($items)->map(function (array $item, int $index) use ($avatar, $flag): array {
            [$username, $position, $picture, $badge, $location, $industry, $status, $contacts] = $item;
            $initials = $item[8] ?? null;
            $color = $item[9] ?? null;

            return [
                'id' => $index,
                'username' => $username,
                'position' => $position,
                'picture' => $avatar($picture),
                'initials' => $initials,
                'color' => $color,
                'badge' => $flag($badge),
                'location' => $location,
                'industry' => $industry,
                'status' => $status,
                'contacts' => $this->flexContacts($contacts),
            ];
        })->all();
    }

    private function flexProjects(): array
    {
        $picture = fn (int $number): string => "/vuero/images/layouts/flex-list/abstracts/{$number}.png";

        $items = [
            ['Website Redesign', 'Blake & Mortimer', '3 months', 1, 'HR Vetting', 'In Progress', [['25.jpg'], ['JP', 'info'], ['vuero-1.svg'], ['5.jpg'], ['SC', 'success']]],
            ['Mobile App Rework', 'Pollos Hermanos', '2 months', 2, 'Food', 'In Progress', [['SC', 'h-purple']]],
            ['ERP Solution Development', 'Best Mart', '6 months', 3, 'Distribution', 'Paused', [['BT', 'success'], ['7.jpg'], ['19.jpg'], ['SC', 'h-purple'], ['SC', 'h-purple'], ['SC', 'h-purple']]],
            ['Website Proof Of Concept', 'Los Fragueros', '1 month', 4, 'Clothing', 'In Progress', [['BT', 'success'], ['AT', 'h-yellow'], ['5.jpg']]],
            ['Add an Accouting Module', 'WorkForce', '3 weeks', 5, 'Software', 'In Progress', [['18.jpg']]],
            ['New UX Exploration', 'WorkForce', '3 weeks', 6, 'Software', 'In Progress', [['25.jpg'], ['11.jpg'], ['5.jpg'], ['SC', 'h-purple'], ['SC', 'h-purple'], ['SC', 'h-purple'], ['SC', 'h-purple']]],
            ['Ordering App Development', 'Find Your Stuff', '5 months', 7, 'Retail', 'In Progress', [['25.jpg'], ['AT', 'h-yellow'], ['5.jpg'], ['SC', 'h-purple'], ['SC', 'h-purple'], ['SC', 'h-purple']]],
            ['Learning App for Children', 'Coding Kids', '2 months', 8, 'Education', 'In Progress', [['25.jpg'], ['BV', 'h-purple'], ['vuero-1.svg'], ['SC', 'h-purple'], ['SC', 'h-purple']]],
            ['App Wireframe Implementation', 'Freako', '2 weeks', 9, 'Retail', 'In Progress', [['25.jpg'], ['13.jpg'], ['5.jpg']]],
            ['Corporate Social Network', 'Publicis', '5 months', 9, 'Media', 'In Progress', [['7.jpg'], ['JD', 'info'], ['27.jpg'], ['SC', 'h-purple'], ['SC', 'h-purple']]],
        ];

        return collect($items)->map(function (array $item, int $index) use ($picture): array {
            [$name, $customer, $duration, $image, $industry, $status, $team] = $item;

            return [
                'id' => $index,
                'name' => $name,
                'customer' => $customer,
                'duration' => $duration,
                'picture' => $picture($image),
                'industry' => $industry,
                'status' => $status,
                'team' => $this->flexContacts($team),
            ];
        })->all();
    }

    private function flexCourses(): array
    {
        $picture = fn (string $file): string => "/vuero/images/layouts/flex-list/courses/{$file}";
        $avatar = fn (string $file): string => '/vuero/images/layouts/list-view-v1/avatars/'.basename($file);

        $items = [
            ['Introduction to Html5', '1.jpg', 'Web Development', 1, 26, 'html5', 'Html5', 'vuero-1.svg', 'Erik K.'],
            ['Mastering VueJS and Typescript', '2.png', 'Web Development', 4, 12, 'nodejs', 'NodeJS', '12.jpg', 'Joshua S.'],
            ['Discovering CSS3 and Stylesheets', '3.jpg', 'Web Development', 2, 16, 'css3', 'CSS3', '5.jpg', 'Mary L.'],
            ['Get Started with Laravel 6', '4.png', 'Web Development', 3, 0, 'laravel', 'Laravel', '18.jpg', 'Esteban C.'],
            ['Python Basics for Beginners', '5.jpeg', 'Web Development', 1, 8, 'python', 'Python', '18.jpg', 'Esteban C.'],
            ['Mastering State with React Hooks', '6.jpg', 'Web Development', 4, 25, 'react', 'ReactJS', '30.jpg', 'Clément D.'],
            ['Setting up an Express Server', '7.png', 'Web Development', 3, 12, 'nodejs', 'NodeJS', '38.jpg', 'Christie D.'],
            ['Learning to use Kubernetes', '9.jpg', 'Web Development', 5, 34, 'kubernetes', 'Kubernetes', '30.jpg', 'Clément D.'],
            ['Another Angular Crash Course', '11.jpg', 'Web Development', 4, 22, 'angular', 'Angular', '34.jpg', 'Daniel R.'],
            ['learning Advanced Docker Concepts', '12.jpg', 'Web Development', 5, 35, 'docker', 'Docker', '30.jpg', 'Clément D.'],
        ];

        return collect($items)->map(function (array $item, int $index) use ($picture, $avatar): array {
            [$name, $image, $category, $difficulty, $price, $skillSlug, $skillName, $authorAvatar, $authorName] = $item;

            return [
                'id' => $index,
                'name' => $name,
                'picture' => $picture($image),
                'category' => $category,
                'difficulty' => $difficulty,
                'price' => $price,
                'skill' => ['slug' => $skillSlug, 'name' => $skillName],
                'author' => [
                    'username' => $authorName,
                    'picture' => $avatar($authorAvatar),
                ],
            ];
        })->all();
    }

    /** @param  list<array{0: string}|list{string}>  $contacts */
    private function flexContacts(array $contacts): array
    {
        return collect($contacts)->map(function (array $contact): array {
            if (count($contact) === 1 && str_contains($contact[0], '.')) {
                return ['picture' => '/vuero/images/layouts/list-view-v1/avatars/'.$contact[0]];
            }

            if (count($contact) === 1 && str_contains($contact[0], 'vuero-1.svg')) {
                return ['picture' => '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg'];
            }

            return ['initials' => $contact[0], 'color' => $contact[1] ?? 'info'];
        })->all();
    }
}
