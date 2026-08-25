@php
    $dashboardActive = request()->routeIs('vuero.admin.dashboard', 'vuero.admin.dashboards.*');
    $settingsActive = request()->routeIs('vuero.admin.profile-settings');
    $sections = [
        'dashboard' => ['label' => 'Dashboards', 'icon' => 'activity', 'active' => $dashboardActive],
        'layouts' => ['label' => 'Layouts', 'icon' => 'apps', 'active' => $settingsActive],
        'elements' => ['label' => 'Elements', 'icon' => 'box', 'active' => false],
        'components' => ['label' => 'Components', 'icon' => 'cpu', 'active' => false],
        'messaging' => ['label' => 'Messaging', 'icon' => 'message', 'active' => false],
    ];

    $dashboardMenus = collect(config('vuero.dashboards.groups'))->map(function (array $group): array {
        return [
            'label' => $group['label'],
            'children' => collect($group['pages'])->map(function (string $label, string $slug): array {
                $isAnalyticsHome = $slug === 'analytics' && request()->routeIs('vuero.admin.dashboard');

                return [
                    'label' => $label,
                    'url' => $isAnalyticsHome ? route('vuero.admin.dashboard') : route('vuero.admin.dashboards.show', $slug),
                    'active' => $isAnalyticsHome || request()->routeIs('vuero.admin.dashboards.show') && request()->route('dashboard') === $slug,
                ];
            })->values()->all(),
        ];
    })->values()->all();

    $menus = [
        'dashboard' => [
            ...$dashboardMenus,
            ['label' => 'Wizard', 'url' => '#', 'separator' => true],
            ['label' => 'Charts', 'url' => '#', 'chevron' => true],
            ['label' => 'Widgets', 'url' => '#', 'chevron' => true],
            ['label' => 'Starters', 'url' => '#', 'chevron' => true],
        ],
        'layouts' => [
            ['label' => 'Lists', 'children' => [['label' => 'List View V1'], ['label' => 'List View V2'], ['label' => 'List View V3']]],
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
</aside>
