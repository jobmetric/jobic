@php
    $heatmap = [
        'Mon' => [8 => 1, 10 => 1, 11 => 3, 14 => 1],
        'Tue' => [9 => 1, 11 => 1, 12 => 1, 17 => 1],
        'Wed' => [8 => 1, 9 => 1, 10 => 1, 15 => 1, 16 => 1, 21 => 1],
        'Thu' => [7 => 1, 9 => 1, 10 => 1, 12 => 1, 13 => 2, 15 => 3],
        'Fri' => [8 => 1, 16 => 3, 17 => 1, 20 => 2],
        'Sat' => [9 => 1, 12 => 2, 19 => 2, 20 => 1],
        'Sun' => [12 => 1, 13 => 1, 16 => 1, 17 => 2, 20 => 2],
    ];
    $habits = [
        ['Post Day', 'Monday', 'Best day to post.', 'calendar', 'green'],
        ['Hashtags', '#Music', 'Most used hashtag.', 'content', 'info'],
        ['Filters', 'Normal', 'Most used filter.', 'media', 'orange'],
    ];
    $payouts = [
        ['Avg. Payout per Post', '$640.42', '+0.8%', 'more than usual.', 'up'],
        ['Lowest Payout', '$451.32', '-0.2%', 'less than usual.', 'down'],
        ['Highest Payout', '$731.87', '+1.2%', 'more than usual.', 'up'],
        ['Lifetime Earnings', '$23.4K', '+4.2%', 'more than usual.', 'up'],
    ];
    $postGroups = [
        ['Most Liked Posts', '15.9K', 'heart', 'red', 'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=900&q=82'],
        ['Most Commented Posts', '2.3K', 'message', 'yellow', 'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=900&q=82'],
        ['Most Engaging Posts', '51.6%', 'activity', 'green', 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=900&q=82'],
    ];
    $thumbnails = [
        'https://images.unsplash.com/photo-1518609878373-06d740f60d8b?auto=format&fit=crop&w=300&q=75',
        'https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=300&q=75',
        'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=300&q=75',
        'https://images.unsplash.com/photo-1516280440614-37939bbacd81?auto=format&fit=crop&w=300&q=75',
    ];
@endphp

<section id="influencer-content" class="lifestyle-tab-panel influencer-content-panel" role="tabpanel" data-panel="content" hidden>
    <header class="lifestyle-section-title influencer-content-title">
        <h2>Content Stats</h2>
        <label class="lifestyle-select">
            <span class="sr-only">Content statistics period</span>
            <select data-content-period>
                <option value="7">Last week</option>
                <option value="30" selected>Last 30 days</option>
                <option value="60">Last 60 days</option>
                <option value="90">Last 90 days</option>
            </select>
            <x-vuero.icon name="chevron-right" size="15" />
        </label>
    </header>

    <article class="lifestyle-card influencer-heatmap-card">
        <header><h3>Posting Habits</h3></header>
        <div class="influencer-heatmap-intro">
            <img src="{{ asset('vuero/images/admin/business/avatars/15.svg') }}" alt="Clarissa Geller">
            <p>Clarissa generally posts media on: <strong>Mondays at 11am</strong>, <strong>Thursdays at 3pm</strong>, <strong>Fridays at 4pm</strong> ...</p>
        </div>
        <div class="influencer-heatmap-scroll" tabindex="0" aria-label="Posting habits heatmap">
            <div class="influencer-heatmap" data-content-heatmap>
                @foreach ($heatmap as $day => $hours)
                    <div class="influencer-heatmap-row">
                        <span class="influencer-heatmap-day">{{ $day }}</span>
                        <div>
                            @for ($hour = 0; $hour < 24; $hour++)
                                @php($level = $hours[$hour] ?? 0)
                                <button type="button" class="heat-{{ $level }}" data-day="{{ $day }}" data-hour="{{ $hour }}" aria-label="{{ $day }} at {{ $hour === 0 ? '12am' : ($hour < 12 ? $hour.'am' : ($hour === 12 ? '12pm' : ($hour - 12).'pm')) }}{{ $level ? ', activity level '.$level : '' }}">
                                    @if ($level === 3)<x-vuero.icon name="flame" size="11" />@endif
                                </button>
                            @endfor
                        </div>
                    </div>
                @endforeach
                <div class="influencer-heatmap-row is-hours" aria-hidden="true">
                    <span class="influencer-heatmap-day"></span>
                    <div>
                        @for ($hour = 0; $hour < 24; $hour++)
                            <span>{{ $hour === 0 ? '12am' : ($hour < 12 ? $hour.'am' : ($hour === 12 ? '12pm' : ($hour - 12).'pm')) }}</span>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </article>

    <div class="influencer-content-habits">
        @foreach ($habits as [$label, $value, $description, $icon, $tone])
            <article class="lifestyle-card influencer-content-habit">
                <header><h3>{{ $label }}</h3><span class="is-{{ $tone }}"><x-vuero.icon :name="$icon" size="19" /></span></header>
                <strong>{{ $value }}</strong>
                <p>{{ $description }}</p>
            </article>
        @endforeach
    </div>

    <article class="lifestyle-card influencer-payouts" data-content-payouts>
        @foreach ($payouts as [$label, $value, $change, $description, $direction])
            <section data-base-amount="{{ $value }}">
                <h3>{{ $label }}</h3>
                <strong>{{ $value }}</strong>
                <p class="is-{{ $direction }}"><b><x-vuero.icon name="arrow-right" size="13" /> {{ $change }}</b> {{ $description }}</p>
            </section>
        @endforeach
    </article>

    <div class="influencer-content-posts">
        @foreach ($postGroups as [$title, $value, $icon, $tone, $image])
            <article class="lifestyle-card influencer-post influencer-content-post">
                <header><span>{{ $title }}</span><strong class="is-{{ $tone }}"><x-vuero.icon :name="$icon" size="20" /> {{ $value }}</strong></header>
                <button type="button" data-post-preview aria-label="Open {{ $title }} preview"><img src="{{ $image }}" alt="{{ $title }}"></button>
                <div>
                    @foreach ($thumbnails as $thumbnail)
                        <button type="button" data-content-thumbnail aria-label="Preview post"><img src="{{ $thumbnail }}" alt=""></button>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>
</section>
