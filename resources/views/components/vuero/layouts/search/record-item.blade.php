@props(['record'])

<article class="search-results-item">
    <div class="search-block">
        <x-vuero.layouts.flex-avatar size="medium" :picture="$record[2]" :squared="$record[3]" />
        <div class="meta">
            <span class="dark-inverted">{{ $record[0] }}</span>
            <span>{{ $record[1] }}</span>
        </div>
        <button type="button" class="v-button is-dark-outlined">Details</button>
    </div>
</article>
