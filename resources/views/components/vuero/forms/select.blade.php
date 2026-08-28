@props([
    'id' => null,
    'name' => null,
    'placeholder' => 'Select an option',
    'options' => [],
    'selected' => null,
    'image' => false,
])

@php
    $selectId = $id ?? 'ms-'.uniqid();
    $normalized = collect($options)->map(function ($option): array {
        if (is_string($option)) {
            return ['value' => $option, 'label' => $option, 'icon' => null];
        }

        return [
            'value' => (string) ($option['value'] ?? $option['label']),
            'label' => (string) $option['label'],
            'icon' => $option['icon'] ?? null,
        ];
    })->values();

    $selectedOption = $normalized->first(function (array $option) use ($selected): bool {
        return $selected !== null && ($option['value'] === (string) $selected || $option['label'] === (string) $selected);
    });
@endphp

<div {{ $attributes->class(['multiselect', 'is-image-select' => $image]) }} data-form-multiselect id="{{ $selectId }}">
    <div class="multiselect-wrapper">
        <button
            type="button"
            class="multiselect-fake-input"
            data-form-multiselect-trigger
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-controls="{{ $selectId }}-listbox"
        ></button>

        <div class="multiselect-single-label" data-form-multiselect-single @if (! $selectedOption) hidden @endif>
            @if ($image)
                <img class="select-label-icon" data-form-multiselect-icon src="{{ $selectedOption['icon'] ?? '' }}" alt="" @if (! ($selectedOption['icon'] ?? null)) hidden @endif>
                <span class="select-label-text" data-form-multiselect-label>{{ $selectedOption['label'] ?? '' }}</span>
            @else
                <span class="multiselect-single-label-text" data-form-multiselect-label>{{ $selectedOption['label'] ?? '' }}</span>
            @endif
        </div>

        <span class="multiselect-placeholder" data-form-multiselect-placeholder @if ($selectedOption) hidden @endif>{{ $placeholder }}</span>
        <span class="multiselect-caret" aria-hidden="true"></span>
    </div>

    <div class="multiselect-dropdown is-hidden" data-form-multiselect-dropdown hidden>
        <ul class="multiselect-options" id="{{ $selectId }}-listbox" role="listbox">
            @foreach ($normalized as $option)
                @php($isSelected = $selectedOption && $selectedOption['value'] === $option['value'])
                <li
                    class="multiselect-option @if ($isSelected) is-selected @endif"
                    role="option"
                    data-form-multiselect-option
                    data-value="{{ $option['value'] }}"
                    data-label="{{ $option['label'] }}"
                    @if ($option['icon']) data-icon="{{ $option['icon'] }}" @endif
                    aria-selected="{{ $isSelected ? 'true' : 'false' }}"
                >
                    @if ($image && $option['icon'])
                        <img class="select-option-icon" src="{{ $option['icon'] }}" alt="">
                        <span class="select-label-text">{{ $option['label'] }}</span>
                    @else
                        <span>{{ $option['label'] }}</span>
                    @endif
                </li>
            @endforeach
        </ul>
    </div>

    @if ($name)
        <input type="hidden" name="{{ $name }}" value="{{ $selectedOption['value'] ?? '' }}" data-form-multiselect-value>
    @endif
</div>
