@php
    $leagues = [['ligue-1','Ligue 1','France'],['laliga','La Liga','Spain'],['liga','Liga','Portugal'],['bundesliga','Bundesliga','Germany']];
    $matches = [
        ['-- : --','live','Live','barcelona','Barcelona','4','0','psg','PSG','Camp Nou'],
        ['10:30','finished','Finished','madrid','Real Madrid','1','0','liverpool','Liverpool','Bernabeu'],
        ['12:00','scheduled','Scheduled','arsenal','Arsenal','-','-','chelsea','Chelsea','Arsenal'],
        ['14:00','scheduled','Scheduled','everton','Everton','-','-','liverpool','Liverpool','Goodison Park'],
        ['16:00','scheduled','Scheduled','valladolid','Valladolid','-','-','zagreb','Zagreb','José Zorrilla'],
        ['18:00','scheduled','Scheduled','psg','PSG','-','-','chelsea','Chelsea','Parc des Princes'],
    ];
@endphp

<div class="lifestyle-page lifestyle-soccer" data-lifestyle="soccer">
    <div class="soccer-page-grid">
        <aside>
            <section class="lifestyle-card soccer-live-card">
                <header><h1>UEFA Champions League</h1><div><p><strong>Group C</strong><small>Match 3 of 6</small></p><span><x-vuero.icon name="video" size="14" /> Live Now</span></div></header>
                <div class="soccer-live-score"><figure><img src="{{ asset('vuero/images/icons/soccer/teams/barcelona.svg') }}" alt="Barcelona"><figcaption>Barcelona</figcaption></figure><strong><span>4</span><i>:</i><span>0</span></strong><figure><img src="{{ asset('vuero/images/icons/soccer/teams/psg.svg') }}" alt="PSG"><figcaption>PSG</figcaption></figure></div>
                <button type="button" class="vuero-button is-primary" data-watch-match>Watch Now</button>
            </section>

            <section class="lifestyle-card soccer-leagues"><header><h2>Available leagues</h2></header><div>@foreach($leagues as [$logo,$name,$country])<button type="button" data-soccer-league="{{ $name }}"><img src="{{ asset('vuero/images/icons/soccer/leagues/'.$logo.'.svg') }}" alt=""><p><strong>{{ $name }}</strong><small>{{ $country }}</small></p><span>8 matches</span><x-vuero.icon name="chevron-right" size="15" /></button>@endforeach</div></section>
        </aside>

        <main>
            <section class="soccer-cta"><img src="https://images.unsplash.com/photo-1579952363873-27f3bade9f55?auto=format&fit=crop&w=700&q=82" alt="Soccer player"><div><h2>Win awesome prizes in our contest</h2><p>Predict who will win and how a match will end and get a chance to win incredible cash prizes.</p><button type="button" class="vuero-button" data-soccer-contest>Learn More</button></div></section>

            <section class="lifestyle-card soccer-matches">
                <nav role="tablist" data-soccer-tabs><button type="button" role="tab" aria-selected="true" class="is-active" data-match-filter="all">All</button><button type="button" role="tab" aria-selected="false" data-match-filter="live">Live</button><button type="button" role="tab" aria-selected="false" data-match-filter="finished">Finished</button><button type="button" role="tab" aria-selected="false" data-match-filter="scheduled">Scheduled</button></nav>
                <div class="soccer-table"><header><span>Time</span><span>Match</span><span>Stadium</span><span>Details</span></header><div data-soccer-matches>
                    @foreach($matches as [$time,$status,$statusLabel,$homeLogo,$home,$homeScore,$awayScore,$awayLogo,$away,$stadium])
                        <article data-match-status="{{ $status }}"><div class="soccer-match-time"><strong>{{ $time }}</strong><span class="is-{{ $status }}"><i></i>{{ $statusLabel }}</span></div><div class="soccer-score"><span>{{ $home }}</span><img src="{{ asset('vuero/images/icons/soccer/teams/'.$homeLogo.'.svg') }}" alt=""><strong>{{ $homeScore }} <i>:</i> {{ $awayScore }}</strong><img src="{{ asset('vuero/images/icons/soccer/teams/'.$awayLogo.'.svg') }}" alt=""><span>{{ $away }}</span></div><span class="soccer-stadium">{{ $stadium }}</span><button type="button" data-match-details aria-label="Match details"><x-vuero.icon name="align-right" size="18" /></button></article>
                    @endforeach
                </div><p class="lifestyle-empty-row" data-soccer-empty hidden>No matches in this category.</p></div>
            </section>
        </main>
    </div>
</div>
