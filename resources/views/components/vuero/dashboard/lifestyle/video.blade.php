@php
    $today = [
        ['The best ways to do a nice team building','01:43','Erik K.',31,2,true,'https://images.unsplash.com/photo-1521737711867-e3b97375f902?auto=format&fit=crop&w=1200&q=82','large'],
        ['Bake beautiful and tasty cupcakes','05:17','Alice C.',13,3,false,'https://images.unsplash.com/photo-1486427944299-d1955d23e34d?auto=format&fit=crop&w=800&q=82','regular'],
        ['My last trip to Rio de Janeiro in 3 min','03:12','Tara S.',15,0,false,'https://images.unsplash.com/photo-1483729558449-99ef09a8c325?auto=format&fit=crop&w=800&q=82','regular'],
        ['You must visit those places','04:24','Joshua S.',12,5,false,'https://images.unsplash.com/photo-1500530855697-b586d89ba3ee?auto=format&fit=crop&w=800&q=82','regular'],
        ['The best workout guide for women','04:24','Greta K.',19,3,false,'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&w=800&q=82','regular'],
        ['10 incredible waves surfed by pros','07:11','Mary L.',5,3,false,'https://images.unsplash.com/photo-1502680390469-be75c86b636f?auto=format&fit=crop&w=800&q=82','regular'],
        ['Enchanting rain forests around the world','12:37','Esteban C.',18,9,false,'https://images.unsplash.com/photo-1441974231531-c6227db76b6e?auto=format&fit=crop&w=800&q=82','regular'],
    ];
    $yesterday = [
        ['The ultimate guide to beard care','04:24','Joshua S.',12,5,'https://images.unsplash.com/photo-1500648767791-00dcc994a43e?auto=format&fit=crop&w=800&q=82'],
        ['Test driving the latest Tesla release','12:18','Mary L.',5,4,'https://images.unsplash.com/photo-1560958089-b8a1929cea89?auto=format&fit=crop&w=800&q=82'],
        ['5 places you should definitely check','06:19','Carmen E.',27,8,'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&w=800&q=82'],
        ['Why you should plan first before shopping','03:49','Elizabeth F.',21,11,'https://images.unsplash.com/photo-1472851294608-062f824d29cc?auto=format&fit=crop&w=800&q=82'],
        ['10 Asian cities you should travel to','07:21','Irina V.',23,9,'https://images.unsplash.com/photo-1480796927426-f609979314bd?auto=format&fit=crop&w=800&q=82'],
        ['Our awesome 6 months worldwide trip','15:39','Jeanne M.',33,2,'https://images.unsplash.com/photo-1527631746610-bca00a040d60?auto=format&fit=crop&w=800&q=82'],
        ['Traditional blue houses in Morocco','11:16','Dwayne H.',22,0,'https://images.unsplash.com/photo-1489749798305-4fea3ae63d43?auto=format&fit=crop&w=800&q=82'],
        ['8 activities to do with the family','08:43','Jason G.',37,6,'https://images.unsplash.com/photo-1511895426328-dc8714191300?auto=format&fit=crop&w=800&q=82'],
    ];
@endphp

<div class="lifestyle-page lifestyle-video" data-lifestyle="video">
    <header class="lifestyle-page-title video-page-title"><div><h1>Recent Videos</h1><p>Recent videos in this workspace</p></div><div class="video-members"><span>Members</span><div><button type="button" data-add-video-member aria-label="Add member"><x-vuero.icon name="plus" size="16" /></button>@foreach([33,12,5,13] as $avatar)<button type="button" data-video-member><img src="{{ asset('vuero/images/admin/business/avatars/'.$avatar.'.svg') }}" alt="Member"></button>@endforeach<button type="button" data-video-members-all>+6</button></div></div></header>

    <section class="video-feed-group"><h2>Today</h2><div class="video-today-grid">
        @foreach($today as [$title,$duration,$author,$avatar,$comments,$new,$poster,$size])
            <article class="lifestyle-video-card is-{{ $size }}" style="--video-poster:url('{{ $poster }}')" data-video-title="{{ $title }}">
                <span class="video-card-shade"></span><span class="video-card-duration">{{ $duration }}</span><button type="button" class="video-card-play" data-video-play aria-label="Play {{ $title }}"><x-vuero.icon name="play" size="{{ $size === 'large' ? 25 : 16 }}" /></button>
                <footer><h3>{{ $title }}</h3><div><img src="{{ asset('vuero/images/admin/business/avatars/'.$avatar.'.svg') }}" alt="{{ $author }}"><button type="button" data-video-author>{{ $author }}</button><i>|</i>@if($new)<span>NEW</span><i>|</i>@endif<button type="button" data-video-comments>{{ $comments }} comments</button></div></footer>
            </article>
        @endforeach
    </div></section>

    <section class="video-feed-group"><h2>Yesterday</h2><div class="video-yesterday-grid">
        @foreach($yesterday as [$title,$duration,$author,$avatar,$comments,$poster])
            <article class="lifestyle-video-card is-regular" style="--video-poster:url('{{ $poster }}')" data-video-title="{{ $title }}"><span class="video-card-shade"></span><span class="video-card-duration">{{ $duration }}</span><button type="button" class="video-card-play" data-video-play aria-label="Play {{ $title }}"><x-vuero.icon name="play" size="16" /></button><footer><h3>{{ $title }}</h3><div><img src="{{ asset('vuero/images/admin/business/avatars/'.$avatar.'.svg') }}" alt="{{ $author }}"><button type="button" data-video-author>{{ $author }}</button><i>|</i><button type="button" data-video-comments>{{ $comments }} comments</button></div></footer></article>
        @endforeach
    </div><button type="button" class="vuero-button video-load-more" data-video-load-more>Load More</button></section>
</div>
