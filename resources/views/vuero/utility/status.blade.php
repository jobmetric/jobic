<x-vuero.layouts.minimal
    title="Utility Status"
    script="resources/js/vuero/pages/utility-status.js"
    body-class="cms-page cms-page--utility"
    wrapper-class="utility-minimal-wrapper"
>
    <main class="status-page-wrapper" data-utility-status>
        <header class="utility-public-head">
            <a class="utility-public-logo" href="{{ route('vuero.home') }}" aria-label="Vuero home"><img src="{{ asset('vuero/images/logos/logo/logo.svg') }}" alt=""></a>
            <nav aria-label="Utility navigation"><a class="action-link" href="{{ route('vuero.admin.dashboard') }}">Home</a><a class="action-link" href="{{ route('vuero.utility.roadmap') }}">Roadmap</a></nav>
        </header>

        <section class="status-block">
            <header class="status-header"><span class="indicator" aria-hidden="true"></span><h1>All services are online.</h1><p>As of September 20, 2020 at 11:08 PM PST.</p></header>
            <div class="status-uptime"><strong>Services</strong><span>Uptime of the past 90 days</span></div>
            <div class="status-list">
                @foreach ($services as $service)
                    <article class="status-service">
                        <span class="status-service-icon"><img src="{{ asset('vuero/images/utility/animaticons/'.$service['icon'].'.gif') }}" alt=""></span>
                        <div class="status-service-progress">
                            <div><span><strong>{{ $service['name'] }}</strong><small>{{ $service['description'] }}</small></span><b>{{ $service['value'] }}%</b></div>
                            <progress class="is-{{ $service['tone'] }}" value="{{ $service['value'] }}" max="100">{{ $service['value'] }}%</progress>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>

        <section class="status-block status-social">
            <header class="tweets-header"><span class="twitter-mark" aria-hidden="true">t</span><strong>&commat;cssninjaStudio</strong><a class="action-link" href="https://twitter.com/cssninjaStudio" target="_blank" rel="noopener">View on Twitter</a></header>
            <div class="status-updates">
                @foreach ([['July 29 at 5:39pm', "We're experiencing higher than normal load on our batch infrastructure. Webhooks are about an hour delayed. API calls are not impacted"], ['July 23 at 9:21pm', "Sorry about that! Our systems are showing everything as caught up now. Let us know if you're still seeing problems!"]] as [$time, $message])
                    <article><span class="status-avatar"><img src="{{ asset('vuero/images/utility/hanzo.svg') }}" alt=""><img src="{{ asset('vuero/images/utility/html5.svg') }}" alt=""></span><div><time>{{ $time }}</time><p>{{ $message }}</p></div></article>
                @endforeach
            </div>
        </section>

        <x-vuero.utility.footer />
    </main>
</x-vuero.layouts.minimal>
