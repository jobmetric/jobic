{{-- Published from Domi 1.14.0; CMS integration documented in docs/domi.md. --}}
@php
    $links = \JobMetric\Domi\Facades\Domi::link();
    $topScripts = \JobMetric\Domi\Facades\Domi::topScript();
    $bottomScripts = \JobMetric\Domi\Facades\Domi::bottomScript();
@endphp
<!DOCTYPE html>
<html lang="@yield('language', app()->getLocale())" dir="@yield('direction', app()->getLocale() === 'fa' ? 'rtl' : 'ltr')" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">


    @php
        $pageTitle = \JobMetric\Domi\Facades\Domi::title() ?: $__env->yieldContent('title', config('app.name'));
        $siteTitle = \JobMetric\Domi\Facades\Domi::siteName() ?: config('app.name');
    @endphp
    <title>@if(config('domi.title_mode') === \JobMetric\Domi\Enums\TitleModeEnum::TITLE()){{ $pageTitle }}@elseif(config('domi.title_mode') === \JobMetric\Domi\Enums\TitleModeEnum::SITE_NAME()){{ $siteTitle }}@else{{ $pageTitle }} | {{ $siteTitle }}@endif</title>

@isDomi('description')
    <meta name="description" content="{{ \JobMetric\Domi\Facades\Domi::call('description') }}">
@endisDomi
@isDomi('keywords')
    <meta name="keywords" content="{{ \JobMetric\Domi\Facades\Domi::call('keywords') }}">
@endisDomi
@isDomi('author')
    <meta name="author" content="{{ \JobMetric\Domi\Facades\Domi::call('author') }}">
@endisDomi

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Open Graph -->
@isDomi('pageType')
    <meta property="og:type" content="{{ \JobMetric\Domi\Facades\Domi::call('pageType') }}">
@endisDomi
@isDomi('title')
    <meta property="og:title" content="{{ \JobMetric\Domi\Facades\Domi::call('title') }}">
@endisDomi
@isDomi('description')
    <meta property="og:description" content="{{ \JobMetric\Domi\Facades\Domi::call('description') }}">
@endisDomi
@isDomi('canonical')
    <meta property="og:url" content="{{ \JobMetric\Domi\Facades\Domi::call('canonical') }}">
@endisDomi
@isDomi('siteName')
    <meta property="og:site_name" content="{{ \JobMetric\Domi\Facades\Domi::call('siteName') }}">
@endisDomi
@isDomi('image')
    <meta property="og:image" content="{{ \JobMetric\Domi\Facades\Domi::call('image') }}"> <!-- 1200x630 -->
@endisDomi

@isDomi('favicon')
    <!-- Icons -->
    <link rel="shortcut icon" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}">
    <link rel="icon" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="icon" type="image/png" sizes="96x96" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="icon" type="image/png" sizes="160x160" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="icon" type="image/png" sizes="196x196" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="apple-touch-icon" sizes="57x57" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="apple-touch-icon" sizes="60x60" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="apple-touch-icon" sizes="72x72" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="apple-touch-icon" sizes="76x76" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="apple-touch-icon" sizes="144x144" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="apple-touch-icon" sizes="120x120" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
    <link rel="apple-touch-icon" sizes="152x152" href="{{ asset(\JobMetric\Domi\Facades\Domi::call('favicon')) }}"/>
@endisDomi
@isDomi('themeColor')
    <meta name="theme-color" content="{{ \JobMetric\Domi\Facades\Domi::call('themeColor') }}">

    <!-- Windows 8 Tiles -->
    <meta name="msapplication-TileColor" content="{{ \JobMetric\Domi\Facades\Domi::call('themeColor') }}">
@endisDomi
@isDomi('image')
    <meta name="msapplication-TileImage" content="{{ \JobMetric\Domi\Facades\Domi::call('image') }}">
@endisDomi
    @yield('domi-head')
    @yield('head')
@foreach($links as $rel => $linkObjects)
    @foreach($linkObjects as $object)
        <link rel="{{ $rel }}" href="{{ $object['href'] }}" @foreach($object['items'] as $name => $value) @if($value !== null) {{ $name }}="{{ $value }}" @endif @endforeach>
    @endforeach
@endforeach
    <!-- Top Script -->
@foreach($topScripts as $script)
    <script @if($script['type']) type="{{ $script['type'] }}" @endif src="{{ asset($script['src']) }}"{{ $script['async'] ? ' async' : '' }}{{ $script['defer'] ? ' defer' : '' }}></script>
@endforeach

</head>
<body @yield('body-attribute') >
@hasSection('domi-content')
    @yield('domi-content')
@else
    @yield('content')
@endif

@isDomi('footerContent')
<!-- Footer Content -->
@domi('footerContent')
@endisDomi
@include('domi::modal')

    <script type="application/json" id="jobic-page-data">{!! json_encode(\JobMetric\Domi\Facades\Domi::localize(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) !!}</script>
    <script>window.localize = JSON.parse(document.getElementById('jobic-page-data').textContent);</script>
    <!-- Bottom Script -->
    @yield('domi-script')
    @yield('script')
@foreach($bottomScripts as $script)
    <script @if($script['type']) type="{{ $script['type'] }}" @endif src="{{ asset($script['src']) }}"{{ $script['async'] ? ' async' : '' }}{{ $script['defer'] ? ' defer' : '' }}></script>
@endforeach
    @yield('script-bottom')
    @yield('domi-script-bottom')

</body>
</html>
