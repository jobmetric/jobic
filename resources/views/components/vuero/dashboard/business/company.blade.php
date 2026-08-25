@php
    $stats = [
        ['icon' => 'users', 'value' => '162', 'label' => 'New Users'],
        ['icon' => 'diamond', 'value' => '$1,835.41', 'label' => 'Daily Income'],
        ['icon' => 'briefcase', 'value' => '378', 'label' => 'Completed Projects'],
        ['icon' => 'ticket', 'value' => '192', 'label' => 'Active Tickets'],
    ];
    $tickets = [
        ['avatar' => '31', 'title' => '[#45651] Cannot save changes to user profile', 'copy' => 'I am not able to save changes I make to my user profile. When I click on the save button, it simply says failed.', 'time' => 'Updated 5 hours ago'],
        ['avatar' => '23', 'title' => '[#45783] Cannot create a new opportunity', 'copy' => 'When I try to create a new opportunity, I am redirected to a 404 page after clicking the action button.', 'time' => 'Updated 2 hours ago'],
        ['avatar' => '32', 'title' => '[#45723] Payment fails when using PayPal', 'copy' => 'When I try to use PayPal as a payment method, it spins forever and I get an error message after that.', 'time' => 'Updated 30 minutes ago'],
        ['avatar' => '13', 'title' => '[#45862] Cannot find the assets in the theme folder', 'copy' => 'I followed the documentation but I am not able to locate the assets in the main folder. Can I get some help?', 'time' => 'Updated 6 hours ago'],
    ];
@endphp

<div class="business-dashboard company-dashboard" data-business-dashboard="company">
    <section class="company-kpis" aria-label="Company metrics">
        @foreach ($stats as $stat)
            <article class="company-kpi">
                <x-vuero.icon :name="$stat['icon']" size="29" />
                <strong>{{ $stat['value'] }}</strong>
                <span>{{ $stat['label'] }}</span>
            </article>
        @endforeach
    </section>

    <section class="company-overview-grid">
        <article class="dashboard-card company-profile-card">
            <div class="business-avatar business-avatar--xl business-avatar--brand">
                <img src="{{ asset('vuero/images/admin/business/brands/udemy.svg') }}" alt="U Learning">
                <button type="button" class="business-icon-button" data-company-follow aria-label="Follow U Learning"><x-vuero.icon name="plus" size="16" /></button>
            </div>
            <h2>U Learning</h2>
            <p class="company-profile-card__type">Online Courses</p>
            <p class="company-profile-card__description">Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed fac ista esse non inportuna.</p>
            <footer class="company-profile-card__stats">
                <span>Posts<strong>864</strong></span><span>Projects<strong>261</strong></span><span>Followers<strong data-company-followers>32K</strong></span>
            </footer>
        </article>
        <article class="dashboard-card company-chart-card">
            <div class="company-chart-card__stats"><span>New<strong class="is-primary">153</strong></span><span>Renewals<strong>641</strong></span><span>Resigns<strong>64</strong></span></div>
            <x-vuero.dashboard.chart tone="purple" height="235" />
        </article>
        <article class="dashboard-card company-chart-card">
            <div class="company-chart-card__stats"><span>Facebook<strong class="is-primary">64K</strong></span><span>Instagram<strong>78K</strong></span><span>Twitter<strong>25K</strong></span></div>
            <x-vuero.dashboard.chart type="bars" height="235" />
        </article>
    </section>

    <section class="company-widgets-grid">
        <article class="dashboard-card company-gauge-card">
            <header class="business-card-heading"><h2>Gauge Widget</h2><button type="button" class="business-more-button" data-business-menu aria-label="Gauge options"><x-vuero.icon name="more" size="18" /></button></header>
            <div class="dashboard-gauge" style="--progress: 72"><span><b>72%</b>Growth</span></div>
            <p>Your score has been calculated based on the latest metrics</p>
        </article>
        <article class="dashboard-card company-subscriptions-card">
            <header class="business-card-heading"><h2>Subscriptions</h2></header>
            <x-vuero.dashboard.chart type="bars" tone="purple" height="235" />
        </article>
        <article class="dashboard-card company-calendar-card"><x-vuero.dashboard.calendar /></article>
    </section>

    <section class="dashboard-card company-tickets-card">
        <header class="business-card-heading"><h2>Pending Tickets</h2><button type="button" class="business-action-link" data-ticket-view-all>View All</button></header>
        <div class="company-ticket-list">
            @foreach ($tickets as $ticket)
                <article class="company-ticket" data-ticket>
                    <img src="{{ asset('vuero/images/admin/business/avatars/'.$ticket['avatar'].'.svg') }}" alt="">
                    <div><h3>{{ $ticket['title'] }}</h3><p>{{ $ticket['copy'] }}</p><span>{{ $ticket['time'] }}</span></div>
                    <button type="button" class="vuero-button is-primary" data-ticket-manage>Manage</button>
                </article>
            @endforeach
        </div>
    </section>
</div>
