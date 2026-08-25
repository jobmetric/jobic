@props(['context' => 'list', 'label' => 'Stock actions'])

<div class="stock-action-menu" data-stock-menu>
    <button
        type="button"
        class="stock-action-menu__toggle"
        aria-label="{{ $label }}"
        aria-haspopup="menu"
        aria-expanded="false"
        data-stock-menu-toggle
    >
        <span aria-hidden="true">⋮</span>
    </button>
    <div class="stock-action-menu__panel" role="menu" data-stock-menu-panel>
        <button type="button" role="menuitem" data-stock-action="invest">
            <x-vuero.icon name="trending-up" size="17" />
            <span><strong>Invest</strong><small>Buy more stocks</small></span>
        </button>
        @if ($context === 'graph')
            <button type="button" role="menuitem" data-stock-action="compare">
                <x-vuero.icon name="activity" size="17" />
                <span><strong>Compare</strong><small>Compare with others</small></span>
            </button>
        @endif
        <button type="button" role="menuitem" data-stock-action="trade">
            <x-vuero.icon name="building" size="17" />
            <span><strong>Trade</strong><small>View opportunities</small></span>
        </button>
        <span class="stock-action-menu__divider" role="separator"></span>
        <button type="button" role="menuitem" data-stock-action="{{ $context === 'graph' ? 'wallet' : 'refresh' }}">
            <x-vuero.icon :name="$context === 'graph' ? 'credit-card' : 'refresh'" size="17" />
            <span>
                <strong>{{ $context === 'graph' ? 'Wallet' : 'Refresh' }}</strong>
                <small>{{ $context === 'graph' ? 'Open stock wallet' : 'Refresh data' }}</small>
            </span>
        </button>
    </div>
</div>
