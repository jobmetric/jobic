@props(['title' => __('admin.history_disclosure_title')])

<details {{ $attributes->class('crud-history-disclosure') }}>
    <summary class="crud-history-disclosure__trigger">
        <span class="crud-history-disclosure__icon"><x-vuero.icon name="clock" size="20" /></span>
        <span class="crud-history-disclosure__copy"><strong>{{ $title }}</strong><small>{{ __('admin.history_disclosure_hint') }}</small></span>
        <span class="crud-history-disclosure__action"><span class="is-closed">{{ __('admin.open_history') }}</span><span class="is-open">{{ __('admin.close_history') }}</span><x-vuero.icon name="chevron-down" size="16" /></span>
    </summary>
    <div class="crud-history-disclosure__content">{{ $slot }}</div>
</details>
