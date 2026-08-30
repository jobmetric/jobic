@props([
    'title' => 'Error',
    'script' => null,
    'bodyClass' => 'cms-page cms-page--error',
    'wrapperClass' => 'minimal-wrapper darker',
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

    <title>{{ $title }} | Vuero</title>

    @vite(['resources/css/vuero/app.css', 'resources/js/vuero/app.js'], 'vuero/build')
    @if ($script)
        @vite($script, 'vuero/build')
    @endif
</head>
<body class="{{ $bodyClass }}">
    <x-vuero.icon-sprite />
    <div class="{{ $wrapperClass }}">
        {{ $slot }}
    </div>
</body>
</html>
