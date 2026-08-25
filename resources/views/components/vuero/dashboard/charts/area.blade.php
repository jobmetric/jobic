@props(['tone' => 'primary', 'uid'])

<svg viewBox="0 0 720 190" preserveAspectRatio="none" aria-hidden="true" data-chart-fallback>
    <defs>
        <linearGradient id="area-{{ $tone }}-{{ $uid }}" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0" stop-color="currentColor" stop-opacity=".32" />
            <stop offset="1" stop-color="currentColor" stop-opacity=".04" />
        </linearGradient>
    </defs>
    <path class="dashboard-chart__grid" d="M0 32H720M0 76H720M0 120H720M0 164H720" />
    <path class="dashboard-chart__area" fill="url(#area-{{ $tone }}-{{ $uid }})" d="M0 151 C45 148 57 105 102 115 S162 154 210 117 278 47 330 85 394 139 446 91 514 37 568 80 626 137 720 57 L720 190H0Z" />
    <path class="dashboard-chart__line" d="M0 151 C45 148 57 105 102 115 S162 154 210 117 278 47 330 85 394 139 446 91 514 37 568 80 626 137 720 57" />
</svg>
