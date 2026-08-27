<?php

return [
    'dashboards' => [
        'groups' => [
            'personal' => [
                'label' => 'Personal',
                'pages' => [
                    'personal-1' => 'Personal V1',
                    'personal-2' => 'Personal V2',
                    'personal-3' => 'Personal V3',
                ],
                'navigation' => [
                    'personal-1' => ['icon' => 'activity'],
                    'personal-2' => ['icon' => 'pie-chart'],
                    'personal-3' => ['icon' => 'trending-up'],
                ],
            ],
            'finance' => [
                'label' => 'Finance',
                'pages' => [
                    'analytics' => 'Analytics Dashboard',
                    'stocks' => 'Stock Dashboard',
                    'sales' => 'Sales Dashboard',
                ],
                'navigation' => [
                    'analytics' => ['icon' => 'activity'],
                    'stocks' => ['label' => 'Stocks Dashboard', 'icon' => 'trending-up'],
                    'sales' => ['icon' => 'credit-card'],
                ],
            ],
            'banking' => [
                'label' => 'Banking',
                'pages' => [
                    'banking-1' => 'Banking V1',
                    'banking-2' => 'Banking V2',
                    'banking-3' => 'Banking V3',
                ],
                'navigation' => [
                    'banking-1' => ['icon' => 'bank'],
                    'banking-2' => ['icon' => 'bank'],
                    'banking-3' => ['icon' => 'bank'],
                ],
            ],
            'business' => [
                'label' => 'Business',
                'pages' => [
                    'flights' => 'Flights Booking',
                    'company' => 'Company Board',
                    'human-resources' => 'HR Board',
                    'course' => 'Course Dashboard',
                    'jobs' => 'Jobs Dashboard',
                ],
                'navigation' => [
                    'flights' => ['icon' => 'plane'],
                    'company' => ['icon' => 'building'],
                    'human-resources' => ['icon' => 'users'],
                    'course' => ['icon' => 'graduation'],
                    'jobs' => ['icon' => 'briefcase'],
                ],
            ],
            'lifestyle' => [
                'label' => 'Lifestyle',
                'pages' => [
                    'influencer' => 'Influencer Dashboard',
                    'hobbies' => 'Hobbies',
                    'health' => 'Health',
                    'writer' => 'Writer',
                    'video' => 'Video',
                    'soccer' => 'Soccer Dashboard',
                ],
                'navigation' => [
                    'influencer' => ['label' => 'Influencer', 'icon' => 'heart'],
                    'hobbies' => ['icon' => 'cloud-sun'],
                    'health' => ['icon' => 'hospital'],
                    'writer' => ['icon' => 'book-open'],
                    'video' => ['icon' => 'video'],
                    'soccer' => ['icon' => 'shirt'],
                ],
            ],
            'ecommerce' => [
                'label' => 'Ecommerce',
                'pages' => [
                    'ecommerce-1' => 'Ecommerce V1',
                ],
                'navigation' => [
                    'ecommerce-1' => ['icon' => 'cart'],
                ],
            ],
            'apps' => [
                'label' => 'Apps',
                'pages' => [
                    'food-delivery' => 'Food Delivery',
                    'maps-1' => 'Maps V1',
                    'maps-2' => 'Maps V2',
                    'inbox' => 'Inbox',
                    'messaging-v1' => 'Messaging V1',
                    'messaging-v2' => 'Messaging V2',
                ],
                'navigation' => [
                    'food-delivery' => ['icon' => 'pizza'],
                    'maps-1' => ['icon' => 'map', 'tag' => 'v1.2'],
                    'maps-2' => ['icon' => 'map', 'tag' => 'v1.2'],
                    'inbox' => ['icon' => 'mail'],
                    'messaging-v1' => ['icon' => 'message'],
                    'messaging-v2' => ['icon' => 'message'],
                ],
            ],
            'charts' => [
                'label' => 'Charts',
                'pages' => [
                    'charts-apex' => 'Charts Apex',
                    'charts-billboardjs' => 'Charts BillboardJS',
                ],
                'navigation' => [
                    'charts-apex' => ['label' => 'Apex Charts', 'icon' => 'pie-chart'],
                    'charts-billboardjs' => ['label' => 'Billboard JS', 'icon' => 'chart-bar'],
                ],
            ],
            'widgets' => [
                'label' => 'Widgets',
                'pages' => [
                    'widgets-sample-ui' => 'Widgets UI',
                    'widgets-sample-creative' => 'Widgets Creative',
                    'widgets-sample-list' => 'Widgets List',
                    'widgets-sample-stats' => 'Widgets Stats',
                ],
                'navigation' => [
                    'widgets-sample-ui' => ['label' => 'UI Widgets', 'icon' => 'layout'],
                    'widgets-sample-creative' => ['label' => 'Creative Widgets', 'icon' => 'layout'],
                    'widgets-sample-list' => ['label' => 'List Widgets', 'icon' => 'layout'],
                    'widgets-sample-stats' => ['label' => 'Stat Widgets', 'icon' => 'layout'],
                ],
            ],
        ],
    ],
    'starters' => [
        'sidebar' => [
            'label' => 'Sidebar',
            'pages' => [
                'sidebar-blank-page-1' => ['title' => 'Blank Page 1', 'menu' => 'Regular Sidebar', 'layout' => 'sidebar-default', 'reference' => 'regular-sidebar'],
                'sidebar-blank-page-2' => ['title' => 'Blank Page 2', 'menu' => 'Curved Sidebar', 'layout' => 'sidebar-curved', 'reference' => 'curved-sidebar'],
                'sidebar-blank-page-3' => ['title' => 'Blank Page 3', 'menu' => 'Colored Sidebar', 'layout' => 'sidebar-color', 'reference' => 'colored-sidebar'],
                'sidebar-blank-page-4' => ['title' => 'Blank Page 4', 'menu' => 'Curved Colored Sidebar', 'layout' => 'sidebar-color-curved', 'reference' => 'curved-colored-sidebar'],
                'sidebar-blank-page-5' => ['title' => 'Blank Page 5', 'menu' => 'Labels Sidebar', 'layout' => 'sidebar-labels', 'reference' => 'sidebar-labels'],
                'sidebar-blank-page-6' => ['title' => 'Blank Page 6', 'menu' => 'Labels Hover Sidebar', 'layout' => 'sidebar-labels-hover', 'reference' => 'hover-labels'],
                'sidebar-blank-page-7' => ['title' => 'Blank Page 7', 'menu' => 'Float Sidebar', 'layout' => 'sidebar-float', 'reference' => 'floating-sidebar'],
            ],
        ],
        'sideblock' => [
            'label' => 'Sideblock',
            'pages' => [
                'sideblock-blank-page-1' => ['title' => 'Blank Page 1', 'menu' => 'Regular Sideblock', 'layout' => 'sideblock-default', 'reference' => 'regular-sideblock', 'open' => true],
                'sideblock-blank-page-2' => ['title' => 'Blank Page 2', 'menu' => 'Curved Sideblock', 'layout' => 'sideblock-curved', 'reference' => 'curved-sideblock', 'open' => true],
                'sideblock-blank-page-3' => ['title' => 'Blank Page 3', 'menu' => 'Colored Sideblock', 'layout' => 'sideblock-color', 'reference' => 'colored-sideblock', 'open' => true],
                'sideblock-blank-page-4' => ['title' => 'Blank Page 4', 'menu' => 'Curved Colored', 'layout' => 'sideblock-color-curved', 'reference' => 'curved-colored-sideblock', 'open' => true],
            ],
        ],
        'navbar' => [
            'label' => 'Navbar',
            'pages' => [
                'navbar-blank-page-1' => ['title' => 'Blank Page 1', 'menu' => 'Regular Navbar', 'layout' => 'navbar-default', 'reference' => 'standard-navbar'],
                'navbar-blank-page-2' => ['title' => 'Blank Page 2', 'menu' => 'Fading Navbar', 'layout' => 'navbar-fade', 'reference' => 'fading-navbar'],
                'navbar-blank-page-3' => ['title' => 'Blank Page 3', 'menu' => 'Colored Navbar', 'layout' => 'navbar-colored', 'reference' => 'colored-navbar'],
                'navbar-blank-page-4' => ['title' => 'Blank Page 4', 'menu' => 'Dropdown Navbar', 'layout' => 'navbar-dropdown', 'reference' => 'dropdown-navbar'],
                'navbar-blank-page-5' => ['title' => 'Blank Page 5', 'menu' => 'Colored Dropdown', 'layout' => 'navbar-dropdown-colored', 'reference' => 'colored-dropdown-navbar'],
                'navbar-blank-page-6' => ['title' => 'Blank Page 6', 'menu' => 'Clean Navbar', 'layout' => 'navbar-clean', 'reference' => 'clean-navbar'],
                'navbar-blank-page-7' => ['title' => 'Blank Page 7', 'menu' => 'Clean Center Navbar', 'layout' => 'navbar-clean-center', 'reference' => 'clean-centered-navbar'],
                'navbar-blank-page-8' => ['title' => 'Blank Page 8', 'menu' => 'Clean Fade Navbar', 'layout' => 'navbar-clean-fade', 'reference' => 'clean-transparent'],
            ],
        ],
    ],
    'wizard' => [
        'steps' => [
            1 => 'Project Type',
            2 => 'Project Info',
            3 => 'Project Details',
            4 => 'Project Files',
            5 => 'Team Members',
            6 => 'Project Tools',
            7 => 'Preview',
            8 => 'Finish',
        ],
        'customers' => [
            ['name' => 'Airbnb', 'location' => 'Los Angeles, CA', 'image' => 'airbnb.svg'],
            ['name' => 'Facebook', 'location' => 'Los Angeles, CA', 'image' => 'facebook.svg'],
            ['name' => 'Dribbble', 'location' => 'Los Angeles, CA', 'image' => 'dribbble.svg'],
            ['name' => 'Figma', 'location' => 'Los Angeles, CA', 'image' => 'figma-brand.svg'],
            ['name' => 'Google', 'location' => 'Los Angeles, CA', 'image' => 'google.svg'],
            ['name' => 'Github', 'location' => 'Los Angeles, CA', 'image' => 'github.svg'],
            ['name' => 'Gitlab', 'location' => 'Los Angeles, CA', 'image' => 'gitlab.svg'],
            ['name' => 'Hubspot', 'location' => 'Los Angeles, CA', 'image' => 'hubspot-brand.svg'],
            ['name' => 'Slack', 'location' => 'Los Angeles, CA', 'image' => 'slack.svg'],
            ['name' => 'Udemy', 'location' => 'Los Angeles, CA', 'image' => 'udemy.svg'],
        ],
        'teammates' => [
            ['name' => 'Yasseene Amzi', 'image' => '31.svg'],
            ['name' => 'Mary Lebowski', 'image' => '5.svg'],
            ['name' => 'Jonathan Krugger', 'image' => '32.svg'],
            ['name' => 'Elizabeth Fisher', 'image' => '21.svg'],
            ['name' => 'Tara Svenson', 'image' => '21.svg'],
        ],
        'tools' => [
            ['name' => 'Illustrator', 'description' => 'Design Software', 'image' => 'illustrator-brand.svg'],
            ['name' => 'Photoshop', 'description' => 'Design Software', 'image' => 'photoshop-brand.svg'],
            ['name' => 'Adobe XD', 'description' => 'Design Software', 'image' => 'xd-brand.svg'],
            ['name' => 'Figma', 'description' => 'Design Software', 'image' => 'xd-brand.svg'],
            ['name' => 'Invision', 'description' => 'Design Software', 'image' => 'invision-brand.svg'],
            ['name' => 'Jira', 'description' => 'Issue Tracker', 'image' => 'jira-brand.svg'],
            ['name' => 'Taiga', 'description' => 'Scrumboard', 'image' => 'taiga-brand.svg'],
            ['name' => 'Slack', 'description' => 'Messaging App', 'image' => 'slack.svg'],
            ['name' => 'Asana', 'description' => 'Task Manager', 'image' => 'asana-brand.svg'],
            ['name' => 'Teamwork', 'description' => 'Collaborative App', 'image' => 'teamwork-brand.svg'],
            ['name' => 'GitHub', 'description' => 'Code Repository', 'image' => 'github.svg'],
            ['name' => 'Gitlab', 'description' => 'Code Repository', 'image' => 'gitlab.svg'],
        ],
    ],
];
