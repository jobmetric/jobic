@php
    $level = max(1, min(6, (int) ($data['level'] ?? 2)));
@endphp
<h{{ $level }}>{!! $renderer->inline($data['text'] ?? '') !!}</h{{ $level }}>
