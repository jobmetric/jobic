@props([
    'title' => 'ورود',
    'script' => null,
])

<!doctype html>
<html lang="fa" dir="rtl" data-theme="light">
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
<body class="cms-page cms-page--auth">
    <x-vuero.icon-sprite />
    {{ $slot }}
</body>
</html>
