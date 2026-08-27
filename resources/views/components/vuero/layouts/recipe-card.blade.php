@props(['recipe', 'index'])

@php
    $tones = [
        'success' => 'is-green',
        'info' => 'is-primary',
        'purple' => 'is-purple',
        'orange' => 'is-orange',
        'danger' => 'is-danger',
        'h-yellow' => 'is-yellow',
        'h-purple' => 'is-purple',
    ];
@endphp

<article class="list-view-item" data-layout-item data-recipe-id="{{ $recipe['id'] }}" data-search="{{ Illuminate\Support\Str::lower(implode(' ', [$recipe['name'], $recipe['category'], $recipe['duration'], $recipe['author']['name']])) }}" style="--item-order: {{ $index }}">
    <div class="list-view-item-inner">
        <div class="pre-meta">
            <h3>{{ $recipe['name'] }}</h3>
        </div>
        <img class="recipe-icon" src="{{ $recipe['icon'] }}" alt="">
        <div class="meta-left">
            <h3>
                <img class="avatar" src="{{ $recipe['author']['avatar'] }}" alt="{{ $recipe['author']['name'] }}">
                <span>{{ $recipe['author']['name'] }}</span>
            </h3>
            <span>
                <x-vuero.icon name="archive" size="12" />
                <span>{{ $recipe['category'] }}</span>
                <i class="meta-separator" aria-hidden="true"></i>
                <x-vuero.icon name="clock" size="12" />
                <span>{{ $recipe['duration'] }}</span>
                <i class="meta-separator" aria-hidden="true"></i>
                <x-vuero.icon name="paperclip" size="12" />
                <span>{{ $recipe['attachments'] }} files</span>
            </span>
        </div>
        <div class="meta-right">
            <div class="network">
                <div class="recipe-followers" aria-label="{{ count($recipe['followers']) }} followers">
                    @foreach (array_slice($recipe['followers'], 0, 3) as $follower)
                        @if (! empty($follower['picture']))
                            <img src="{{ $follower['picture'] }}" alt="">
                        @else
                            <span class="{{ $tones[$follower['color']] ?? 'is-primary' }}">{{ $follower['initials'] }}</span>
                        @endif
                    @endforeach
                    @if (count($recipe['followers']) > 3)
                        <span class="recipe-followers__more">+{{ count($recipe['followers']) - 3 }}</span>
                    @endif
                </div>
                <span>Like this</span>
            </div>
            <div class="buttons">
                <button type="button" class="vuero-list-button is-outline" data-recipe-action="view" data-item-name="{{ $recipe['name'] }}">View Recipe</button>
                <button type="button" class="recipe-save" aria-label="Save {{ $recipe['name'] }}" aria-pressed="false" data-recipe-action="save" data-item-name="{{ $recipe['name'] }}">
                    <x-vuero.icon name="heart" size="15" />
                </button>
            </div>
        </div>
    </div>
</article>
