@props([
    'label',
    'description' => null,
    'passed' => false,
    'variant' => 'full',
])

<li
    {{ $attributes->class([
        'reqv-row',
        'reqv-row--compact' => $variant === 'compact',
        'reqv-row--failed' => ! $passed,
    ]) }}
>
    <span
        class="reqv-row__status"
        aria-hidden="true"
    >
        @if ($passed)
            <x-vuero.icon name="check" size="13" />
        @else
            <x-vuero.icon name="x" size="13" />
        @endif
    </span>

    <span class="reqv-row__name">
        {{ $label }}
    </span>

    @if ($description && $variant === 'full')
        <span class="reqv-row__description">
            {{ $description }}
        </span>
    @endif

    <span
        class="reqv-row__badge {{ $passed ? 'is-ready' : 'is-missing' }}"
    >
        {{ __('installation.'.(
            $passed
                ? 'requirements_status_ready'
                : 'requirements_status_missing'
        )) }}
    </span>

</li>
