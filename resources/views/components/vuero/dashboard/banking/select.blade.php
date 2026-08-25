@props([
    'id',
    'name',
    'options',
    'placeholder' => 'Select an option',
    'minimal' => false,
    'preselect' => true,
])

@php
    $selected = $preselect
        ? ($options[0] ?? ['value' => '', 'label' => $placeholder])
        : ['value' => '', 'label' => $placeholder];
    $selectedLabel = $selected['label'] ?? $placeholder;
    $selectedIcon = $selected['icon'] ?? null;
@endphp

<div class="bank-select {{ $minimal ? 'bank-select--minimal' : 'bank-select--account' }}" data-bank-select>
    <button
        type="button"
        id="{{ $id }}-trigger"
        class="bank-select__trigger"
        aria-haspopup="listbox"
        aria-controls="{{ $id }}-listbox"
        aria-expanded="false"
        data-bank-select-trigger
    >
        @if (!$minimal)
            <img src="{{ $selectedIcon }}" alt="" data-bank-select-icon>
        @endif
        <span data-bank-select-label>{{ $selectedLabel }}</span>
        <x-vuero.icon name="chevron-right" size="15" />
    </button>
    <button type="button" class="bank-select__clear" aria-label="Clear selection" data-bank-select-clear>×</button>
    <div id="{{ $id }}-listbox" class="bank-select__options" role="listbox" aria-labelledby="{{ $id }}-trigger" data-bank-select-options>
        @foreach ($options as $index => $option)
            <button
                type="button"
                role="option"
                aria-selected="{{ $preselect && $index === 0 ? 'true' : 'false' }}"
                data-value="{{ $option['value'] }}"
                data-label="{{ $option['label'] }}"
                @if (!empty($option['icon'])) data-icon="{{ $option['icon'] }}" @endif
            >
                @if (!empty($option['icon']))<img src="{{ $option['icon'] }}" alt="">@endif
                <span>{{ $option['label'] }}</span>
                <x-vuero.icon name="check" size="15" />
            </button>
        @endforeach
    </div>
    <input type="hidden" name="{{ $name }}" value="{{ $selected['value'] ?? '' }}" data-bank-select-input>
</div>
