@php
    $adminAccess = app(\App\Support\AdminAccess::class);
    $adminUser = auth()->user();
    $canDashboard = $adminAccess->allows($adminUser, 'admin.dashboard');
    $canUsers = $adminAccess->allowsAny($adminUser, ['admin.users.view', 'admin.users.manage']);
    $canRoles = $adminAccess->allowsAny($adminUser, ['admin.roles.view', 'admin.roles.manage']);
    $canMenus = $adminAccess->allowsAny($adminUser, ['admin.menus.view', 'admin.menus.manage']);
    $taxonomyMenuItems = collect(\JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::values())
        ->reject(fn ($type) => $type === 'menu')
        ->map(fn ($type) => ['label' => \JobMetric\Taxonomy\Facades\TaxonomyTypeRegistry::for($type)->getLabel(), 'icon' => 'list-tree', 'url' => route('admin.taxonomies.index', $type), 'active' => request()->routeIs('admin.taxonomies.*') && request()->route('type') === $type])
        ->values()->all();
    $canSettings = !empty($settingsSections);
    $userMenuItems = array_values(array_filter([
        $canUsers ? ['label' => __('users.title'), 'icon' => 'users', 'url' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')] : null,
        $canRoles ? ['label' => __('roles.title'), 'icon' => 'shield', 'url' => route('admin.roles.index'), 'active' => request()->routeIs('admin.roles.*')] : null,
    ]));
    $contentMenuItems = array_values(array_filter([
        $canMenus ? ['label' => __('menus.title'), 'icon' => 'menu', 'url' => route('admin.menus.index'), 'active' => request()->routeIs('admin.menus.*')] : null,
        ...$taxonomyMenuItems,
    ]));
    $sections = array_filter([
        'dashboard' => $canDashboard ? ['label' => __('installation.dashboard'), 'icon' => 'activity', 'active' => request()->routeIs('admin.dashboard')] : null,
        'users' => $userMenuItems ? ['label' => __('users.title'), 'icon' => 'users', 'active' => request()->routeIs('admin.users.*', 'admin.roles.*')] : null,
        'content' => $contentMenuItems ? ['label' => __('roles.permissions.content'), 'icon' => 'content', 'active' => request()->routeIs('admin.menus.*', 'admin.taxonomies.*')] : null,
        'settings' => $canSettings ? ['label' => __('installation.settings'), 'icon' => 'settings', 'active' => request()->routeIs('admin.settings.*')] : null,
    ]);
@endphp
<nav class="admin-mobile-navbar" aria-label="Mobile navigation">
    <div class="admin-mobile-navbar__inner">
        <button class="vuero-hamburger admin-mobile-navbar__burger" type="button" data-sidebar-toggle aria-label="{{ __('admin.control_open_navigation') }}" aria-controls="admin-mobile-navigation" aria-expanded="false"><span></span><span></span><span></span></button>
        <a class="admin-mobile-navbar__brand" href="{{ route('admin.dashboard') }}" aria-label="{{ config('app.name') }}"><x-brand compact /></a>
        <div class="admin-mobile-navbar__actions">@include('common.profile', ['placement' => 'mobile'])</div>
    </div>
</nav>
<aside class="admin-sidebar" id="admin-mobile-navigation" aria-label="{{ __('admin.control_main_navigation') }}" data-mobile-navigation>
    <a class="admin-sidebar__brand" href="{{ route('admin.dashboard') }}" aria-label="{{ config('app.name') }}"><x-brand compact /></a>
    <nav class="admin-sidebar__nav">
        @foreach ($sections as $id => $section)
            <button class="admin-sidebar__link {{ $section['active'] ? 'is-active' : '' }}" type="button" aria-label="{{ $section['label'] }}" aria-controls="sidebar-panel-{{ $id }}" aria-expanded="false" data-sidebar-panel-target="{{ $id }}">
                <x-vuero.icon :name="$section['icon']" size="21" /><span>{{ $section['label'] }}</span>
            </button>
        @endforeach
    </nav>
    <div class="admin-sidebar__footer">
        <button class="admin-sidebar__link" type="button" data-panel-open="search" aria-label="{{ __('admin.search') }}"><x-vuero.icon name="search" size="20" /><span>{{ __('admin.search') }}</span></button>
        @if($canSettings)<a class="admin-sidebar__link" href="{{ route('admin.settings.index') }}" aria-label="{{ __('admin.settings') }}"><x-vuero.icon name="settings" size="20" /><span>{{ __('admin.settings') }}</span></a>@endif
        @include('common.profile', ['placement' => 'sidebar'])
    </div>
</aside>
<aside class="admin-subsidebar" id="admin-subsidebar" aria-label="Secondary navigation" aria-hidden="true" data-subsidebar>
    <header class="admin-subsidebar__header"><h2 data-subsidebar-title>{{ config('app.name') }}</h2><button type="button" data-subsidebar-close aria-label="{{ __('admin.control_close_navigation') }}">&times;</button></header>
    <div class="admin-subsidebar__body">
        @if($canDashboard)<section id="sidebar-panel-dashboard" data-sidebar-panel="dashboard" hidden>
            <x-vuero.navigation.menu :items="[['label' => __('installation.dashboard'), 'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')]]" />
        </section>@endif
        @if($userMenuItems)<section id="sidebar-panel-users" data-sidebar-panel="users" hidden>
            <x-vuero.navigation.menu :items="$userMenuItems" />
        </section>@endif
        @if($contentMenuItems)<section id="sidebar-panel-content" data-sidebar-panel="content" hidden>
            <x-vuero.navigation.menu :items="$contentMenuItems" />
        </section>@endif
        @if($canSettings)<section id="sidebar-panel-settings" data-sidebar-panel="settings" hidden>
            @include('common.settings.menu')
        </section>@endif
    </div>
</aside>

<aside class="admin-sideblock" id="admin-sideblock" aria-label="{{ __('admin.control_navigation') }}" aria-hidden="true" data-sideblock inert>
    <header class="admin-sideblock__header"><a href="{{ route('admin.dashboard') }}" aria-label="Jobic"><x-brand compact /></a><h2>{{ config('app.name') }}</h2></header>
    <nav class="admin-sideblock__nav">
        @if($canDashboard)<a class="admin-sideblock__action" href="{{ route('admin.dashboard') }}"><x-vuero.icon name="activity" size="19" /><span>{{ __('admin.dashboard') }}</span></a>@endif
        @if($userMenuItems)
        <section class="admin-sideblock__group {{ request()->routeIs('admin.users.*', 'admin.roles.*') ? 'is-open' : '' }}" data-sideblock-group>
            <button type="button" aria-expanded="{{ request()->routeIs('admin.users.*', 'admin.roles.*') ? 'true' : 'false' }}" aria-controls="sideblock-panel-users" data-sideblock-group-toggle>
                <span class="admin-sideblock__group-icon"><x-vuero.icon name="users" size="20" /></span>
                <span>{{ __('users.title') }}</span>
                <x-vuero.icon name="chevron-right" size="17" />
            </button>
            <div class="admin-sideblock__children" id="sideblock-panel-users" @unless(request()->routeIs('admin.users.*', 'admin.roles.*')) hidden @endunless>
                <x-vuero.navigation.menu :items="$userMenuItems" />
            </div>
        </section>
        @endif
        @if($contentMenuItems)
        <section class="admin-sideblock__group {{ request()->routeIs('admin.menus.*', 'admin.taxonomies.*') ? 'is-open' : '' }}" data-sideblock-group>
            <button type="button" aria-expanded="{{ request()->routeIs('admin.menus.*') ? 'true' : 'false' }}" aria-controls="sideblock-panel-content" data-sideblock-group-toggle>
                <span class="admin-sideblock__group-icon"><x-vuero.icon name="content" size="20" /></span><span>{{ __('roles.permissions.content') }}</span><x-vuero.icon name="chevron-right" size="17" />
            </button>
            <div class="admin-sideblock__children" id="sideblock-panel-content" @unless(request()->routeIs('admin.menus.*', 'admin.taxonomies.*')) hidden @endunless><x-vuero.navigation.menu :items="$contentMenuItems" /></div>
        </section>
        @endif
        @if($canSettings)@include('common.settings.menu')@endif
    </nav>
    <footer class="admin-sideblock__footer">
        <button class="admin-sideblock__action" type="button" data-panel-open="search"><x-vuero.icon name="search" size="19" /><span>{{ __('admin.search') }}</span></button>
        @include('common.profile', ['placement' => 'sidebar'])
    </footer>
</aside>
