@props(['name' => 'Erik Kovalsky'])

@php
    $tiles = [
        ['Total Sales', '641.4K', 'award', 'info', 'M2,34 C12,28 19,36 29,25 S47,10 57,18 72,27 84,8 99,16 113,29 124,12 142,20'],
        ['Total Income', '$389.9K', 'dollar', 'purple', 'M2,30 C16,16 25,35 38,22 S58,11 69,28 84,34 96,13 109,18 126,25 142,7'],
        ['New Orders', '371', 'box', 'green', 'M2,31 C15,34 22,13 36,20 S54,37 66,25 81,8 94,16 108,35 121,21 142,25'],
        ['Abandonned', '29', 'briefcase', 'orange', 'M2,25 C17,9 28,28 41,24 S60,34 72,18 88,12 100,31 113,25 127,9 142,17'],
    ];
    $orders = [
        ['18.svg', 'Mary L.', '#158456', 'Oct 31, 2020', '863.42', 'paid', 'TR-7295'],
        ['22.svg', 'Jimmy H.', '#15893', 'Oct 31, 2020', '128.00', 'paid', 'TR-6259'],
        ['25.svg', 'Melany W.', '#155848', 'Oct 31, 2020', '236.79', 'pending', null],
        ['12.svg', 'Joshua S.', '#154736', 'Oct 30, 2020', '98.31', 'paid', 'TR-48951'],
        ['13.svg', 'Tara S.', '#158315', 'Oct 30, 2020', '112.00', 'paid', 'TR-48555'],
    ];
@endphp

<div class="ecommerce-v1" data-ecommerce="ecommerce-1">
    <header class="ecommerce-v1-header">
        <img src="{{ asset('vuero/images/avatars/svg/vuero-1.svg') }}" alt="{{ $name }}">
        <div><h1>Welcome back, {{ $name }}</h1><p>We're very happy to see you again on your dashboard.</p></div>
        <nav aria-label="Ecommerce actions"><button type="button" class="vuero-button" data-ecommerce-report>View Reports</button><button type="button" class="vuero-button is-primary" data-ecommerce-shop>Manage Shop</button></nav>
    </header>

    <section class="ecommerce-tile-grid" aria-label="Store overview">
        @foreach ($tiles as [$label, $value, $icon, $tone, $path])
            <article class="ecommerce-tile" data-ecommerce-tile>
                <header><h2>{{ $label }}</h2><span class="is-{{ $tone }}"><x-vuero.icon :name="$icon" size="19" /></span></header>
                <div><strong>{{ $value }}</strong><svg viewBox="0 0 144 44" preserveAspectRatio="none" aria-hidden="true"><path d="{{ $path }}" /></svg></div>
            </article>
        @endforeach
    </section>

    <section class="ecommerce-chart-grid" aria-label="Revenue charts">
        @foreach ([['line','M0,165 C45,155 56,125 96,137 S155,70 198,97 258,138 303,75 372,56 430,79 486,29 550,45'],['area','M0,178 C44,169 64,107 107,126 S168,157 208,100 272,53 318,89 381,132 425,71 482,29 550,39']] as [$type,$path])
            <article class="ecommerce-widget ecommerce-line-widget is-{{ $type }}">
                <header><h2>Revenue</h2><button type="button" data-ecommerce-widget-menu aria-label="Revenue options"><x-vuero.icon name="more" size="19" /></button></header>
                <div class="ecommerce-summary"><p><span>This Month</span><strong>$75,648.43</strong></p><p><span>Last Month</span><strong>$91,512.18</strong></p></div>
                <div class="ecommerce-line-chart" aria-label="Revenue trend chart">
                    <span>$100K</span><span>$75K</span><span>$50K</span><span>$25K</span>
                    <svg viewBox="0 0 550 205" preserveAspectRatio="none"><defs><linearGradient id="ecommerce-area-{{ $loop->index }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="currentColor" stop-opacity=".3"/><stop offset="1" stop-color="currentColor" stop-opacity="0"/></linearGradient></defs>@if($type === 'area')<path class="fill" d="{{ $path }} L550,205 L0,205Z" style="fill:url(#ecommerce-area-{{ $loop->index }})"/>@endif<path class="line" d="{{ $path }}" /></svg>
                </div>
            </article>
        @endforeach
    </section>

    <section class="ecommerce-chart-grid" aria-label="Sales revenue charts">
        <article class="ecommerce-widget ecommerce-flex-widget">
            <header><h2>Sales Revenue</h2><button type="button" data-ecommerce-widget-menu aria-label="Sales revenue options"><x-vuero.icon name="more" size="19" /></button></header>
            <div class="ecommerce-radial-layout">
                <div class="ecommerce-donut" style="--value:72"><span><strong>72%</strong><small>Completed</small></span></div>
                <div class="ecommerce-chart-legend"><p><i class="is-primary"></i><span>Online Sales</span><strong>$37,648</strong></p><p><i class="is-purple"></i><span>Retail Sales</span><strong>$21,357</strong></p><p><i class="is-orange"></i><span>Refunds</span><strong>$4,983</strong></p></div>
            </div>
        </article>
        <article class="ecommerce-widget ecommerce-flex-widget">
            <header><h2>Sales Revenue</h2><button type="button" data-ecommerce-widget-menu aria-label="Sales revenue options"><x-vuero.icon name="more" size="19" /></button></header>
            <div class="ecommerce-stripe-layout">
                @foreach ([['Online Sales',82,'$37,648'],['Retail Sales',67,'$21,357'],['Refunds',28,'$4,983']] as [$label,$value,$amount])
                    <div><p><span>{{ $label }}</span><strong>{{ $amount }}</strong></p><i><b style="--value:{{ $value }}%"></b></i></div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="ecommerce-chart-grid" aria-label="Additional store statistics">
        @foreach ([false,true] as $gauge)
            <article class="ecommerce-widget ecommerce-grouped-widget{{ $gauge ? ' is-gauge' : '' }}">
                <header><h2>Additional Stats</h2><button type="button" data-ecommerce-widget-menu aria-label="Additional statistics options"><x-vuero.icon name="more" size="19" /></button></header>
                <div>
                    @foreach ([['264','New Deals',74],['1,203','Proposals',58],['3,078','Closed Deals',86]] as [$value,$label,$progress])
                        <section><div class="ecommerce-mini-ring" style="--value:{{ $progress }}"><span>{{ $progress }}%</span></div><strong>{{ $value }}</strong><small>{{ $label }}</small></section>
                    @endforeach
                </div>
            </article>
        @endforeach
    </section>

    <section class="ecommerce-orders" data-ecommerce-orders>
        <header class="ecommerce-orders-header"><h2>Recent Orders</h2><label class="ecommerce-select"><span class="sr-only">Order period</span><select data-order-period><option value="7">Last week</option><option value="30" selected>Last 30 days</option><option value="60">Last 60 days</option><option value="90">Last 90 days</option></select><x-vuero.icon name="chevron-right" size="15" /></label></header>
        <div class="ecommerce-order-table">
            <div class="ecommerce-order-head" aria-hidden="true"><span>Customer</span><span>Date</span><span>Amount</span><span>Status</span><span>Tracking</span><span>Actions</span></div>
            @foreach ($orders as [$avatar,$name,$id,$date,$amount,$status,$tracking])
                <article data-order-row data-order-id="{{ $id }}" data-order-customer="{{ $name }}" data-order-amount="{{ $amount }}" data-order-status="{{ $status }}">
                    <div class="ecommerce-order-customer"><img src="{{ asset('vuero/images/admin/business/avatars/'.$avatar) }}" alt=""><p><strong>{{ $name }}</strong><small>{{ $id }}</small></p></div>
                    <time>{{ $date }}</time>
                    <strong class="ecommerce-order-amount">${{ $amount }}</strong>
                    <span class="ecommerce-order-status is-{{ $status }}">{{ ucfirst($status) }}</span>
                    @if ($tracking)<button type="button" class="ecommerce-tracking" data-order-tracking>{{ $tracking }}</button>@else<span class="ecommerce-tracking-na">N/A</span>@endif
                    <button type="button" class="vuero-button ecommerce-view-order" data-view-order>View Order</button>
                </article>
            @endforeach
            <p class="ecommerce-orders-empty" data-orders-empty hidden>No orders match this period.</p>
        </div>
    </section>
</div>
