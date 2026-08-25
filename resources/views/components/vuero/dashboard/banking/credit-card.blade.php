@props([
    'number',
    'type' => 'Credit Card',
    'tone' => 'primary',
    'balance' => null,
    'large' => false,
])

@if ($large)
    <div class="banking-card-slide" tabindex="0" role="button" data-bank-card data-balance="{{ $balance }}" data-number="{{ $number }}">
        <div class="banking-credit-card banking-credit-card--large">
            <span class="banking-credit-card__shape"></span>
            <picture>
                <source srcset="{{ asset('vuero/images/icons/dashboards/banking/visa-text-white.svg') }}" media="(prefers-color-scheme: dark)">
                <img src="{{ asset('vuero/images/icons/dashboards/banking/visa-text-dark.svg') }}" alt="Visa">
            </picture>
            <span class="banking-credit-card__balance">{{ $balance }}</span>
            <span class="banking-credit-card__number">{{ $number }}</span>
        </div>
    </div>
@else
    <div class="banking-card-account">
        <div class="banking-credit-card banking-credit-card--small banking-credit-card--{{ $tone }}">
            <span class="banking-credit-card__shape"></span>
            <span class="banking-credit-card__short-number">{{ $number }}</span>
            <img src="{{ asset('vuero/images/icons/dashboards/banking/visa-squared-white.svg') }}" alt="Visa">
            <span class="banking-credit-card__type">{{ $type }}</span>
        </div>
        <div class="banking-card-account__meta">
            <small>Card Balance</small>
            <strong>{{ $balance }}</strong>
        </div>
        <x-vuero.dashboard.banking.menu :label="$number . ' card actions'" />
    </div>
@endif
