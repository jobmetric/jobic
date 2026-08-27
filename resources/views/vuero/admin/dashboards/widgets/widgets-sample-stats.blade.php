@php
    $avatar = fn (string $name) => "/vuero/images/admin/business/avatars/{$name}.svg";
    $members = [
        ['name' => 'Tina Dasilva', 'picture' => $avatar('12'), 'membership' => 'Apr 2019', 'income' => '7,854.32', 'status' => 'Approved'],
        ['name' => 'John Wistmus', 'picture' => $avatar('15'), 'membership' => 'Jun 2018', 'income' => '5,435.49', 'status' => 'Approved'],
        ['name' => 'Sam Watson', 'picture' => $avatar('32'), 'membership' => 'Jun 2020', 'income' => '824.67', 'status' => 'Pending'],
        ['name' => 'Jolaine Joestar', 'picture' => $avatar('21'), 'membership' => 'Feb 2020', 'income' => '261.12', 'status' => 'Suspended'],
        ['name' => 'Anders Jensen', 'picture' => $avatar('31'), 'membership' => 'Nov 2020', 'income' => '4,231.67', 'status' => 'Approved'],
    ];
@endphp

<x-vuero.widgets.page :dashboard="$dashboard" :script="$dashboardScript">
    <div class="widget-stats-layout">
        <div class="widget-spark-grid">
            @foreach ([['Total Sales','9,374','primary'],['Total Profit','$24,273.31','info'],['Total Orders','4,361','purple'],['Consolidated Profit','$16,264.37','violet']] as $stat)
                <article class="widget-card widget-spark-card is-{{ $stat[2] }}" data-widget-card><span>{{ $stat[0] }}</span><strong>{{ $stat[1] }}</strong><div class="widget-chart" data-widget-chart="spark-{{ $loop->iteration }}"></div></article>
            @endforeach
        </div>

        <div class="widget-stats-pair">
            @foreach ([['line-revenue','This Month','$75,648.43','Last Month','$91,512.18'],['area-revenue','This Month','$75,648.43','Last Month','$91,512.18']] as $chart)
                <article class="widget-card widget-line-stat" data-widget-card><h2>Revenue</h2><div class="widget-stat-summary"><span>{{ $chart[1] }}<strong>{{ $chart[2] }}</strong></span><span>{{ $chart[3] }}<strong>{{ $chart[4] }}</strong></span></div><div class="widget-chart" data-widget-chart="{{ $chart[0] }}"></div></article>
            @endforeach
        </div>

        <div class="widget-stats-pair">
            <article class="widget-card widget-flex-stat" data-widget-card><div><h2>Sales Revenue</h2><strong>$9,641.26</strong><p><b>+5.1%</b> since last month</p><button type="button" data-widget-details>View Reports</button></div><div class="widget-chart" data-widget-chart="radial-circle"></div></article>
            <article class="widget-card widget-flex-stat" data-widget-card><div><h2>Sales Revenue</h2><strong>$9,641.26</strong><p><b>+5.1%</b> since last month</p><button type="button" data-widget-details>View Reports</button></div><div class="widget-chart" data-widget-chart="radial-stripes"></div></article>
        </div>

        <div class="widget-stats-pair">
            @foreach (['circle','gauge'] as $group)
                <article class="widget-card widget-group-stat" data-widget-card><h2>Additional Stats</h2><div>
                    @foreach ([['264','New Deals'],['1,203','Proposals'],['3,078','Closed Deals']] as $metric)<section><div class="widget-chart" data-widget-chart="group-{{ $group }}-{{ $loop->iteration }}"></div><strong>{{ $metric[0] }}</strong><span>{{ $metric[1] }}</span></section>@endforeach
                </div></article>
            @endforeach
        </div>

        <div class="widget-social-stat-grid">
            @foreach (['plain','squared','colored'] as $variant)<article class="widget-card widget-social-stat is-{{ $variant }}" data-widget-card><span><x-vuero.icon name="twitter" size="26" /></span><strong>138K<small>Followers</small></strong><b>+12.6%</b></article>@endforeach
        </div>

        <x-vuero.widgets.member-table :members="$members" squared />
        <x-vuero.widgets.member-table :members="$members" />
    </div>
</x-vuero.widgets.page>
