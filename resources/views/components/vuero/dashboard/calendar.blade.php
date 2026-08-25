<div class="dashboard-calendar">
    <header>
        <button type="button" aria-label="Previous month"><x-vuero.icon name="chevron-left" size="15" /></button>
        <strong>October 2020</strong>
        <button type="button" aria-label="Next month"><x-vuero.icon name="chevron-right" size="15" /></button>
    </header>
    <div class="dashboard-calendar__week"><b>MON</b><b>TUE</b><b>WED</b><b>THU</b><b>FRI</b><b>SAT</b><b>SUN</b></div>
    <div class="dashboard-calendar__days">
        @foreach ([29,30,31,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31,1] as $day)
            <span class="{{ $loop->iteration <= 3 || $loop->last ? 'is-muted' : '' }} {{ $day === 18 ? 'is-current' : '' }}">{{ $day }}</span>
        @endforeach
    </div>
</div>
