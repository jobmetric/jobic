@props([
    'label',
    'checked' => false,
    'name' => null,
])

<label class="layout-switch-block">
    <input
        type="checkbox"
        class="layout-switch-block__input"
        @checked($checked)
        @if ($name) name="{{ $name }}" @endif
    >
    <span class="layout-switch-block__track" aria-hidden="true"></span>
    <span class="layout-switch-block__label">{{ $label }}</span>
</label>
