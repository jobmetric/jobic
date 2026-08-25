@switch($dashboard['slug'])
    @case('company')
        <x-vuero.dashboard.business.company />
        @break
    @case('course')
        <x-vuero.dashboard.business.course />
        @break
    @case('flights')
        <x-vuero.dashboard.business.flights />
        @break
    @case('human-resources')
        <x-vuero.dashboard.business.human-resources />
        @break
    @case('jobs')
        <x-vuero.dashboard.business.jobs />
        @break
@endswitch
