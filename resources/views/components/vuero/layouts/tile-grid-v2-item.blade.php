@props(['file'])

<article class="column is-4" data-tile-item data-search="{{ Illuminate\Support\Str::lower($file['name'].' '.$file['size'].' '.$file['updated']) }}">
    <div class="tile-grid-item" tabindex="0" data-tile-file data-tile-label="{{ $file['name'] }}">
        <div class="tile-grid-item-inner">
            <img src="{{ $file['icon'] }}" alt="" loading="lazy">
            <div class="meta">
                <span class="dark-inverted">{{ $file['name'] }}</span>
                <span>
                    <span>{{ $file['size'] }}</span>
                    <span class="icon-separator" aria-hidden="true"></span>
                    <span>Updated {{ $file['updated'] }}</span>
                </span>
            </div>
            <x-vuero.layouts.file-tile-dropdown />
        </div>
    </div>
</article>
