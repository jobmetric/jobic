@props(['name', 'id' => null, 'type' => 'text', 'value' => null])
<input {{ $attributes->merge(['class' => 'input', 'id' => $id ?: $name, 'name' => $name, 'type' => $type, 'value' => $value]) }}>
