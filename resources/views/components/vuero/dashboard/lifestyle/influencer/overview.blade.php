@php
    $stats = [
        ['Content Posts (30 days)', '112', '-0.4%', 'less than usual.', 'media', 'purple', 'down'],
        ['Followers (30 days)', '14.3K', '22.7%', 'follower growth.', 'users', 'blue', 'up'],
        ['Following (30 days)', '44', '1.4%', 'following growth.', 'user-plus', 'yellow', 'up'],
        ['Likes (30 days)', '140.8K', '3.6%', 'likes growth.', 'heart', 'red', 'up'],
        ['Comments (30 days)', '9.3K', '-1.1%', 'less than usual.', 'message', 'yellow', 'down'],
        ['Profile Views (30 days)', '12.1K', '22.7%', 'more profile views.', 'eye', 'green', 'up'],
    ];
    $hashtags = [
        ['adobe', 1], ['animation', 2], ['arts', 3], ['bizarre', 4], ['blogs', 5], ['books', 6],
        ['comics', 1], ['computer-graphics', 2], ['cooking', 3], ['cyberculture', 4], ['design', 5], ['drawing', 6],
        ['fine-arts', 1], ['graphic-design', 2], ['humor', 3], ['illustration', 4], ['indesign', 5], ['internet', 6],
        ['movies', 1], ['mac-os', 2], ['multimedia', 3], ['online-games', 4], ['photography', 5], ['web-design', 6], ['css', 1],
    ];
@endphp

<section id="influencer-overview" class="lifestyle-tab-panel is-active" role="tabpanel" data-panel="overview">
    <header class="lifestyle-section-title">
        <h2>Instagram Stats</h2>
        <label class="lifestyle-select"><span class="sr-only">Statistics period</span><select data-influencer-period><option value="7">Last week</option><option value="30" selected>Last 30 days</option><option value="60">Last 60 days</option><option value="90">Last 90 days</option></select><x-vuero.icon name="chevron-right" size="15" /></label>
    </header>

    <div class="influencer-stat-grid" data-influencer-stats>
        @foreach ($stats as [$label, $value, $change, $description, $icon, $color, $direction])
            <article class="lifestyle-card influencer-stat-card" data-base-value="{{ $value }}">
                <header><h3>{{ $label }}</h3><span class="is-{{ $color }}"><x-vuero.icon :name="$icon" size="19" /></span></header>
                <strong>{{ $value }}</strong>
                <p class="is-{{ $direction }}"><b><x-vuero.icon name="arrow-right" size="13" /> {{ $change }}</b> {{ $description }}</p>
            </article>
        @endforeach
    </div>

    <div class="influencer-insight-grid">
        <div class="influencer-insight-stack">
            <article class="lifestyle-card influencer-reputation">
                <div><h3>Reputation Growth</h3><strong>+ 2,654</strong><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Bonum integritas corporis: misera debilitas. Ita ne hoc quidem modo paria.</p></div>
                <div class="lifestyle-ring is-reputation" style="--progress:65" role="img" aria-label="Reputation progress 65 percent"><span>65%</span></div>
            </article>
            <article class="lifestyle-card influencer-social">
                <h3>Social Points</h3>
                <div>
                    @foreach ([['Creativity','+ 234',31,'info'],['Engagement','+37%',53,'lime'],['Popularity','+82%',84,'green']] as [$label,$value,$progress,$tone])
                        <section><div class="lifestyle-ring is-small is-{{ $tone }}" style="--progress:{{ $progress }}" role="img" aria-label="{{ $label }} {{ $progress }} percent"></div><strong>{{ $value }}</strong><small>{{ $label }}</small></section>
                    @endforeach
                </div>
            </article>
            <article class="lifestyle-card influencer-growth">
                <header><h3>Influence Growth</h3></header>
                <div class="influencer-growth-radial" role="img" aria-label="Influence growth 65 percent">
                    <svg viewBox="0 0 120 120" aria-hidden="true"><path class="track" d="M28.9 28.9 A44 44 0 1 1 28.9 91.1" pathLength="100"/><path class="value" d="M28.9 28.9 A44 44 0 1 1 28.9 91.1" pathLength="100"/></svg><span>65%</span>
                </div>
            </article>
        </div>

        <article class="lifestyle-card influencer-hashtags">
            <h3>Recent Hashtags</h3>
            <div class="hashtag-cloud">
                @foreach ($hashtags as [$hashtag, $size])<button type="button" class="size{{ $size }}">{{ $hashtag }}</button>@endforeach
            </div>
        </article>
    </div>

    <article class="lifestyle-card influencer-additional">
        <h3>Additional Stats</h3>
        <div>
            @foreach ([['264','New Deals',31,'info'],['1,203','Proposals',53,'orange'],['3,078','Closed Deals',84,'lime']] as [$value,$label,$progress,$tone])
                <section><div class="lifestyle-ring is-{{ $tone }}" style="--progress:{{ $progress }}" role="img" aria-label="{{ $label }} {{ $progress }} percent"></div><p><strong>{{ $value }}</strong><small>{{ $label }}</small></p></section>
            @endforeach
        </div>
    </article>
</section>
