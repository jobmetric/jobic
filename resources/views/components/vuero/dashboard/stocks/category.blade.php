@props(['icon', 'label'])

<button type="button" class="stock-category" data-stock-category="{{ $icon }}" aria-pressed="false">
    <span class="stock-category__diamond" aria-hidden="true">
        <x-vuero.icon :name="$icon" size="19" />
    </span>
    <span class="stock-category__name">{{ $label }}</span>
</button>
