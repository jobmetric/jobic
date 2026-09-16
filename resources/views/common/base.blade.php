@extends('domi::layout')

@section('domi-head')
    @include('common.head')
    @stack('styles')
    @cmsHook('jobic.page.head.end')
@endsection

@section('body-attribute')class="cms-page @yield('body-class')"@endsection

@section('domi-content')
    <x-vuero.icon-sprite />
    @cmsHook('jobic.page.body.start')
    @yield('body')
    @yield('footer')
    @stack('overlays')
@endsection

@section('domi-script')
    @stack('scripts')
@endsection

@section('domi-script-bottom')
    @cmsHook('jobic.page.body.end')
@endsection
