@props([
    'label' => null,
    'labelHint' => null,
    'for' => null,
    'hint' => null,
    'error' => null,
    'variant' => 'settings',
])

@if($variant === 'crud')
    <div {{ $attributes->merge(['class' => 'crud-field']) }}>
        @if($label !== null)
            <label @if($for) for="{{ $for }}" @endif>
                @if($labelHint)<span class="crud-field-label-row"><span>{{ $label }}</span><small>{{ $labelHint }}</small></span>@else{{ $label }}@endif
            </label>
        @endif
        {{ $slot }}
        @if($error)<small data-error-for="{{ $error }}" id="error-{{ $error }}"></small>@endif
        @if($hint)<small>{{ $hint }}</small>@endif
    </div>
@else
    <div {{ $attributes->merge(['class' => 'cms-settings-field']) }}>
        @if($label !== null)<label @if($for) for="{{ $for }}" @endif>{{ $label }}</label>@endif
        <div class="cms-settings-control">{{ $slot }}</div>
        @if($error)<small data-error-for="{{ $error }}" id="error-{{ $error }}"></small>@endif
        @if($hint)<small>{{ $hint }}</small>@endif
    </div>
@endif
