@props([
    'title' => 'Dashboard',
    'script' => null,
    'chrome' => 'default',
    'layout' => null,
    'starter' => null,
    'openNavigation' => false,
])

<!doctype html>
<html lang="en" dir="ltr" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#2aac8c">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&family=Roboto:wght@300;400;500;600;700&display=swap">

    <title>{{ $title }} | {{ config('app.name', 'Jobic') }}</title>

    @vite(['resources/css/vuero/app.css', 'resources/js/vuero/app.js'], 'vuero/build')
    @if ($script)
        @vite($script, 'vuero/build')
    @endif
</head>
<body class="cms-page cms-page--admin cms-page--{{ $chrome }} {{ $starter ? 'cms-page--starter' : '' }}">
    <x-vuero.icon-sprite />

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
                {{ $slot }}
            </main>
        </div>

        <button class="admin-shell__overlay" type="button" tabindex="-1" data-sidebar-close aria-label="Close navigation"></button>
    </div>
</body>
</html>
