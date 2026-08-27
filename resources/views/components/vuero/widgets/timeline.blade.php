@props(['items', 'squared' => false, 'colored' => false])

<ol class="widget-timeline {{ $squared ? 'is-squared' : '' }} {{ $colored ? 'is-colored' : '' }}">
    @foreach ($items as $item)
        <li>
            <span class="widget-timeline__icon is-{{ $item['color'] }}"><x-vuero.icon :name="$item['icon']" size="16" /></span>
            <span class="widget-timeline__content"><strong>{{ $item['title'] }}</strong><small>{{ $item['text'] }}</small></span>
            <time>{{ $item['time'] }}</time>
        </li>
    @endforeach
</ol>
