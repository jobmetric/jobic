@php
    $adminMenu = app(\App\Support\AdminMenuRegistry::class);
    $sections = $adminMenu->sections();
    $canSettings = ! empty($settingsSections);
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
    <nav class="admin-sidebar__nav">{!! $adminMenu->render() !!}</nav>
    <div class="admin-sidebar__footer">
        <button class="admin-sidebar__link" type="button" data-panel-open="search" aria-label="{{ __('admin.search') }}"><x-vuero.icon name="search" size="20" /><span>{{ __('admin.search') }}</span></button>
        @if($canSettings)<a class="admin-sidebar__link" href="{{ route('admin.settings.index') }}" aria-label="{{ __('admin.settings') }}"><x-vuero.icon name="settings" size="20" /><span>{{ __('admin.settings') }}</span></a>@endif
        @include('common.profile', ['placement' => 'sidebar'])
    </div>
</aside>
<aside class="admin-subsidebar" id="admin-subsidebar" aria-label="Secondary navigation" aria-hidden="true" data-subsidebar>
    <header class="admin-subsidebar__header"><h2 data-subsidebar-title>{{ config('app.name') }}</h2><button type="button" data-subsidebar-close aria-label="{{ __('admin.control_close_navigation') }}">&times;</button></header>
    <div class="admin-subsidebar__body">
        @foreach($sections as $section)
            <section id="sidebar-panel-{{ $section['key'] }}" data-sidebar-panel="{{ $section['key'] }}" hidden>
                {!! $adminMenu->render($section['key']) !!}
            </section>
        @endforeach
    </div>
</aside>

<aside class="admin-sideblock" id="admin-sideblock" aria-label="{{ __('admin.control_navigation') }}" aria-hidden="true" data-sideblock inert>
    <header class="admin-sideblock__header"><a href="{{ route('admin.dashboard') }}" aria-label="Jobic"><x-brand compact /></a><h2>{{ config('app.name') }}</h2></header>
    <nav class="admin-sideblock__nav">
        @foreach($sections as $section)
            @if($section['sideblock'] === 'link')
                @php($item = $section['items'][0] ?? null)
                @if($item)<a class="admin-sideblock__action {{ $item['active'] ? 'is-active' : '' }}" href="{{ $item['url'] ?? '#' }}"><x-vuero.icon :name="$section['icon']" size="19" /><span>{{ $section['label'] }}</span></a>@endif
            @else
                <section class="admin-sideblock__group {{ $section['active'] ? 'is-open' : '' }}" data-sideblock-group>
                    <button type="button" aria-expanded="{{ $section['active'] ? 'true' : 'false' }}" aria-controls="sideblock-panel-{{ $section['key'] }}" data-sideblock-group-toggle>
                        <span class="admin-sideblock__group-icon"><x-vuero.icon :name="$section['icon']" size="20" /></span><span>{{ $section['label'] }}</span><x-vuero.icon name="chevron-right" size="17" />
                    </button>
                    <div class="admin-sideblock__children" id="sideblock-panel-{{ $section['key'] }}" @unless($section['active']) hidden @endunless>{!! $adminMenu->render($section['key']) !!}</div>
                </section>
            @endif
        @endforeach
    </nav>
    <footer class="admin-sideblock__footer">
        <button class="admin-sideblock__action" type="button" data-panel-open="search"><x-vuero.icon name="search" size="19" /><span>{{ __('admin.search') }}</span></button>
        @include('common.profile', ['placement' => 'sidebar'])
    </footer>
</aside>
