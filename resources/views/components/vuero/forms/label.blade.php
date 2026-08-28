@props(['for' => null, 'hidden' => false])

<label {{ $attributes->class(['label', 'is-vhidden' => $hidden]) }} @if ($for) for="{{ $for }}" @endif>{{ $slot }}</label>
