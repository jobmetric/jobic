<?php

return [
    // Used only before the language table exists; installation cannot require a database.
    'bootstrap' => [
        'en' => [
            'name'      => 'English',
            'locale'    => 'en',
            'direction' => 'ltr',
            'flag'      => 'us',
            'calendar'  => 'gregorian',
        ],
        'fa' => [
            'name'      => 'فارسی',
            'locale'    => 'fa',
            'direction' => 'rtl',
            'flag'      => 'ir',
            'calendar'  => 'jalali',
        ],
    ],
    'fallback'  => 'en',
];
