@props(['name', 'id' => null, 'value' => null])
<textarea {{ $attributes->merge(['class' => 'input', 'id' => $id ?: $name, 'name' => $name]) }}>{{ $value }}</textarea>
