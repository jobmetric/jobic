@php
    $avatar = fn (string $name) => "/vuero/images/admin/business/avatars/{$name}.svg";
@endphp

<x-vuero.widgets.page :dashboard="$dashboard" :script="$dashboardScript">
    <div class="widget-grid widget-grid--four widget-ui-grid">
        <div class="widget-stack">
            <x-vuero.widgets.card title="Gauge Widget" class="widget-gauge-card">
                <div class="widget-gauge" data-widget-gauge><span><strong>91.4%</strong></span></div>
                <p class="widget-centered-copy">Your score has been calculated<br>based on the latest metrics</p>
            </x-vuero.widgets.card>

            <x-vuero.widgets.card class="widget-followers-card" :menu="false">
                <x-slot:toolbar>
                    <h2>New Followers</h2>
                    <div class="widget-avatar-stack">
                        <x-vuero.widgets.avatar :src="$avatar('13')" name="Alice" :size="30" />
                        <x-vuero.widgets.avatar src="/vuero/images/admin/avatars/vuero-1.svg" name="Erik" :size="30" />
                        <x-vuero.widgets.avatar :src="$avatar('5')" name="Mary" :size="30" />
                    </div>
                </x-slot:toolbar>
                <p class="widget-copy">Great News! <strong>Alice</strong>, <strong>Erik</strong> and <strong>Mary</strong> are now following you. Take some time to look at their profile.</p>
            </x-vuero.widgets.card>

            <article class="widget-card widget-search-card" data-widget-card>
                <form data-widget-search><label><span class="sr-only">Search</span><input type="search" placeholder="Search..."><button type="submit" aria-label="Search"><x-vuero.icon name="search" size="18" /></button></label></form>
                <div class="widget-topics"><button>#Politics</button><button>#Movies</button><button>#Tech</button></div>
                <p class="widget-search-result" hidden data-widget-search-result></p>
            </article>

            <article class="widget-card widget-video-card" data-widget-card>
                <div class="widget-video-visual"><img src="/vuero/images/admin/dashboards/reading.svg" alt="Workspace"><button type="button" data-widget-play aria-label="Play video"><x-vuero.icon name="play" size="19" /></button><span><x-vuero.icon name="eye" size="14" />38,274</span></div>
            </article>

            <article class="widget-card widget-tags-card" data-widget-card><div class="widget-tags"><button>Family</button><button>Home</button><button>Health</button><button>Business</button><button>Personal</button><button>Utility</button></div></article>
        </div>

        <div class="widget-stack">
            <article class="widget-card widget-icon-toolbar" data-widget-card>
                @foreach (['activity', 'search', 'content', 'home'] as $icon)<button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-widget-icon-tab aria-label="{{ $icon }}"><x-vuero.icon :name="$icon" size="18" /></button>@endforeach
            </article>

            <article class="widget-card widget-days-card" data-widget-card>
                @foreach ([['22','Monday'],['23','Tuesday'],['24','Wednesday'],['25','Thursday']] as $day)<button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-widget-day><strong>{{ $day[0] }}</strong><small>{{ $day[1] }}</small></button>@endforeach
            </article>

            <article class="widget-card widget-calendar-card" data-widget-card data-widget-calendar>
                <header><button type="button" data-calendar-prev aria-label="Previous month"><x-vuero.icon name="chevron-left" size="15" /></button><h2 data-calendar-title>October 2020</h2><button type="button" data-calendar-next aria-label="Next month"><x-vuero.icon name="chevron-right" size="15" /></button></header>
                <div class="widget-calendar-week"><b>Mon</b><b>Tue</b><b>Wed</b><b>Thu</b><b>Fri</b><b>Sat</b><b>Sun</b></div>
                <div class="widget-calendar-days" data-calendar-grid></div>
            </article>

            <article class="widget-card widget-icon-list" data-widget-card>
                @foreach ([['mail','Messages','5 new messages'],['calendar','Calendar','3 upcoming events'],['users','Team','18 online members'],['settings','Settings','Account preferences']] as $item)
                    <button type="button" data-widget-list-action="{{ $item[1] }}"><span><x-vuero.icon :name="$item[0]" size="18" /></span><strong>{{ $item[1] }}<small>{{ $item[2] }}</small></strong><x-vuero.icon name="chevron-right" size="14" /></button>
                @endforeach
            </article>
        </div>

        <div class="widget-stack">
            <x-vuero.widgets.card title="Payment" :menu="false" class="widget-payment-card">
                <p class="widget-copy">You have an upcoming payment for your recurring subscription fee due on <strong>Sept 20, 2020</strong>.</p>
            </x-vuero.widgets.card>

            <article class="widget-card widget-image-card" data-widget-card>
                <div><img src="/vuero/images/admin/dashboards/interviews.svg" alt="Team at work"></div><progress value="55" max="100">55%</progress>
            </article>

            <x-vuero.widgets.card title="Inbox" class="widget-inbox-card">
                <button type="button" class="widget-inbox-message" data-widget-message>
                    <x-vuero.widgets.avatar :src="$avatar('13')" name="Alice C." :size="38" />
                    <span><strong>Alice C. just sent you an inbox message.</strong><small>2 minutes ago</small></span>
                </button>
            </x-vuero.widgets.card>
        </div>

        <div class="widget-stack">
            <x-vuero.widgets.card title="Labels" class="widget-label-card"><div class="widget-tags"><button>Family</button><button class="is-active">Home</button><button>Health</button><button>Business</button><button>Personal</button><button class="is-active">Utility</button></div></x-vuero.widgets.card>

            <x-vuero.widgets.card title="Labels" class="widget-notification-card">
                @foreach ([['Personal',4],['Business',9],['Family',2]] as $notice)<button type="button" data-widget-notification><span>{{ $notice[0] }}</span><b>{{ $notice[1] }}</b></button>@endforeach
            </x-vuero.widgets.card>

            <x-vuero.widgets.card title="" class="widget-trend-card">
                <div class="widget-trend-value"><strong>$1638,42 <x-vuero.icon name="trending-up" size="25" /></strong><span>Earned from Sales</span></div>
                <div class="widget-chart" data-widget-chart="trend"></div>
            </x-vuero.widgets.card>

            <x-vuero.widgets.card title="Messages" :menu="false" class="widget-message-card">
                <p class="widget-copy">You currently have more than <strong>10 unread messages</strong> in your inbox. It could be a good time to check them out.</p>
            </x-vuero.widgets.card>
        </div>
    </div>

    <div class="widget-lightbox" hidden data-widget-lightbox><button type="button" data-widget-lightbox-close aria-label="Close"><x-vuero.icon name="x" size="20" /></button><div><x-vuero.icon name="play" size="38" /><strong>Vuero media preview</strong></div></div>
</x-vuero.widgets.page>
