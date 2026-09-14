@extends('common.base')
@section('language', 'en')
@section('direction', 'ltr')
@section('body-class', 'cms-page--admin cms-page--default')
@section('body')
    <div class="admin-shell" data-admin-shell data-initial-layout="sidebar-default">
        @section('navigation')
            @include('common.navigation')
        @show
        <div class="admin-shell__main">
            @section('header')
                @include('common.header')
            @show
            <main class="admin-content">
                @yield('content')
            </main>
        </div>
        <button class="admin-shell__overlay" type="button" tabindex="-1" data-sidebar-close aria-label="Close navigation"></button>
    </div>
@endsection
