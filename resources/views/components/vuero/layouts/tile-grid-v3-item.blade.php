@props([
    'size' => 'medium',
    'title',
    'image',
    'authorName',
    'authorAvatar',
    'published',
    'excerpt' => null,
    'paragraphs' => [],
    'excerptClass' => 'h-hidden-tablet-l h-hidden-desktop',
    'extraParagraphClass' => null,
])

@php
    $excerptText = $excerpt ?? 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Optime, inquam. Duo Reges constructio.';
@endphp

<a href="#" class="tile is-child tile-grid-item is-{{ $size }}" data-tile-item data-tile-post data-tile-label="{{ $title }}" data-search="{{ Illuminate\Support\Str::lower($title.' '.$authorName) }}">
    <div class="tile-grid-item-inner">
        <img src="{{ $image }}" alt="" loading="lazy">
        <div class="meta">
            <div class="tile-title">
                <h3 class="dark-inverted" data-filter-match>{{ $title }}</h3>
                @if (in_array($size, ['medium', 'large'], true))
                    <p class="{{ $excerptClass }}">{{ $excerptText }}</p>
                @elseif (in_array($size, ['wide', 'tall'], true))
                    <p>{{ $excerptText }}</p>
                    @foreach ($paragraphs as $paragraph)
                        <p @if ($extraParagraphClass) class="{{ $extraParagraphClass }}" @endif>{{ $paragraph }}</p>
                    @endforeach
                @endif
            </div>
            <div class="tile-meta">
                <x-vuero.layouts.flex-avatar size="small" :picture="$authorAvatar" />
                <div class="meta-inner">
                    <span class="dark-inverted" data-filter-match>{{ $authorName }}</span>
                    <span>{{ $published }}</span>
                </div>
            </div>
        </div>
    </div>
</a>
