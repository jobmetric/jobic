@php
    $summary = [['900 kcal','Burnt today'],['2300 kcal','Eaten today'],['10,864','Steps walked'],['2% fat','Burnt today'],['8.4 km','Distance today']];
    $vitals = [
        ['droplet','114/90','Min/Max','Blood'], ['heart','112','Bpm','Heart Rate'], ['activity','12/14','units','Blood Pressure'], ['users','60.4','lbs','Weight'],
    ];
    $charts = [
        ['Overall Condition','Your overall condition is very good. Make it even better.','line','M0,114 C55,96 75,104 115,78 S200,93 240,59 S330,79 375,44 S455,51 520,25 S585,27 640,12'],
        ['Energy Report','Your energy seems a bit unstable. You can improve it.','bars',''],
        ['Oxygenation','Your oxygen seems a bit unstable. You can improve it.','area','M0,95 C45,82 74,103 118,76 S205,46 258,65 S336,90 390,58 S482,34 536,51 S600,34 640,19'],
        ['Overall Progress','Your overall condition is very good. Make it even better.','radial',''],
    ];
@endphp

<div class="lifestyle-page lifestyle-health" data-lifestyle="health">
    <section class="health-illustration">
        <div class="health-illustration__image"><img src="{{ asset('vuero/images/admin/dashboards/doctor.svg') }}" alt="Health assistant"></div>
        <div><h1>Today's Summary</h1><p>Monitor your activity and keep improving your weak points.</p><div class="health-summary-stats">@foreach($summary as [$value,$label])<span><strong>{{ $value }}</strong><small>{{ $label }}</small></span>@endforeach</div></div>
    </section>

    <section class="health-vital-grid">
        @foreach($vitals as [$icon,$value,$unit,$label])
            <article class="health-vital" data-health-vital><header><span><x-vuero.icon :name="$icon" size="20" /></span><p><strong>{{ $value }}</strong><small>{{ $unit }}</small></p></header><h2>{{ $label }}</h2><p>Lorem ipsum dolor sit amet, consectetur adipiscing elit illis videtur.</p></article>
        @endforeach
    </section>

    <div class="health-content-grid">
        <main class="health-chart-grid">
            @foreach($charts as [$title,$copy,$type,$path])
                <article class="lifestyle-card health-chart-card" data-health-chart="{{ $type }}">
                    <header><h2>{{ $title }}</h2><button type="button" data-lifestyle-menu aria-label="{{ $title }} options"><x-vuero.icon name="more" size="18" /></button></header>
                    <p class="health-chart-copy"><x-vuero.icon name="help-circle" size="15" /> {{ $copy }} <button type="button" data-health-tip>Read how</button></p>
                    <div class="health-chart" aria-label="{{ $title }} chart">
                        @if($type === 'bars')
                            <div class="health-bars">@foreach([46,64,38,80,57,88,61,72,49,84,67,92] as $bar)<i style="--height:{{ $bar }}%"></i>@endforeach</div><span class="health-chart-average">average</span>
                        @elseif($type === 'radial')
                            <div class="health-progress-ring lifestyle-ring" style="--progress:84"><span><strong>84%</strong><small>Complete</small></span></div>
                        @else
                            <svg viewBox="0 0 640 145" preserveAspectRatio="none"><defs><linearGradient id="health-fill-{{ $loop->index }}" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#41b883" stop-opacity=".35"/><stop offset="1" stop-color="#41b883" stop-opacity="0"/></linearGradient></defs><path class="area" style="fill:url(#health-fill-{{ $loop->index }})" d="{{ $path }} L640,145 L0,145Z"/><path class="line" d="{{ $path }}"/></svg>
                        @endif
                        <div class="health-axis"><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span><span>Sun</span></div>
                    </div>
                </article>
            @endforeach
        </main>

        <aside class="health-sidebar">
            <section class="lifestyle-card health-search"><label><input type="search" placeholder="Search..." data-health-search><button type="button" aria-label="Search health resources" data-health-submit><x-vuero.icon name="search" size="18" /></button></label><div>@foreach(['Health','Cardio','Nutrition'] as $tag)<button type="button" data-health-topic>{{ '#'.$tag }}</button>@endforeach</div></section>
            <section class="lifestyle-card health-calendar"><x-vuero.dashboard.calendar /></section>
            <section class="lifestyle-card health-goals"><header><h2>Daily Goals</h2><button type="button" data-lifestyle-menu aria-label="Daily goal options"><x-vuero.icon name="more" size="18" /></button></header>@foreach([['Water','6 of 8 glasses',75,'droplet'],['Steps','10,864 of 12,000',91,'activity'],['Sleep','6.5 of 8 hours',81,'moon']] as [$label,$value,$progress,$icon])<div><span><x-vuero.icon :name="$icon" size="17" /></span><p><strong>{{ $label }}</strong><small>{{ $value }}</small><i><b style="width:{{ $progress }}%"></b></i></p></div>@endforeach</section>
        </aside>
    </div>
</div>
