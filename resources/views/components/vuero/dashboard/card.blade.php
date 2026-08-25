@props([
    'title' => null,
    'action' => null,
    'class' => '',
])

<article {{ $attributes->class(['dashboard-card', $class]) }}>
    @if ($title || $action)
        <header class="dashboard-card__header">
            @if ($title)<h2>{{ $title }}</h2>@endif
            @if ($action)<button type="button" class="dashboard-link" data-ui-message="{{ $action }}">{{ $action }}</button>@endif
        </header>
    @endif
    {{ $slot }}
</article>
