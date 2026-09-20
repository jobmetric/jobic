@props(['name', 'id' => null])
<select {{ $attributes->merge(['class' => 'input', 'id' => $id ?: $name, 'name' => $name]) }}>{{ $slot }}</select>
