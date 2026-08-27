@php
    $avatar = fn (string $name) => "/vuero/images/admin/business/avatars/{$name}.svg";
    $people = [
        ['name' => 'Courtney W.', 'picture' => $avatar('12')],
        ['name' => 'Alice C.', 'picture' => $avatar('13')],
        ['name' => 'Erik K.', 'picture' => '/vuero/images/admin/avatars/vuero-1.svg'],
    ];
@endphp

<x-vuero.widgets.page :dashboard="$dashboard" :script="$dashboardScript">
    <div class="widget-grid widget-grid--four widget-creative-grid">
        <div class="widget-stack">
            <x-vuero.widgets.illustration-card picture="/vuero/images/illustrations/widgets/1.svg" class="widget-illustration--badged">
                <img class="widget-badge widget-badge--left" src="/vuero/images/illustrations/widgets/2.svg" alt="">
                <img class="widget-badge widget-badge--right" src="/vuero/images/illustrations/widgets/3.svg" alt="">
            </x-vuero.widgets.illustration-card>
            <x-vuero.widgets.contact-card />
            <x-vuero.widgets.illustration-card picture="/vuero/images/illustrations/widgets/7.svg" />
            <article class="widget-card widget-avatar-list-card" data-widget-card>
                @foreach ($people as $person)<button type="button" data-widget-person="{{ $person['name'] }}"><x-vuero.widgets.avatar :src="$person['picture']" :name="$person['name']" squared :size="42" /><span><strong>{{ $person['name'] }}</strong><small>Product team</small></span><x-vuero.icon name="chevron-right" size="14" /></button>@endforeach
            </article>
        </div>

        <div class="widget-stack">
            <x-vuero.widgets.illustration-card picture="/vuero/images/illustrations/widgets/4.svg" />
            <article class="widget-card widget-social-buttons" data-widget-card>
                @foreach ([['brand-facebook','Facebook'],['content','LinkedIn'],['twitter','Twitter'],['media','Instagram']] as $social)<button type="button" class="{{ $loop->first ? 'is-active' : '' }}" data-widget-social aria-label="{{ $social[1] }}"><x-vuero.icon :name="$social[0]" size="16" /></button>@endforeach
            </article>
            <article class="widget-card widget-avatar-list-card" data-widget-card>
                <label class="widget-people-search"><x-vuero.icon name="search" size="15" /><input type="search" placeholder="Search again..." data-widget-people-search></label>
                @foreach ($people as $person)<button type="button" data-widget-person="{{ $person['name'] }}"><x-vuero.widgets.avatar :src="$person['picture']" :name="$person['name']" :size="42" /><strong>{{ $person['name'] }}</strong></button>@endforeach
            </article>
            <x-vuero.widgets.card title="Followers" class="widget-follow-stats">
                @foreach ([['brand-facebook','22K','Facebook'],['twitter','38K','Twitter'],['media','5K','Instagram']] as $stat)<button type="button" data-widget-social><span><x-vuero.icon :name="$stat[0]" size="19" /></span><strong>{{ $stat[1] }}<small>{{ $stat[2] }}</small></strong></button>@endforeach
            </x-vuero.widgets.card>
            <article class="widget-card widget-cart" data-widget-card>
                <header><h2>Cart</h2><span data-cart-total>$298.00</span></header>
                @foreach ([['Fossil watch',149],['Leather watch',149]] as $product)<div class="widget-product"><span class="widget-watch"></span><strong>{{ $product[0] }}<small>${{ number_format($product[1], 2) }}</small></strong><div><button type="button" data-cart-minus>−</button><b data-cart-quantity>1</b><button type="button" data-cart-plus>+</button></div></div>@endforeach
                <button type="button" class="widget-primary-button widget-checkout" data-widget-checkout>Checkout</button>
            </article>
        </div>

        <div class="widget-stack">
            <x-vuero.widgets.card title="@cssninjaStudio" :menu="false" class="widget-tweet-card">
                <p class="widget-copy">Vuero will be released very soon, probably around the end of the year of the Christmas holidays.</p><p class="widget-hashtags">#uikit #dashboards #bulma</p>
            </x-vuero.widgets.card>
            <x-vuero.widgets.illustration-card picture="/vuero/images/illustrations/widgets/5.svg" class="widget-illustration--large" />
            <x-vuero.widgets.contact-card squared reversed />
            <article class="widget-card widget-social-buttons is-rounded" data-widget-card>
                @foreach ([['brand-facebook','Facebook'],['content','LinkedIn'],['twitter','Twitter'],['media','Instagram']] as $social)<button type="button" data-widget-social aria-label="{{ $social[1] }}"><x-vuero.icon :name="$social[0]" size="16" /></button>@endforeach
            </article>
            <x-vuero.widgets.card title="Designers" class="widget-designer-card is-squared">
                @foreach ($people as $person)<button type="button" data-widget-person="{{ $person['name'] }}"><x-vuero.widgets.avatar :src="$person['picture']" :name="$person['name']" squared :size="34" /><strong>{{ $person['name'] }}</strong></button>@endforeach
            </x-vuero.widgets.card>
        </div>

        <div class="widget-stack">
            <x-vuero.widgets.card title="Designers" class="widget-designer-card">
                @foreach ($people as $person)<button type="button" data-widget-person="{{ $person['name'] }}"><x-vuero.widgets.avatar :src="$person['picture']" :name="$person['name']" :size="34" /><strong>{{ $person['name'] }}</strong></button>@endforeach
            </x-vuero.widgets.card>
            <article class="widget-card widget-call-card" data-widget-card>
                <div class="widget-call-art"><img src="/vuero/images/illustrations/widgets/6.svg" alt="Call Alice"><x-vuero.widgets.avatar :src="$avatar('13')" name="Alice" :size="46" /></div>
                <h2 data-call-title>Call Alice?</h2>
                <div><button type="button" data-call-action="video" aria-label="Video"><x-vuero.icon name="video" size="17" /></button><button type="button" data-call-action="accept" aria-label="Call"><x-vuero.icon name="phone" size="17" /></button><button type="button" data-call-action="message" aria-label="Message"><x-vuero.icon name="message" size="17" /></button></div>
            </article>
            <x-vuero.widgets.illustration-card picture="/vuero/images/illustrations/widgets/8.svg" />
        </div>
    </div>
</x-vuero.widgets.page>
