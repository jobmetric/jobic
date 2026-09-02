@props([
    'title' => 'مدیریت',
])

<!doctype html>
<html lang="fa" dir="rtl" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light dark">
    <meta name="theme-color" content="#2aac8c">

    <title>{{ $title }} | {{ config('app.name', 'Jobic') }}</title>

    @vite(['resources/css/vuero/app.css', 'resources/js/vuero/app.js'], 'vuero/build')
</head>
<body class="cms-page cms-page--admin">
    <x-vuero.icon-sprite />
    {{ $slot }}
</body>
</html>
