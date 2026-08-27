@props(['current' => 42])

<nav class="list-layout-pagination" aria-label="Pagination" data-layout-pagination>
    <button type="button" class="pagination-edge" aria-label="Previous page" data-layout-page="{{ $current - 1 }}"><x-vuero.icon name="chevron-left" size="16" /></button>
    <ol>
        @foreach ([1, null, 39, 40, 41, 42, 43, 44, 45, null, 88] as $page)
            <li>
                @if (is_null($page))
                    <span class="pagination-ellipsis">…</span>
                @else
                    <button type="button" class="{{ $page === $current ? 'is-current' : '' }}" data-layout-page="{{ $page }}" @if ($page === $current) aria-current="page" @endif>{{ $page }}</button>
                @endif
            </li>
        @endforeach
    </ol>
    <button type="button" class="pagination-edge" aria-label="Next page" data-layout-page="{{ $current + 1 }}"><x-vuero.icon name="chevron-right" size="16" /></button>
</nav>
