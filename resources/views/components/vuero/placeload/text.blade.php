@props([
    'width' => '100%',
    'lastLineWidth' => '100%',
    'lines' => 2,
    'centered' => false,
])

<div {{ $attributes->class(['content-shape-group', 'is-centered' => $centered]) }}>
    @for ($line = 1; $line < $lines; $line++)
        <x-vuero.placeload.shape :width="$width" :centered="$centered" />
    @endfor
    <x-vuero.placeload.shape :width="$lastLineWidth" :centered="$centered" />
</div>
