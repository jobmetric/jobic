@props([
    'labelFalse' => null,
    'labelTrue' => null,
    'checked' => false,
    'primary' => false,
])

<div {{ $attributes->class(['switch-segment']) }}>
    @if ($labelFalse)
        <span class="is-label">{{ $labelFalse }}</span>
    @endif
    <label @class(['form-switch', 'is-primary' => $primary])>
        <input type="checkbox" class="is-switch" @checked($checked)>
        <i aria-hidden="true"></i>
    </label>
    @if ($labelTrue)
        <span class="is-label">{{ $labelTrue }}</span>
    @endif
</div>
