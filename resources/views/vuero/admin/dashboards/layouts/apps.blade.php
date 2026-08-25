@switch($dashboard['slug'])
    @case('food-delivery')
        <div class="food-layout"><main><section class="food-hero"><img src="{{ asset('vuero/images/admin/dashboards/food-header.svg') }}" alt="Food delivery"><div><h1>$0 delivery for 30 days! 🎉</h1><p>$0 delivery fee for orders over $10 for 30 days.</p><button>LEARN MORE &nbsp; →</button></div></section><header class="food-title"><h2>Restaurants</h2><div><button>‹</button><button>›</button></div></header><div class="food-categories">@foreach ([['icon-1','Fish'],['icon-2','All'],['icon-3','Pizza'],['icon-4','Asian'],['icon-5','Fast'],['icon-6','Grills'],['icon-7','Cakes'],['icon-8','Thai']] as $category)<button><span><img src="{{ asset('vuero/images/icons/food/'.$category[0].'.svg') }}"></span>{{ $category[1] }}</button>@endforeach</div><div class="restaurant-grid">@foreach ([['Mario\'s place','Pizza','4.9','45'],['Jimmy\'s Downtown','Fast','4.5','30'],['Fruity Loops','Fruits','4.8','45'],['Thai Garden','Thai','4.7','25'],['Green Grill','Grills','4.6','35'],['Cake House','Cakes','4.9','20']] as $restaurant)<article><span class="delivery-time"><b>{{ $restaurant[3] }}</b>MIN</span><div class="restaurant-image"></div><footer><span class="food-mini-icon">◉</span><p><strong>{{ $restaurant[0] }}</strong><small>{{ $restaurant[1] }} &nbsp; • &nbsp; ⭐ {{ $restaurant[2] }}</small></p></footer></article>@endforeach</div></main><aside><nav><button class="is-active">▽</button><button>⌁</button><button>⌖</button><button>⚙</button></nav><x-vuero.dashboard.card class="order-card"><header><h2>My Order</h2><span>0 items</span></header><div class="empty-order"><strong>🛒</strong><h3>No Items</h3><p>Your cart is currently empty. Start adding products.</p></div><footer><p>TOTAL<strong>$0.00</strong></p><button class="vuero-button is-primary">Start Checkout</button></footer></x-vuero.dashboard.card></aside></div>
        @break

    @case('messaging-v1')
        <x-vuero.dashboard.apps.messaging-v1 />
        @break

    @case('messaging-v2')
        <x-vuero.dashboard.apps.messaging-v2 />
        @break
@endswitch
