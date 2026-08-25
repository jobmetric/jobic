@php
    $graphs = [
        [
            'symbol' => 'SPCH', 'name' => 'Snapchat', 'brand' => 'snapchat', 'change' => '+14%', 'tone' => 'primary',
            'path' => 'M0 43.634 C5.934 43.634 11.318 51.209 17.462 51.342 C33.219 51.683 30.603 59.567 39.187 59.868 C46.963 60.141 50.44 44.192 60.537 43.77 C69.126 43.77 72.129 52.461 79.739 52.433 C90.904 52.433 94.93 38.455 106.648 39.78 C129.082 42.317 124.556 27.606 139.157 27.177 C153.758 26.747 158.235 44.485 171.96 44.725 C196.438 45.155 189.782 17.1 208.248 17.1',
            'tail' => 'M209.328 17.34 C221.956 17.588 235.264 32.599 250 22.328',
            'pointX' => 209.328, 'pointY' => 17.218, 'labelX' => 195, 'labelY' => 6,
        ],
        [
            'symbol' => 'GTHB', 'name' => 'Github', 'brand' => 'github', 'change' => '-5%', 'tone' => 'dark',
            'path' => 'M0 20 C0 20 13.4 27.4 17.6 40.9 S31.8 76 49.8 74.1 S83.9 48 100.4 55.2 C100.4 55.2 127.4 70.1 138.9 67.1 C144 64.1 142.9 53.9 152.3 56.1 C161.7 58.4 162.8 37.7 169.4 40.9 C175.9 44 177.6 53.4 182.8 47.1 S189.3 21 208.2 32.2',
            'tail' => 'M209.3 32.3 C221.9 32.5 235.2 47.6 250 37.3',
            'pointX' => 209.3, 'pointY' => 32.2, 'labelX' => 195, 'labelY' => 21,
        ],
        [
            'symbol' => 'INVS', 'name' => 'Invision', 'brand' => 'invision', 'change' => '+8%', 'tone' => 'danger',
            'path' => 'M0 57.5 C0 57.5 13 46.2 27.5 49.8 S59.2 68.2 72.9 66.2 C72.9 66.2 116.3 64.5 127.6 26.5 C138.9 -11.5 159.1 52.6 173.5 51.2 C188 49.8 185.9 26.2 206.7 32.2',
            'tail' => 'M209.3 32.3 C221.9 32.5 235.2 47.6 250 37.3',
            'pointX' => 209.3, 'pointY' => 32.2, 'labelX' => 195, 'labelY' => 21,
        ],
    ];

    $categories = [
        ['energy', 'Energy'], ['gold', 'Gold'], ['real-estate', 'Real Estate'], ['food', 'Food'],
        ['technology', 'Technology'], ['healthcare', 'Healthcare'], ['research', 'Research'], ['finance', 'Finance'],
    ];

    $lists = [
        [
            'title' => "Today's Gainers",
            'stocks' => [
                ['INVS', 'Invision Inc.', '+14.5%', 'invision'],
                ['FB', 'Facebook, Inc.', '+12.9%', 'facebook'],
                ['AMZN', 'Amazon.com, Inc.', '+10.2%', 'amazon'],
                ['SPCH', 'Snapchat, Inc.', '+10.2%', 'snapchat'],
            ],
        ],
        [
            'title' => 'Popular this week',
            'stocks' => [
                ['TWTR', 'Twitter Inc.', '+14.5%', 'twitter'],
                ['DOCKR', 'Docker Inc.', '+12.9%', 'docker'],
                ['ALGO', 'Algolia Inc.', '+10.2%', 'algolia'],
                ['GTHB', 'Github, Inc.', '+10.2%', 'github'],
            ],
        ],
    ];
@endphp

<div class="stock-dashboard" data-stock-dashboard>
    <section class="stock-graphs" aria-label="Featured stocks">
        @foreach ($graphs as $graph)
            <x-vuero.dashboard.stocks.graph-card
                :symbol="$graph['symbol']"
                :name="$graph['name']"
                :brand="$graph['brand']"
                :change="$graph['change']"
                :tone="$graph['tone']"
                :path="$graph['path']"
                :tail="$graph['tail']"
                :point-x="$graph['pointX']"
                :point-y="$graph['pointY']"
                :label-x="$graph['labelX']"
                :label-y="$graph['labelY']"
            />
        @endforeach
    </section>

    <nav class="stock-categories" aria-label="Stock categories">
        @foreach ($categories as [$icon, $label])
            <x-vuero.dashboard.stocks.category :icon="$icon" :label="$label" />
        @endforeach
    </nav>

    <section class="stock-lists" aria-label="Stock rankings">
        @foreach ($lists as $list)
            <x-vuero.dashboard.stocks.list-card :title="$list['title']" :stocks="$list['stocks']" />
        @endforeach
    </section>
</div>
