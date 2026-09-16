<?php

return [
    'back'    => 'Back to sign in',
    'privacy' => [
        'title'    => 'Privacy policy',
        'intro'    => 'How the core sign-in system handles your information.',
        'sections' => [
            [
                'title' => 'Information processed',
                'text'  => 'The system processes your email address and password to authenticate you. Account passwords are stored as hashes. Sign-in events record the email address, account identifier when available, IP address, browser user agent, time and whether the attempt succeeded.',
            ],
            [
                'title' => 'Why this information is used',
                'text'  => 'Account information enables sign-in and account recovery. Sign-in records help the website operator investigate access problems and suspicious activity. Session information keeps your authenticated requests connected.',
            ],
            [
                'title' => 'Retention of sign-in records',
                'text'  => 'The core system currently has no scheduled automatic deletion of sign-in events. The website operator must define and apply a retention policy for these records, server logs and backups.',
            ],
            [
                'title' => 'Account information',
                'text'  => 'Account information remains stored until the operator removes it through the applicable account-management process. Removing an account does not necessarily remove separate logs or backups; their retention is managed independently.',
            ],
            [
                'title' => 'Access and service providers',
                'text'  => 'Authorized administrators and infrastructure providers may process information where needed to operate the site. Hosting, email delivery and installed extensions can introduce additional processing outside this core sign-in system.',
            ],
            [
                'title' => 'Your choices',
                'text'  => 'Contact the website operator to ask about access, corrections or deletion of your information. Do not send your password when making a request. You can sign out and clear browser cookies on shared devices.',
            ],
            [
                'title' => 'Scope of this notice',
                'text'  => 'This notice describes the core CMS authentication features. The website operator is responsible for explaining any additional services, extensions, local requirements and contact channels used on this site.',
            ],
        ],
    ],
    'cookies' => [
        'title'    => 'Cookie policy',
        'intro'    => 'What the core sign-in system stores in your browser and why.',
        'sections' => [
            [
                'title' => 'Essential session cookies',
                'text'  => 'Session cookies link requests to your current session. A CSRF token helps protect forms against forged requests. These mechanisms support authentication and cannot be disabled without affecting sign-in and form submission.',
            ],
            [
                'title' => 'Session lifetime',
                'text'  => 'The configured inactivity lifetime is :minutes minutes. Closing the browser may also end the session when the site’s session settings require it. Server-side session validity and browser cookie storage are separate concerns.',
            ],
            [
                'title' => 'Remember me',
                'text'  => 'If you select “Keep me signed in”, the authentication cookie is configured for :days days. This duration comes from the same configuration used by sign-in. Signing out or invalidating the account’s authentication token can end this access earlier.',
            ],
            [
                'title' => 'Language and appearance',
                'text'  => 'The selected language is kept in the session. Appearance preferences may use browser storage to restore your chosen theme. Browser storage is separate from cookies and can also be cleared through browser settings.',
            ],
            [
                'title' => 'Analytics and extensions',
                'text'  => 'The core sign-in pages do not add advertising or analytics cookies. Services and extensions installed by the website operator may behave differently and should be disclosed separately.',
            ],
            [
                'title' => 'Managing browser storage',
                'text'  => 'You can inspect, block or delete cookies in your browser settings. Deleting essential cookies can sign you out and reset preferences. On a shared computer, sign out when finished and avoid using the remember-me option.',
            ],
        ],
    ],
];
