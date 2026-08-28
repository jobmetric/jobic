@props(['post'])

<article class="column is-3" data-grid-item data-search="{{ Illuminate\Support\Str::lower($post['title'].' '.$post['author']['name']) }}">
    <a href="#" class="card-grid-item" data-grid-post data-grid-label="{{ $post['title'] }}">
        <img src="{{ $post['image'] }}" alt="" loading="lazy">
        <div class="card-grid-item-content">
            <h3 class="dark-inverted">{{ $post['title'] }}</h3>
        </div>
        <div class="card-grid-item-footer">
            <x-vuero.layouts.flex-avatar size="small" :picture="$post['author']['avatar']" />
            <div class="meta">
                <span class="dark-inverted">{{ $post['author']['name'] }}</span>
                <span>{{ $post['published'] }}</span>
            </div>
        </div>
    </a>
</article>
