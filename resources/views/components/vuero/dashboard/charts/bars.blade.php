<div class="dashboard-chart__fallback-bars" data-chart-fallback>
    @foreach ([38, 62, 47, 81, 56, 72, 43, 91, 67, 84, 52, 76] as $bar)
        <i style="--value: {{ $bar }}%"></i>
    @endforeach
</div>
