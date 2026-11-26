@props(['name', 'id' => null, 'multiple' => false, 'placeholder' => false])
<select
    @if($multiple) multiple @endif
    {{ $attributes->class(['input', 'cms-select', 'cms-select--multiple' => $multiple])->merge(['id' => $id ?: $name, 'name' => $name]) }}
>@if($placeholder && !$multiple)<option value="" data-select-placeholder>{{ __('admin.options.select') }}</option>@endif{{ $slot }}</select>
