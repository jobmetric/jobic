<!doctype html>
<html lang="@yield('language', app()->getLocale())" dir="@yield('direction', app()->getLocale() === 'fa' ? 'rtl' : 'ltr')" data-theme="light">
<head>
    @include('common.head')
    @yield('head')
    @stack('styles')
</head>
<body class="cms-page @yield('body-class')">
    <x-vuero.icon-sprite />
    @yield('body')
    @yield('footer')
    @stack('overlays')
    @stack('scripts')
</body>
</html>
