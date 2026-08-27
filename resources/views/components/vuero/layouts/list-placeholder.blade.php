@props(['title', 'subtitle', 'image', 'darkImage', 'name' => 'empty'])

<section class="list-layout-placeholder" data-layout-placeholder="{{ $name }}" @if ($name === 'search') hidden @endif>
    <img class="is-light" src="{{ $image }}" alt="">
    <img class="is-dark" src="{{ $darkImage }}" alt="">
    <h2>{{ $title }}</h2>
    <p>{{ $subtitle }}</p>
</section>
