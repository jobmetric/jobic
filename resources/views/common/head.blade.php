<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#2aac8c">
<title>@yield('title', config('app.name', 'Jobic')) | {{ config('app.name', 'Jobic') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&family=Roboto:wght@300;400;500;600;700&display=swap">
@if (! request()->is('installation', 'installation/*') || is_file(public_path('vuero/build/manifest.json')))
    @vite(['resources/css/vuero/app.css', 'resources/js/vuero/app.js'], 'vuero/build')
    @if (! empty($script)) @vite($script, 'vuero/build') @endif
@endif
