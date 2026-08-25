@php
    $flights = [
        ['logo' => 'company1.svg', 'start' => '11:30 am', 'end' => '06:59 pm', 'price' => 374, 'stops' => 1, 'route' => '1 stop', 'luggage' => 1],
        ['logo' => 'company2.svg', 'start' => '09:30 am', 'end' => '04:18 pm', 'price' => 392, 'stops' => 1, 'route' => '1 stop', 'luggage' => 2],
        ['logo' => 'company1.svg', 'start' => '06:42 am', 'end' => '02:11 pm', 'price' => 398, 'stops' => 0, 'route' => 'direct', 'luggage' => 1],
        ['logo' => 'company3.svg', 'start' => '07:23 am', 'end' => '01:46 pm', 'price' => 407, 'stops' => 0, 'route' => 'direct', 'luggage' => 0],
        ['logo' => 'company1.svg', 'start' => '10:12 am', 'end' => '03:39 pm', 'price' => 431, 'stops' => 1, 'route' => '1 stop', 'luggage' => 2],
    ];
@endphp

<div class="business-dashboard flights-dashboard" data-business-dashboard="flights">
    <div class="flights-page-grid">
        <main class="flights-main">
            <section class="flight-booking-card">
                <img src="{{ asset('vuero/images/admin/business/flights/travel.svg') }}" alt="" class="flight-travel-illustration flight-travel-illustration--light">
                <img src="{{ asset('vuero/images/admin/business/flights/travel-dark.svg') }}" alt="" class="flight-travel-illustration flight-travel-illustration--dark">
                <div class="flight-booking-heading">
                    <x-vuero.icon name="plane" size="36" />
                    <div><h1>Paris <small>[PAR]</small> - New York <small>[NY]</small></h1><p>1 adult - Business</p></div>
                </div>
                <form class="flight-date-form" data-flight-date-form>
                    <label><x-vuero.icon name="calendar" size="17" /><input type="date" value="2026-08-23" aria-label="Departure date" data-flight-departure></label>
                    <label><x-vuero.icon name="calendar" size="17" /><input type="date" value="2026-08-30" aria-label="Return date" data-flight-return></label>
                </form>
            </section>

            <header class="flights-toolbar">
                <h2><span data-flight-count>{{ count($flights) }}</span> results</h2>
                <div class="business-dropdown" data-business-dropdown>
                    <button type="button" class="business-more-button" aria-haspopup="menu" aria-expanded="false" data-business-dropdown-trigger><x-vuero.icon name="more" size="20" /></button>
                    <div class="business-dropdown__menu business-dropdown__menu--media" role="menu">
                        <button type="button" role="menuitem" data-flight-action="reload"><x-vuero.icon name="refresh" size="18" /><span><strong>Reload</strong><small>Refresh search results</small></span></button>
                        <button type="button" role="menuitem" data-flight-action="save"><x-vuero.icon name="download" size="18" /><span><strong>Save</strong><small>Save this search</small></span></button>
                        <hr>
                        <button type="button" role="menuitem" data-flight-action="settings"><x-vuero.icon name="settings" size="18" /><span><strong>Settings</strong><small>Configuration options</small></span></button>
                    </div>
                </div>
            </header>

            <div class="flight-summary-grid" data-flight-summary>
                <button type="button" data-flight-deal="cheapest"><strong>312</strong><span><b>Cheapest</b>7h32min</span></button>
                <button type="button" class="is-active" data-flight-deal="best"><strong>428</strong><span><b>Best</b>7h16min</span></button>
                <button type="button" data-flight-deal="fastest"><strong>549</strong><span><b>Fastest</b>5h36min</span></button>
            </div>

            <div class="flight-results" data-flight-results>
                @foreach ($flights as $flight)
                    <button type="button" class="dashboard-card flight-result-card" data-flight-result data-price="{{ $flight['price'] }}" data-stops="{{ $flight['stops'] }}" data-luggage="{{ $flight['luggage'] }}" data-search="paris new york orly jfk {{ $flight['start'] }} {{ $flight['end'] }}">
                        <img src="{{ asset('vuero/images/admin/business/flights/'.$flight['logo']) }}" alt="Airline">
                        <span class="flight-result-card__time"><strong>{{ $flight['start'] }}</strong><small>Paris ORLY</small><small>Monday, Jul 7th</small></span>
                        <span class="flight-result-card__route"><i></i><b data-content="{{ $flight['route'] }}"></b><x-vuero.icon name="plane" size="27" /></span>
                        <span class="flight-result-card__time"><strong>{{ $flight['end'] }}</strong><small>New York JFK</small><small>Monday, Jul 7th</small></span>
                        <span class="flight-result-card__price">{{ $flight['price'] }}</span>
                    </button>
                @endforeach
                <p class="business-empty-state" data-flight-empty hidden>No flights match the selected filters.</p>
            </div>
        </main>

        <aside class="flights-sidebar">
            <section class="dashboard-card flight-search-widget">
                <label><input type="search" placeholder="Search..." data-flight-search><button type="button" data-flight-search-button aria-label="Search flights"><x-vuero.icon name="search" size="18" /></button></label>
                <div><button type="button" data-flight-topic="Paris">#Paris</button><button type="button" data-flight-topic="Berlin">#Berlin</button><button type="button" data-flight-topic="Chicago">#Chicago</button></div>
            </section>
            <section class="dashboard-card flight-calendar-widget"><x-vuero.dashboard.calendar /></section>
            <section class="dashboard-card flight-filter-card">
                <button type="button" class="vuero-button is-primary" data-flight-favorite><x-vuero.icon name="heart" size="16" /> <span>Add To Favorites</span></button>
                <fieldset data-flight-filter="stops">
                    <legend>Stops</legend>
                    @foreach ([['all', 'All Flights'], ['0', 'No Stops'], ['1', '1 Stop'], ['2', '2 Stops']] as $option)
                        <label><input type="checkbox" value="{{ $option[0] }}" @checked($loop->first)> <i></i><span>{{ $option[1] }}</span></label>
                    @endforeach
                </fieldset>
                <fieldset data-flight-filter="luggage">
                    <legend>Luggage</legend>
                    @foreach ([['all', 'All Options'], ['1', '1 Cabin Luggage'], ['2', '2 Cabin Luggage'], ['0', 'None']] as $option)
                        <label><input type="checkbox" value="{{ $option[0] }}" @checked($loop->first)> <i></i><span>{{ $option[1] }}</span></label>
                    @endforeach
                </fieldset>
            </section>
        </aside>
    </div>
</div>
