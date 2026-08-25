@switch($dashboard['slug'])
    @case('banking-1')
        <x-vuero.dashboard.banking.dashboard-v1 />
        @break

    @case('banking-2')
        <x-vuero.dashboard.banking.dashboard-v2 />
        @break

    @case('banking-3')
        <header class="crypto-header"><div><span>Latest Bitcoin (BTC) Price Chart</span><h1>$10584.30 <small>+3.4%</small></h1></div><div class="period-tabs" data-dashboard-tabs>@foreach (['Hour','Day','Week','Month','Year'] as $period)<button class="{{ $period === 'Week' ? 'is-active' : '' }}" type="button">{{ $period }}</button>@endforeach</div></header>
        <div class="crypto-layout"><main><div class="dashboard-grid dashboard-grid--3 crypto-stats">@foreach ([['Market Cap','$129,992,260,090'],['Volume 24h','$453,178 K'],['Low/high 24h','$10,212 - $10,584']] as $stat)<x-vuero.dashboard.card><span>{{ $stat[0] }}</span><strong>{{ $stat[1] }}</strong></x-vuero.dashboard.card>@endforeach</div><x-vuero.dashboard.card title="Bitcoin (BTC) Price Chart" class="crypto-main-chart"><div class="chart-legend"><span>Expected</span><span>Real</span></div><x-vuero.dashboard.chart tone="purple" height="295" /></x-vuero.dashboard.card><div class="dashboard-grid dashboard-grid--2"><x-vuero.dashboard.card title="Bitcoin (BTC) Evolution"><x-vuero.dashboard.chart /></x-vuero.dashboard.card><x-vuero.dashboard.card title="Bitcoin (BTC) Popularity"><div class="dashboard-gauge" style="--progress:67"><span><b>67%</b>Popularity</span></div></x-vuero.dashboard.card></div></main><aside class="dashboard-stack"><x-vuero.dashboard.card class="coin-card"><header><span class="coin-icon">₿</span><p><strong>Bitcoin</strong><small>BTC</small></p></header><p class="soft-copy">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Vide, quantum, inquam, fallare, Torquate. Quid censes in Latino fore?</p><div class="button-pair"><button class="vuero-button is-primary">Buy BTC</button><button class="vuero-button">Compare</button></div></x-vuero.dashboard.card><x-vuero.dashboard.card title="FOLLOWERS" action="View All"><div class="avatar-row"><span>MK</span><span>SC</span><span>JD</span><span>AT</span><span>50K</span></div></x-vuero.dashboard.card><x-vuero.dashboard.card title="POPULAR COINS" action="View All"><div class="coin-list">@foreach ([['◉','Monero'],['Ł','Litecoin'],['◇','Chainlink']] as $coin)<p><span>{{ $coin[0] }}</span><strong>{{ $coin[1] }}</strong><button>＋</button></p>@endforeach</div></x-vuero.dashboard.card></aside></div>
        @break
@endswitch
