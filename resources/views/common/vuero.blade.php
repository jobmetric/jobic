@extends('common.base')
@section('language', 'en')
@section('direction', 'ltr')
@section('body-class', 'cms-page--admin cms-page--'.$chrome.($starter ? ' cms-page--starter' : ''))
@section('body')
    <div
        class="admin-shell"
        data-admin-shell
        @if ($layout) data-initial-layout="{{ $layout }}" @endif
        @if ($openNavigation) data-open-navigation="true" @endif
        @if ($starter) data-starter-shell="{{ $starter['slug'] }}" @endif
    >
        @if ($chrome !== 'immersive')
            <x-vuero.navigation.mobile-navbar />
            <x-vuero.navigation.sidebar :starter="$starter" />
        @endif
        @if ($chrome === 'default')
            <x-vuero.navigation.desktop-navbar :title="$title" />
        @endif
        <x-vuero.navigation.circular-menu />
        <x-vuero.panels.activity />
        <x-vuero.panels.languages />
        <x-vuero.panels.search />
        <x-vuero.modals.layout-switcher />

        <div class="admin-shell__main">
            @if ($chrome === 'default')
                <x-vuero.navigation.topbar :title="$title" />
            @endif

            <main class="admin-content">
                @yield('content')
            </main>
        </div>

        <button class="admin-shell__overlay" type="button" tabindex="-1" data-sidebar-close aria-label="{{ __('admin.control_close_navigation') }}"></button>
    </div>
@endsection
