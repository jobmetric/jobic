<?php

namespace App\Http\Controllers\Vuero\Admin;

use App\Http\Controllers\Controller;
use Illuminate\View\View;

class LayoutController extends Controller
{
    public function show(string $layout): View
    {
        abort_unless(in_array($layout, ['list-view-v1', 'list-view-v2', 'list-view-v3'], true), 404);

        return view("vuero.admin.layouts.{$layout}", match ($layout) {
            'list-view-v1' => ['users' => $this->users(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-view-v1.js'],
            'list-view-v2' => ['properties' => $this->properties(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-view-v2.js'],
            'list-view-v3' => ['jobs' => $this->jobs(), 'layoutScript' => 'resources/js/vuero/pages/admin/layouts/list-view-v3.js'],
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
}
