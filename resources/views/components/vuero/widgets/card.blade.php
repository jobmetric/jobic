@props([
    'title' => null,
    'class' => '',
    'menu' => true,
])

<article {{ $attributes->class(['widget-card', $class]) }} data-widget-card>
    @if ($title || $menu || isset($toolbar))
        <header class="widget-card__header">
            @isset($toolbar)
                {{ $toolbar }}
            @else
                <h2>{{ $title }}</h2>
                @if ($menu)
                    <x-vuero.widgets.dropdown />
                @endif
            @endisset
        </header>
    @endif
    <div class="widget-card__body">{{ $slot }}</div>
</article>
