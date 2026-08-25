@props(['symbol', 'name', 'brand'])

<span class="stock-identity">
    <span class="stock-brand stock-brand--{{ $brand }}" aria-hidden="true">
        <x-vuero.icon :name="'brand-' . $brand" size="17" />
    </span>
    <span class="stock-identity__copy">
        <strong>{{ $symbol }}</strong>
        <small>{{ $name }}</small>
    </span>
</span>
