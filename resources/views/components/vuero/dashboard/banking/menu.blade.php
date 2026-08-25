@props(['label' => 'Card actions'])

<div class="bank-action-menu" data-bank-menu>
    <button type="button" class="bank-action-menu__toggle" aria-label="{{ $label }}" aria-haspopup="menu" aria-expanded="false" data-bank-menu-toggle><span aria-hidden="true">⋮</span></button>
    <div class="bank-action-menu__panel" role="menu">
        <button type="button" role="menuitem" data-bank-action="invest"><x-vuero.icon name="trending-up" size="17" /><span><strong>Invest</strong><small>Buy more stocks</small></span></button>
        <button type="button" role="menuitem" data-bank-action="compare"><x-vuero.icon name="activity" size="17" /><span><strong>Compare</strong><small>Compare with others</small></span></button>
        <button type="button" role="menuitem" data-bank-action="trade"><x-vuero.icon name="building" size="17" /><span><strong>Trade</strong><small>View opportunities</small></span></button>
        <span role="separator"></span>
        <button type="button" role="menuitem" data-bank-action="wallet"><x-vuero.icon name="credit-card" size="17" /><span><strong>Wallet</strong><small>Open card wallet</small></span></button>
    </div>
</div>
