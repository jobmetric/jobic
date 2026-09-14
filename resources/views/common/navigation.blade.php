@php
    $sections = [
        'dashboard' => ['label' => __('installation.dashboard'), 'icon' => 'activity', 'active' => request()->routeIs('admin.dashboard')],
        'settings' => ['label' => __('installation.settings'), 'icon' => 'settings', 'active' => request()->routeIs('admin.settings.*')],
    ];
@endphp
<nav class="admin-mobile-navbar" aria-label="Mobile navigation">
    <div class="admin-mobile-navbar__inner">
        <button class="vuero-hamburger admin-mobile-navbar__burger" type="button" data-sidebar-toggle aria-label="Open navigation" aria-controls="admin-mobile-navigation" aria-expanded="false"><span></span><span></span><span></span></button>
        <a class="admin-mobile-navbar__brand" href="{{ route('admin.dashboard') }}" aria-label="{{ config('app.name') }}"><x-vuero.brand compact /></a>
        <span>{{ auth()->user()?->name }}</span>
    </div>
</nav>
<aside class="admin-sidebar" id="admin-mobile-navigation" aria-label="Main navigation" data-mobile-navigation>
    <a class="admin-sidebar__brand" href="{{ route('admin.dashboard') }}" aria-label="{{ config('app.name') }}"><x-vuero.brand compact /></a>
    <nav class="admin-sidebar__nav">
        @foreach ($sections as $id => $section)
            <button class="admin-sidebar__link {{ $section['active'] ? 'is-active' : '' }}" type="button" aria-label="{{ $section['label'] }}" aria-controls="sidebar-panel-{{ $id }}" aria-expanded="false" data-sidebar-panel-target="{{ $id }}">
                <x-vuero.icon :name="$section['icon']" size="21" /><span>{{ $section['label'] }}</span>
            </button>
        @endforeach
    </nav>
</aside>
<aside class="admin-subsidebar" id="admin-subsidebar" aria-label="Secondary navigation" aria-hidden="true" data-subsidebar>
    <header class="admin-subsidebar__header"><h2 data-subsidebar-title>{{ config('app.name') }}</h2><button type="button" data-subsidebar-close aria-label="Close navigation">&times;</button></header>
    <div class="admin-subsidebar__body">
        <section id="sidebar-panel-dashboard" data-sidebar-panel="dashboard" hidden>
            <x-vuero.navigation.menu :items="[['label' => __('installation.dashboard'), 'url' => route('admin.dashboard'), 'active' => request()->routeIs('admin.dashboard')]]" />
        </section>
        <section id="sidebar-panel-settings" data-sidebar-panel="settings" hidden>
            <x-vuero.navigation.menu :items="[['label' => __('installation.groups.mail'), 'url' => route('admin.settings.mail.edit'), 'active' => request()->routeIs('admin.settings.mail.*')]]" />
        </section>
    </div>
</aside>
