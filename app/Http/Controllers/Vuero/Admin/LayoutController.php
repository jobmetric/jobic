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
            'placeload-v1', 'placeload-v2', 'placeload-v3', 'placeload-v4',
            'grid-card-v1', 'grid-card-v2', 'grid-card-v3', 'grid-card-v4',
            'grid-tile-v1', 'grid-tile-v2', 'grid-tile-v3',
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
            'placeload-v1' => ['layoutScript' => 'resources/js/vuero/pages/admin/layouts/placeload-v1.js'],
            'placeload-v2' => ['layoutScript' => 'resources/js/vuero/pages/admin/layouts/placeload-v2.js'],
            'placeload-v3' => ['layoutScript' => 'resources/js/vuero/pages/admin/layouts/placeload-v3.js'],
            'placeload-v4' => ['layoutScript' => 'resources/js/vuero/pages/admin/layouts/placeload-v4.js'],
            'grid-card-v1' => ['users' => $this->gridCardUsers(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-card-v1.js'],
            'grid-card-v2' => ['projects' => $this->gridCardProjects(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-card-v2.js'],
            'grid-card-v3' => ['projects' => $this->gridCardProjectsV3(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-card-v3.js'],
            'grid-card-v4' => ['posts' => $this->gridCardPosts(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-card-v4.js'],
            'grid-tile-v1' => ['users' => $this->tileGridUsers(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-tile-v1.js'],
            'grid-tile-v2' => ['files' => $this->tileGridFiles(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-tile-v2.js'],
            'grid-tile-v3' => ['layoutScript' => 'resources/js/vuero/pages/admin/layouts/grid-tile-v3.js'],
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

    private function gridCardSocialLinks(): array
    {
        return [
            ['channel' => 'Linkedin', 'url' => 'https://linkedin.com', 'icon' => 'brand-linkedin'],
            ['channel' => 'Twitter', 'url' => 'https://twitter.com', 'icon' => 'brand-twitter'],
            ['channel' => 'Dribbble', 'url' => 'https://dribbble.com', 'icon' => 'brand-dribbble'],
            ['channel' => 'GitHub', 'url' => 'https://github.com', 'icon' => 'brand-github'],
        ];
    }

    private function gridCardBadge(string $file): string
    {
        $flags = ['united-states-of-america', 'france', 'germany', 'england', 'australia', 'canada', 'spain'];

        if (in_array($file, $flags, true)) {
            return "/vuero/images/layouts/list-view-v1/flags/{$file}.svg";
        }

        return '/vuero/images/layouts/grid-cards/stacks/'.pathinfo($file, PATHINFO_FILENAME).'.svg';
    }

    private function gridCardUsers(): array
    {
        $avatar = fn (string $file): string => '/vuero/images/layouts/list-view-v1/avatars/'.$file;
        $social = $this->gridCardSocialLinks();

        $items = [
            ['Tara Svenson', 'UI/UX Designer', 65, '13.jpg', 'united-states-of-america'],
            ['Helmut Fritz', 'Product Manager', 38, '37.jpg', 'france'],
            ['Melany Wallace', 'Web Developer', 58, '25.jpg', 'js'],
            ['Hakeem Calami', 'Software Engineer', 85, '29.jpg', 'python'],
            ['Alejandro Badajoz', 'Business Analyst', 28, '39.jpg', 'united-states-of-america'],
            ['Jeanne Marchand', 'Mobile Developer', 67, '40.jpg', 'android'],
            ['Greta Kroppfer', 'Sales Manager', 56, '19.jpg', 'germany'],
            ['Ana Baker', 'UI/UX Designer', 12, '9.jpg', 'photoshop'],
            ['Mary Lebowski', 'Project Manager', 29, '5.jpg', 'united-states-of-america'],
            ['Jonathan Krugger', 'Product Manager', 91, '32.jpg', 'united-states-of-america'],
            ['Benoit Leblanc', 'Software Engineer', 44, '36.jpg', 'reactjs'],
            ['Christie Dallas', 'Web Developer', 61, '38.jpg', 'vuejs'],
            ['Courtney Wilson', 'Web Developer', 53, '26.jpg', 'html5'],
            ['Jason Guarank', 'Business Analyst', 78, '16.jpg', 'united-states-of-america'],
            ['Irina Vierbovsky', 'Project Manager', 36, '23.jpg', 'united-states-of-america'],
            ['Edouard Falant', 'Web Developer', 49, '28.jpg', 'angular'],
            ['Alice Carasca', 'Software Engineer', 78, '7.jpg', 'csharp'],
            ['Dwayne Hicks', 'Product Manager', 17, '22.jpg', 'united-states-of-america'],
        ];

        return collect($items)->map(fn (array $item): array => [
            'name' => $item[0],
            'role' => $item[1],
            'progress' => $item[2],
            'avatar' => $avatar($item[3]),
            'badge' => $this->gridCardBadge($item[4]),
            'social' => $social,
        ])->all();
    }

    private function gridCardProjects(): array
    {
        $photo = fn (string $file): string => '/vuero/images/layouts/grid-cards/apps/'.$file;
        $avatar = fn (string $file): string => str_contains($file, 'vuero-1.svg')
            ? '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg'
            : '/vuero/images/layouts/list-view-v1/avatars/'.$file;

        $items = [
            ['Delivery App Project', 'August 2020', '3m ago', '1.jpg', '7.jpg', 'Alice C.', [['27.jpg'], ['15.jpg'], ['12.jpg']]],
            ['Health and Fitness Dashboard', 'October 2020', '5h ago', '2.png', 'vuero-1.svg', 'Erik K.', [['13.jpg'], ['21.jpg']]],
            ['Learning Tracker Dashboard', 'September 2020', '5h ago', '3.png', '25.jpg', 'Melany W.', [['14.jpg']]],
            ['Banking and Finance Dashboard', 'October 2020', '5h ago', '4.png', '23.jpg', 'Irina V.', [['13.jpg'], ['21.jpg']]],
            ['Resume Management Mobile App', 'October 2020', '2h ago', '5.png', '11.jpg', 'Kelly M.', [['30.jpg'], ['39.jpg']]],
            ['Banking Landing Page', 'December 2020', '3d ago', '6.png', '9.jpg', 'Ana B.', [['15.jpg'], ['40.jpg'], ['vuero-1.svg']]],
            ['Learning App Design', 'November 2020', '1h ago', '7.png', '25.jpg', 'Melany W.', [['11.jpg']]],
            ['Educational App Design', 'November 2020', '4d ago', '8.png', '5.jpg', 'Mary L.', [['21.jpg'], ['29.jpg'], ['33.jpg']]],
            ['Banking Solution Website', 'October 2020', '2h ago', '9.png', '27.jpg', 'Carmen E.', [['30.jpg'], ['19.jpg']]],
            ['HR Webapp Design', 'August 2020', '2h ago', '10.png', '27.jpg', 'Carmen E.', [['24.jpg'], ['22.jpg']]],
            ['Ice Cream Delivery Dashboard', 'January 2021', '2h ago', '11.png', '13.jpg', 'Tara S.', [['28.jpg']]],
            ['Mobile App Landing Page', 'November 2020', '4d ago', '12.jpg', '12.jpg', 'Joshua S.', [['SC', 'h-purple']]],
            ['Web Marketing Landing Page', 'November 2020', '3m ago', '13.png', '12.jpg', 'Joshua S.', [['vuero-1.svg'], ['15.jpg'], ['40.jpg']]],
            ['Startup Landing Page', 'November 2020', '3m ago', '14.jpg', '15.jpg', 'Hilde V.', [['31.jpg']]],
            ['Expense Management System', 'January 2021', '2d ago', '15.png', '39.jpg', 'Alejandro B.', [['5.jpg'], ['24.jpg']]],
        ];

        return collect($items)->map(function (array $item, int $index) use ($photo, $avatar): array {
            return [
                'id' => $index + 1,
                'name' => $item[0],
                'dueDate' => $item[1],
                'updated' => $item[2],
                'image' => $photo($item[3]),
                'owner' => [
                    'name' => $item[5],
                    'avatar' => $avatar($item[4]),
                ],
                'team' => $this->flexContacts($item[6]),
            ];
        })->all();
    }

    private function gridCardProjectsV3(): array
    {
        $photo = fn (string $file): string => '/vuero/images/layouts/grid-cards/apps/'.$file;
        $stack = fn (string $name): string => '/vuero/images/layouts/grid-cards/stacks/'.$name.'.svg';

        $items = [
            ['Delivery App Project', '3 months', 'illustrator', '1.jpg', true, false, [['BT', 'h-yellow'], ['18.jpg'], ['JD', 'info'], ['7.jpg']]],
            ['Health and Fitness Dashboard', '2 months', 'reactjs', '2.png', false, false, [['21.jpg'], ['AT', 'success'], ['39.jpg']]],
            ['Learning Tracker Dashboard', '4 months', 'angular', '3.png', true, true, [['21.jpg'], ['SC', 'h-purple'], ['39.jpg']]],
            ['Banking and Finance Dashboard', '2 weeks', 'js', '4.png', false, false, [['28.jpg'], ['33.jpg']]],
            ['Resume Management Mobile App', '1 month', 'csharp', '5.png', true, false, [['AT', 'success'], ['23.jpg']]],
            ['Banking Landing Page', '3 weeks', 'vuejs', '6.png', false, false, [['BT', 'h-yellow'], ['18.jpg'], ['JD', 'info']]],
            ['Learning App Design', '5 months', 'swift', '7.png', false, false, [['SC', 'h-purple'], ['27.jpg']]],
            ['Educational App Design', '2 months', 'android', '8.png', false, false, [['SC', 'h-purple'], ['27.jpg']]],
            ['Banking Solution Website', '2 months', 'html5', '9.png', false, false, [['AT', 'success'], ['23.jpg']]],
            ['HR Webapp Design', '2 months', 'photoshop', '10.png', false, false, [['AT', 'success'], ['23.jpg']]],
            ['Ice Cream Delivery Dashboard', '1 week', 'js', '11.png', false, false, [['BT', 'h-yellow'], ['18.jpg'], ['JD', 'info'], ['7.jpg']]],
            ['Mobile App Landing Page', '3 months', 'css3', '12.jpg', false, false, [['21.jpg'], ['SC', 'h-purple'], ['39.jpg']]],
            ['Web Marketing Landing Page', '4 months', 'reactjs', '13.png', false, false, [['BT', 'h-yellow'], ['31.jpg']]],
            ['Startup Landing Page', '1 month', 'reactjs', '14.jpg', false, false, [['33.jpg'], ['26.jpg']]],
            ['Expense Management System', '1 month', 'reactjs', '15.png', false, false, [['21.jpg'], ['AT', 'success'], ['39.jpg']]],
        ];

        return collect($items)->map(function (array $item, int $index) use ($photo, $stack): array {
            return [
                'id' => $index + 1,
                'name' => $item[0],
                'remaining' => $item[1],
                'badge' => $stack($item[2]),
                'image' => $photo($item[3]),
                'lockable' => $item[4],
                'locked' => $item[5],
                'description' => 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Quo tandem modo? At certe gravius.',
                'team' => $this->flexContacts($item[6]),
            ];
        })->all();
    }

    private function gridCardPosts(): array
    {
        $photo = fn (int $number): string => '/vuero/images/layouts/grid-cards/posts/'.$number.'.jpg';
        $avatar = fn (string $file): string => str_contains($file, 'vuero-1.svg')
            ? '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg'
            : '/vuero/images/layouts/list-view-v1/avatars/'.$file;

        $items = [
            ['How to serve coffee at the office the proper way', '30 minutes ago', 11, '39.jpg', 'Alejandro B.'],
            ['Teamwork can dramatically increase productivity', '4 hours ago', 12, '15.jpg', 'Hilde V.'],
            ['Is team building a scam or the next thing?', '5 hours ago', 13, '24.jpg', 'Sandrine C.'],
            ['3 things you should know when applying for developer jobs', '8 hours ago', 14, '27.jpg', 'Carmen E.'],
            ['The construction business massively invests in mobile apps', '1 day ago', 15, '10.jpg', 'Henry G.'],
            ['Finding the right spot for your startup', '1 day ago', 17, '22.jpg', 'Jimmy H.'],
            ['4 tips to make your business lunches awesome', '2 days ago', 18, '22.jpg', 'Jimmy H.'],
            ['Setting up a design system for your app project', '2 days ago', 18, '26.jpg', 'Courtney W.'],
            ['Setting up a design system for your app project', '3 days ago', 20, 'vuero-1.svg', 'Erik K.'],
            ['Have you considered product management training?', '3 days ago', 21, '14.jpg', 'Ryan B.'],
            ['Using flashy colors in your websites and apps', '3 days ago', 22, '9.jpg', 'Ana B.'],
            ['Why it pays to profile your customers', '3 days ago', 23, '40.jpg', 'Jeanne M.'],
            ['Building a consistent and talented team', '3 days ago', 24, '19.jpg', 'Greta K.'],
            ['Building a consistent and talented team', '3 days ago', 25, '28.jpg', 'Edouard F.'],
            ['What framework to choose to build a mobile app in 2020?', '3 days ago', 26, '5.jpg', 'Mary L.'],
            ['Diving into building an e-commerce brand - part 1', '4 days ago', 27, '33.jpg', 'Harvey M.'],
            ['Diving into building an e-commerce brand - part 2', '4 days ago', 28, '33.jpg', 'Harvey M.'],
            ['How to make sure to reach the goals you set', '4 days ago', 29, '31.jpg', 'Yasseen A.'],
        ];

        return collect($items)->map(function (array $item, int $index) use ($photo, $avatar): array {
            return [
                'id' => $index + 1,
                'title' => $item[0],
                'published' => $item[1],
                'image' => $photo($item[2]),
                'author' => [
                    'name' => $item[4],
                    'avatar' => $avatar($item[3]),
                ],
            ];
        })->all();
    }

    private function tileGridUsers(): array
    {
        $avatar = fn (?string $file): ?string => match (true) {
            $file === null => null,
            str_contains($file, 'vuero-1.svg') => '/vuero/images/layouts/list-view-v1/avatars/vuero-1.svg',
            default => '/vuero/images/layouts/list-view-v1/avatars/'.basename($file),
        };

        $items = [
            ['Hakeem C.', 'Software Engineer', '29.jpg', 'python', 'info', null],
            ['Elisabeth F.', 'Mobile Developer', '21.jpg', 'england', 'success', null],
            ['Erik K.', 'Software Engineer', 'vuero-1.svg', 'united-states-of-america', 'purple', null],
            ['Alice C.', 'Software Engineer', '7.jpg', 'js', 'info', null],
            ['Betty T.', 'Product Manager', null, 'united-states-of-america', 'warning', 'BT'],
            ['Irina V.', 'Project Manager', '23.jpg', 'united-states-of-america', 'info', null],
            ['Helmut F.', 'Product Manager', '37.jpg', 'france', 'purple', null],
            ['Melany W.', 'Web Developer', '25.jpg', 'js', 'success', null],
            ['Alejandro B.', 'Business Analyst', '39.jpg', 'python', 'purple', null],
            ['Greta K.', 'Sales Manager', '19.jpg', 'germany', 'yellow', null],
            ['Anna B.', 'UI/UX Designer', '9.jpg', 'illustrator', 'info', null],
            ['Alan T.', 'Software Engineer', null, 'united-states-of-america', 'success', 'AT'],
            ['Sara C.', 'UI/UX Designer', null, 'united-states-of-america', 'purple', 'SC'],
            ['Jonathan K.', 'Product Manager', '32.jpg', 'united-states-of-america', 'info', null],
            ['Benoit B.', 'Software Engineer', '36.jpg', 'reactjs', 'purple', null],
            ['Christie B.', 'Web Developer', '38.jpg', 'vuejs', 'info', null],
            ['Courtney W.', 'Web Developer', '26.jpg', 'html5', 'info', null],
            ['Jason G.', 'Business Analyst', '16.jpg', 'united-states-of-america', 'danger', null],
            ['Edouard F.', 'Web Developer', '28.jpg', 'angular', 'success', null],
            ['Dwayne H.', 'Product Manager', '22.jpg', 'united-states-of-america', 'info', null],
            ['Tara S.', 'UI/UX Designer', '13.jpg', 'illustrator', 'danger', null],
            ['Sandrine C.', 'HR Manager', '24.jpg', 'france', 'info', null],
            ['Carmen E.', 'HR Manager', '27.jpg', 'spain', 'yellow', null],
            ['Kelly M.', 'HR Manager', '11.jpg', 'england', 'success', null],
            ['Hilde V.', 'Software Engineer', '15.jpg', 'germany', 'info', null],
            ['Ryan B.', 'Web Developer', '14.jpg', 'england', 'info', null],
            ['Henry G.', 'Business Analyst', '10.jpg', 'united-states-of-america', 'danger', null],
            ['Esteban C.', 'UI/UX Designer', '18.jpg', 'united-states-of-america', 'success', null],
        ];

        return collect($items)->map(fn (array $item): array => [
            'username' => $item[0],
            'position' => $item[1],
            'picture' => $avatar($item[2]),
            'initials' => $item[5],
            'color' => $item[4],
            'badge' => $this->gridCardBadge($item[3]),
        ])->all();
    }

    private function tileGridFiles(): array
    {
        $icon = fn (string $name): string => '/vuero/images/layouts/grid-tiles/files/'.$name.'.svg';

        $items = [
            ['Company UX Guide', '2 days ago', '4.7 MB', 'pdf'],
            ['Tech Summit Expenses', '1 week ago', '34 KB', 'sheet'],
            ['Project Outline', '2 weeks ago', '77 KB', 'doc-2'],
            ['UX Presentation', '4 days ago', '2.3 MB', 'ppt'],
            ['Website Homepage Redesign', '3 hours ago', '4.8 MB', 'ai'],
            ['UX Ramp Up for Interns', '6 hours ago', '1.8 MB', 'doc-2'],
            ['2020 Projects Digest', '2 days ago', '8.9 MB', 'pdf'],
            ['Financial Report - 2020', '2 months ago', '1.2 MB', 'doc-2'],
            ['2020 Supplier Expenses', '2 weeks ago', '250 KB', 'sheet'],
            ['Website About Page Redesign', '2 months ago', '3.9 MB', 'ai'],
            ['Website Pricing Page Redesign', '2 months ago', '2.6 MB', 'ai'],
            ['Financial Report - 2019', '8 months ago', '1.1 MB', 'doc-2'],
            ['2019 Supplier Expenses', '8 months ago', '250 KB', 'sheet'],
            ['Website Contact Page Redesign', '3 months ago', '5.8 MB', 'ai'],
            ['Company Brand Book', '3 weeks ago', '5.3 MB', 'pdf'],
            ['2019 Projects Digest', '9 months ago', '4.7 MB', 'pdf'],
        ];

        return collect($items)->map(fn (array $item, int $index): array => [
            'id' => $index + 1,
            'name' => $item[0],
            'updated' => $item[1],
            'size' => $item[2],
            'icon' => $icon($item[3]),
        ])->all();
    }
}
