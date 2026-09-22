@props(['name', 'id' => null, 'multiple' => false])
<select
    @if($multiple) multiple @endif
    {{ $attributes->class(['input', 'cms-select', 'cms-select--multiple' => $multiple])->merge(['id' => $id ?: $name, 'name' => $name]) }}
>{{ $slot }}</select>
