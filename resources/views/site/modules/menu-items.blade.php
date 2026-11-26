<ul>
    @foreach($items as $item)
        <li>
            @if($item['url'])
                <a href="{{ $item['url'] }}">{{ $item['label'] }}</a>
            @else
                <span @class(['site-menu__separator' => $item['separator']])>{{ $item['label'] }}</span>
            @endif
            @if($item['children'])
                @include('site.modules.menu-items', ['items' => $item['children']])
            @endif
        </li>
    @endforeach
</ul>
