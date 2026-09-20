@extends('domi::layout')

@unless($__env->hasSection('domi-head'))
@section('domi-head')
    @include('common.head')
    @stack('styles')
    @cmsHook('jobic.page.head.end')
@endsection
@endunless

@section('body-attribute')class="cms-page @yield('body-class')"@endsection

@section('domi-content')
    <x-vuero.icon-sprite />
    <svg width="0" height="0" aria-hidden="true" focusable="false" style="position:absolute;pointer-events:none">
        <defs>
            <filter id="brand-dark-contrast" color-interpolation-filters="sRGB">
                <feColorMatrix in="SourceGraphic" type="matrix" values="0 0 0 0 0  0 0 0 0 0  0 0 0 0 0  0 -4 0 0 1.8" />
                <feComponentTransfer><feFuncA type="discrete" tableValues="0 1" /></feComponentTransfer>
                <feComposite in2="SourceAlpha" operator="in" result="navy" />
                <feFlood flood-color="#e4edf7" />
                <feComposite in2="navy" operator="in" />
                <feComposite in2="SourceGraphic" operator="over" />
            </filter>
        </defs>
    </svg>
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
