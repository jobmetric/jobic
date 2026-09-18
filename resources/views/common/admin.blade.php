@extends('common.base')
@section('language', app()->getLocale())
@section('direction', \App\Support\LocaleCatalog::direction())
@section('body-class', 'cms-page--admin cms-page--default')
@section('body')
    <div class="admin-shell" data-admin-shell data-cms-shell data-initial-layout="{{ ($adminPreferences ?? config('admin.appearance'))['layout'] }}" data-preferences="{{ json_encode($adminPreferences ?? config('admin.appearance')) }}" data-preferences-url="{{ route('admin.settings.appearance.update') }}" data-save-failed="{{ __('admin.save_failed') }}" data-saved="{{ __('admin.saved') }}">
        @section('navigation')
            @include('common.navigation')
        @show
        @include('common.desktop-navbar')
        @include('common.panels.index')
        <div class="admin-shell__main">
            @section('header')
                @include('common.header')
            @show
            @cmsHook('jobic.admin.header.after')
            <main class="admin-content">
                @cmsHook('jobic.admin.content.before')
                @yield('content')
                @cmsHook('jobic.admin.content.after')
            </main>
        </div>
        <button class="admin-shell__overlay" type="button" tabindex="-1" data-sidebar-close aria-label="{{ __('admin.control_close_navigation') }}"></button>
    </div>
@endsection

@push('scripts')
    @vite('resources/js/admin/shell.js', 'vuero/build')
@endpush
