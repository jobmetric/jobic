@props(['title', 'date', 'amount', 'tone' => 'green', 'icon' => 'box'])

<button type="button" class="banking-transaction" data-bank-transaction>
    <span class="banking-transaction__icon banking-transaction__icon--{{ $tone }}"><x-vuero.icon :name="$icon" size="18" /></span>
    <span class="banking-transaction__copy"><strong>{{ $title }}</strong><small>{{ $date }}</small></span>
    <strong class="banking-transaction__amount">{{ $amount }}</strong>
</button>
