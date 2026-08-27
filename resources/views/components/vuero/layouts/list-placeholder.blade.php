@props(['title', 'subtitle', 'image', 'darkImage', 'name' => 'empty', 'large' => false])

<section class="list-layout-placeholder {{ $large ? 'is-larger' : '' }}" data-layout-placeholder="{{ $name }}" @if ($name === 'search') hidden @endif>
    <img class="is-light {{ $large ? 'is-larger' : '' }}" src="{{ $image }}" alt="">
    <img class="is-dark {{ $large ? 'is-larger' : '' }}" src="{{ $darkImage }}" alt="">
    <h2>{{ $title }}</h2>
    <p>{{ $subtitle }}</p>
</section>
