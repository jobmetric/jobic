@props(['course'])

@php
    $search = Illuminate\Support\Str::lower(implode(' ', [
        $course['name'],
        $course['category'],
        $course['skill']['name'],
        $course['author']['username'],
    ]));
@endphp

<div
    class="flex-table-item"
    data-layout-item
    data-search="{{ $search }}"
    data-item-name="{{ $course['name'] }}"
>
    <div class="flex-table-cell is-media is-grow" data-th="Course">
        <img class="media" src="{{ $course['picture'] }}" alt="" loading="lazy" width="130" height="95">
        <div>
            <span class="item-name dark-inverted">{{ $course['name'] }}</span>
            <div class="item-meta">
                <div class="flex-media">
                    <x-vuero.layouts.flex-avatar :picture="$course['author']['picture']" size="small" />
                    <div class="meta"><span>by {{ $course['author']['username'] }}</span></div>
                </div>
            </div>
        </div>
    </div>
    <div class="flex-table-cell" data-th="Category">
        <span class="light-text">{{ $course['category'] }}</span>
    </div>
    <div class="flex-table-cell" data-th="Main Skill">
        <span class="flex-skill-icon is-{{ $course['skill']['slug'] }} is-pushed-mobile" aria-hidden="true"></span>
        <span class="light-text no-push">{{ $course['skill']['name'] }}</span>
    </div>
    <div class="flex-table-cell" data-th="Difficulty">
        <span class="dot-levels" aria-label="Difficulty level {{ $course['difficulty'] }} of 5">
            @for ($level = 1; $level <= 5; $level++)
                <span @class(['dot', 'active' => $course['difficulty'] >= $level])></span>
            @endfor
        </span>
    </div>
    <div class="flex-table-cell cell-end h-hidden-tablet-p" data-th="Actions">
        <button type="button" class="flex-purchase-button is-pushed-mobile" data-flex-purchase data-item-name="{{ $course['name'] }}" data-item-price="{{ $course['price'] }}">
            Purchase<span class="flex-purchase-button__dot" aria-hidden="true"></span>${{ $course['price'] }}
        </button>
    </div>
</div>
