@props(['title', 'text'])

<div class="media-flex layout-media-block">
    <div class="icon-wrap" aria-hidden="true">
        <x-vuero.icon name="check" size="16" />
    </div>
    <div class="flex-meta">
        <span>{{ $title }}</span>
        <p>{{ $text }}</p>
    </div>
</div>
