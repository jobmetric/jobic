<meta name="color-scheme" content="light dark">
@if(\App\Support\LocaleCatalog::direction() !== 'rtl')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800;900&family=Roboto:wght@300;400;500;600;700&display=swap">
@endif
@if (! request()->is('installation', 'installation/*') || is_file(public_path('vuero/build/manifest.json')))
    @vite(['resources/css/vuero/app.css', 'resources/js/vuero/app.js'], 'vuero/build')
    @if (! empty($script)) @vite($script, 'vuero/build') @endif
@endif
