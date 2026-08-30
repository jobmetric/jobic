@props(['person'])

<article class="search-results-item">
    <div class="search-block">
        <x-vuero.layouts.flex-avatar size="medium" :picture="$person[2]" />
        <div class="meta">
            <span class="dark-inverted">{{ $person[0] }}</span>
            <span>{{ $person[1] }}</span>
        </div>
        <button type="button" class="v-button is-dark-outlined">Profile</button>
    </div>
</article>
