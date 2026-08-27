@props(['starter' => null])

@php
    $dashboardActive = request()->routeIs('vuero.admin.dashboard', 'vuero.admin.dashboards.*', 'vuero.admin.wizard', 'vuero.admin.starters.*');
    $layoutsActive = request()->routeIs('vuero.admin.layouts.*');
    $settingsActive = request()->routeIs('vuero.admin.profile-settings');
    $sections = [
        'dashboard' => ['label' => 'Dashboards', 'icon' => 'activity', 'active' => $dashboardActive],
        'layouts' => ['label' => 'Layouts', 'icon' => 'apps', 'active' => $layoutsActive || $settingsActive],
        'elements' => ['label' => 'Elements', 'icon' => 'box', 'active' => false],
        'components' => ['label' => 'Components', 'icon' => 'cpu', 'active' => false],
        'messaging' => ['label' => 'Messaging', 'icon' => 'message', 'active' => false],
    ];

    $dashboardMenus = collect(config('vuero.dashboards.groups'))->map(function (array $group): array {
        return [
            'label' => $group['label'],
            'children' => collect($group['pages'])->map(function (string $label, string $slug) use ($group): array {
                $isAnalyticsHome = $slug === 'analytics' && request()->routeIs('vuero.admin.dashboard');
                $navigation = $group['navigation'][$slug] ?? [];

                return [
                    'label' => $navigation['label'] ?? $label,
                    'url' => $isAnalyticsHome ? route('vuero.admin.dashboard') : route('vuero.admin.dashboards.show', $slug),
                    'active' => $isAnalyticsHome || request()->routeIs('vuero.admin.dashboards.show') && request()->route('dashboard') === $slug,
                    'icon' => $navigation['icon'] ?? 'activity',
                    'tag' => $navigation['tag'] ?? null,
                ];
            })->values()->all(),
        ];
    });

    $starterChildren = [];

    foreach (config('vuero.starters') as $family => $group) {
        if ($starterChildren !== []) {
            $starterChildren[] = ['type' => 'divider'];
        }

        foreach ($group['pages'] as $slug => $page) {
            $starterChildren[] = [
                'label' => $page['menu'],
                'url' => route('vuero.admin.starters.show', $slug),
                'active' => request()->routeIs('vuero.admin.starters.show') && request()->route('starter') === $slug,
                'icon' => str_starts_with($slug, 'navbar-') ? 'layout-navbar' : 'layout',
                'tag' => str_starts_with($slug, 'sideblock-') ? 'v2.2' : null,
            ];
        }
    }

    $starterMenu = [
        'label' => 'Starters',
        'active' => request()->routeIs('vuero.admin.starters.show'),
        'children' => $starterChildren,
    ];

    $menus = [
        'dashboard' => [
            ...$dashboardMenus->only(['personal', 'finance', 'banking', 'business', 'lifestyle', 'ecommerce', 'apps'])->values()->all(),
            ['type' => 'divider'],
            ['label' => 'Wizard', 'url' => route('vuero.admin.wizard'), 'active' => request()->routeIs('vuero.admin.wizard')],
            ['type' => 'divider'],
            $dashboardMenus->get('charts'),
            $dashboardMenus->get('widgets'),
            $starterMenu,
        ],
        'layouts' => [
            ['label' => 'Lists', 'open' => $layoutsActive, 'children' => [
                ['label' => 'List View V1', 'url' => route('vuero.admin.layouts.show', 'list-view-v1'), 'active' => request()->routeIs('vuero.admin.layouts.show') && request()->route('layout') === 'list-view-v1', 'icon' => 'layout'],
                ['label' => 'List View V2', 'url' => route('vuero.admin.layouts.show', 'list-view-v2'), 'active' => request()->routeIs('vuero.admin.layouts.show') && request()->route('layout') === 'list-view-v2', 'icon' => 'layout'],
                ['label' => 'List View V3', 'url' => route('vuero.admin.layouts.show', 'list-view-v3'), 'active' => request()->routeIs('vuero.admin.layouts.show') && request()->route('layout') === 'list-view-v3', 'icon' => 'layout'],
                ['label' => 'List View V4', 'url' => route('vuero.admin.layouts.show', 'list-view-v4'), 'active' => request()->routeIs('vuero.admin.layouts.show') && request()->route('layout') === 'list-view-v4', 'icon' => 'layout'],
            ]],
            ['label' => 'Flex Lists', 'open' => request()->routeIs('vuero.admin.layouts.show') && in_array(request()->route('layout'), ['flex-list-v1', 'flex-list-v2', 'flex-list-v3'], true), 'children' => [
                ['label' => 'Flex List V1', 'url' => route('vuero.admin.layouts.show', 'flex-list-v1'), 'active' => request()->routeIs('vuero.admin.layouts.show') && request()->route('layout') === 'flex-list-v1', 'icon' => 'layout'],
                ['label' => 'Flex List V2', 'url' => route('vuero.admin.layouts.show', 'flex-list-v2'), 'active' => request()->routeIs('vuero.admin.layouts.show') && request()->route('layout') === 'flex-list-v2', 'icon' => 'layout'],
                ['label' => 'Flex List V3', 'url' => route('vuero.admin.layouts.show', 'flex-list-v3'), 'active' => request()->routeIs('vuero.admin.layouts.show') && request()->route('layout') === 'flex-list-v3', 'icon' => 'layout'],
            ]],
            ['label' => 'Grids', 'children' => [['label' => 'Cards Grid'], ['label' => 'Tiles Grid'], ['label' => 'Users Grid']]],
            ['label' => 'Profiles', 'children' => [['label' => 'Profile View'], ['label' => 'Profile Settings', 'url' => route('vuero.admin.profile-settings'), 'active' => $settingsActive]]],
            ['label' => 'Search', 'children' => [['label' => 'Search Results'], ['label' => 'Empty Search']]],
            ['label' => 'Utility', 'children' => [['label' => 'Invoice'], ['label' => 'Pricing'], ['label' => 'FAQ']]],
        ],
        'elements' => [
            ['label' => 'Forms', 'children' => [['label' => 'Inputs'], ['label' => 'Selects'], ['label' => 'Checkboxes']]],
            ['label' => 'Buttons', 'children' => [['label' => 'Regular'], ['label' => 'Rounded'], ['label' => 'Loading']]],
            ['label' => 'Typography', 'url' => '#', 'chevron' => true],
            ['label' => 'Colors', 'url' => '#', 'chevron' => true],
        ],
        'components' => [
            ['label' => 'Navigation', 'children' => [['label' => 'Dropdowns'], ['label' => 'Tabs'], ['label' => 'Breadcrumbs']]],
            ['label' => 'Data Display', 'children' => [['label' => 'Cards'], ['label' => 'Tables'], ['label' => 'Tags']]],
            ['label' => 'Feedback', 'children' => [['label' => 'Modals'], ['label' => 'Messages'], ['label' => 'Progress']]],
        ],
        'messaging' => [
            ['label' => 'Mailbox', 'children' => [['label' => 'Inbox'], ['label' => 'Sent'], ['label' => 'Archive']]],
            ['label' => 'Conversations', 'children' => [['label' => 'Direct Messages'], ['label' => 'Team Channels']]],
            ['label' => 'Contacts', 'url' => '#', 'chevron' => true],
        ],
    ];
@endphp

<aside class="admin-sidebar" id="admin-mobile-navigation" aria-label="Main navigation" data-mobile-navigation>
    <a class="admin-sidebar__brand" href="{{ route('vuero.admin.dashboard') }}" aria-label="Jobic"><x-vuero.brand compact /></a>

    <nav class="admin-sidebar__nav">
        @foreach ($sections as $id => $section)
            <button class="admin-sidebar__link {{ $section['active'] ? 'is-active' : '' }}" type="button" aria-label="{{ $section['label'] }}" aria-controls="sidebar-panel-{{ $id }}" aria-expanded="false" data-sidebar-panel-target="{{ $id }}">
                <x-vuero.icon :name="$section['icon']" size="21" />
                <span>{{ $section['label'] }}</span>
            </button>
        @endforeach
    </nav>

    <div class="admin-sidebar__footer">
        <button class="admin-sidebar__link admin-sidebar__layout-switcher" type="button" aria-label="Select Layout" aria-controls="layout-switcher" aria-expanded="false" data-modal-open="layout-switcher"><x-vuero.icon name="panel" size="20" /><span>Select Layout</span></button>
        <button class="admin-sidebar__link" type="button" aria-label="Search" aria-controls="search-panel" aria-expanded="false" data-panel-open="search"><x-vuero.icon name="search" size="21" /><span>Search</span></button>
        <a class="admin-sidebar__link {{ $settingsActive ? 'is-active' : '' }}" href="{{ route('vuero.admin.profile-settings') }}" aria-label="Profile Settings" @if ($settingsActive) aria-current="page" @endif><x-vuero.icon name="settings" size="21" /><span>Profile Settings</span></a>

        <x-vuero.navigation.profile-dropdown />
    </div>
</aside>

<aside class="admin-subsidebar" id="admin-subsidebar" aria-label="Secondary navigation" aria-hidden="true" data-subsidebar>
    <header class="admin-subsidebar__header"><span class="project-avatar">H</span><h2 data-subsidebar-title>Dashboards</h2><button type="button" data-subsidebar-close aria-label="Close secondary navigation">&times;</button></header>
    <div class="admin-subsidebar__body">
        @foreach ($menus as $id => $items)
            <section id="sidebar-panel-{{ $id }}" data-sidebar-panel="{{ $id }}" hidden>
                <x-vuero.navigation.menu :items="$items" />
            </section>
        @endforeach
    </div>
</aside>

<aside class="admin-sideblock" id="admin-sideblock" aria-label="Sideblock navigation" aria-hidden="true" data-sideblock inert>
    <header class="admin-sideblock__header">
        <a href="{{ route('vuero.admin.dashboard') }}" aria-label="Jobic"><x-vuero.brand compact /></a>
        <h2>Vuero</h2>
    </header>

    @if ($starter)
        <nav class="admin-sideblock__nav starter-sideblock__nav" aria-label="Starter navigation">
            @foreach ([
                ['dashboard', 'Dashboard', 'apps', route('vuero.admin.dashboard')],
                ['projects', 'Projects', 'briefcase', '#'],
                ['messages', 'Messages', 'message', '#', 3],
                ['collections', 'Collections', 'box', '#'],
                ['users', 'Users', 'users', '#'],
                ['reports', 'Reports', 'briefcase', '#', null, true],
                ['inbox', 'Inbox', 'mail', '#'],
                ['settings', 'Settings', 'settings', '#', null, true, true],
                ['starters', 'Starters', 'energy', route('vuero.admin.starters.show', 'sideblock-blank-page-1'), null, true],
            ] as $item)
                <a class="starter-sideblock__link {{ ($item[6] ?? false) ? 'has-separator' : '' }}" href="{{ $item[3] }}" @if ($item[3] === '#') data-ui-message="{{ $item[1] }} navigation selected." @endif>
                    <x-vuero.icon :name="$item[2]" size="17" /><span>{{ $item[1] }}</span>
                    @if ($item[4] ?? null)<b>{{ $item[4] }}</b>@endif
                    @if ($item[5] ?? false)<x-vuero.icon name="chevron-right" size="14" />@endif
                </a>
            @endforeach
        </nav>
        <footer class="admin-sideblock__footer starter-sideblock__footer">
            <x-vuero.navigation.profile-dropdown />
            <button class="admin-sideblock__action" type="button" aria-label="Search" aria-controls="search-panel" aria-expanded="false" data-panel-open="search"><x-vuero.icon name="search" size="19" /><span>Search</span></button>
        </footer>
    @else
        <nav class="admin-sideblock__nav">
            @foreach ($sections as $id => $section)
                <section class="admin-sideblock__group {{ $section['active'] ? 'is-open' : '' }}" data-sideblock-group>
                    <button type="button" aria-expanded="{{ $section['active'] ? 'true' : 'false' }}" aria-controls="sideblock-panel-{{ $id }}" data-sideblock-group-toggle>
                        <span class="admin-sideblock__group-icon"><x-vuero.icon :name="$section['icon']" size="20" /></span>
                        <span>{{ $section['label'] }}</span>
                        <x-vuero.icon name="chevron-right" size="17" />
                    </button>
                    <div class="admin-sideblock__children" id="sideblock-panel-{{ $id }}" @if (! $section['active']) hidden @endif>
                        <x-vuero.navigation.menu :items="$menus[$id]" />
                    </div>
                </section>
            @endforeach
        </nav>

        <footer class="admin-sideblock__footer">
            <button class="admin-sideblock__action" type="button" aria-label="Select Layout" aria-controls="layout-switcher" aria-expanded="false" data-modal-open="layout-switcher"><x-vuero.icon name="panel" size="19" /><span>Select Layout</span></button>
            <button class="admin-sideblock__action" type="button" aria-label="Search" aria-controls="search-panel" aria-expanded="false" data-panel-open="search"><x-vuero.icon name="search" size="19" /><span>Search</span></button>
            <a class="admin-sideblock__action {{ $settingsActive ? 'is-active' : '' }}" href="{{ route('vuero.admin.profile-settings') }}" aria-label="Profile Settings"><x-vuero.icon name="settings" size="19" /><span>Profile Settings</span></a>
            <x-vuero.navigation.profile-dropdown />
        </footer>
    @endif
</aside>
