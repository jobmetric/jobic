@switch($dashboard['slug'])
    @case('analytics')
        <div class="analytics-reference">
            <div class="analytics-reference__main">
                <div class="dashboard-grid dashboard-grid--3">
                    @foreach ([['Transactions','8,637','+6.4%','primary','diamond'],['Subscriptions','1,378','-2.1%','orange','user-plus'],['Referals','1,911','+4.2%','green','megaphone']] as $metric)
                        <x-vuero.dashboard.card class="metric-tile"><header><h2>{{ $metric[0] }}</h2><span class="dashboard-stat__icon dashboard-stat--{{ $metric[3] }}"><x-vuero.icon :name="$metric[4]" size="19" /></span></header><strong>{{ $metric[1] }}</strong><p class="{{ str_starts_with($metric[2], '+') ? 'is-positive' : 'is-negative' }}">{{ $metric[2] }} ↗ <small>since last month</small></p></x-vuero.dashboard.card>
                    @endforeach
                </div>
                <div class="dashboard-grid analytics-main-grid">
                    <x-vuero.dashboard.card title="Revenue"><div class="summary-pair"><p>This Month<strong class="is-positive">$75,648.43</strong></p><p>Last Month<strong>$91,512.18</strong></p></div><x-vuero.dashboard.chart height="238" /></x-vuero.dashboard.card>
                    <x-vuero.dashboard.card title="Goal Overview" class="gauge-card"><div class="dashboard-gauge" style="--progress:71.5"><span><small>Total</small><b>71.5%</b></span></div><div class="split-footer"><p>Completed<strong>1,223</strong></p><p>In Progress<strong>467</strong></p></div></x-vuero.dashboard.card>
                </div>
                <div class="dashboard-grid analytics-bottom-grid"><x-vuero.dashboard.card title="Sales"><strong class="big-number">78%</strong><div class="dashboard-progress"><i style="width:78%"></i></div><p class="is-positive">+6.4% ↗ <small>since last month</small></p></x-vuero.dashboard.card><x-vuero.dashboard.card title="Profit"><div class="chart-legend"><span>Net Profit</span><span>Revenue</span><span>Free Cash Flow</span></div><x-vuero.dashboard.chart type="bars" /></x-vuero.dashboard.card></div>
            </div>
            <aside class="analytics-reference__rail"><x-vuero.dashboard.card><div class="profile-summary"><div><h2>Erik K.</h2><a>Vuero Ltd.</a><small>Product Manager</small></div><img src="{{ asset('vuero/images/avatars/svg/vuero-1.svg') }}" alt="Erik K."></div><p class="soft-copy">⌖ Los Angeles, CA<br>☎ +1 444-5156<br><span class="is-positive">erikkovalsky@vuero.io</span></p></x-vuero.dashboard.card><x-vuero.dashboard.card><x-vuero.dashboard.calendar /></x-vuero.dashboard.card><x-vuero.dashboard.card title="New Followers"><p class="soft-copy">Great News! Alice, Erik and Mary are now following you. Take some time to look at their profile.</p></x-vuero.dashboard.card></aside>
        </div>
        @break

    @case('stocks')
        <x-vuero.dashboard.stocks.dashboard />
        @break

    @case('sales')
        <header class="sales-welcome"><div><h1>Welcome back, Erik.</h1><p>We're very happy to see you again.</p></div><div class="dashboard-actions"><button class="vuero-button">View Reports</button><button class="vuero-button is-primary">Manage Store</button></div></header>
        <div class="dashboard-grid dashboard-grid--4 sales-metrics">
            @foreach ([['Total Sales','$23,461.41','+4.2%'],['Total Profit','$14,095.51','+1.8%'],['New Orders','1,243','+8.9%'],['Consolidated Profit','$9,756.11','+2.3%']] as $metric)<x-vuero.dashboard.card><span>{{ $metric[0] }}</span><strong>{{ $metric[1] }}</strong><small class="is-positive">{{ $metric[2] }} since last month</small></x-vuero.dashboard.card>@endforeach
        </div>
        <div class="dashboard-grid sales-main-grid">
            <x-vuero.dashboard.card title="Revenue Overview"><div class="summary-pair"><p>This Month<strong class="is-positive">$75,648.43</strong></p><p>Last Month<strong>$91,512.18</strong></p></div><x-vuero.dashboard.chart height="245" /></x-vuero.dashboard.card>
            <div class="dashboard-stack"><x-vuero.dashboard.card title="Personal Score"><div class="dashboard-gauge" style="--progress:84"><span><b>84%</b>Great score</span></div></x-vuero.dashboard.card><x-vuero.dashboard.card title="Orders Summary"><div class="summary-pair"><p>Completed<strong>1,223</strong></p><p>Pending<strong>467</strong></p></div></x-vuero.dashboard.card></div>
        </div>
        <div class="dashboard-grid dashboard-grid--2"><x-vuero.dashboard.card title="Sales Revenue"><div class="chart-legend"><span>Sales</span><span>Profit</span></div><x-vuero.dashboard.chart type="bars" /></x-vuero.dashboard.card><x-vuero.dashboard.card title="Additional Stats"><div class="quick-stat-grid"><x-vuero.dashboard.stat label="Conversion" value="43%" /><x-vuero.dashboard.stat label="Customers" value="7,841" tone="purple" /><x-vuero.dashboard.stat label="Refunds" value="32" tone="orange" /><x-vuero.dashboard.stat label="Growth" value="+18%" tone="green" /></div></x-vuero.dashboard.card></div>
        @break
@endswitch
