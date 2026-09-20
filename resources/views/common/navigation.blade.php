@php
    $sections = [
        'dashboard' => ['label' => __('installation.dashboard'), 'icon' => 'activity', 'active' => request()->routeIs('admin.dashboard')],
        'users' => ['label' => __('users.title'), 'icon' => 'users', 'active' => request()->routeIs('admin.users.*')],
        'settings' => ['label' => __('installation.settings'), 'icon' => 'settings', 'active' => request()->routeIs('admin.settings.*')],
    ];
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
        <button class="admin-sidebar__link" type="button" data-modal-open="layout-switcher" aria-label="{{ __('admin.layout') }}"><x-vuero.icon name="panel" size="20" /><span>{{ __('admin.layout') }}</span></button>
        <button class="admin-sidebar__link" type="button" data-panel-open="search" aria-label="{{ __('admin.search') }}"><x-vuero.icon name="search" size="20" /><span>{{ __('admin.search') }}</span></button>
        <a class="admin-sidebar__link" href="{{ route('admin.settings.index') }}" aria-label="{{ __('admin.settings') }}"><x-vuero.icon name="settings" size="20" /><span>{{ __('admin.settings') }}</span></a>
        @include('common.profile', ['placement' => 'sidebar'])
    </div>
</aside>
<aside class="admin-subsidebar" id="admin-subsidebar" aria-label="Secondary navigation" aria-hidden="true" data-subsidebar>
    <header class="admin-subsidebar__header"><h2 data-subsidebar-title>{{ config('app.name') }}</h2><button type="button" data-subsidebar-close aria-label="{{ __('admin.control_close_navigation') }}">&times;</button></header>
    <div class="admin-subsidebar__body">
        <section id="sidebar-panel-dashboard" data-sidebar-panel="dashboard" hidden>
            <x-vuero.navigation.menu :items="[['label' => __('installation.dashboard'), 'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')]]" />
        </section>
        <section id="sidebar-panel-users" data-sidebar-panel="users" hidden>
            <x-vuero.navigation.menu :items="[['label' => __('users.title'), 'url' => route('admin.users.index'), 'active' => request()->routeIs('admin.users.*')]]" />
        </section>
        <section id="sidebar-panel-settings" data-sidebar-panel="settings" hidden>
            @include('common.settings.menu')
        </section>
    </div>
</aside>

<aside class="admin-sideblock" id="admin-sideblock" aria-label="{{ __('admin.control_navigation') }}" aria-hidden="true" data-sideblock inert>
    <header class="admin-sideblock__header"><a href="{{ route('admin.dashboard') }}" aria-label="Jobic"><x-brand compact /></a><h2>{{ config('app.name') }}</h2></header>
    <nav class="admin-sideblock__nav">
        <a class="admin-sideblock__action" href="{{ route('admin.dashboard') }}"><x-vuero.icon name="activity" size="19" /><span>{{ __('admin.dashboard') }}</span></a>
        <a class="admin-sideblock__action" href="{{ route('admin.users.index') }}"><x-vuero.icon name="users" size="19" /><span>{{ __('users.title') }}</span></a>
        @include('common.settings.menu')
    </nav>
    <footer class="admin-sideblock__footer">
        <button class="admin-sideblock__action" type="button" data-modal-open="layout-switcher"><x-vuero.icon name="panel" size="19" /><span>{{ __('admin.layout') }}</span></button>
        <button class="admin-sideblock__action" type="button" data-panel-open="search"><x-vuero.icon name="search" size="19" /><span>{{ __('admin.search') }}</span></button>
        @include('common.profile', ['placement' => 'sidebar'])
    </footer>
</aside>
