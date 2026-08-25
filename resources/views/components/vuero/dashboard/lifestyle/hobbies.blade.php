@php
    $featured = [
        ['Foot Trekks', '28.3K', 'activity', 'https://images.unsplash.com/photo-1551632811-561732d1e306?auto=format&fit=crop&w=800&q=82'],
        ['Rafting Trips', '8.1K', 'plane', 'https://images.unsplash.com/photo-1530866495561-507c9faab2ed?auto=format&fit=crop&w=800&q=82'],
        ['Climbing', '19.7K', 'activity', 'https://images.unsplash.com/photo-1522163182402-834f871fd851?auto=format&fit=crop&w=800&q=82'],
        ['Biking', '48.4K', 'energy', 'https://images.unsplash.com/photo-1541625602330-2277a4c46182?auto=format&fit=crop&w=800&q=82'],
    ];
    $lists = [
        ['Trending Now', 'Check out the latest activities', [
            ['Extreme Foot Trekk','Oct 31, 2020',5,1], ['Rafting trip','Oct 30, 2020',4,2], ['Climbing Group [Pro]','Oct 31, 2020',5,3], ['Bicycle Madness Trek','Oct 31, 2020',5,4],
        ]],
        ['Popular Now', 'Our popular best sellers', [
            ['Extreme Triathlon','Nov 2, 2020',5,5], ['Group Running','Oct 29, 2020',4,6], ['Suit Diving','Oct 28, 2020',5,7], ['Desert Trekk','Oct 27, 2020',5,8],
        ]],
    ];
    $topics = [['energy','Biking','yellow'],['activity','Running','orange'],['trending-up','Skiing','green'],['droplet','Swimming','blue'],['healthcare','Workout','purple']];
@endphp

<div class="lifestyle-page lifestyle-hobbies" data-lifestyle="hobbies">
    <header class="lifestyle-page-title"><div><h1>Explore Hobbies</h1><p>Explore some of the best activities nearby in your region</p></div><button type="button" class="lifestyle-action" data-hobbies-view-all>View All</button></header>
    <div class="hobbies-page-grid">
        <main>
            <div class="hobbies-featured">
                @foreach ($featured as [$title,$members,$icon,$image])
                    <article class="hobby-long-card" style="--hobby-image:url('{{ $image }}')" data-hobby-card data-search="{{ strtolower($title) }}">
                        <button type="button" aria-label="Open {{ $title }}"><span class="hobby-long-card__shade"></span><span class="hobby-long-card__content"><span><strong>{{ $title }}</strong><x-vuero.icon :name="$icon" size="20" /></span><span><b>{{ $members }}</b><i class="lifestyle-avatar-stack">@foreach ([12,22,33] as $avatar)<img src="{{ asset('vuero/images/admin/business/avatars/'.$avatar.'.svg') }}" alt="">@endforeach</i></span></span></button>
                    </article>
                @endforeach
            </div>
            <p class="lifestyle-empty-row" data-hobby-empty hidden>No hobbies match your search.</p>

            <div class="hobby-lists-grid">
                @foreach ($lists as [$heading,$subtitle,$items])
                    <section class="hobby-list">
                        <header><div><h2>{{ $heading }}</h2><p>{{ $subtitle }}</p></div>@if($loop->last)<button type="button" class="lifestyle-action" data-list-view-all>View All</button>@endif</header>
                        <div>
                            @foreach ($items as [$title,$date,$rating,$photo])
                                <article class="hobby-list-item" data-hobby-row data-search="{{ strtolower($title) }}">
                                    <img src="{{ $featured[($photo - 1) % count($featured)][3] }}" alt="{{ $title }}">
                                    <p><strong>{{ $title }}</strong><small>{{ $date }}</small></p>
                                    <span class="hobby-rating" aria-label="{{ $rating }} out of 5">@foreach(range(1, 5) as $star)<i @class(['is-filled' => $star <= $rating])>★</i>@endforeach</span>
                                    <button type="button" class="lifestyle-circle-button" data-hobby-open aria-label="Open {{ $title }}"><x-vuero.icon name="arrow-right" size="16" /></button>
                                </article>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        </main>

        <aside class="hobbies-sidebar">
            <section class="lifestyle-card hobbies-search"><label><input type="search" placeholder="Search..." data-hobby-search><button type="button" data-hobby-submit aria-label="Search"><x-vuero.icon name="search" size="18" /></button></label><div>@foreach (['Trekking','Climbing','Biking'] as $tag)<button type="button" data-hobby-tag>{{ '#'.$tag }}</button>@endforeach</div></section>
            <section class="lifestyle-card hobbies-message"><header><span><x-vuero.icon name="message" size="18" /></span><h2>Messages</h2><button type="button" data-lifestyle-menu aria-label="Message options"><x-vuero.icon name="more" size="18" /></button></header><p>You currently have more than <strong>10 unread messages</strong> in your inbox. It could be a good time to check them out.</p><button type="button" class="lifestyle-action" data-open-messages>Open inbox</button></section>
            <section class="lifestyle-card hobbies-topics"><header><h2>Hot Topics</h2><button type="button" data-lifestyle-menu aria-label="Topic options"><x-vuero.icon name="more" size="18" /></button></header><div>@foreach ($topics as [$icon,$name,$color])<button type="button" data-hobby-tag="{{ $name }}"><span class="is-{{ $color }}"><x-vuero.icon :name="$icon" size="18" /></span><p><strong>{{ $name }}</strong><small>Sports</small></p><x-vuero.icon name="chevron-right" size="15" /></button>@endforeach</div></section>
        </aside>
    </div>
</div>
