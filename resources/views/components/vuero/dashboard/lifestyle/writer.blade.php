@php
    $authors = [
        [13,'Alice C.','Software Engineer','112K'], [21,'Elizabeth F.','Web Developer','91K'], [32,'Jonathan K.','UI/UX Designer','72K'], [25,'Christie D.','Software Engineer','19K'],
    ];
    $updates = [
        ['Alice C. published a new article in Product.','Oct 23'], ['Your article received 12 new comments.','Oct 23'], ['Jonathan K. started following your work.','Oct 22'], ['The editorial team approved your draft.','Oct 22'], ['Your weekly readership report is ready.','Oct 21'],
    ];
    $articles = [
        ['Learning the modern novel','Some article content and lorem ipsum sit dolor amet','Alice C.',13,'https://images.unsplash.com/photo-1455390582262-044cdead277a?auto=format&fit=crop&w=900&q=82',12,'recent'],
        ['5 Writing tips for you','Some article content and lorem ipsum sit dolor amet','Erik K.',31,'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=900&q=82',8,'recent'],
        ['The future of product writing','A practical guide to clearer and more useful product content','Jonathan K.',32,'https://images.unsplash.com/photo-1499750310107-5fef28a66643?auto=format&fit=crop&w=900&q=82',31,'popular'],
        ['Building a daily creative habit','Small routines that make consistent writing much easier','Elizabeth F.',21,'https://images.unsplash.com/photo-1456324504439-367cee3b3c32?auto=format&fit=crop&w=900&q=82',24,'popular'],
    ];
@endphp

<div class="lifestyle-page lifestyle-writer" data-lifestyle="writer">
    <div class="writer-page-grid">
        <main>
            <section class="writer-illustration">
                <div><img src="{{ asset('vuero/images/admin/dashboards/reading.svg') }}" alt="People reading"></div>
                <section><h1>Hello, Erik.</h1><p>Have any ideas for a new article? If not, you should definitely check the feed for some inspiration.</p><button type="button" class="vuero-button" data-new-article><x-vuero.icon name="plus" size="16" /> New Article</button></section>
            </section>

            <div class="writer-body-grid">
                <section>
                    <div class="writer-stat-grid">@foreach([['Articles','209'],['Readers','863'],['Comments','386']] as [$label,$value])<article class="lifestyle-card"><span>{{ $label }}</span><strong>{{ $value }}</strong></article>@endforeach</div>
                    <article class="lifestyle-card writer-authors"><header><h2>Featured Authors</h2><button type="button" class="lifestyle-action" data-authors-view-all>View All</button></header><div>@foreach($authors as [$avatar,$name,$role,$readers])<button type="button" data-writer-author><img src="{{ asset('vuero/images/admin/business/avatars/'.$avatar.'.svg') }}" alt="{{ $name }}"><p><strong>{{ $name }}</strong><small>{{ $role }}</small></p><span><b>{{ $readers }}</b><small>Readers</small></span><x-vuero.icon name="chevron-right" size="15" /></button>@endforeach</div></article>
                </section>

                <article class="lifestyle-card writer-updates"><header><h2>Updates</h2><button type="button" class="lifestyle-action" data-updates-view-all>View All</button></header><div>@foreach($updates as [$text,$date])<button type="button" data-update-item><span></span><p>{{ $text }}</p><time>{{ $date }}</time></button>@endforeach</div></article>
            </div>
        </main>

        <aside class="writer-feed">
            <header><h2>New Articles</h2><button type="button" class="lifestyle-action" data-articles-view-all>View All</button></header>
            <nav class="writer-feed-tabs" role="tablist" data-writer-tabs><button type="button" class="is-active" role="tab" aria-selected="true" data-article-filter="recent">Recent</button><button type="button" role="tab" aria-selected="false" data-article-filter="popular">Popular</button><i aria-hidden="true"></i></nav>
            <div class="writer-feed-list" data-writer-feed>
                @foreach($articles as [$title,$copy,$author,$avatar,$image,$comments,$type])
                    <article data-article-type="{{ $type }}" @if($type !== 'recent') hidden @endif>
                        <button type="button" class="writer-article-image" data-open-article><img src="{{ $image }}" alt="{{ $title }}"><span><x-vuero.icon name="content" size="18" /></span></button>
                        <section><h3>{{ $title }}</h3><p>{{ $copy }}</p><footer><img src="{{ asset('vuero/images/admin/business/avatars/'.$avatar.'.svg') }}" alt=""><span><strong>{{ $author }}</strong><small>{{ $comments }} comments</small></span><button type="button" data-save-article aria-label="Save article"><x-vuero.icon name="heart" size="17" /></button></footer></section>
                    </article>
                @endforeach
            </div>
        </aside>
    </div>
</div>
